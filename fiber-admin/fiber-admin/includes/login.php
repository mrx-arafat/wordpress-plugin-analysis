<?php
// Exit if accessed directly
if(!defined('ABSPATH')){
	exit;
}

class Fiber_Admin_Login{
	private const MAX_LOGO_WIDTH = 320;
	private const CACHE_KEY = 'fiad_admin_login_cache'; // CSS cache key
	private const CACHE_EXPIRATION = 24 * HOUR_IN_SECONDS; // CSS cache expiration (24 hours)
	
	/**
	 * Constructor - Initialize hooks
	 */
	public function __construct(){
		// enqueue styles
		add_action('login_enqueue_scripts', [$this, 'fiad_enqueue_login_styles']);
		
		// clear cache
		add_action('updated_option', [$this, 'fiad_maybe_clear_cache']);
		add_action('added_option', [$this, 'fiad_maybe_clear_cache']);
		add_action('deleted_option', [$this, 'fiad_maybe_clear_cache']);
	}
	
	/**
	 * Enqueue login page styles
	 */
	public function fiad_enqueue_login_styles(){
		$cached_css = get_transient(self::CACHE_KEY);
		
		if(false === $cached_css){
			$cached_css = $this->generate_login_css();
			set_transient(self::CACHE_KEY, $cached_css, self::CACHE_EXPIRATION);
		}
		
		if(!empty($cached_css)){
			wp_add_inline_style('login', $cached_css);
		}
	}
	
	/**
	 * Generate login page CSS
	 */
	private function generate_login_css(){
		$css_parts = [
			$this->get_general_css(),
			$this->get_logo_css(),
			$this->get_background_css(),
			$this->get_form_css(),
			$this->get_extra_css(),
		];
		
		return implode("\n", array_filter($css_parts));
	}
	
	/**
	 * Get general login page CSS
	 */
	private function get_general_css(){
		return '
        body.login{display: flex;flex-direction: column;justify-content: center;min-height: 100vh;margin: 0;}
        body.login div#login{margin: auto;padding:0;}
        body.login .language-switcher {text-align: center;padding-bottom:0;}
        body.login div#login form#loginform{margin:0;}
        body.login div#login .privacy-policy-page-link{margin:40px 0 0 0;}
        ';
	}
	
	/**
	 * Get logo CSS
	 */
	private function get_logo_css(){
		$logo_url = fiad_get_general_option('login_logo');
		if(empty($logo_url)){
			return '';
		}
		
		$logo_width  = absint(fiad_get_general_option('login_logo_width'));
		$logo_height = absint(fiad_get_general_option('login_logo_height'));
		
		// start
		$css = 'body.login div#login h1 a, body.login h1 a {';
		
		// change logo
		$css .= sprintf('background-image: url(%s);', esc_url($logo_url));
		$css .= 'max-width: 100%;';
		
		// width
		$css .= $logo_width > 0 && $logo_width <= self::MAX_LOGO_WIDTH ? sprintf('width: %dpx;', $logo_width) : 'width: auto;';
		
		// height
		$css .= $logo_height > 0 ? sprintf('height: %dpx;', $logo_height) : '';
		
		// background sizes
		$css .= $logo_width > 0 && $logo_height > 0
			? sprintf('background-size: %dpx %dpx;', $logo_width, $logo_height)
			: 'background-size: contain; background-position-y: center;';
		
		// consistent bottom spacing
		$css .= 'margin-bottom: 40px;';
		
		// end
		$css .= '}';
		
		return $css;
	}
	
	/**
	 * Get background CSS
	 */
	private function get_background_css(){
		$bg_color = fiad_get_general_option('login_bg_color');
		$bg_image = fiad_get_general_option('login_bg_img');
		
		// use background color first
		if($bg_color){
			return sprintf('body.login { background-color: %s; }', sanitize_hex_color($bg_color));
		}
		
		// fallback background image
		if($bg_image){
			return sprintf(
				'body.login { background: url(%s) center / cover no-repeat; }',
				esc_url($bg_image)
			);
		}
		
		return '';
	}
	
	/**
	 * Get form CSS
	 */
	private function get_form_css(){
		$css = '';
		
		// form background color
		$form_bg_color = fiad_get_general_option('form_bg_color');
		if($form_bg_color){
			$css .= sprintf('body.login div#login form#loginform { background-color: %s; }', sanitize_hex_color($form_bg_color));
		}
		
		// remove form border
		if(fiad_get_general_option('form_disable_border')){
			$css .= 'body.login div#login form#loginform { border: none; box-shadow: none; }';
		}
		
		// button styles
		$css .= $this->get_button_css();
		
		// link color
		$link_color = fiad_get_general_option('link_color');
		if($link_color){
			$css .= sprintf('body.login div#login a { color: %s; }', sanitize_hex_color($link_color));
		}
		
		return $css;
	}
	
	/**
	 * Get button CSS
	 */
	private function get_button_css(){
		$btn_text_color = fiad_get_general_option('form_btn_text_color');
		$btn_bg_color   = fiad_get_general_option('form_button_color');
		
		if(!$btn_text_color && !$btn_bg_color){
			return '';
		}
		
		$css = 'body.login div#login form#loginform input[type="submit"] {';
		
		if($btn_text_color){
			$css .= sprintf('color: %s;', sanitize_hex_color($btn_text_color));
			$css .= 'text-shadow: none; border-color: transparent; box-shadow: none;';
		}
		
		if($btn_bg_color){
			$css .= sprintf('background-color: %s;', sanitize_hex_color($btn_bg_color));
			$css .= 'border: 0; box-shadow: none;';
		}
		
		$css .= '}';
		
		return $css;
	}
	
	/**
	 * Get extra CSS
	 */
	private function get_extra_css(){
		$extra_css = fiad_get_general_option('login_extra_css');
		
		return $extra_css ? wp_strip_all_tags($extra_css) : '';
	}
	
	/**
	 * Maybe clear cache when options are updated
	 */
	public function fiad_maybe_clear_cache($option_name){
		if($option_name === 'fiber_admin'){
			$this->clear_css_cache();
		}
	}
	
	/**
	 * Clear CSS cache transient
	 */
	public function clear_css_cache(){
		return delete_transient(self::CACHE_KEY);
	}
}

new Fiber_Admin_Login();