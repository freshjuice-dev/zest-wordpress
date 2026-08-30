<?php
/**
 * Plugin Name: Zest CMP
 * Plugin URI: https://cookiezest.com
 * Description: Lightweight GDPR/CCPA cookie consent toolkit with script blocking, cookie interception, and a beautiful Shadow DOM UI — powered by @freshjuice/zest.
 * Version: 1.0.0
 * Author: FreshJuice
 * Author URI: https://freshjuice.dev
 * License: GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: zest-cmp
 * Domain Path: /languages
 *
 * @package ZestCMP
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ZEST_CMP_VERSION', '1.0.0' );
define( 'ZEST_CMP_ZEST_VERSION', '2.7.0' );
define( 'ZEST_CMP_FILE', __FILE__ );
define( 'ZEST_CMP_DIR', plugin_dir_path( __FILE__ ) );
define( 'ZEST_CMP_URL', plugin_dir_url( __FILE__ ) );
define( 'ZEST_CMP_BASENAME', plugin_basename( __FILE__ ) );

require_once ZEST_CMP_DIR . 'includes/class-zest-cmp-settings.php';
require_once ZEST_CMP_DIR . 'includes/class-zest-cmp-enqueue.php';

final class Zest_CMP {

	private static ?Zest_CMP $instance = null;

	public static function get_instance(): Zest_CMP {
		return self::$instance ??= new self();
	}

	private function __construct() {
		add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
		add_action( 'plugins_loaded', [ $this, 'init' ] );
		add_filter( 'plugin_action_links_' . ZEST_CMP_BASENAME, [ $this, 'settings_link' ] );
	}

	public function load_textdomain(): void {
		load_plugin_textdomain( 'zest-cmp', false, dirname( ZEST_CMP_BASENAME ) . '/languages' );
	}

	public function init(): void {
		Zest_CMP_Settings::get_instance();
		Zest_CMP_Enqueue::get_instance();
	}

	/** @param string[] $links */
	public function settings_link( array $links ): array {
		$settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=zest-cmp' ) ) . '">' . esc_html__( 'Settings', 'zest-cmp' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}
}

Zest_CMP::get_instance();