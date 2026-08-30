<?php
/**
 * Uninstall cleanup for Zest CMP.
 *
 * @package ZestCMP
 */

declare( strict_types = 1 );

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'zest_cmp_settings' );
