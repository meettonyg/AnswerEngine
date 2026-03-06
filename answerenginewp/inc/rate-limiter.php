<?php
/**
 * Rate Limiter
 *
 * IP-based rate limiting using WordPress transients.
 *
 * @package AnswerEngineWP
 */

/**
 * Check if the current IP has exceeded the rate limit.
 *
 * @return true|WP_Error True if allowed, WP_Error if rate limited.
 */
function aewp_check_rate_limit() {
    $ip      = aewp_get_client_ip();
    $ip_hash = md5( $ip . ( defined( 'AUTH_SALT' ) ? AUTH_SALT : 'aewp-salt' ) );
    $key     = 'aewp_rate_' . $ip_hash;
    $limit   = 10; // scans per hour
    $window  = HOUR_IN_SECONDS;

    $data = get_transient( $key );

    if ( false === $data ) {
        // First request
        set_transient( $key, array(
            'count'   => 1,
            'started' => time(),
        ), $window );
        return true;
    }

    if ( $data['count'] >= $limit ) {
        $elapsed   = time() - $data['started'];
        $remaining = $window - $elapsed;
        if ( $remaining <= 0 ) {
            // Window expired, reset
            delete_transient( $key );
            set_transient( $key, array(
                'count'   => 1,
                'started' => time(),
            ), $window );
            return true;
        }
        return new WP_Error(
            'rate_limited',
            'You have reached the scan limit. Try again later.',
            $remaining
        );
    }

    // Increment counter
    $data['count']++;
    set_transient( $key, $data, $window - ( time() - $data['started'] ) );

    return true;
}
