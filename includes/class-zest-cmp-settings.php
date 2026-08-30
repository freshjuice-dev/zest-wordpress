<?php
/**
 * Settings page for Zest CMP.
 *
 * Registers a Settings page under Settings > Cookie Consent using the
 * WordPress Settings API. All field values are stored in a single
 * wp_options entry as a serialized array.
 *
 * @package ZestCMP
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Zest_CMP_Settings {

	private static ?Zest_CMP_Settings $instance = null;

	private string $option_name = 'zest_cmp_settings';

	/** @var array<string, mixed> */
	private array $defaults;

	private function __construct() {
		$this->defaults = [
			'enabled'         => '1',
			'position'        => 'bottom',
			'theme'            => 'auto',
			'accent_color'    => '#0071e3',
			'radius'          => 12,
			'color_bg'        => '#ffffff',
			'color_bg_secondary' => '#f6f7f7',
			'color_text'      => '#1d2327',
			'color_text_secondary' => '#646970',
			'color_border'    => '#c3c4c7',
			'button_style'    => 'fill',
			'button_layout'   => 'row',
			'backdrop_blur'   => '0',
			'hard_wall'        => '0',
			'mode'             => 'safe',
			'lang'             => 'auto',
			'dnt_behavior'    => 'reject',
			'expiration'       => 365,
			'geo'              => '0',
			'blocked_domains' => '',
			'allowed_domains' => '',
			'show_widget'     => '1',
			'branding'         => '1',
			'policy_url'       => '',
			'imprint_url'      => '',
			'hide_categories' => [],
			'custom_styles'   => '',
		];

		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_init', [ $this, 'register_widgets' ] );
		add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
		add_action( 'wp_ajax_zest_preview', [ $this, 'render_preview_endpoint' ] );
		add_action( 'admin_post_zest_reset', [ $this, 'handle_reset' ] );
	}

	public static function get_instance(): Zest_CMP_Settings {
		return self::$instance ??= new self();
	}

	public function get_option_name(): string {
		return $this->option_name;
	}

	/** @return array<string, mixed> */
	public function get_defaults(): array {
		return $this->defaults;
	}

	/** @return array<string, mixed> */
	public function get_settings(): array {
		$saved = get_option( $this->option_name, [] );
		if ( ! is_array( $saved ) ) {
			$saved = [];
		}
		return wp_parse_args( $saved, $this->defaults );
	}

	public function register_settings(): void {
		register_setting( 'zest_cmp_group', $this->option_name, [ $this, 'sanitize' ] );
	}

	/** @param array<string, mixed> $input */
	public function sanitize( array $input ): array {
		$out = [];

		$out['enabled']         = ( '1' === ( $input['enabled'] ?? '' ) ) ? '1' : '0';
		$out['position']         = sanitize_text_field( $input['position'] ?? 'bottom' );
		$out['theme']            = sanitize_text_field( $input['theme'] ?? 'auto' );
		$out['accent_color']    = sanitize_text_field( $input['accent_color'] ?? '#0071e3' );
		$out['radius']          = min( 24, max( 0, absint( $input['radius'] ?? 12 ) ) );
		foreach ( [ 'color_bg', 'color_bg_secondary', 'color_text', 'color_text_secondary', 'color_border' ] as $color_key ) {
			$raw                       = sanitize_text_field( $input[ $color_key ] ?? '' );
			$out[ $color_key ]         = preg_match( '/^#[0-9A-Fa-f]{6}$/', $raw ) ? $raw : '';		}
		$out['button_style']     = sanitize_text_field( $input['button_style'] ?? 'fill' );
		$out['button_layout']    = sanitize_text_field( $input['button_layout'] ?? 'row' );
		$out['backdrop_blur']    = absint( $input['backdrop_blur'] ?? 0 );
		$out['hard_wall']        = ( '1' === ( $input['hard_wall'] ?? '' ) ) ? '1' : '0';
		$out['mode']             = sanitize_text_field( $input['mode'] ?? 'safe' );
		$out['lang']             = sanitize_text_field( $input['lang'] ?? 'auto' );
		$out['dnt_behavior']    = in_array( $input['dnt_behavior'] ?? '', [ 'reject', 'preselect', 'ignore' ], true ) ? $input['dnt_behavior'] : 'reject';
		$out['expiration']      = min( 365, max( 1, absint( $input['expiration'] ?? 365 ) ) );
		$geo_in        = sanitize_text_field( $input['geo'] ?? '0' );
		$out['geo']             = ( 'gateway' === $geo_in ) ? 'gateway' : '0';
		$out['blocked_domains'] = $this->domains_from_text( $input['blocked_domains'] ?? '' );
		$out['allowed_domains'] = $this->domains_from_text( $input['allowed_domains'] ?? '' );
		$out['show_widget']     = ( '1' === ( $input['show_widget'] ?? '' ) ) ? '1' : '0';
		$out['branding']         = sanitize_text_field( $input['branding'] ?? '1' );
		$out['policy_url']       = esc_url_raw( $input['policy_url'] ?? '' );
		$out['imprint_url']      = esc_url_raw( $input['imprint_url'] ?? '' );

		$hide_cats              = (array) ( $input['hide_categories'] ?? [] );
		$valid_cats             = [ 'functional', 'analytics', 'marketing' ];
		$out['hide_categories'] = array_values( array_intersect( $hide_cats, $valid_cats ) );

		$out['custom_styles']   = wp_strip_all_tags( $input['custom_styles'] ?? '' );

		return $out;
	}

	public function add_menu_page(): void {
		add_options_page(
			__( 'Cookie Consent', 'zest-cmp' ),
			__( 'Cookie Consent', 'zest-cmp' ),
			'manage_options',
			'zest-cmp',
			[ $this, 'render_page' ]
		);
	}

	public function render_page(): void {
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'overview';
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
				<nav class="nav-tab-wrapper" style="margin-bottom:6px">
					<a href="<?php echo esc_url( admin_url( 'options-general.php?page=zest-cmp&tab=overview' ) ); ?>" class="nav-tab <?php echo 'overview' === $tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Overview', 'zest-cmp' ); ?></a>
					<a href="<?php echo esc_url( admin_url( 'options-general.php?page=zest-cmp&tab=settings' ) ); ?>" class="nav-tab <?php echo 'settings' === $tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Settings', 'zest-cmp' ); ?></a>
				</nav>
				<?php if ( 'settings' === $tab ) : ?>
				<span style="display:flex;gap:8px">
					<button type="submit" class="button button-primary" form="zest-settings-form"><?php esc_html_e( 'Save Changes', 'zest-cmp' ); ?></button>
					<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=zest_reset' ), 'zest_reset' ) ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Reset all Zest settings to defaults? This cannot be undone.', 'zest-cmp' ) ); ?>');"><?php esc_html_e( 'Reset to defaults', 'zest-cmp' ); ?></a>
				</span>
				<?php endif; ?>
			</div>
		<?php
		if ( 'settings' === $tab ) {
			$this->render_settings_tab();
		} else {
			$this->render_overview_tab();
		}
		echo '</div>';
	}

	private function render_overview_tab(): void {
		$enabled = ! empty( $this->get_settings()['enabled'] );
		wp_enqueue_style( 'dashboard' );
		?>
		<style>
			/* Overview widgets are fixed — hide core's reorder/toggle action cluster. */
			#dashboard-widgets .handle-actions { display: none; }
		</style>
		<div id="dashboard-widgets" class="metabox-holder">
			<div id="postbox-container-1" class="postbox-container">
				<?php do_meta_boxes( 'zest_cmp_overview', 'normal', '' ); ?>
			</div>
			<div id="postbox-container-2" class="postbox-container">
				<?php do_meta_boxes( 'zest_cmp_overview', 'side', '' ); ?>
			</div>
			<div id="postbox-container-3" class="postbox-container">
				<?php do_meta_boxes( 'zest_cmp_overview', 'column3', '' ); ?>
			</div>
		</div>


		<?php
	}

	/**
	 * Register overview meta boxes on the plugin page hook.
	 */
	public function register_widgets(): void {
		$screen = 'zest_cmp_overview';
		add_meta_box( 'zest_status', __( 'Status', 'zest-cmp' ), [ $this, 'render_status_widget' ], $screen, 'normal', 'high' );
		add_meta_box( 'zest_kb', __( 'Knowledge base', 'zest-cmp' ), [ $this, 'render_kb_widget' ], $screen, 'side', 'high' );
		add_meta_box( 'zest_blog', __( 'From the blog', 'zest-cmp' ), [ $this, 'render_blog_widget' ], $screen, 'column3', 'high' );
		add_meta_box( 'zest_changelog', __( 'Changelog', 'zest-cmp' ), [ $this, 'render_changelog_widget' ], $screen, 'column3', 'low' );
	}

	/**
	 * Status widget.
	 */
	public function render_status_widget(): void {
		$settings = $this->get_settings();
		$enabled  = ! empty( $settings['enabled'] );
		$bundle   = file_exists( ZEST_CMP_DIR . 'dist/zest.min.js' );
		$modes    = $this->get_modes();
		?>
		<table class="widefat striped">
			<tbody>
				<tr><th><?php esc_html_e( 'Banner', 'zest-cmp' ); ?></th><td><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:<?php echo $enabled ? '#00a32a' : '#dba617'; ?>;margin-right:6px;vertical-align:middle"></span><?php echo esc_html( $enabled ? __( 'Enabled', 'zest-cmp' ) : __( 'Disabled', 'zest-cmp' ) ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Script blocking mode', 'zest-cmp' ); ?></th><td><?php echo esc_html( $modes[ $settings['mode'] ] ?? $settings['mode'] ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Banner position', 'zest-cmp' ); ?></th><td><?php echo esc_html( $settings['position'] ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Theme', 'zest-cmp' ); ?></th><td><?php echo esc_html( $settings['theme'] ); ?></td></tr>
				<tr>
					<th><?php esc_html_e( 'Script source', 'zest-cmp' ); ?></th>
					<td><?php echo $bundle ? esc_html__( 'Self-hosted (dist/zest.min.js)', 'zest-cmp' ) : esc_html__( 'CDN fallback (jsDelivr)', 'zest-cmp' ); ?></td>
				</tr>
				<tr><th><?php esc_html_e( 'Plugin version', 'zest-cmp' ); ?></th><td><?php echo esc_html( ZEST_CMP_VERSION ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Zest library version', 'zest-cmp' ); ?></th><td><?php echo esc_html( ZEST_CMP_ZEST_VERSION ); ?></td></tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Knowledge base widget.
	 */
	public function render_kb_widget(): void {
		$links = [
			'https://cookiezest.com/docs/getting-started/' => __( 'Getting started', 'zest-cmp' ),
			'https://cookiezest.com/docs/script-blocking/' => __( 'Script blocking', 'zest-cmp' ),
			'https://cookiezest.com/docs/configuration/'   => __( 'Configuration', 'zest-cmp' ),
			'https://cookiezest.com/docs/events/'          => __( 'Consent events', 'zest-cmp' ),
			'https://cookiezest.com/learn/'                => __( 'Learn about compliance', 'zest-cmp' ),
			'https://cookiezest.com/docs/'                 => __( 'All documentation', 'zest-cmp' ),
		];
		echo '<ul style="margin:0">';
		foreach ( $links as $url => $label ) {
			echo '<li style="padding:4px 0"><a href="' . esc_url( $url ) . '" target="_blank">' . esc_html( $label ) . '</a></li>';
		}
		echo '</ul>';
	}

	/**
	 * Blog feed widget.
	 */
	public function render_blog_widget(): void {
		$feeds = $this->get_feeds();
		echo '<div class="rss-widget">';
		$this->render_feed_items( $feeds['blog'] ?? [] );
		echo '</div>';
	}

	/**
	 * Changelog feed widget.
	 */
	public function render_changelog_widget(): void {
		$feeds = $this->get_feeds();
		echo '<div class="rss-widget">';
		$this->render_feed_items( $feeds['changelog'] ?? [] );
		echo '</div>';
		?>
		<p class="community-events-footer" style="border-top:1px solid #f0f0f1;padding-top:8px">
			<a href="https://cookiezest.com/blog/" target="_blank"><?php esc_html_e( 'Blog', 'zest-cmp' ); ?> <span class="screen-reader-text"> (opens in a new tab)</span><span aria-hidden="true" class="dashicons dashicons-external"></span></a>
			|
			<a href="https://cookiezest.com/changelog/" target="_blank"><?php esc_html_e( 'Changelog', 'zest-cmp' ); ?> <span class="screen-reader-text"> (opens in a new tab)</span><span aria-hidden="true" class="dashicons dashicons-external"></span></a>
			|
			<a href="https://cookiezest.com/learn/" target="_blank"><?php esc_html_e( 'Learn about compliance', 'zest-cmp' ); ?> <span class="screen-reader-text"> (opens in a new tab)</span><span aria-hidden="true" class="dashicons dashicons-external"></span></a>
		</p>
		<?php
	}

	/**
	 * Render feed items in the WordPress dashboard RSS-widget style.
	 *
	 * @param array<int, array<string, mixed>> $items Feed items.
	 */
	private function render_feed_items( array $items ): void {
		if ( empty( $items ) ) {
			echo '<p style="color:#646970">' . esc_html__( 'Feed is temporarily unavailable.', 'zest-cmp' ) . '</p>';
			return;
		}
		$date_format = get_option( 'date_format' );
		echo '<ul>';
		foreach ( $items as $item ) {
			echo '<li>';
			echo '<a class="rsswidget" href="' . esc_url( $item['url'] ?? '' ) . '" target="_blank">' . esc_html( $item['title'] ?? '' ) . '</a>';
			echo ' <span class="rss-date">' . esc_html( date_i18n( $date_format, strtotime( $item['date_published'] ?? '' ) ) ) . '</span>';
			if ( ! empty( $item['summary'] ) ) {
				echo '<div class="rssSummary">' . esc_html( wp_html_excerpt( $item['summary'], 120, '…' ) ) . '</div>';
			}
			echo '</li>';
		}
		echo '</ul>';
	}

	/**
	 * Reset all plugin settings to defaults.
	 */
	public function handle_reset(): void {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'zest_reset' ) ) {
			wp_die();
		}
		delete_option( $this->option_name );
		delete_transient( 'zest_cmp_feeds' );
		wp_safe_redirect( add_query_arg( [ 'page' => 'zest-cmp', 'tab' => 'settings', 'zest-reset' => '1' ], admin_url( 'options-general.php' ) ) );
		exit;
	}

	/**
	 * Live-preview endpoint: minimal HTML page that boots Zest from the
	 * form values passed as zest_* GET params (sanitized like a save),
	 * falling back to saved settings for anything not sent. Loaded into
	 * an iframe on the Settings tab.
	 */
	public function render_preview_endpoint(): void {
		if ( ! current_user_can( 'manage_options' ) || ! check_ajax_referer( 'zest_preview', '_wpnonce', false ) ) {
			wp_die();
		}

		$input = [];
		foreach ( array_keys( $_GET ) as $key ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- checked above
			if ( is_string( $key ) && 0 === strpos( $key, 'zest_' ) ) {
				$param = sanitize_text_field( wp_unslash( $_GET[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput
				if ( '' === $param ) {
					continue;
				}
				$name = substr( $key, 5 );
				// Multi-value groups arrive comma-joined from the preview JS.
				$input[ $name ] = 'hide_categories' === $name
					? array_filter( array_map( 'trim', explode( ',', $param ) ) )
					: $param;
			}
		}

		$options = Zest_CMP_Enqueue::get_instance()->build_config( $this->sanitize( array_merge( $this->get_settings(), $input ) ) );
		$src     = file_exists( ZEST_CMP_DIR . 'dist/zest.min.js' )
			? ZEST_CMP_URL . 'dist/zest.min.js'
			: 'https://cdn.jsdelivr.net/npm/@freshjuice/zest@' . ZEST_CMP_ZEST_VERSION . '/dist/zest.min.js';

		header( 'Content-Type: text/html; charset=utf-8' );
		?>
<!DOCTYPE html>
<html>
<head><meta name="viewport" content="width=device-width,initial-scale=1"><style>body{margin:0;font-family:sans-serif;background:#f6f7f7;min-height:260px}</style></head>
<body>
<p style="text-align:center;color:#8c8f94;padding-top:110px">Simulated page — banner appears here</p>
<script>
window.ZestConfig = <?php echo wp_json_encode( $options ); ?>;
window.addEventListener( 'message', function( e ) {
	if ( e.origin === '<?php echo esc_js( home_url() ); ?>' && e.data && 'zest-reset' === e.data.type && window.Zest ) {
		try { window.Zest.reset(); window.Zest.init( window.ZestConfig ); } catch ( err ) {}
	}
} );
</script>
<script src="<?php echo esc_url( $src ); ?>" onload="try{Zest.reset();Zest.init(window.ZestConfig);}catch(e){}"></script>
</body>
</html>
		<?php
		wp_die();
	}

	/**
	 * Fetch blog + changelog JSON feeds from cookiezest.com, cached 12h.
	 *
	 * @return array{blog?: array<int, array<string, mixed>>, changelog?: array<int, array<string, mixed>>}
	 */
	private function get_feeds(): array {
		$cached = get_transient( 'zest_cmp_feeds' );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$feeds = [];
		foreach ( [ 'blog', 'changelog' ] as $slug ) {
			$response = wp_remote_get( 'https://cookiezest.com/' . $slug . '/feed.json', [ 'timeout' => 10 ] );
			if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
				continue;
			}
			$data = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( ! is_array( $data['items'] ?? null ) ) {
				continue;
			}
			$feeds[ $slug ] = array_slice( $data['items'], 0, 3 );
		}

		if ( ! empty( $feeds ) ) {
			set_transient( 'zest_cmp_feeds', $feeds, 12 * HOUR_IN_SECONDS );
		}

		return $feeds;
	}

	private function render_settings_tab(): void {
		$settings = $this->get_settings();
		wp_enqueue_style( 'wp-components' );
		if ( isset( $_GET['zest-reset'] ) && '1' === $_GET['zest-reset'] ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Zest settings have been reset to defaults.', 'zest-cmp' ) . '</p></div>';
		}
		?>
			<style>
				.zest-builder { display: flex; gap: 16px; align-items: flex-start; flex-wrap: wrap; margin-top: 12px; }
				.zest-builder > form { flex: 1 1 540px; min-width: 0; margin: 0; display: grid; grid-template-columns: 1fr; }
				.zest-card { background: #fff; border: 1px solid #c3c4c7; min-width: 0; }
				.postbox.zest-card.closed > .zest-form-grid { display: none; }
				.zest-preview-pane { flex: 1 1 520px; min-width: 0; position: sticky; top: 46px; }
				@media (max-width: 1100px) { .zest-preview-pane { position: static; } }
				.zest-form-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 0 20px; }
				.zest-form-grid .form-table, .zest-form-grid .form-table tbody { display: contents; }
				.zest-form-grid .form-table tr { display: block; margin: 0; }
				.zest-form-grid .form-table tr.zest-row-wide { grid-column: 1 / -1; }
				.zest-form-grid .form-table th { display: block; width: auto; padding: 10px 0 4px; font-weight: 600; }
				.zest-form-grid .form-table td { display: block; padding: 0 0 10px; }
				.zest-form-grid .form-table td input[type="text"], .zest-form-grid .form-table td input[type="url"], .zest-form-grid .form-table td input[type="number"], .zest-form-grid .form-table td select, .zest-form-grid .form-table td textarea { width: 100%; max-width: 100%; }
				.zest-form-grid .form-table td input[type="color"] { width: 34px; height: 34px; padding: 2px; border-radius: 4px; cursor: pointer; }
				.zest-color-row { display: inline-flex; gap: 6px; align-items: center; width: 100%; max-width: 100%; }
				.zest-color-row .zest-color-hex { flex: 1 1 0; min-width: 0; width: 100%; max-width: 100%; font-family: monospace; color: #646970; background: #f6f7f7; }
				@media (max-width: 560px) { .zest-form-grid { grid-template-columns: 1fr; } }
				/* WP components-form-toggle, CSS-driven (no React): :checked drives the state */
				.zest-form-grid .components-form-toggle { display: inline-flex; }
				.zest-form-grid .components-form-toggle input:checked ~ .components-form-toggle__track { background-color: var(--wp-components-color-accent, var(--wp-admin-theme-color, #3858e9)); border-color: var(--wp-components-color-accent, var(--wp-admin-theme-color, #3858e9)); }
				.zest-form-grid .components-form-toggle input:checked ~ .components-form-toggle__thumb { background-color: #fff; border-width: 0; transform: translateX(16px); }
				.zest-toggle-row { display: flex; align-items: center; gap: 10px; padding: 6px 0; }
				.zest-form-grid .form-table td select.zest-multi-select { width: 100%; max-width: 100%; }
				.zest-form-grid .form-table tr.zest-row-wide > td select { width: calc(50% - 10px); min-width: 160px; }
				.zest-form-grid .form-table tr.zest-row-toggle td { padding: 0 0 10px; }
				.zest-toggle-line { display: flex; align-items: center; gap: 10px; cursor: pointer; }
				.zest-toggle-line .zest-toggle-label { font-weight: 400; }
			</style>
			<div class="zest-builder metabox-holder">
			<form method="post" action="options.php" id="zest-settings-form">
				<?php
				settings_fields( 'zest_cmp_group' );
				?>
				<div class="postbox zest-card">
					<div class="postbox-header"><h2 class="hndle"><?php esc_html_e( 'General', 'zest-cmp' ); ?></h2><div class="handle-actions hide-if-no-js"><button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text"><?php printf( esc_html__( /* translators: %s: card title */ 'Toggle panel: %s', 'zest-cmp' ), esc_html( 'General' ) ); ?></span><span class="toggle-indicator" aria-hidden="true"></span></button></div></div>
					<div class="zest-form-grid" style="padding:0 16px 8px">
				<?php
				$this->render_section_fields( [
					[ 'checkbox', 'enabled', __( 'Enable Zest consent banner', 'zest-cmp' ), null, 'zest-row-wide' ],
					[ 'select', 'mode', __( 'Script blocking mode', 'zest-cmp' ), $this->get_modes() ],
					[ 'select', 'dnt_behavior', __( 'Do Not Track / GPC signal', 'zest-cmp' ), [
						'reject'     => __( 'Respect — auto-reject non-essential', 'zest-cmp' ),
						'preselect'  => __( 'Respect — show banner, non-essential unchecked', 'zest-cmp' ),
						'ignore'     => __( 'Ignore the browser privacy signal', 'zest-cmp' ),
					], null, __( 'What to do when the visitor\'s browser sends a Do Not Track or Global Privacy Control signal.', 'zest-cmp' ) ],
					[ 'number', 'expiration', __( 'Consent expiration (days)', 'zest-cmp' ), 1, 365 ],
					[ 'checkbox', 'show_widget', __( 'Show floating widget after consent', 'zest-cmp' ) ],
					[ 'url', 'policy_url', __( 'Privacy policy URL', 'zest-cmp' ) ],
					[ 'url', 'imprint_url', __( 'Imprint / legal URL', 'zest-cmp' ) ],
				] );
				?>
					</div>
				</div>

				<div class="postbox zest-card">
					<div class="postbox-header"><h2 class="hndle"><?php esc_html_e( 'Appearance', 'zest-cmp' ); ?></h2><div class="handle-actions hide-if-no-js"><button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text"><?php printf( esc_html__( 'Toggle panel: %s', 'zest-cmp' ), esc_html( 'Appearance' ) ); ?></span><span class="toggle-indicator" aria-hidden="true"></span></button></div></div>
					<div class="zest-form-grid" style="padding:0 16px 8px">
				<?php
				$this->render_section_fields( [
					[ 'select', 'position', __( 'Banner position', 'zest-cmp' ), $this->get_positions() ],
					[ 'select', 'theme', __( 'Theme', 'zest-cmp' ), $this->get_themes() ],
					[ 'select', 'lang', __( 'Language', 'zest-cmp' ), $this->get_langs() ],
					[ 'color', 'accent_color', __( 'Accent color', 'zest-cmp' ) ],
					[ 'select', 'button_style', __( 'Button style', 'zest-cmp' ), [
						'fill'    => __( 'Fill (solid)', 'zest-cmp' ),
						'outline' => __( 'Outline (bordered)', 'zest-cmp' ),
					] ],
					[ 'select', 'button_layout', __( 'Button layout', 'zest-cmp' ), [
						'row'          => __( 'Row (default)', 'zest-cmp' ),
						'split'        => __( 'Split', 'zest-cmp' ),
						'split-modern' => __( 'Split modern', 'zest-cmp' ),
					] ],
					[ 'number', 'backdrop_blur', __( 'Backdrop blur (px)', 'zest-cmp' ), 0, 16 ],
					[ 'number', 'radius', __( 'Corner radius (px)', 'zest-cmp' ), 0, 24 ],
					[ 'color', 'color_bg', __( 'Background', 'zest-cmp' ) ],
					[ 'color', 'color_bg_secondary', __( 'Secondary background', 'zest-cmp' ) ],
					[ 'color', 'color_text', __( 'Text', 'zest-cmp' ) ],
					[ 'color', 'color_text_secondary', __( 'Secondary text', 'zest-cmp' ) ],
					[ 'color', 'color_border', __( 'Border', 'zest-cmp' ) ],
					[ 'checkbox', 'hard_wall', __( 'Hard wall — block page interaction until visitor decides', 'zest-cmp' ) ],
				] );
				?>
					</div>
				</div>

				<div class="postbox zest-card">
					<div class="postbox-header"><h2 class="hndle"><?php esc_html_e( 'Advanced', 'zest-cmp' ); ?></h2><div class="handle-actions hide-if-no-js"><button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text"><?php printf( esc_html__( 'Toggle panel: %s', 'zest-cmp' ), esc_html( 'Advanced' ) ); ?></span><span class="toggle-indicator" aria-hidden="true"></span></button></div></div>
					<div class="zest-form-grid" style="padding:0 16px 8px">
				<?php
				$this->render_section_fields( [
					[ 'checkboxes', 'hide_categories', __( 'Remove categories from settings modal', 'zest-cmp' ), [
						'functional' => __( 'Functional', 'zest-cmp' ),
						'analytics'  => __( 'Analytics', 'zest-cmp' ),
						'marketing'  => __( 'Marketing', 'zest-cmp' ),
					], __( 'Hidden categories are forced to rejected. Essential is always shown.', 'zest-cmp' ) ],
					[ 'textarea', 'blocked_domains', __( 'Always-blocked domains (one per line, *.wild ok)', 'zest-cmp' ), null, 'zest-row-wide' ],
					[ 'textarea', 'allowed_domains', __( 'Always-allowed domains (one per line, overrides blocking)', 'zest-cmp' ), null, 'zest-row-wide' ],
					[ 'textarea', 'custom_styles', __( 'Custom CSS (injected into Shadow DOM)', 'zest-cmp' ), null, 'zest-row-wide' ],
					[ 'select', 'geo', __( 'Geo / jurisdiction gating', 'zest-cmp' ), [
						'0'       => __( 'Off — show the banner to everyone', 'zest-cmp' ),
						'gateway' => __( 'Gateway — GDPR banner in the EU/EEA/UK, notice in US states', 'zest-cmp' ),
					], 'zest-row-wide', __( 'Visitors outside regulated regions never see the banner and no consent cookie is set. Uses the hosted zest-geo gateway (geo.cookiezest.com) — no tracking, only country-level lookup.', 'zest-cmp' ) ],
					[ 'select', 'branding', __( 'Show some love for open source', 'zest-cmp' ), [
						'1'      => __( 'Keep the credit — thank you! (default)', 'zest-cmp' ),
						'0'      => __( 'Hide it (we will cry quietly)', 'zest-cmp' ),
						'modal'  => __( 'Credit in settings modal only', 'zest-cmp' ),
						'banner' => __( 'Credit on banner only', 'zest-cmp' ),
					], 'zest-row-wide' ],
				] );
				?>
					<p class="description" style="margin:0 0 4px"><?php esc_html_e( 'Zest is free, open source, and built in the open. The tiny credit helps other people find it — and keeps the project alive. Keep it on if you can.', 'zest-cmp' ); ?></p>
				<?php
				?>
					</div>
				</div>
			</form>

			<div class="zest-preview-pane">
				<div class="postbox">
					<div class="postbox-header"><h2 class="hndle"><?php esc_html_e( 'Live preview', 'zest-cmp' ); ?></h2>
					<div class="handle-actions hide-if-no-js"><button type="button" class="button-link" id="zest-replay" style="text-decoration:none; padding: 0 16px;"><?php esc_html_e( '↻ Replay banner', 'zest-cmp' ); ?></button></div></div>
					<div class="inside" style="padding:0 16px">
					<iframe id="zest-preview" title="<?php esc_attr_e( 'Live banner preview', 'zest-cmp' ); ?>" style="display:block;width:100%;height:560px;border:0;background:#f6f7f7" loading="lazy"></iframe>
					<p class="description" style="padding:0 12px"><?php esc_html_e( 'Updates ~600ms after you change any setting. Blocked scripts and consent state are not simulated here.', 'zest-cmp' ); ?></p>
					</div>
				</div>
			</div>
			</div>
			<script>
			// jQuery ready — form exists by then; keeps init off the mid-body inline run.
			jQuery( function() {
				// Native postbox collapse for settings cards.
				jQuery( '.zest-card' ).each( function() {
					var card = jQuery( this );
					card.find( '.handlediv' ).on( 'click', function() {
						var btn = jQuery( this );
						var open = 'true' === btn.attr( 'aria-expanded' );
						btn.attr( 'aria-expanded', open ? 'false' : 'true' );
						card.toggleClass( 'closed', open );
					} );
				} );
				var iframe = document.getElementById( 'zest-preview' );
				var form   = iframe ? iframe.closest( '.wrap' ).querySelector( 'form' ) : null;
				if ( ! iframe || ! form ) { return; }
				var timer  = null;
				var fields = form.querySelectorAll( 'input, select, textarea' );

				// Mirror the live form values onto the preview config.
				function collect() {
					var cfg = {};
					fields.forEach( function( el ) {
						// Multi-checkbox groups (e.g. hide_categories[]) accumulate.
						var g = el.name.match( /^zest_cmp_settings\[([a-z_]+)\]\[\]$/ );
						if ( g ) {
							if ( el.checked ) {
								cfg[ g[1] ] = cfg[ g[1] ] ? cfg[ g[1] ] + ',' + el.value : el.value;
							}
							return;
						}
						var m = el.name.match( /^zest_cmp_settings\[([a-z_]+)\]$/ );
						if ( ! m ) { return; }
						var key = m[1];
						if ( 'checkbox' === el.type ) {
							cfg[key] = el.checked ? '1' : '';
						} else {
							cfg[key] = el.value;
						}
					} );
					return cfg;
				}

				function reload() {
					var q = new URLSearchParams();
					var cfg = collect();
					Object.keys( cfg ).forEach( function( k ) {
						if ( '' !== cfg[k] ) {
							q.set( 'zest_' + k, cfg[k] );
						}
					} );
					q.set( '_wpnonce', '<?php echo esc_js( wp_create_nonce( 'zest_preview' ) ); ?>' );
					iframe.src = '<?php echo esc_url( admin_url( 'admin-ajax.php?action=zest_preview' ) ); ?>' + '&' + q.toString();
				}

				function debounce() {
					clearTimeout( timer );
					timer = setTimeout( reload, 600 );
				}

				fields.forEach( function( el ) {
					el.addEventListener( 'change', debounce );
					el.addEventListener( 'input', debounce );
				} );

				document.getElementById( 'zest-replay' ).addEventListener( 'click', function() {
					iframe.contentWindow.postMessage( { type: 'zest-reset' }, window.location.origin );
				} );

				// Custom-theme color cells only apply when theme = custom.
				var themeSel = document.getElementById( 'zest_theme' );
				function syncColorRows() {
					var on = 'custom' === themeSel.value;
					form.querySelectorAll( '.zest-color-cell' ).forEach( function( tr ) {
						tr.style.display = on ? '' : 'none';
					} );
				}
				themeSel.addEventListener( 'change', function() { syncColorRows(); debounce(); } );
				syncColorRows();

				// Mirror native color input value into the readonly hex field.
				form.addEventListener( 'input', function( e ) {
					if ( 'color' === e.target.type && e.target.name ) {
						var hex = form.querySelector( '.zest-color-hex[data-for="' + e.target.id + '"]' );
						if ( hex ) { hex.value = e.target.value; }
					}
				} );

				reload();
			} );
			</script>
			<?php
			}

	/**
	 * Parse a newline/comma-separated domain list into a clean array.
	 *
	 * @param string $text Raw textarea value.
	 * @return string Normalized newline-separated domain list.
	 */
	private function domains_from_text( string $text ): string {
		$parts  = preg_split( '/[\n,]+/', $text ) ?: [];
		$domains = [];
		foreach ( $parts as $part ) {
			$domain = strtolower( trim( $part ) );
			if ( '' !== $domain && preg_match( '/^(\*\.)?[a-z0-9\-]+(\.[a-z0-9\-]+)+$/', $domain ) ) {
				$domains[] = $domain;
			}
		}
		return implode( "\n", array_unique( $domains ) );
	}

	/**
	 * Render field rows without a section wrapper (cards are rendered by the caller).
	 *
	 * @param array[] $fields Field definitions.
	 */
	private function render_section_fields( array $fields ): void {
		$settings = $this->get_settings();
		echo '<table class="form-table" role="presentation">';

		foreach ( $fields as $field ) {
			$type   = $field[0];
			$key    = $field[1];
			$label  = $field[2];
			$value  = $settings[ $key ] ?? '';
			$option = $this->option_name . '[' . $key . ']';
			$extra  = ( 'number' === $type ) ? '' : ( $field[4] ?? '' );
			if ( 'color' === $type && 'accent_color' !== $key ) {
				$extra = trim( $extra . ' zest-color-cell' );
			}

			echo '<tr' . ( $extra ? ' class="' . esc_attr( $extra ) . '"' : '' ) . '><th scope="row"><label for="zest_' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td>';

			switch ( $type ) {
				case 'checkbox':
					echo '<select id="zest_' . esc_attr( $key ) . '" name="' . esc_attr( $option ) . '">';
					echo '<option value="1"' . selected( $value, '1', false ) . '>' . esc_html__( 'Enabled', 'zest-cmp' ) . '</option>';
					echo '<option value="0"' . selected( $value, '0', false ) . '>' . esc_html__( 'Disabled', 'zest-cmp' ) . '</option>';
					echo '</select>';
					break;

				case 'checkboxes':
					$options  = $field[3] ?? [];
					$hint     = $field[4] ?? '';
					$selected = is_array( $value ) ? $value : [];
					foreach ( $options as $val => $lbl ) {
						echo '<label class="zest-toggle-row"><span class="components-form-toggle' . ( in_array( $val, $selected, true ) ? ' is-checked' : '' ) . '"><input type="checkbox" class="components-form-toggle__input" name="' . esc_attr( $this->option_name . '[' . $key . '][]' ) . '" value="' . esc_attr( $val ) . '"' . checked( in_array( $val, $selected, true ), true, false ) . ' /><span class="components-form-toggle__track"></span><span class="components-form-toggle__thumb"></span></span> ' . esc_html( $lbl ) . '</label>';
					}
					if ( $hint ) {
						echo '<p class="description">' . esc_html( $hint ) . '</p>';
					}
					break;

				case 'select':
					$options = $field[3] ?? [];
					echo '<select id="zest_' . esc_attr( $key ) . '" name="' . esc_attr( $option ) . '">';
					foreach ( $options as $val => $lbl ) {
						echo '<option value="' . esc_attr( $val ) . '"' . selected( $value, $val, false ) . '>' . esc_html( $lbl ) . '</option>';
					}
					echo '</select>';
					break;

				case 'color':
					echo '<span class="zest-color-row" id="zest_color_row_' . esc_attr( $key ) . '">';
					echo '<input type="color" id="zest_' . esc_attr( $key ) . '" name="' . esc_attr( $option ) . '" value="' . esc_attr( $value ) . '" />';
					echo '<input type="text" class="zest-color-hex" value="' . esc_attr( $value ) . '" data-for="zest_' . esc_attr( $key ) . '" readonly aria-label="' . esc_attr__( 'Selected color value', 'zest-cmp' ) . '" />';
					echo '</span>';
					break;

				case 'number':
					$min = $field[3] ?? 0;
					$max = $field[4] ?? 999;
					echo '<input type="number" id="zest_' . esc_attr( $key ) . '" name="' . esc_attr( $option ) . '" value="' . esc_attr( $value ) . '" min="' . esc_attr( (string) $min ) . '" max="' . esc_attr( (string) $max ) . '" class="small-text" />';
					break;

				case 'url':
					echo '<input type="url" id="zest_' . esc_attr( $key ) . '" name="' . esc_attr( $option ) . '" value="' . esc_attr( $value ) . '" class="regular-text" />';
					break;

				case 'textarea':
					echo '<textarea id="zest_' . esc_attr( $key ) . '" name="' . esc_attr( $option ) . '" rows="6" class="large-text code">' . esc_textarea( $value ) . '</textarea>';
					break;
			}

			if ( ! empty( $field[5] ) ) {
				echo '<p class="description">' . esc_html( $field[5] ) . '</p>';
			}
			echo '</td></tr>';
		}

		echo '</table>';
	}

	/** @return array<string, string> */
	private function get_positions(): array {
		return [
			'bottom'       => __( 'Bottom (centered)', 'zest-cmp' ),
			'bottom-left'  => __( 'Bottom left', 'zest-cmp' ),
			'bottom-right' => __( 'Bottom right', 'zest-cmp' ),
			'top'          => __( 'Top (centered)', 'zest-cmp' ),
			'top-left'     => __( 'Top left', 'zest-cmp' ),
			'top-right'    => __( 'Top right', 'zest-cmp' ),
			'center'       => __( 'Center (overlay)', 'zest-cmp' ),
		];
	}

	/** @return array<string, string> */
	private function get_themes(): array {
		return [
			'auto'   => __( 'Auto (system)', 'zest-cmp' ),
			'light'  => __( 'Light', 'zest-cmp' ),
			'dark'   => __( 'Dark', 'zest-cmp' ),
			'custom' => __( 'Custom (override colors)', 'zest-cmp' ),
		];
	}

	/** @return array<string, string> */
	private function get_modes(): array {
		return [
			'safe'     => __( 'Safe — blocks known trackers, allows the rest (recommended)', 'zest-cmp' ),
			'manual'   => __( 'Manual — only blocks domains you list', 'zest-cmp' ),
			'strict'   => __( 'Strict — blocks everything non-essential', 'zest-cmp' ),
			'doomsday' => __( 'Doomsday — blocks all third-party scripts', 'zest-cmp' ),
		];
	}

	/** @return array<string, string> */
	private function get_langs(): array {
		return [
			'auto' => __( 'Auto-detect', 'zest-cmp' ),
			'en'   => 'English',
			'de'   => 'Deutsch',
			'es'   => 'Español',
			'fr'   => 'Français',
			'it'   => 'Italiano',
			'pt'   => 'Português',
			'nl'   => 'Nederlands',
			'pl'   => 'Polski',
			'uk'   => 'Українська',
			'ru'   => 'Русский',
			'ja'   => '日本語',
			'zh'   => '中文',
		];
	}
}
