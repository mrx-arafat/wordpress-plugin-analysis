<?php
// Exit if accessed directly
if(!defined('ABSPATH')){
	exit;
}

/**
 * Helper functions
 */

if(!function_exists('fiad_get_option')){
	function fiad_get_option($key, $fiber_admin){
		if(is_array($fiber_admin)){
			if(array_key_exists($key, $fiber_admin)){
				return $fiber_admin[$key];
			}
		}
		
		return '';
	}
}

if(!function_exists('fiad_get_general_option')){
	function fiad_get_general_option($key){
		return fiad_get_option($key, get_option('fiber_admin'));
	}
}

if(!function_exists('fiad_get_miscellaneous_option')){
	function fiad_get_miscellaneous_option($key){
		return fiad_get_option($key, get_option('fiad_miscellaneous'));
	}
}

if(!function_exists('fiad_get_cpo_option')){
	function fiad_get_cpo_option($key){
		return fiad_get_option($key, get_option('fiad_cpo'));
	}
}

if(!function_exists('fiad_is_screen_sortable')){
	function fiad_is_screen_sortable(){
		if(is_admin()){
			$post_types = fiad_get_cpo_option('post_types');
			$taxonomies = fiad_get_cpo_option('taxonomies');
			
			if(!function_exists('get_current_screen')){
				require_once ABSPATH . '/wp-admin/includes/screen.php';
			}
			$screen = get_current_screen();
			
			if(($post_types || $taxonomies) && $screen){
				if($screen->taxonomy && $taxonomies && strpos($screen->base, 'edit') !== false){
					$screen_tax = $screen->taxonomy;
					if(in_array($screen_tax, $taxonomies)){
						return true;
					}
				}elseif($post_types && $screen->base == 'edit'){
					$screen_post_type = $screen->post_type;
					if(in_array($screen_post_type, $post_types)){
						return true;
					}
				}
			}
		}
		
		return false;
	}
}

if(!function_exists('fiad_get_duplicate_option')){
	function fiad_get_duplicate_option($key){
		return fiad_get_option($key, get_option('fiad_duplicate'));
	}
}

if(!function_exists('fiad_is_admin_user_role')){
	function fiad_is_admin_user_role(){
		if(is_user_logged_in()){
			if(current_user_can('edit_posts')){
				return true;
			}
		}
		
		return false;
	}
}

if(!function_exists('fiad_get_db_error_option')){
	function fiad_get_db_error_option($key){
		return fiad_get_option($key, get_option('fiad_db_error'));
	}
}

if(!function_exists('fiad_check_db_error_file')){
	function fiad_check_db_error_file(){
		return (file_exists(WP_CONTENT_DIR . '/db-error.php'));
	}
}

if(!function_exists('fiad_array_key_exists')){
	function fiad_array_key_exists($key, $array, $default = ''){
		if($array && is_array($array)){
			if(array_key_exists($key, $array)){
				return $array[$key] ? : $default;
			}
		}
		
		return $default;
	}
}

if(!function_exists('fiad_dequeue_assets')){
	function fiad_dequeue_assets(){
		global $wp_scripts, $wp_styles;
		
		$exclude_assets = ['jquery-core', 'admin-bar'];
		foreach($wp_scripts->registered as $registered){
			$handle = $registered->handle;
			if(!in_array($handle, $exclude_assets)){
				wp_deregister_script($handle);
			}
		}
		
		foreach($wp_styles->registered as $registered){
			$handle = $registered->handle;
			if(!in_array($handle, $exclude_assets)){
				wp_deregister_style($handle);
			}
		}
	}
}

if(!function_exists('fiad_code_editor')){
	function fiad_code_editor($type, $id){
		// Enqueue code editor and settings for manipulating HTML.
		$settings = wp_enqueue_code_editor(['type' => $type]);
		
		// Return if the editor was not enqueued.
		if(false === $settings){
			return;
		}
		
		wp_add_inline_script(
			'code-editor',
			sprintf(
				'jQuery( function() { wp.codeEditor.initialize( "' . $id . '", %s ); } );',
				wp_json_encode($settings)
			)
		);
	}
}

if(!function_exists('fiad_file_get_content')){
	function fiad_file_get_content($file_url){
		$curl_session = curl_init($file_url);
		curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, true);
		
		return curl_exec($curl_session);
	}
}

if(!function_exists('fiad_get_file_upload_path')){
	function fiad_get_file_upload_path($url){
		return explode('wp-content', $url)[1];
	}
}