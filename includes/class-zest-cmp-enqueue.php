<?php
/**
 * Enqueue class for Zest CMP.
 *
 * Enqueues the self-hosted Zest JS bundle from dist/ and outputs the
 * window.ZestConfig object as an inline script immediately before it.
 *
 * @package ZestCMP
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Zest_CMP_Enqueue {

	private static ?Zest_CMP_Enqueue $instance = null;

	public static function get_instance(): Zest_CMP_Enqueue {
		return self::$instance ??= new self();
	}

	private function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_bundle' ] );
	}

	/**
	 * Enqueue the self-hosted bundle and print the config before it.
	 */
	public function enqueue_bundle(): void {
		$options = Zest_CMP_Settings::get_instance()->get_settings();

		if ( empty( $options['enabled'] ) ) {
			return;
		}

		if ( ! file_exists( ZEST_CMP_DIR . 'dist/zest.min.js' ) ) {
			return;
		}

		wp_enqueue_script( 'zest-cmp', ZEST_CMP_URL . 'dist/zest.min.js', [], ZEST_CMP_VERSION, false );
		wp_add_inline_script( 'zest-cmp', 'window.ZestConfig = ' . wp_json_encode( $this->build_config( $options ) ) . ';', 'before' );
	}

	/**
	 * Build the Zest config object from plugin settings.
	 *
	 * @param array<string, mixed> $options Plugin settings.
	 * @return array<string, mixed>
	 */
	public function build_config( array $options ): array {
		$config = [
			'position'      => $options['position'],
			'theme'          => 'custom' === $options['theme'] ? 'auto' : $options['theme'],
			'accentColor'   => $options['accent_color'],
			'buttonStyle'   => $options['button_style'],
			'buttonLayout'  => $options['button_layout'],
			'mode'           => $options['mode'],
			'lang'           => $options['lang'],
			'dntBehavior'   => $options['dnt_behavior'] ?? 'reject',
			'expiration'     => (int) ( $options['expiration'] ?? 365 ),
			'showWidget'    => (bool) $options['show_widget'],
			'respectDNT'     => 'ignore' !== ( $options['dnt_behavior'] ?? 'reject' ),
			'branding'       => $this->normalize_branding( $options['branding'] ),
		];

		if ( 'gateway' === ( $options['geo'] ?? '0' ) ) {
			$config['geo'] = true;
		}

		$blocked = is_string( $options['blocked_domains'] ?? null ) ? trim( $options['blocked_domains'] ) : '';
		if ( '' !== $blocked ) {
			$config['blockedDomains'] = preg_split( '/\s+/', $blocked ) ?: [];
		}

		$allowed = is_string( $options['allowed_domains'] ?? null ) ? trim( $options['allowed_domains'] ) : '';
		if ( '' !== $allowed ) {
			$config['allowedDomains'] = preg_split( '/\s+/', $allowed ) ?: [];
		}

		if ( ! empty( $options['backdrop_blur'] ) ) {
			$config['backdropBlur'] = (int) $options['backdrop_blur'];
		}

		if ( ! empty( $options['hard_wall'] ) ) {
			$config['hardWall'] = true;
		}

		if ( ! empty( $options['policy_url'] ) ) {
			$config['policyUrl'] = $options['policy_url'];
		}

		if ( ! empty( $options['imprint_url'] ) ) {
			$config['imprintUrl'] = $options['imprint_url'];
		}

		// Compiled customStyles: theme-vars (custom theme only) + free-form CSS.
		// Mirrors the /play codegen: :host and :host([data-theme="dark"]) so
		// overrides hold in both themes.
		$vars = [];
		if ( 'custom' === ( $options['theme'] ?? '' ) ) {
			foreach ( [
				'--zest-bg'             => 'color_bg',
				'--zest-bg-secondary'   => 'color_bg_secondary',
				'--zest-text'           => 'color_text',
				'--zest-text-secondary' => 'color_text_secondary',
				'--zest-border'         => 'color_border',
			] as $css_var => $key ) {
				if ( ! empty( $options[ $key ] ) ) {
					$vars[] = sprintf( '%s: %s;', $css_var, $options[ $key ] );
				}
			}
		}
		if ( isset( $options['radius'] ) && 12 !== (int) $options['radius'] ) {
			$vars[] = sprintf( '--zest-radius: %dpx;', (int) $options['radius'] );
			$vars[] = sprintf( '--zest-radius-sm: %dpx;', (int) $options['radius'] );
			// Radius applies to any theme, so anchor it without the custom guard.
		}
		$parts = [];
		if ( $vars ) {
			$block = implode( "\n", $vars );
			$parts[] = ":host,\n:host([data-theme=\"light\"]),\n:host([data-theme=\"dark\"]),\n:host([data-theme=\"auto\"]) {\n$block\n}";
		}
		$extra = trim( $options['custom_styles'] ?? '' );
		if ( '' !== $extra ) {
			$parts[] = $extra;
		}
		if ( $parts ) {
			$config['customStyles'] = implode( "\n", $parts );
		}

		// Hidden categories.
		if ( ! empty( $options['hide_categories'] ) && is_array( $options['hide_categories'] ) ) {
			$config['categories'] = [];
			foreach ( $options['hide_categories'] as $cat ) {
				$config['categories'][ $cat ] = [ 'hidden' => true ];
			}
		}

		return $config;
	}

	/**
	 * Normalize branding value: '1' -> true, '0' -> false, string stays.
	 *
	 * @param string $value
	 * @return bool|string
	 */
	private function normalize_branding( string $value ) {
		if ( '1' === $value ) {
			return true;
		}
		if ( '0' === $value ) {
			return false;
		}
		return $value;
	}
}