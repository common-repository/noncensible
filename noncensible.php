<?php
/*
Plugin Name: Noncensible
Description: Plugs some default WordPress functions to ensure nonces have a guaranteed lifespan. This ensures forms, actions, and URLs do not expire prematurely.
Version: 1.1.0
Requires at least: 2.5.0
Requires PHP: 5.4.0
Author: Roy Orbitson
Author URI: https://profiles.wordpress.org/lev0/
License: GPLv2 or later
*/

const NONCENSIBLE_TICKS_PER_NONCE_LIFE = 8;

if (
	!(
		function_exists('wp_nonce_tick')
		|| function_exists('wp_verify_nonce')
		|| function_exists('wp_create_nonce') # not plugged here, but avoid conflicts if so
	)
) {
	/**
	 * Returns the time-dependent variable for nonce creation.
	 *
	 * A nonce has a lifespan of eight ticks. Nonces in their fifth tick onwards
	 * (equivalent to core's second tick) may be updated, e.g. by autosave.
	 *
	 * @return float Float value rounded up to the next highest integer.
	 */
	function wp_nonce_tick( $action = -1 ) {
		/**
		 * Filters the lifespan of nonces in seconds.
		 *
		 * @param int        $lifespan Lifespan of nonces in seconds. Default 86,400 seconds, or one day.
		 * @param string|int $action   The nonce action, or -1 if none was provided.
		 */
		$nonce_life = apply_filters( 'nonce_life', DAY_IN_SECONDS, $action );

		return ceil( time() / ( $nonce_life / NONCENSIBLE_TICKS_PER_NONCE_LIFE ) );
	}

	/**
	 * Verifies that a correct security nonce was used with time limit.
	 * Return values are compatible with core's 2 ticks per nonce lifespan.
	 *
	 * A nonce is valid for 24 - 27 hours (by default), i.e. 1 - 1⅛ of a lifespan.
	 *
	 * @param string     $nonce  Nonce value that was used for verification, usually via a form field.
	 * @param string|int $action Should give context to what is taking place and be the same when nonce was created.
	 * @return int|false 1 if the nonce is valid and generated between 0-15 hours ago,
	 *                   2 if the nonce is valid and generated between 12-27 hours ago.
	 *                   False if the nonce is invalid.
	 */
	function wp_verify_nonce( $nonce, $action = -1 ) {
		$nonce = (string) $nonce;
		$user  = wp_get_current_user();
		$uid   = (int) $user->ID;
		if ( ! $uid ) {
			/**
			 * Filters whether the user who generated the nonce is logged out.
			 *
			 * @param int        $uid    ID of the nonce-owning user.
			 * @param string|int $action The nonce action, or -1 if none was provided.
			 */
			$uid = apply_filters( 'nonce_user_logged_out', $uid, $action );
		}

		if ( empty( $nonce ) ) {
			return false;
		}

		$token = wp_get_session_token();
		$tick  = wp_nonce_tick( $action );
		$i     = 0;

		while ( $i <= NONCENSIBLE_TICKS_PER_NONCE_LIFE ) { # yes, ticks + 1
			$expected = substr( wp_hash( "$tick|$action|$uid|$token", 'nonce' ), -12, 10 );
			if ( hash_equals( $expected, $nonce ) ) {
				// Nonce generated 0-15 hours ago.
				if ( $i <= ( NONCENSIBLE_TICKS_PER_NONCE_LIFE / 2 ) ) {
					return 1;
				}

				// Nonce generated 12-27 hours ago.
				return 2;
			}
			$tick--;
			$i++;
		}

		/**
		 * Fires when nonce verification fails.
		 *
		 * @param string     $nonce  The invalid nonce.
		 * @param string|int $action The nonce action.
		 * @param WP_User    $user   The current user object.
		 * @param string     $token  The user's session token.
		 */
		do_action( 'wp_verify_nonce_failed', $nonce, $action, $user, $token );

		// Invalid nonce.
		return false;
	}
}
