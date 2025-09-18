<?php
// Exit if accessed directly
if(!defined('ABSPATH')){
	exit;
}

/**
 * Default functions
 */
class Fiber_Admin_Default{
	public function __construct(){
		//white label
		if(fiad_get_general_option('hide_wordpress_branding')){
			// Replace "WordPress" in the page titles.
			add_filter('admin_title', [$this, 'fiad_title'], 10, 2);
			
			// Remove "WordPress" in login title
			add_filter('login_title', [$this, 'fiad_admin_title']);
			
			// Admin footer modification
			add_filter('admin_footer_text', [$this, 'fiad_update_admin_footer']);
			
			// Update dashboard title
			add_action('admin_head', [$this, 'fiad_update_dashboard_name']);
			
			// Remove unused dashboard widgets
			add_action('admin_init', [$this, 'fiad_remove_dashboard_widgets']);
			
			// Update logo link and title
			add_filter('login_headerurl', [$this, 'fiad_login_logo_url']);
			add_filter('login_headertext', [$this, 'fiad_login_logo_title']);
			
			// Remove Lost your password link
			add_filter('gettext', [$this, 'fiad_remove_lostpassword']);
			
			// Remove Back to blog
			add_action('login_enqueue_scripts', [$this, 'fiad_remove_backtoblog']);
			
			// Hide / Show Admin Bar Frontend for all users
			if(!fiad_get_general_option('enable_admin_toolbar')){
				add_filter('show_admin_bar', '__return_false');
				add_action('wp_print_styles', [$this, 'fiad_deregister_styles'], 100);
			}elseif(!fiad_is_admin_user_role()){
				add_action('wp_print_styles', [$this, 'fiad_deregister_styles'], 100);
			}
			
			// Remove WordPress admin bar logo
			add_action('wp_before_admin_bar_render', [$this, 'fiad_remove_admin_bar_logo'], 0);
			
			// Remove Welcome Dashboard Widget
			remove_action('welcome_panel', 'wp_welcome_panel');
			
			// Remove generators from feed
			remove_action('rss2_head', 'the_generator');
			remove_action('rss_head', 'the_generator');
			remove_action('rdf_header', 'the_generator');
			remove_action('atom_head', 'the_generator');
			remove_action('commentsrss2_head', 'the_generator');
			remove_action('opml_head', 'the_generator');
			remove_action('app_head', 'the_generator');
			remove_action('comments_atom_head', 'the_generator');
			
			// Disable plugin generator tags
			remove_action('wp_head', 'wp_generator'); // default WordPress
			add_filter('the_generator', '__return_null'); // default WordPress
			remove_filter('get_the_generator_html', 'wc_generator_tag'); // Woocommerce
			remove_filter('get_the_generator_xhtml', 'wc_generator_tag'); // Woocommerce
			add_filter('revslider_meta_generator', '__return_empty_string'); // Revolution Slider
			remove_action('wp_head', 'xforwc__add_meta_information_action', 99); // Product Filter for WooCommerce
			remove_action('wp_head', ['Redux_Functions_Ex', 'meta_tag']); // WP Mail Logging
			add_action('wp_head', [$this, 'fiad_remove_meta_generators'], 1); // other plugins
			
			// Add favicon to admin bar logo
			add_action('admin_head', [$this, 'fiad_favicon_admin_logo']);
		}
		
		// disable auto update
		if(!fiad_get_miscellaneous_option('enable_auto_update')){
			// WordPress automatic update
			add_filter('auto_update_core', '__return_false');
			add_filter('automatic_updater_disabled', '__return_false');
			add_filter('auto_update_theme', '__return_false');
			add_filter('auto_update_plugin', '__return_false');
			add_filter('auto_update_translation', '__return_false');
			
			// disable email notification
			apply_filters('auto_core_update_send_email', '__return_false');
			apply_filters('send_core_update_notification_email', '__return_false');
			apply_filters('automatic_updates_send_debug_email', '__return_false');
		}
	}
	
	public function fiad_title($admin_title, $title){
		return get_bloginfo('name') . ' &bull; ' . $title;
	}
	
	public function fiad_admin_title($login_title){
		return str_replace([' &lsaquo;', ' &#8212; WordPress'], [' &lsaquo;', ''], $login_title);
	}
	
	public function fiad_update_admin_footer(){
		$current_theme            = wp_get_theme();
		$current_theme_author_url = $current_theme->get('AuthorURI');
		$current_theme_author     = $current_theme->get('Author');
		
		echo '<span id="footer-thankyou">Developed by <a href="' . esc_url($current_theme_author_url) . '" target="_blank">' . esc_attr($current_theme_author) . '</a></span>';
		
	}
	
	public function fiad_update_dashboard_name(){
		if($GLOBALS['title'] != 'Dashboard'){
			return;
		}
		
		$GLOBALS['title'] = get_bloginfo('name');
	}
	
	public function fiad_remove_dashboard_widgets(){
		remove_meta_box('dashboard_primary', 'dashboard', 'core');
		remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
		remove_meta_box('dashboard_activity', 'dashboard', 'normal');
		remove_meta_box('dashboard_incoming_links', 'dashboard', 'normal');
		remove_meta_box('dashboard_site_health', 'dashboard', 'normal');
	}
	
	public function fiad_login_logo_url(){
		return home_url();
	}
	
	public function fiad_login_logo_title(){
		return get_bloginfo('name');
	}
	
	public function fiad_remove_lostpassword($text){
		if($text == 'Lost your password?'){
			$text = '';
		}
		
		return $text;
	}
	
	public function fiad_remove_backtoblog(){
		echo '<style>#nav,#backtoblog{display:none}</style>';
	}
	
	public function fiad_remove_admin_bar_logo(){
		global $wp_admin_bar;
		$wp_admin_bar->remove_menu('wp-logo');
	}
	
	public function fiad_remove_meta_generators(){
		// WPBakery Page Builder
		if(class_exists('Vc_Manager')){
			remove_action('wp_head', [visual_composer(), 'addMetaData']);
		}
		
		// WPML
		if(function_exists('icl_object_id')){
			global $sitepress;
			remove_action('wp_head', [$sitepress, 'meta_generator_tag']);
		}
	}
	
	public function fiad_favicon_admin_logo(){
		echo '<style>
			   .wp-admin #wpadminbar #wp-admin-bar-site-name>.ab-item:before{
			  	content:"";
			  	background:transparent url("' . get_site_icon_url() . '") no-repeat center/contain !important;
			  	width: 20px; height: 20px;
			   }
			  </style>';
	}
	
	public function fiad_deregister_styles(){
		wp_deregister_style('dashicons');
	}
}

new Fiber_Admin_Default();