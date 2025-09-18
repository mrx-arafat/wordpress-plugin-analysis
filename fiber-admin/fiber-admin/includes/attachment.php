<?php
// Exit if accessed directly
if(!defined('ABSPATH')){
	exit;
}

/**
 * Handle attachment
 */
class Fiber_Admin_Attachment{
	public function __construct(){
		//Return character `%` in special chars array
		add_filter('sanitize_file_name_chars', [$this, 'fiad_special_chars']);
		
		// Format filename
		add_filter('sanitize_file_name', [$this, 'fiad_cleanup_attachment_name'], 10, 2);
		
		// Generate attachment title from filename
		add_filter('add_attachment', [$this, 'fiad_change_metadata']);
	}
	
	public function fiad_special_chars($special_chars){
		if(($key = array_search('%', $special_chars)) !== false){
			unset($special_chars[$key]);
		}
		
		return $special_chars;
	}
	
	public function fiad_handle_special_chars($sanitized_filename){
		//Replace all special chars and row of `-` with one `-` only
		$patterns           = ['/[^A-Za-z0-9- ]/', '/-{2,}/'];
		$sanitized_filename = preg_replace($patterns, '-', $sanitized_filename);
		
		// Remove character `-` from the beginning and the end
		return trim($sanitized_filename, '-');
	}
	
	public function fiad_cleanup_attachment_name($filename, $filename_raw){
		$file_extension       = pathinfo($filename, PATHINFO_EXTENSION);
		$supported_exts       = [];
		$ext_types            = wp_get_ext_types();
		$supported_file_types = ['image', 'audio', 'video', 'document', 'spreadsheet'];
		foreach($supported_file_types as $type){
			$supported_exts = array_merge($supported_exts, $ext_types[$type]);
		}
		if($file_extension && in_array($file_extension, $supported_exts)){
			$sanitized_filename = strtolower(basename($filename, "." . $file_extension));
			
			//handle urlencoded chars
			preg_match_all('/%[0-9A-Fa-f]{2}/', $filename_raw, $matches);
			$urlencoded_chars = fiad_array_key_exists(0, $matches);
			if($urlencoded_chars){
				$urlencoded_chars   = array_map(function($char){
					return strtolower(trim($char, '%'));
				}, $urlencoded_chars);
				$sanitized_filename = str_replace($urlencoded_chars, "", $sanitized_filename);
			}
			
			//special chars case
			$sanitized_filename = $this->fiad_handle_special_chars($sanitized_filename);
			
			return $sanitized_filename . "." . $file_extension;
		}
		
		return $filename;
	}
	
	public function fiad_change_metadata($post_id){
		$readable_name = $this->fiad_get_readable_attachmentname($post_id);
		$this->fiad_update_post_meta($post_id, $readable_name);
		
		if(wp_attachment_is_image($post_id)){
			update_post_meta($post_id, '_wp_attachment_image_alt', $readable_name);
		}
	}
	
	public function fiad_get_readable_attachmentname($post_id){
		$file          = get_attached_file($post_id);
		$file_pathinfo = pathinfo($file);
		$file_name     = fiad_array_key_exists('filename', $file_pathinfo);
		
		// check if the file name contain index at the end
		$pattern = '/-\d+$/';
		if(preg_match($pattern, $file_name)){
			$file_name = preg_replace($pattern, '', $file_name);
		}
		
		// remove separator character `-` in file name
		$file_name = str_replace('-', ' ', $file_name);
		
		return ucwords($file_name);
	}
	
	public function fiad_update_post_meta($post_id, $post_title, $extra_args = []){
		$fiber_meta = [
			'ID'         => $post_id,
			'post_title' => $post_title,
		];
		if($extra_args){
			$fiber_meta = array_merge($fiber_meta, $extra_args);
		};
		
		wp_update_post($fiber_meta);
	}
}

new Fiber_Admin_Attachment();