<?php //phpcs:ignore Generic.Files.LineEndings.InvalidEOLChar
/**
 * Handle frontend scripts
 *
 * @package Revenue
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend scripts class.
 */
class Revenue_Frontend_Scripts {

	/**
	 * Contains an array of script handles registered by Revenue
	 *
	 * @var array
	 */
	private static $scripts = array();

	/**
	 * Contains an array of script handles registered by Revenue
	 *
	 * @var array
	 */
	private static $styles = array();

	/**
	 * Return asset URL.
	 *
	 * @param string $path Assets path.
	 * @return string
	 */
	private static function get_asset_url( $path ) {
		return apply_filters( 'revenue_get_asset_url', plugins_url( $path, REVENUE_FILE ), $path );
	}

	/**
	 * Register a script for use.
	 *
	 * @uses   wp_register_script()
	 * @param  string   $handle    Name of the script. Should be unique.
	 * @param  string   $path      Full URL of the script, or path of the script relative to the WordPress root directory.
	 * @param  string[] $deps      An array of registered script handles this script depends on.
	 * @param  string   $version   String specifying script version number, if it has one, which is added to the URL as a query string for cache busting purposes. If version is set to false, a version number is automatically added equal to current installed WordPress version. If set to null, no version is added.
	 * @param  boolean  $in_footer Whether to enqueue the script before </body> instead of in the <head>. Default 'false'.
	 */
	private static function register_script( $handle, $path, $deps = array( 'jquery' ), $version = REVENUE_VER, $in_footer = array( 'strategy' => 'defer' ) ) {
		self::$scripts[] = $handle;
		wp_register_script( $handle, $path, $deps, $version, $in_footer );
	}

	/**
	 * Register and enqueue a script for use.
	 *
	 * @uses   wp_enqueue_script()
	 * @param  string   $handle    Name of the script. Should be unique.
	 * @param  string   $path      Full URL of the script, or path of the script relative to the WordPress root directory.
	 * @param  string[] $deps      An array of registered script handles this script depends on.
	 * @param  string   $version   String specifying script version number, if it has one, which is added to the URL as a query string for cache busting purposes. If version is set to false, a version number is automatically added equal to current installed WordPress version. If set to null, no version is added.
	 * @param  boolean  $in_footer Whether to enqueue the script before </body> instead of in the <head>. Default 'false'.
	 */
	private static function enqueue_script( $handle, $path = '', $deps = array( 'jquery' ), $version = REVENUE_VER, $in_footer = array( 'strategy' => 'defer' ) ) {
		if ( ! in_array( $handle, self::$scripts, true ) && $path ) {
			self::register_script( $handle, $path, $deps, $version, $in_footer );
		}
		wp_enqueue_script( $handle );
	}

	/**
	 * Register a style for use.
	 *
	 * @uses   wp_register_style()
	 * @param  string   $handle  Name of the stylesheet. Should be unique.
	 * @param  string   $path    Full URL of the stylesheet, or path of the stylesheet relative to the WordPress root directory.
	 * @param  string[] $deps    An array of registered stylesheet handles this stylesheet depends on.
	 * @param  string   $version String specifying stylesheet version number, if it has one, which is added to the URL as a query string for cache busting purposes. If version is set to false, a version number is automatically added equal to current installed WordPress version. If set to null, no version is added.
	 * @param  string   $media   The media for which this stylesheet has been defined. Accepts media types like 'all', 'print' and 'screen', or media queries like '(orientation: portrait)' and '(max-width: 640px)'.
	 * @param  boolean  $has_rtl If has RTL version to load too.
	 */
	private static function register_style( $handle, $path, $deps = array(), $version = REVENUE_VER, $media = 'all', $has_rtl = false ) {
		self::$styles[] = $handle;
		wp_register_style( $handle, $path, $deps, $version, $media );

		if ( $has_rtl ) {
			wp_style_add_data( $handle, 'rtl', 'replace' );
		}
	}

	/**
	 * Register and enqueue a styles for use.
	 *
	 * @uses   wp_enqueue_style()
	 * @param  string   $handle  Name of the stylesheet. Should be unique.
	 * @param  string   $path    Full URL of the stylesheet, or path of the stylesheet relative to the WordPress root directory.
	 * @param  string[] $deps    An array of registered stylesheet handles this stylesheet depends on.
	 * @param  string   $version String specifying stylesheet version number, if it has one, which is added to the URL as a query string for cache busting purposes. If version is set to false, a version number is automatically added equal to current installed WordPress version. If set to null, no version is added.
	 * @param  string   $media   The media for which this stylesheet has been defined. Accepts media types like 'all', 'print' and 'screen', or media queries like '(orientation: portrait)' and '(max-width: 640px)'.
	 * @param  boolean  $has_rtl If has RTL version to load too.
	 */
	private static function enqueue_style( $handle, $path = '', $deps = array(), $version = REVENUE_VER, $media = 'all', $has_rtl = false ) {
		if ( ! in_array( $handle, self::$styles, true ) && $path ) {
			self::register_style( $handle, $path, $deps, $version, $media, $has_rtl );
		}
		wp_enqueue_style( $handle );
	}

	/**
	 * Register all Revenue scripts.
	 */
	private static function register_scripts() {
		$version = REVENUE_VER;

		$register_scripts = array(
			'revenue-campaign'             => array(
				'src'     => self::get_asset_url( 'assets/js/frontend/campaign.js' ),
				'deps'    => array( 'jquery' ),
				'version' => $version,
            ),
			'revenue-add-to-cart'                 => array(
				'src'     => self::get_asset_url( 'assets/js/frontend/add-to-cart.js' ),
				'deps'    => array( 'jquery' ),
				'version' => $version,
            ),
			'revenue-popup'                 => array(
				'src'     => self::get_asset_url( 'assets/js/frontend/popup.js' ),
				'deps'    => array( 'jquery' ),
				'version' => $version,
			),
			'revenue-floating'             => array(
				'src'     => self::get_asset_url( 'assets/js/frontend/floating.js' ),
				'deps'    => array( 'jquery' ),
				'version' => $version,
			),
			'revenue-drawer'             => array(
				'src'     => self::get_asset_url( 'assets/js/frontend/drawer.js' ),
				'deps'    => array( 'jquery' ),
				'version' => $version,
			),
			'revenue-spending-goal'             => array(
				'src'     => self::get_asset_url( 'assets/js/frontend/spending-goal.js' ),
				'deps'    => array( 'jquery' ),
				'version' => $version,
			),
			'revenue-free-shipping-bar'             => array(
				'src'     => self::get_asset_url( 'assets/js/frontend/free-shipping-bar.js' ),
				'deps'    => array( 'jquery' ),
				'version' => $version,
			),
			'revenue-upsell-slider'             => array(
				'src'     => self::get_asset_url( 'assets/js/frontend/upsell-slider.js' ),
				'deps'    => array( 'jquery' ),
				'version' => $version,
			),
			'revenue-countdown'            => array(
				'src'     => self::get_asset_url( 'assets/js/frontend/countdown.js' ),
				'deps'    => array( 'jquery' ),
				'version' => $version,
			),
			'revenue-animated-add-to-cart' => array(
				'src'     => self::get_asset_url( 'assets/js/frontend/animated-atc.js' ),
				'deps'    => array( 'jquery' ),
				'version' => $version,
			),
			'revenue-campaign-countdown' => array(
				'src'     => self::get_asset_url( 'assets/js/frontend/countdown-timer.js' ),
				'deps'    => array( 'jquery' ),
				'version' => $version,
			),
			'revenue-campaign-stock-scarcity' => array(
				'src'     => self::get_asset_url( 'assets/js/frontend/stock-scarcity.js' ),
				'deps'    => array( 'jquery' ),
				'version' => $version,
			),
		);
		foreach ( $register_scripts as $name => $props ) {
			self::register_script( $name, $props['src'], $props['deps'], $props['version'] );
		}
	}



	/**
	 * Register all Revenue styles.
	 */
	private static function register_styles() {
		$version = REVENUE_VER;

		$register_styles = array(
			'revenue-campaign-buyx_gety'             => array(
				'src'     => self::get_asset_url( 'assets/css/frontend/campaign/buyx_gety.css' ),
				'deps'    => array(),
				'version' => $version,
				'has_rtl' => false,
			),
			'revenue-campaign-double_order'             => array(
				'src'     => self::get_asset_url( 'assets/css/frontend/campaign/double_order.css' ),
				'deps'    => array(),
				'version' => $version,
				'has_rtl' => false,
			),
			'revenue-campaign-spending_goal'             => array(
				'src'     => self::get_asset_url( 'assets/css/frontend/campaign/spending_goal.css' ),
				'deps'    => array(),
				'version' => $version,
				'has_rtl' => false,
			),
			'revenue-campaign-fsb'             => array(
				'src'     => self::get_asset_url( 'assets/css/frontend/campaign/free_shipping_bar.css' ),
				'deps'    => array(),
				'version' => $version,
				'has_rtl' => false,
			),
			'revenue-campaign-fbt'             => array(
				'src'     => self::get_asset_url( 'assets/css/frontend/campaign/frequently_bought_together.css' ),
				'deps'    => array(),
				'version' => $version,
				'has_rtl' => false,
			),
			'revenue-campaign-mix_match'             => array(
				'src'     => self::get_asset_url( 'assets/css/frontend/campaign/mix_match.css' ),
				'deps'    => array(),
				'version' => $version,
				'has_rtl' => false,
			),
			'revenue-campaign'             => array(
				'src'     => self::get_asset_url( 'assets/css/frontend/campaign.css' ),
				'deps'    => array(),
				'version' => $version,
				'has_rtl' => false,
			),
			'revenue-responsive'           => array(
				'src'     => self::get_asset_url( 'assets/css/frontend/responsive.css' ),
				'deps'    => array(),
				'version' => $version,
				'has_rtl' => false,
			),
			'revenue-utility'              => array(
				'src'     => self::get_asset_url( 'assets/css/frontend/utility.css' ),
				'deps'    => array(),
				'version' => $version,
				'has_rtl' => false,
			),
			'revenue-popup'                => array(
				'src'     => self::get_asset_url( 'assets/css/frontend/popup.css' ),
				'deps'    => array(),
				'version' => $version,
				'has_rtl' => false,
			),
			'revenue-floating'             => array(
				'src'     => self::get_asset_url( 'assets/css/frontend/floating.css' ),
				'deps'    => array(),
				'version' => $version,
				'has_rtl' => false,
			),
			'revenue-countdown'            => array(
				'src'     => self::get_asset_url( 'assets/css/frontend/countdown.css' ),
				'deps'    => array(),
				'version' => $version,
				'has_rtl' => false,
			),
			'revenue-animated-add-to-cart' => array(
				'src'     => self::get_asset_url( 'assets/css/frontend/animated-atc.css' ),
				'deps'    => array(),
				'version' => $version,
				'has_rtl' => false,
			),
			'revenue-campaign-countdown' => array(
				'src'     => self::get_asset_url( 'assets/css/frontend/countdown-timer.css' ),
				'deps'    => array(),
				'version' => $version,
				'has_rtl' => false,
			),
			'revenue-campaign-stock-scarcity' => array(
				'src'     => self::get_asset_url( 'assets/css/frontend/stock-scarcity.css' ),
				'deps'    => array(),
				'version' => $version,
				'has_rtl' => false,
			),
		);
		foreach ( $register_styles as $name => $props ) {
			self::register_style( $name, $props['src'], $props['deps'], $props['version'], 'all', $props['has_rtl'] );
		}
	}

	/**
	 * Register/queue frontend scripts.
	 */
	public static function load_scripts() {
		global $post;

		if ( ! did_action( 'before_revenue_init' ) ) {
			return;
		}

		self::register_scripts();
		self::register_styles();

	}

}
