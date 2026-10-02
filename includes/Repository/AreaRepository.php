<?php
/**
 * SQL for {prefix}wbpc_areas. The only class that queries that table.
 *
 * @package Wbcom\PincodeChecker
 */

declare( strict_types = 1 );

namespace Wbcom\PincodeChecker\Repository;

use Wbcom\PincodeChecker\Domain\Postcode;

defined( 'ABSPATH' ) || exit;

/**
 * Rows are plain associative arrays. Every write bumps wbpc_areas_version, which is part of every
 * lookup cache key, so a stale "not serviceable" can never outlive an edit.
 */
final class AreaRepository {

	public const CACHE_GROUP = 'wbpc';

	private const COLUMNS = array( 'country', 'match_type', 'code', 'code_to', 'code_norm', 'range_start', 'range_end', 'status', 'city', 'state', 'days_min', 'days_max', 'shipping_fee', 'cod_allowed', 'cod_fee', 'note' );

	/**
	 * Table name.
	 */
	public function table(): string {
		global $wpdb;

		return $wpdb->prefix . 'wbpc_areas';
	}

	/**
	 * Cache version; part of every cache key.
	 */
	public function version(): int {
		return (int) get_option( 'wbpc_areas_version', 1 );
	}

	/**
	 * Invalidate every cached lookup and count.
	 */
	public function bump_version(): void {
		update_option( 'wbpc_areas_version', $this->version() + 1, true );
	}

	/**
	 * Every area that could match a code: exact, any prefix of it, and (numeric codes) covering ranges.
	 * Three indexed branches in one round trip; precedence is decided by the caller.
	 *
	 * @param string $norm    Normalised code.
	 * @param string $country ISO country of the customer.
	 * @return array<int, array<string, mixed>>
	 */
	public function candidates( string $norm, string $country ): array {
		global $wpdb;

		$table    = $this->table();
		$prefixes = Postcode::prefixes( $norm );
		$in       = implode( ',', array_fill( 0, count( $prefixes ), '%s' ) );
		$args     = array_merge( array( $norm, $country ), $prefixes, array( $country ) );

		$sql = "(SELECT * FROM {$table} WHERE match_type = 1 AND code_norm = %s AND country IN (%s, ''))
			UNION ALL
			(SELECT * FROM {$table} WHERE match_type = 2 AND code_norm IN ({$in}) AND country IN (%s, ''))";

		$number = Postcode::numeric( $norm );

		if ( null !== $number ) {
			// ponytail: interval lookup scans every range starting at or below the code (2,300 rows
			// at ~4k ranges, ~2ms). Fine for stores with up to ~10k ranges; beyond that add a
			// bucket column (range_start DIV 1000) to the index.
			$sql   .= " UNION ALL (SELECT * FROM {$table} WHERE match_type = 3 AND range_start <= %d AND range_end >= %d AND country IN (%s, '') LIMIT 50)";
			$args[] = $number;
			$args[] = $number;
			$args[] = $country;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery -- Table name is internal; values are placeholders. Cached by the caller.
		return (array) $wpdb->get_results( $wpdb->prepare( $sql, $args ), ARRAY_A );
	}

	/**
	 * Serviceable exact codes that share the longest possible leading part with a code.
	 *
	 * Tries the code minus 1, 2 and 3 trailing characters; each probe is an index range scan on
	 * code_norm. Used for "we deliver nearby" suggestions.
	 *
	 * @param string $norm    Normalised code.
	 * @param string $country ISO country.
	 * @param int    $limit   Max suggestions.
	 * @return array<int, array<string, mixed>>
	 */
	public function nearby( string $norm, string $country, int $limit = 5 ): array {
		global $wpdb;

		$table = $this->table();

		$length = strlen( $norm );

		for ( $cut = 1; $cut <= 3 && $length - $cut >= 2; $cut++ ) {
			$like = $wpdb->esc_like( substr( $norm, 0, -$cut ) ) . '%';

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery -- Internal table name.
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT code, city, state FROM {$table} WHERE code_norm LIKE %s AND match_type = 1 AND status = 1 AND country IN (%s, '') AND code_norm <> %s ORDER BY code_norm LIMIT %d", $like, $country, $norm, $limit ), ARRAY_A );

			if ( $rows ) {
				return $rows;
			}
		}

		return array();
	}

	/**
	 * Number of areas, cached per version.
	 */
	public function count(): int {
		global $wpdb;

		$key   = 'count:' . $this->version();
		$count = wp_cache_get( $key, self::CACHE_GROUP );

		if ( false === $count ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery -- Internal table name, no input.
			$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table()}" );
			wp_cache_set( $key, $count, self::CACHE_GROUP, HOUR_IN_SECONDS );
		}

		return (int) $count;
	}

	/**
	 * One area by id.
	 *
	 * @param int $id Area id.
	 * @return array<string, mixed>|null
	 */
	public function find( int $id ): ?array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery -- Internal table name.
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table()} WHERE id = %d", $id ), ARRAY_A );

		return $row ? $row : null;
	}

	/**
	 * Id of an existing area with the same unique key, if any.
	 *
	 * @param array<string, mixed> $rule Parsed rule + country.
	 * @param int                  $except Id to ignore (the area being edited).
	 */
	public function duplicate_of( array $rule, int $except = 0 ): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery -- Internal table name.
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$this->table()} WHERE country = %s AND match_type = %d AND code_norm = %s AND range_start = %d AND id <> %d", $rule['country'], $rule['match_type'], $rule['code_norm'], $rule['range_start'], $except ) );
	}

	/**
	 * A page of areas. Orderby/order are whitelisted here, never trusted from the caller.
	 *
	 * Measured at 103k rows: newest-first and filtered pages ~1ms; deep pages, code/city sorts and
	 * cross-column searches up to ~65ms. Fine for an admin list.
	 *
	 * @param array  $filters  See where().
	 * @param string $orderby  newest|code|city|days|updated.
	 * @param string $order    asc|desc.
	 * @param int    $per_page 1-100.
	 * @param int    $page     1-based.
	 * @return array<int, array<string, mixed>>
	 */
	public function page( array $filters, string $orderby, string $order, int $per_page, int $page ): array {
		global $wpdb;

		$dir = 'asc' === strtolower( $order ) ? 'ASC' : 'DESC';
		$col = array(
			'code'    => 'code_norm',
			'city'    => 'city',
			'days'    => 'days_min',
			'updated' => 'updated_at',
		)[ $orderby ] ?? 'id';

		[ $where, $args ] = $this->where( $filters );

		$per_page = max( 1, min( 100, $per_page ) );
		$args[]   = $per_page;
		$args[]   = ( max( 1, $page ) - 1 ) * $per_page;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Column/direction whitelisted above; values are placeholders.
		return (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->table()} {$where} ORDER BY {$col} {$dir}, id {$dir} LIMIT %d OFFSET %d", $args ), ARRAY_A );
	}

	/**
	 * Number of areas matching filters, cached per filter set and version.
	 *
	 * @param array $filters See where().
	 */
	public function count_where( array $filters ): int {
		global $wpdb;

		[ $where, $args ] = $this->where( $filters );

		if ( '' === $where ) {
			return $this->count();
		}

		$key   = 'count:' . $this->version() . ':' . md5( (string) wp_json_encode( $filters ) );
		$count = wp_cache_get( $key, self::CACHE_GROUP );

		if ( false === $count ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- WHERE built from placeholders.
			$count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$this->table()} {$where}", $args ) );
			wp_cache_set( $key, $count, self::CACHE_GROUP, HOUR_IN_SECONDS );
		}

		return (int) $count;
	}

	/**
	 * WHERE clause for list filters.
	 *
	 * @param array{search?:string, type?:int, status?:int, cod?:int, country?:string} $filters Filters; unset = any.
	 * @return array{0:string, 1:array}
	 */
	private function where( array $filters ): array {
		global $wpdb;

		$sql  = array();
		$args = array();

		if ( isset( $filters['search'] ) && '' !== $filters['search'] ) {
			$sql[]  = '(code_norm LIKE %s OR city LIKE %s)';
			$args[] = $wpdb->esc_like( Postcode::normalize( $filters['search'] ) ) . '%';
			$args[] = $wpdb->esc_like( trim( $filters['search'] ) ) . '%';
		}

		foreach ( array(
			'type'   => 'match_type',
			'status' => 'status',
			'cod'    => 'cod_allowed',
		) as $key => $col ) {
			if ( isset( $filters[ $key ] ) ) {
				$sql[]  = "{$col} = %d";
				$args[] = (int) $filters[ $key ];
			}
		}

		if ( isset( $filters['country'] ) ) {
			$sql[]  = 'country = %s';
			$args[] = $filters['country'];
		}

		return array( $sql ? 'WHERE ' . implode( ' AND ', $sql ) : '', $args );
	}

	/**
	 * Insert one area.
	 *
	 * @param array<string, mixed> $row Validated row.
	 * @return int New id, 0 on failure.
	 */
	public function insert( array $row ): int {
		global $wpdb;

		$now = current_time( 'mysql', true );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Custom table.
		$ok = $wpdb->insert(
			$this->table(),
			$this->only_columns( $row ) + array(
				'created_at' => $now,
				'updated_at' => $now,
			)
		);

		if ( ! $ok ) {
			return 0;
		}

		$this->bump_version();

		return (int) $wpdb->insert_id;
	}

	/**
	 * Update one area.
	 *
	 * @param int                  $id  Area id.
	 * @param array<string, mixed> $row Validated row.
	 */
	public function update( int $id, array $row ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Custom table.
		$ok = false !== $wpdb->update( $this->table(), $this->only_columns( $row ) + array( 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => $id ) );

		if ( $ok ) {
			$this->bump_version();
		}

		return $ok;
	}

	/**
	 * Delete areas by id.
	 *
	 * @param int[] $ids Ids.
	 * @return int Rows deleted.
	 */
	public function delete_ids( array $ids ): int {
		global $wpdb;

		$ids = array_values( array_filter( array_map( 'intval', $ids ) ) );

		if ( ! $ids ) {
			return 0;
		}

		$in = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Placeholders only.
		$deleted = (int) $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->table()} WHERE id IN ({$in})", $ids ) );

		if ( $deleted ) {
			$this->bump_version();
		}

		return $deleted;
	}

	/**
	 * Delete every area matching filters, 5,000 rows per statement so no single lock runs long.
	 *
	 * @param array $filters See where(). Empty = all areas.
	 * @return int Rows deleted.
	 */
	public function delete_where( array $filters ): int {
		global $wpdb;

		[ $where, $args ] = $this->where( $filters );
		$deleted          = 0;

		do {
			$sql = "DELETE FROM {$this->table()} {$where} LIMIT 5000";
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery -- WHERE built from placeholders.
			$batch    = (int) $wpdb->query( $args ? $wpdb->prepare( $sql, $args ) : $sql );
			$deleted += $batch;
		} while ( 5000 === $batch );

		if ( $deleted ) {
			$this->bump_version();
		}

		return $deleted;
	}

	/**
	 * Rows after an id, in id order: the export reader. Keyset paging keeps every batch an index range.
	 *
	 * @param int   $after_id Last id already read.
	 * @param array $filters  See where().
	 * @param int   $limit    Batch size.
	 * @return array<int, array<string, mixed>>
	 */
	public function after_id( int $after_id, array $filters, int $limit = 2000 ): array {
		global $wpdb;

		[ $where, $args ] = $this->where( $filters );
		$where            = ( '' === $where ? 'WHERE ' : $where . ' AND ' ) . 'id > %d';
		$args[]           = $after_id;
		$args[]           = $limit;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- WHERE built from placeholders.
		return (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->table()} {$where} ORDER BY id LIMIT %d", $args ), ARRAY_A );
	}

	/**
	 * Keep only real columns.
	 *
	 * @param array<string, mixed> $row Row.
	 * @return array<string, mixed>
	 */
	private function only_columns( array $row ): array {
		return array_intersect_key( $row, array_flip( self::COLUMNS ) );
	}

	/**
	 * Write many rows, one statement per 500. Existing areas (same unique key) are skipped, or
	 * overwritten when $update is true.
	 *
	 * @param array<int, array<string, mixed>> $rows        Rows keyed by column name (parsed via Postcode::parse_rule()).
	 * @param bool                             $update      Overwrite existing areas instead of skipping them.
	 * @param string[]                         $update_cols Columns an update may overwrite; empty = all value columns.
	 *                                                      Imports pass only the columns the file has, so a partial
	 *                                                      file never blanks data it did not mention.
	 * @return int MySQL affected rows: inserts count 1, updates 2, unchanged 0.
	 */
	public function insert_many( array $rows, bool $update = false, array $update_cols = array() ): int {
		global $wpdb;

		$now      = current_time( 'mysql', true );
		$inserted = 0;
		$verb     = $update ? 'INSERT' : 'INSERT IGNORE';
		$tail     = '';

		if ( $update ) {
			// VALUES() is deprecated in MySQL 8.0.20+ but still works there and in every MariaDB;
			// the newer row-alias syntax breaks MariaDB.
			$set        = array();
			$value_cols = array_diff( self::COLUMNS, array( 'country', 'match_type', 'code_norm', 'range_start' ) );
			foreach ( $update_cols ? array_intersect( $value_cols, $update_cols ) : $value_cols as $col ) {
				$set[] = "{$col} = VALUES({$col})";
			}
			$tail = ' ON DUPLICATE KEY UPDATE ' . implode( ', ', $set ) . ', updated_at = VALUES(updated_at)';
		}

		foreach ( array_chunk( $rows, 500 ) as $chunk ) {
			$values = array();
			$args   = array();

			foreach ( $chunk as $row ) {
				$marks = array();

				foreach ( self::COLUMNS as $col ) {
					$value = array_key_exists( $col, $row ) ? $row[ $col ] : $this->default( $col );

					// wpdb::prepare() turns null into '', so NULL is written as a literal.
					if ( null === $value ) {
						$marks[] = 'NULL';
					} else {
						$marks[] = '%s';
						$args[]  = $value;
					}
				}

				$values[] = '(' . implode( ',', $marks ) . ',%s,%s)';
				$args[]   = $now;
				$args[]   = $now;
			}

			$cols = implode( ',', self::COLUMNS ) . ',created_at,updated_at';

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Internal table/columns; values are placeholders.
			$inserted += (int) $wpdb->query( $wpdb->prepare( "{$verb} INTO {$this->table()} ({$cols}) VALUES " . implode( ',', $values ) . $tail, $args ) );
		}

		if ( $inserted ) {
			$this->bump_version();
		}

		return $inserted;
	}

	/**
	 * Column default for inserts.
	 *
	 * @param string $col Column.
	 * @return mixed
	 */
	private function default( string $col ) {
		return match ( $col ) {
			'days_min', 'days_max', 'shipping_fee' => null,
			'match_type', 'status', 'cod_allowed' => 1,
			'range_start', 'range_end', 'cod_fee' => 0,
			default => '',
		};
	}
}
