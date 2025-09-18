<?php
// Exit if accessed directly
if(!defined('ABSPATH')){
	exit;
}

if(fiad_get_miscellaneous_option('enable_svg')
   && !is_plugin_active('svg-support/svg-support.php')
   && !interface_exists('enshrined\svgSanitize\data\AttributeInterface', false)){
	require_once(FIBERADMIN_DIR . 'lib/svg-sanitizer/data/AttributeInterface.php');
	require_once(FIBERADMIN_DIR . 'lib/svg-sanitizer/data/TagInterface.php');
	require_once(FIBERADMIN_DIR . 'lib/svg-sanitizer/data/AllowedAttributes.php');
	require_once(FIBERADMIN_DIR . 'lib/svg-sanitizer/data/AllowedTags.php');
	require_once(FIBERADMIN_DIR . 'lib/svg-sanitizer/data/XPath.php');
	require_once(FIBERADMIN_DIR . 'lib/svg-sanitizer/ElementReference/Resolver.php');
	require_once(FIBERADMIN_DIR . 'lib/svg-sanitizer/ElementReference/Subject.php');
	require_once(FIBERADMIN_DIR . 'lib/svg-sanitizer/ElementReference/Usage.php');
	require_once(FIBERADMIN_DIR . 'lib/svg-sanitizer/Exceptions/NestingException.php');
	require_once(FIBERADMIN_DIR . 'lib/svg-sanitizer/Helper.php');
	require_once(FIBERADMIN_DIR . 'lib/svg-sanitizer/Sanitizer.php');
}

use enshrined\svgSanitize\Sanitizer;

/**
 * Image
 */
class Fiber_Admin_Image{
	public function __construct(){
		// Enable SVG
		if(fiad_get_miscellaneous_option('enable_svg')){
			add_filter('upload_mimes', [$this, 'fiad_svg_mime_types']);
			add_action('admin_head', [$this, 'fiad_fix_svg_display']);
			
			add_action("admin_enqueue_scripts", [$this, 'fiad_svg_enqueue_scripts']);
			add_filter('wp_handle_upload_prefilter', [$this, 'fiad_check_for_svg']);
			
			// SVG metadata
			add_filter('wp_get_attachment_metadata', [$this, 'fiad_svg_attachment_metadata'], 10, 2);
			add_filter('wp_generate_attachment_metadata', [$this, 'fiad_svg_attachment_metadata'], 10, 2);
		}
	}
	
	public function fiad_svg_enqueue_scripts(){
		$screen = get_current_screen();
		if($screen->id == 'upload'){
			$suffix = !FIBERADMIN_DEV_MODE ? '.min' : '';
			wp_enqueue_script('fiber-admin-svg', FIBERADMIN_ASSETS_URL . 'js/fiber-svg' . $suffix . '.js', ['jquery'], FIBERADMIN_VERSION);
			wp_localize_script('fiber-admin-svg', 'script_vars',
				[
					'ajaxurl' => admin_url('admin-ajax.php'),
				]
			);
		}
	}
	
	public function fiad_svg_mime_types($mimes){
		$mimes['svg']  = 'image/svg+xml';
		$mimes['svgz'] = 'image/svg+xml';
		
		return $mimes;
	}
	
	public function fiad_fix_svg_display(){
		echo '<style>
		    td.media-icon img[src$=".svg"], img[src$=".svg"].attachment-post-thumbnail{
		      width: 100% !important;
		      height: auto !important;
		    }
		  </style>';
	}
	
	public function fiad_svg_attachment_metadata($data, $id){
		$attachment = get_post($id);
		$mime_type  = $attachment->post_mime_type;
		
		//If the attachment is an SVG
		if($mime_type == 'image/svg+xml'){
			//If the svg metadata are empty or the width is empty or the height is empty
			//then get the attributes from xml.
			if(empty($data) || empty($data['width']) || empty($data['height'])){
				$xml     = simplexml_load_file(get_attached_file($id));
				$attr    = $xml->attributes();
				$viewbox = explode(' ', $attr->viewBox);
				$width   = isset($attr->width) && preg_match('/\d+/', $attr->width, $value) ? (int) $value[0] : (count($viewbox) == 4 ? (int) $viewbox[2] : null);
				$height  = isset($attr->height) && preg_match('/\d+/', $attr->height, $value) ? (int) $value[0] : (count($viewbox) == 4 ? (int) $viewbox[3] : null);
				
				$data['width']  = ceil($width);
				$data['height'] = ceil($height);
			}
		}
		
		return $data;
	}
	
	protected function fiad_get_svg_dimensions($svg){
		$svg    = @simplexml_load_file($svg);
		$width  = 0;
		$height = 0;
		if($svg){
			$attributes = $svg->attributes();
			if(isset($attributes->width, $attributes->height)){
				$width  = floatval($attributes->width);
				$height = floatval($attributes->height);
			}elseif(isset($attributes->viewBox)){
				$sizes = explode(' ', $attributes->viewBox);
				if(isset($sizes[2], $sizes[3])){
					$width  = floatval($sizes[2]);
					$height = floatval($sizes[3]);
				}
			}else{
				return false;
			}
		}
		
		return [
			'width'       => ceil($width),
			'height'      => ceil($height),
			'orientation' => ($width > $height) ? 'landscape' : 'portrait',
		];
	}
	
	public function fiad_check_for_svg($file){
		if($file['type'] == 'image/svg+xml'){
			if(!$this->fiad_santialize_svg($file['tmp_name'])){
				$file['error'] = __("Sorry, this file couldn't be sanitized so for security reasons wasn't uploaded",
					'safe-svg');
			}
		}
		
		return $file;
	}
	
	protected function fiad_santialize_svg($file_path){
		// Create a new sanitizer instance
		$sanitizer = new Sanitizer();
		
		// Load the dirty svg
		$dirtySVG = file_get_contents($file_path);
		
		// Pass it to the sanitizer and get it back clean
		$cleanSVG = $sanitizer->sanitize($dirtySVG);
		
		file_put_contents($file_path, $cleanSVG);
		
		return true;
	}
}

new Fiber_Admin_Image();