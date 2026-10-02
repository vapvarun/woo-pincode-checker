<?php
/**
 * CSV import as a chain of Action Scheduler chunks.
 *
 * @package Wbcom\PincodeChecker
 */

declare( strict_types = 1 );

namespace Wbcom\PincodeChecker\Services;

use Wbcom\PincodeChecker\Repository\AreaRepository;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Flow: stage() validates the file and previews 20 rows -> start() queues the first chunk ->
 * run_chunk() writes up to CHUNK rows, saves its byte offset and queues the next one.
 */
final class ImportService {

	// ponytail: one import at a time, state in the wbpc_job option. A jobs table if parallel imports
	// or a long history are ever needed.


	public const HOOK  = 'wbpc_import_chunk';
	public const GROUP = 'wbpc';
	public const MODES = array( 'skip', 'update', 'replace' );

	private const CHUNK      = 2000;
	private const MAX_BYTES  = 10 * MB_IN_BYTES;
	private const STALE_SECS = 600;

	/**
	 * Constructor.
	 *
	 * @param AreaService    $service Validation + formatting.
	 * @param AreaRepository $areas   Storage.
	 * @param CsvService     $csv     CSV format.
	 */
	public function __construct( private AreaService $service, private AreaRepository $areas, private CsvService $csv ) {}

	/**
	 * The current (or most recent) job.
	 */
	public function current(): ?array {
		$job = get_option( 'wbpc_job' );

		return is_array( $job ) ? $job : null;
	}

	/**
	 * Last 10 finished jobs, newest first.
	 */
	public function history(): array {
		return (array) get_option( 'wbpc_job_history', array() );
	}

	/**
	 * Whether a job is queued or running and still alive.
	 */
	public function is_busy(): bool {
		$job = $this->current();

		return $job && in_array( $job['status'], array( 'queued', 'running' ), true ) && time() - (int) $job['heartbeat'] < self::STALE_SECS;
	}

	/**
	 * Validate an uploaded file, keep it privately, preview the first rows.
	 *
	 * @param string $path    Uploaded/temporary file.
	 * @param string $name    Original file name.
	 * @param string $mode    skip|update|replace.
	 * @param string $country Fallback country for rows without one ('' = any country).
	 * @return array|WP_Error { job, preview }.
	 */
	public function stage( string $path, string $name, string $mode, string $country ) {
		if ( $this->is_busy() ) {
			return new WP_Error( 'wbpc_import_running', __( 'An import is already running. Wait for it to finish or cancel it first.', 'woo-pincode-checker' ), array( 'status' => 409 ) );
		}
		if ( ! in_array( $mode, self::MODES, true ) ) {
			return new WP_Error( 'wbpc_import_mode', __( 'Choose how existing areas should be handled.', 'woo-pincode-checker' ), array( 'status' => 400 ) );
		}
		if ( ! in_array( strtolower( pathinfo( $name, PATHINFO_EXTENSION ) ), array( 'csv', 'txt' ), true ) ) {
			return new WP_Error( 'wbpc_import_type', __( 'Upload a .csv file.', 'woo-pincode-checker' ), array( 'status' => 400 ) );
		}
		if ( ! is_readable( $path ) || filesize( $path ) > self::MAX_BYTES ) {
			return new WP_Error( 'wbpc_import_size', __( 'The file is missing or larger than 10 MB. Split it into smaller files.', 'woo-pincode-checker' ), array( 'status' => 400 ) );
		}

		$this->discard_staged();

		$file = wp_generate_password( 32, false ) . '.csv';
		$dest = $this->csv->private_dir() . $file;
		$ok   = is_uploaded_file( $path ) ? move_uploaded_file( $path, $dest ) : copy( $path, $dest );

		if ( ! $ok ) {
			return new WP_Error( 'wbpc_import_store', __( 'The file could not be saved on the server.', 'woo-pincode-checker' ), array( 'status' => 500 ) );
		}

		$handle = fopen( $dest, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$map    = $this->csv->map_header( (array) CsvService::read( $handle ) );

		if ( ! in_array( 'code', $map, true ) ) {
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			wp_delete_file( $dest );
			return new WP_Error( 'wbpc_import_header', __( 'The first row must be a header with a "code" (or pincode, postcode, zip) column. Download the sample file to see the format.', 'woo-pincode-checker' ), array( 'status' => 400 ) );
		}

		$offset  = ftell( $handle );
		$preview = array();
		$total   = 0;
		$line    = 1;

		while ( false !== ( $values = CsvService::read( $handle ) ) ) { // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
			++$line;
			if ( $this->is_blank( $values ) ) {
				continue;
			}
			++$total;

			if ( count( $preview ) < 20 ) {
				$input     = $this->csv->to_input( $map, $values, $country );
				$row       = $this->service->validate( $input, 0, false );
				$preview[] = array(
					'line'  => $line,
					'code'  => trim( ( $input['code'] ?? '' ) . ( empty( $input['code_to'] ) ? '' : ' - ' . $input['code_to'] ) ),
					'city'  => $input['city'] ?? '',
					'error' => is_wp_error( $row ) ? $this->error_text( $row ) : '',
				);
			}
		}
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		$job = array(
			'id'         => strtolower( wp_generate_password( 12, false ) ), // Lowercase: download links pass it through sanitize_key().
			'status'     => 'staged',
			'name'       => sanitize_file_name( $name ),
			'mode'       => $mode,
			'country'    => $country,
			'file'       => $file,
			'error_file' => '',
			'map'        => $map,
			'offset'     => $offset,
			'line'       => 1,
			'total'      => $total,
			'processed'  => 0,
			'added'      => 0,
			'updated'    => 0,
			'skipped'    => 0,
			'failed'     => 0,
			'replaced'   => false,
			'user'       => get_current_user_id(),
			'started'    => 0,
			'heartbeat'  => time(),
			'message'    => '',
		);
		$this->save( $job );

		return array(
			'job'     => $this->public_job( $job ),
			'preview' => $preview,
		);
	}

	/**
	 * Queue the staged job.
	 *
	 * @return array|WP_Error Job.
	 */
	public function start() {
		$job = $this->current();

		if ( ! $job || 'staged' !== $job['status'] ) {
			return new WP_Error( 'wbpc_import_not_staged', __( 'Upload a file first.', 'woo-pincode-checker' ), array( 'status' => 400 ) );
		}

		$job['status']  = 'queued';
		$job['started'] = time();
		$this->save( $job );
		$this->queue();

		return $this->public_job( $job );
	}

	/**
	 * Cancel a staged, queued or running job. Rows already written stay.
	 *
	 * @return array|WP_Error Job.
	 */
	public function cancel() {
		$job = $this->current();

		if ( ! $job || ! in_array( $job['status'], array( 'staged', 'queued', 'running' ), true ) ) {
			return new WP_Error( 'wbpc_import_none', __( 'There is no import to cancel.', 'woo-pincode-checker' ), array( 'status' => 400 ) );
		}

		// An upload that was never started is simply discarded: nothing ran, so no history entry.
		if ( 'staged' === $job['status'] ) {
			$this->discard_staged();
			delete_option( 'wbpc_job' );
			return array( 'status' => 'discarded' ) + $this->public_job( $job );
		}

		as_unschedule_all_actions( self::HOOK, array(), self::GROUP );
		$job['status']  = 'cancelled';
		$job['message'] = __( 'Import cancelled. Areas already imported were kept.', 'woo-pincode-checker' );

		return $this->finish( $job );
	}

	/**
	 * Action Scheduler callback: import the next chunk.
	 *
	 * @param bool $queue_next Queue the following chunk (false when WP-CLI drives the loop).
	 * @return bool False when another process holds the lock.
	 */
	public function run_chunk( bool $queue_next = true ): bool {
		// Action Scheduler and the progress poll can both call this: only one chunk runs at a time.
		if ( ! $this->lock() ) {
			return false;
		}

		try {
			$this->chunk( $queue_next );
		} finally {
			delete_option( 'wbpc_import_lock' );
		}

		return true;
	}

	/**
	 * Run chunks for up to $seconds in this request. The progress poll calls this, so an import
	 * moves as soon as it starts instead of waiting for WP-Cron; Action Scheduler still finishes
	 * it if the page is closed.
	 *
	 * @param float $seconds Time budget.
	 */
	public function run_for( float $seconds ): void {
		$until = microtime( true ) + $seconds;

		do {
			if ( ! $this->run_chunk() ) {
				return; // Action Scheduler is on it.
			}
			$job = $this->current();
		} while ( $job && in_array( $job['status'], array( 'queued', 'running' ), true ) && microtime( true ) < $until );
	}

	/**
	 * Queue the next chunk unless one is already pending. Checks PENDING only:
	 * as_has_scheduled_action() also counts the in-progress action calling this, which broke the chain.
	 */
	private function queue(): void {
		if ( ! as_get_scheduled_actions(
			array(
				'hook'     => self::HOOK,
				'group'    => self::GROUP,
				'status'   => \ActionScheduler_Store::STATUS_PENDING,
				'per_page' => 1,
			),
			'ids'
		) ) {
			as_enqueue_async_action( self::HOOK, array(), self::GROUP );
		}
	}

	/**
	 * Atomic lock: add_option() fails when the row exists. A lock older than 2 minutes is a crashed run.
	 */
	private function lock(): bool {
		if ( add_option( 'wbpc_import_lock', time(), '', false ) ) {
			return true;
		}

		wp_cache_delete( 'wbpc_import_lock', 'options' );
		if ( time() - (int) get_option( 'wbpc_import_lock' ) > 120 ) {
			update_option( 'wbpc_import_lock', time(), false );
			return true;
		}

		return false;
	}

	/**
	 * Import the next chunk (caller holds the lock).
	 *
	 * @param bool $queue_next Queue the following chunk.
	 */
	private function chunk( bool $queue_next ): void {
		wp_cache_delete( 'wbpc_job', 'options' ); // Another process may have advanced the job.
		$job = $this->current();

		if ( ! $job || ! in_array( $job['status'], array( 'queued', 'running' ), true ) ) {
			return;
		}

		$job['status'] = 'running';

		try {
			if ( 'replace' === $job['mode'] && ! $job['replaced'] ) {
				$this->areas->delete_where( array() );
				$job['replaced'] = true;
			}

			$handle = fopen( $this->csv->private_dir() . $job['file'], 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
			if ( ! $handle ) {
				$job['status']  = 'failed';
				$job['message'] = __( 'The uploaded file is no longer on the server.', 'woo-pincode-checker' );
				$this->finish( $job );
				return;
			}
			fseek( $handle, (int) $job['offset'] );

			$rows   = array();
			$errors = array();
			$read   = 0;

			while ( $read < self::CHUNK && false !== ( $values = CsvService::read( $handle ) ) ) { // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
				++$job['line'];
				if ( $this->is_blank( $values ) ) {
					continue;
				}
				++$read;

				$input = $this->csv->to_input( $job['map'], $values, $job['country'] );
				$row   = $this->service->validate( $input, 0, false );

				if ( is_wp_error( $row ) ) {
					$errors[] = array( $job['line'], $input['code'] ?? '', $this->error_text( $row ) );
				} else {
					$rows[] = $row;
				}
			}

			$eof           = feof( $handle ) || $read < self::CHUNK;
			$job['offset'] = ftell( $handle );
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

			$this->write( $job, $rows );
			$this->log_errors( $job, $errors );

			$job['processed'] += $read;
			$job['failed']    += count( $errors );

			if ( $eof ) {
				$job['status']  = 'done';
				$job['message'] = __( 'Import finished.', 'woo-pincode-checker' );
				$this->finish( $job );

				/**
				 * An import finished.
				 *
				 * @param array $job Final job summary.
				 */
				do_action( 'wbpc_areas_imported', $this->public_job( $job ) );
				return;
			}

			$this->save( $job );

			if ( $queue_next ) {
				$this->queue();
			}
		} catch ( \Throwable $e ) {
			$job['status']  = 'failed';
			$job['message'] = $e->getMessage();
			$this->finish( $job );
		}
	}

	/**
	 * Run a staged import to the end in this process (WP-CLI).
	 *
	 * @param callable|null $tick Called with the job after each chunk.
	 */
	public function run_now( ?callable $tick = null ): array {
		$started = $this->start();

		if ( is_wp_error( $started ) ) {
			return $started->get_error_data() + array( 'message' => $started->get_error_message() );
		}

		as_unschedule_all_actions( self::HOOK, array(), self::GROUP );

		do {
			$this->run_chunk( false );
			$job = (array) $this->current();
			if ( $tick ) {
				$tick( $job );
			}
		} while ( in_array( $job['status'], array( 'queued', 'running' ), true ) );

		return $this->public_job( $job );
	}

	/**
	 * Job fields the UI needs (no internal file names or header map).
	 *
	 * @param array $job Job.
	 */
	public function public_job( array $job ): array {
		return array_intersect_key( $job, array_flip( array( 'id', 'status', 'name', 'mode', 'country', 'total', 'processed', 'added', 'updated', 'skipped', 'failed', 'started', 'heartbeat', 'message' ) ) )
			+ array( 'has_errors' => '' !== ( $job['error_file'] ?? '' ) );
	}

	/**
	 * Absolute path of a job's error report, if it still exists.
	 *
	 * @param string $job_id Job id (current or history).
	 */
	public function error_file( string $job_id ): string {
		foreach ( array_merge( array( $this->current() ), $this->history() ) as $job ) {
			if ( is_array( $job ) && $job['id'] === $job_id && '' !== ( $job['error_file'] ?? '' ) ) {
				$path = $this->csv->private_dir() . $job['error_file'];
				return is_readable( $path ) ? $path : '';
			}
		}

		return '';
	}

	/**
	 * Write a chunk and count the outcome.
	 *
	 * @param array $job  Job (by reference).
	 * @param array $rows Valid rows.
	 */
	private function write( array &$job, array $rows ): void {
		if ( ! $rows ) {
			return;
		}

		if ( 'update' === $job['mode'] ) {
			// Only columns present in the file are overwritten; code/code_to/range_end follow the code column.
			$cols = array_values( array_filter( $job['map'] ) );
			if ( in_array( 'code', $cols, true ) ) {
				array_push( $cols, 'code_to', 'range_end' );
			}

			$before = $this->areas->count();
			$this->areas->insert_many( $rows, true, $cols );
			$added           = $this->areas->count() - $before;
			$job['added']   += $added;
			$job['updated'] += count( $rows ) - $added;
			return;
		}

		$inserted        = $this->areas->insert_many( $rows );
		$job['added']   += $inserted;
		$job['skipped'] += count( $rows ) - $inserted;
	}

	/**
	 * Append rejected rows to the job's error report.
	 *
	 * @param array $job    Job (by reference).
	 * @param array $errors [ line, code, message ] rows.
	 */
	private function log_errors( array &$job, array $errors ): void {
		if ( ! $errors ) {
			return;
		}

		$new = '' === $job['error_file'];
		if ( $new ) {
			$job['error_file'] = wp_generate_password( 32, false ) . '-errors.csv';
		}

		$handle = fopen( $this->csv->private_dir() . $job['error_file'], 'a' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( $new ) {
			CsvService::write( $handle, array( 'line', 'code', 'error' ) );
		}
		foreach ( $errors as $error ) {
			CsvService::write( $handle, $error );
		}
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	}

	/**
	 * End a job: delete its upload, keep the error report, record history.
	 *
	 * @param array $job Job.
	 */
	private function finish( array $job ): array {
		wp_delete_file( $this->csv->private_dir() . $job['file'] );
		$this->save( $job );

		$history = array_filter(
			$this->history(),
			static fn( $old ) => is_array( $old ) && $old['id'] !== $job['id']
		);
		array_unshift( $history, $job );

		// Error reports of jobs that fall out of the last 10 go with them.
		foreach ( array_slice( $history, 10 ) as $old ) {
			if ( ! empty( $old['error_file'] ) ) {
				wp_delete_file( $this->csv->private_dir() . $old['error_file'] );
			}
		}
		update_option( 'wbpc_job_history', array_slice( $history, 0, 10 ), false );

		return $this->public_job( $job );
	}

	/**
	 * Drop a staged-but-never-started upload before staging a new one.
	 */
	private function discard_staged(): void {
		$job = $this->current();

		if ( $job && 'staged' === $job['status'] ) {
			wp_delete_file( $this->csv->private_dir() . $job['file'] );
		}
	}

	/**
	 * Persist job state.
	 *
	 * @param array $job Job.
	 */
	private function save( array $job ): void {
		$job['heartbeat'] = time();
		update_option( 'wbpc_job', $job, false );
	}

	/**
	 * Whether a CSV row is empty.
	 *
	 * @param array $values Cells.
	 */
	private function is_blank( array $values ): bool {
		return '' === trim( implode( '', array_map( 'strval', $values ) ) );
	}

	/**
	 * One-line text of a validation error.
	 *
	 * @param WP_Error $error Error from AreaService::validate().
	 */
	private function error_text( WP_Error $error ): string {
		$fields = (array) ( $error->get_error_data()['fields'] ?? array() );
		unset( $fields['_duplicate_id'] );

		return $fields ? implode( ' ', $fields ) : $error->get_error_message();
	}
}
