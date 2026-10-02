<?php
/**
 * Per-client request limit for public endpoints.
 *
 * @package Wbcom\PincodeChecker
 */

declare( strict_types = 1 );

namespace Wbcom\PincodeChecker\Services;

defined( 'ABSPATH' ) || exit;

/**
 * Fixed one-minute window per hashed client IP. Atomic with a persistent object cache
 * (wp_cache_incr); transients otherwise.
 */
final class RateLimiter {

	// ponytail: the transient fallback is read-modify-write, so a burst of parallel requests can
	// slightly exceed the limit. Fine for abuse damping; add a persistent object cache for exact limits.


	/**
	 * Count one request; false when the client is over the limit.
	 *
	 * @param string $bucket Endpoint name.
	 */
	public function hit( string $bucket ): bool {
		/**
		 * Requests per minute per client.
		 *
		 * @param int    $limit  Default 60.
		 * @param string $bucket Endpoint.
		 */
		$limit = (int) apply_filters( 'wbpc_rate_limit', 60, $bucket );
		$key   = 'wbpc_rl_' . md5( $bucket . '|' . $this->client_ip() . '|' . (int) floor( time() / 60 ) );

		if ( wp_using_ext_object_cache() ) {
			wp_cache_add( $key, 0, 'wbpc_rl', 70 );
			$count = (int) wp_cache_incr( $key, 1, 'wbpc_rl' );
		} else {
			$count = (int) get_transient( $key ) + 1;
			set_transient( $key, $count, 70 );
		}

		return $count <= $limit;
	}

	/**
	 * Client IP. REMOTE_ADDR only: forwarded headers are trivially spoofed. Sites behind a proxy
	 * or CDN restore the real IP with the wbpc_client_ip filter.
	 */
	private function client_ip(): string {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		/**
		 * Client IP used for rate limiting.
		 *
		 * @param string $ip REMOTE_ADDR.
		 */
		return (string) apply_filters( 'wbpc_client_ip', $ip );
	}
}
