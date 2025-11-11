<?php
/**
 * Nearby Pincode Suggestions Handler
 *
 * Provides nearby serviceable pincode suggestions when user's pincode is not available.
 * Uses OpenStreetMap Nominatim for geocoding (100% free).
 *
 * @package    Woo_Pincode_Checker
 * @subpackage Woo_Pincode_Checker/includes
 * @since      1.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Nearby Suggestions Handler Class
 *
 * @since 1.5.0
 */
class Woo_Pincode_Nearby_Suggestions {

	/**
	 * Geocode a pincode using Nominatim (OpenStreetMap)
	 * Free service, rate limit: 1 request per second
	 *
	 * @param string $pincode Pincode to geocode
	 * @return array|null Array with latitude and longitude, or null on failure
	 */
	public function geocode_pincode_nominatim( $pincode ) {
		// Rate limiting: wait 1 second between requests
		$last_request = get_transient( 'wpc_last_nominatim_request' );
		if ( false !== $last_request ) {
			$elapsed = time() - $last_request;
			if ( $elapsed < 1 ) {
				sleep( 1 - $elapsed );
			}
		}
		set_transient( 'wpc_last_nominatim_request', time(), 5 );

		$url = add_query_arg(
			array(
				'postalcode' => $pincode,
				'country'    => 'IN', // India - change if needed
				'format'     => 'json',
				'limit'      => 1
			),
			'https://nominatim.openstreetmap.org/search'
		);

		$response = wp_remote_get( $url, array(
			'headers' => array(
				'User-Agent' => 'WooCommerce Pincode Checker Plugin/1.5 (' . get_site_url() . ')'
			),
			'timeout' => 10
		) );

		if ( is_wp_error( $response ) ) {
			error_log( 'WPC Nominatim Error: ' . $response->get_error_message() );
			return null;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! empty( $data[0] ) && isset( $data[0]['lat'], $data[0]['lon'] ) ) {
			return array(
				'latitude'  => floatval( $data[0]['lat'] ),
				'longitude' => floatval( $data[0]['lon'] )
			);
		}

		return null;
	}

	/**
	 * Geocode a pincode and return full details including city and state
	 * Free service using OpenStreetMap Nominatim
	 *
	 * @param string $pincode Pincode to geocode
	 * @return array|null Array with latitude, longitude, city, and state, or null on failure
	 */
	public function geocode_pincode_with_details( $pincode ) {
		// Rate limiting: wait 1 second between requests
		$last_request = get_transient( 'wpc_last_nominatim_request' );
		if ( false !== $last_request ) {
			$elapsed = time() - $last_request;
			if ( $elapsed < 1 ) {
				sleep( 1 - $elapsed );
			}
		}
		set_transient( 'wpc_last_nominatim_request', time(), 5 );

		$url = add_query_arg(
			array(
				'postalcode'       => $pincode,
				'country'          => 'IN', // India - change if needed
				'format'           => 'json',
				'limit'            => 1,
				'addressdetails'   => 1 // Request address components
			),
			'https://nominatim.openstreetmap.org/search'
		);

		$response = wp_remote_get( $url, array(
			'headers' => array(
				'User-Agent' => 'WooCommerce Pincode Checker Plugin/1.5 (' . get_site_url() . ')'
			),
			'timeout' => 10
		) );

		if ( is_wp_error( $response ) ) {
			error_log( 'WPC Nominatim Error: ' . $response->get_error_message() );
			return null;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! empty( $data[0] ) && isset( $data[0]['lat'], $data[0]['lon'] ) ) {
			$address = isset( $data[0]['address'] ) ? $data[0]['address'] : array();

			// Extract city (try multiple fields)
			$city = '';
			if ( ! empty( $address['city'] ) ) {
				$city = $address['city'];
			} elseif ( ! empty( $address['town'] ) ) {
				$city = $address['town'];
			} elseif ( ! empty( $address['village'] ) ) {
				$city = $address['village'];
			} elseif ( ! empty( $address['suburb'] ) ) {
				$city = $address['suburb'];
			}

			// Extract state
			$state = '';
			if ( ! empty( $address['state'] ) ) {
				$state = $address['state'];
			}

			return array(
				'latitude'  => floatval( $data[0]['lat'] ),
				'longitude' => floatval( $data[0]['lon'] ),
				'city'      => $city,
				'state'     => $state,
				'display_name' => isset( $data[0]['display_name'] ) ? $data[0]['display_name'] : ''
			);
		}

		return null;
	}

	/**
	 * Update pincode coordinates in database
	 *
	 * @param string $pincode Pincode
	 * @param array  $coords  Coordinates array with latitude and longitude
	 * @return bool True on success, false on failure
	 */
	public function update_pincode_coordinates( $pincode, $coords ) {
		if ( empty( $coords ) || ! isset( $coords['latitude'], $coords['longitude'] ) ) {
			return false;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'pincode_checker';

		$result = $wpdb->update(
			$table_name,
			array(
				'latitude'  => $coords['latitude'],
				'longitude' => $coords['longitude']
			),
			array( 'pincode' => $pincode ),
			array( '%f', '%f' ),
			array( '%s' )
		);

		return false !== $result;
	}

	/**
	 * Get pincode coordinates from database
	 *
	 * @param string $pincode Pincode
	 * @return array|null Array with latitude and longitude, or null if not found
	 */
	public function get_pincode_coordinates( $pincode ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'pincode_checker';

		$coords = $wpdb->get_row( $wpdb->prepare(
			"SELECT latitude, longitude FROM {$table_name} WHERE pincode = %s",
			$pincode
		) );

		if ( $coords && ! is_null( $coords->latitude ) && ! is_null( $coords->longitude ) ) {
			return array(
				'latitude'  => floatval( $coords->latitude ),
				'longitude' => floatval( $coords->longitude )
			);
		}

		return null;
	}

	/**
	 * Calculate distance between two coordinates using Haversine formula
	 *
	 * @param float $lat1 Latitude of point 1
	 * @param float $lon1 Longitude of point 1
	 * @param float $lat2 Latitude of point 2
	 * @param float $lon2 Longitude of point 2
	 * @return float Distance in kilometers
	 */
	public function calculate_distance( $lat1, $lon1, $lat2, $lon2 ) {
		$earth_radius = 6371; // Earth radius in kilometers

		$lat_diff = deg2rad( $lat2 - $lat1 );
		$lon_diff = deg2rad( $lon2 - $lon1 );

		$a = sin( $lat_diff / 2 ) * sin( $lat_diff / 2 ) +
			cos( deg2rad( $lat1 ) ) * cos( deg2rad( $lat2 ) ) *
			sin( $lon_diff / 2 ) * sin( $lon_diff / 2 );

		$c = 2 * atan2( sqrt( $a ), sqrt( 1 - $a ) );

		return $earth_radius * $c;
	}

	/**
	 * Find nearby serviceable pincodes using distance calculation
	 *
	 * @param string $pincode   User's pincode (not serviceable)
	 * @param int    $radius_km Search radius in kilometers (default 20)
	 * @param int    $limit     Maximum number of suggestions (default 5)
	 * @return array Array of nearby pincodes with details
	 */
	public function find_nearby_by_distance( $pincode, $radius_km = 20, $limit = 5 ) {
		// Get coordinates for user's pincode
		$user_coords = $this->get_pincode_coordinates( $pincode );

		if ( ! $user_coords ) {
			// Try to geocode it
			$user_coords = $this->geocode_pincode_nominatim( $pincode );

			if ( ! $user_coords ) {
				return array(); // Can't geocode, can't find nearby
			}
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'pincode_checker';

		// Get all pincodes with coordinates
		$pincodes = $wpdb->get_results( "
			SELECT pincode, city, state, delivery_days, latitude, longitude
			FROM {$table_name}
			WHERE latitude IS NOT NULL
			AND longitude IS NOT NULL
			AND pincode != '{$pincode}'
		" );

		if ( empty( $pincodes ) ) {
			return array();
		}

		// Calculate distances
		$nearby = array();
		foreach ( $pincodes as $pin ) {
			$distance = $this->calculate_distance(
				$user_coords['latitude'],
				$user_coords['longitude'],
				floatval( $pin->latitude ),
				floatval( $pin->longitude )
			);

			if ( $distance <= $radius_km ) {
				$nearby[] = array(
					'pincode'       => $pin->pincode,
					'city'          => $pin->city,
					'state'         => $pin->state,
					'delivery_days' => $pin->delivery_days,
					'distance_km'   => round( $distance, 1 )
				);
			}
		}

		// Sort by distance
		usort( $nearby, function( $a, $b ) {
			return $a['distance_km'] <=> $b['distance_km'];
		} );

		return array_slice( $nearby, 0, $limit );
	}

	/**
	 * Find nearby pincodes using prefix and city matching (fallback method)
	 * Works without geocoding data
	 *
	 * @param string $pincode User's pincode (not serviceable)
	 * @param int    $limit   Maximum number of suggestions (default 5)
	 * @return array Array of nearby pincodes with details
	 */
	public function find_nearby_by_prefix( $pincode, $limit = 5 ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'pincode_checker';

		$results = array();

		// Level 1: Try 4-digit prefix match
		$prefix_4 = substr( $pincode, 0, 4 );
		if ( strlen( $prefix_4 ) === 4 ) {
			$results = $wpdb->get_results( $wpdb->prepare(
				"SELECT pincode, city, state, delivery_days
				FROM {$table_name}
				WHERE pincode LIKE %s
				AND pincode != %s
				LIMIT %d",
				$prefix_4 . '%',
				$pincode,
				$limit
			) );
		}

		// Level 2: If not enough results, try 3-digit prefix
		if ( count( $results ) < $limit ) {
			$prefix_3 = substr( $pincode, 0, 3 );
			$additional = $wpdb->get_results( $wpdb->prepare(
				"SELECT pincode, city, state, delivery_days
				FROM {$table_name}
				WHERE pincode LIKE %s
				AND pincode != %s
				AND pincode NOT IN (SELECT pincode FROM {$table_name} WHERE pincode LIKE %s)
				LIMIT %d",
				$prefix_3 . '%',
				$pincode,
				$prefix_4 . '%',
				$limit - count( $results )
			) );

			$results = array_merge( $results, $additional );
		}

		// Format results
		$nearby = array();
		foreach ( $results as $pin ) {
			$nearby[] = array(
				'pincode'       => $pin->pincode,
				'city'          => $pin->city,
				'state'         => $pin->state,
				'delivery_days' => $pin->delivery_days
			);
		}

		return array_slice( $nearby, 0, $limit );
	}

	/**
	 * Find nearby pincodes - smart method that uses best available strategy
	 *
	 * @param string $pincode User's pincode (not serviceable)
	 * @param array  $options Options: radius_km, limit, method
	 * @return array Array of nearby pincodes with details
	 */
	public function find_nearby( $pincode, $options = array() ) {
		$defaults = array(
			'radius_km' => 20,
			'limit'     => 5,
			'method'    => 'auto' // auto, distance, prefix
		);

		$options = wp_parse_args( $options, $defaults );

		// Check if geocoding is available
		$has_geocoding = $this->check_geocoding_available();

		if ( $options['method'] === 'distance' || ( $options['method'] === 'auto' && $has_geocoding ) ) {
			// Try distance-based search
			$results = $this->find_nearby_by_distance( $pincode, $options['radius_km'], $options['limit'] );

			if ( ! empty( $results ) ) {
				return $results;
			}
		}

		// Fall back to prefix matching
		return $this->find_nearby_by_prefix( $pincode, $options['limit'] );
	}

	/**
	 * Check if geocoding data is available for pincodes
	 *
	 * @return bool True if at least 50% of pincodes have coordinates
	 */
	public function check_geocoding_available() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'pincode_checker';

		$total = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
		$geocoded = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name} WHERE latitude IS NOT NULL" );

		if ( $total == 0 ) {
			return false;
		}

		$percentage = ( $geocoded / $total ) * 100;

		return $percentage >= 50;
	}

	/**
	 * Get geocoding statistics
	 *
	 * @return array Statistics with total, geocoded, and percentage
	 */
	public function get_geocoding_stats() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'pincode_checker';

		$total = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
		$geocoded = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name} WHERE latitude IS NOT NULL" );

		$percentage = $total > 0 ? round( ( $geocoded / $total ) * 100, 1 ) : 0;

		return array(
			'total'      => intval( $total ),
			'geocoded'   => intval( $geocoded ),
			'remaining'  => intval( $total - $geocoded ),
			'percentage' => $percentage
		);
	}

	/**
	 * Geocode all pincodes in batches (background processing)
	 *
	 * @param int $batch_size Number of pincodes to process per batch
	 * @return array Result with processed count and remaining count
	 */
	public function geocode_batch( $batch_size = 50 ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'pincode_checker';

		// Get pincodes without coordinates
		$pincodes = $wpdb->get_results( $wpdb->prepare(
			"SELECT pincode FROM {$table_name}
			WHERE latitude IS NULL
			LIMIT %d",
			$batch_size
		) );

		if ( empty( $pincodes ) ) {
			return array(
				'processed' => 0,
				'remaining' => 0,
				'completed' => true
			);
		}

		$processed = 0;

		foreach ( $pincodes as $pin ) {
			$coords = $this->geocode_pincode_nominatim( $pin->pincode );

			if ( $coords ) {
				$this->update_pincode_coordinates( $pin->pincode, $coords );
				$processed++;
			}
		}

		$remaining = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name} WHERE latitude IS NULL" );

		return array(
			'processed' => $processed,
			'remaining' => intval( $remaining ),
			'completed' => $remaining == 0
		);
	}
}
