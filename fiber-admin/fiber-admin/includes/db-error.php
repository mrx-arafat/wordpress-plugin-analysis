<?php
// Exit if accessed directly
if(!defined('ABSPATH')){
	exit;
}

/**
 * DB Error
 */
class Fiber_Admin_DB_Error{
	public function __construct(){
		add_action('admin_init', [$this, 'fiad_db_error_file']);
		register_activation_hook(FIBERADMIN_FILENAME, [$this, 'fiad_db_error_file']);
		register_deactivation_hook(FIBERADMIN_FILENAME, [$this, 'fiad_remove_db_error_file']);
	}
	
	public function fiad_db_error_file(){
		$db_error_added  = fiad_get_db_error_option('db_error_added');
		$enable          = fiad_get_db_error_option('db_error_enable');
		$db_error_option = get_option('fiad_db_error');
		if($enable && !$db_error_added){
			// add rules to .htaccess file in wp-content if it exists
			$content_htaccess = WP_CONTENT_DIR . '/.htaccess';
			if(file_exists($content_htaccess)){
				$lines   = [];
				$lines[] = '<Files db-error.php>';
				$lines[] = '    Allow From All';
				$lines[] = '    Satisfy any';
				$lines[] = '</Files>';
				
				insert_with_markers($content_htaccess, 'FIBER ADMIN DB ERROR PAGE', $lines);
			}
			
			// generate DB Error content based on settings
			$db_error_message = fiad_get_db_error_option('db_error_message');
			$title            = fiad_get_db_error_option('db_error_title');
			$logo             = fiad_get_db_error_option('db_error_logo');
			$logo_width       = fiad_get_db_error_option('db_error_logo_width');
			$logo_height      = fiad_get_db_error_option('db_error_logo_height');
			$bg_color         = fiad_get_db_error_option('db_error_bg');
			$style            = $bg_color ? 'body {background-color: ' . $bg_color . '}' : '';
			$server           = $_SERVER;
			$http             = fiad_array_key_exists('HTTPS', $server) ? "https://" : "http://";
			$http_host        = $http . fiad_array_key_exists('HTTP_HOST', $server);
			
			$php = '<?php';
			$php .= PHP_EOL;
			$php .= 'header(\'HTTP/1.1 503 Service Temporarily Unavailable\');';
			$php .= PHP_EOL;
			$php .= 'header(\'Status: 503 Service Temporarily Unavailable\');';
			$php .= PHP_EOL;
			$php .= 'header(\'Retry-After: 3600\');';
			$php .= PHP_EOL;
			$php .= '$absolute_url = "' . $http_host . '" . explode($_SERVER[\'DOCUMENT_ROOT\'], __DIR__)[1];';
			$php .= PHP_EOL;
			$php .= '?>';
			
			$html = $php;
			$html .= '<!DOCTYPE HTML>';
			$html .= '<html ' . get_language_attributes() . '>';
			$html .= '<head>';
			$html .= '<title>' . $title . '</title>';
			$html .= "<style>
					@import url('https://fonts.googleapis.com/css2?family=Maven+Pro:wght@400;900&display=swap');
			        * {-webkit-box-sizing:border-box;box-sizing:border-box}
			        body {font-family:'Maven Pro', sans-serif;padding:0;margin:0}
			        " . $style . "
			        .db-error__container {position:relative;height:100vh}
			        .db-error__container .db-error__inner {position:absolute;left:50%;top:50%;-webkit-transform:translate(-50%, -50%);-ms-transform:translate(-50%, -50%);transform:translate(-50%, -50%)}
			        .db-error__inner {max-width:920px;width:100%;line-height:1.4;text-align:center;padding-left:15px;padding-right:15px}
			        .db-error__inner .db-error__logo {margin:0 auto 100px auto; max-width:300px; text-align:center;}
			        .db-error__inner .db-error__logo img {max-width:300px;}
			        .db-error__inner .db-error__content {position:relative;}
			        .db-error__inner .db-error__error-503 {position:absolute;height:100px;top:0;left:50%;-webkit-transform:translateX(-50%);-ms-transform:translateX(-50%);transform:translateX(-50%);z-index:-1}
			        .db-error__inner .db-error__error-503 h1 {color:#ececec;font-weight:900;font-size:276px;margin:0;position:absolute;left:50%;top:50%;-webkit-transform:translate(-50%, -50%);-ms-transform:translate(-50%, -50%);transform:translate(-50%, -50%)}
			        .db-error__inner h2, .db-error__inner h3, .db-error__inner h4, .db-error__inner h5 {font-size:46px;color:#000;font-weight:900;text-transform:uppercase;margin:0}
			        .db-error__inner p {font-size:16px;color:#000;font-weight:400;text-transform:uppercase;margin-top:15px}
			        @media only screen and (max-width:480px) {
			            .db-error__inner .db-error__error-503 h1 {font-size:162px}
			            .db-error__inner h2 {font-size:26px}
			        }
					</style>";
			$html .= '<link rel="icon" type="image/png" href="<?= $absolute_url; ?>' . fiad_get_file_upload_path(get_site_icon_url()) . '"/>';
			$html .= '</head>';
			$html .= '<body class="db-error">';
			
			$html .= '<div class="db-error__container">';
			$html .= '<div class="db-error__inner">';
			
			if($logo){
				$html .= '<div class="db-error__logo">';
				$html .= '<img src="<?= $absolute_url; ?>' . fiad_get_file_upload_path($logo) . '"  alt="' . get_bloginfo('name') . '" width="' . $logo_width . '" height="' . $logo_height . '"/>';
				$html .= '</div>';
			}
			
			$html .= '<div class="db-error__content">';
			
			$html .= '<div class="db-error__error-503"><h1>503</h1></div>';
			
			$html .= '<div class="db-error__error-message">';
			$html .= stripslashes($db_error_message);
			$html .= '</div>';
			
			$html .= '</div>'; // db-error__content
			
			$html .= '</div>'; // db-error__inner
			$html .= '</div>'; // db-error__container
			
			$html .= '</body>'; // db-error
			$html .= '</html>';
			
			file_put_contents(WP_CONTENT_DIR . '/db-error.php', $html);
			
			$db_error_option['db_error_added'] = true;
		}else{
			if(fiad_check_db_error_file() && !$enable){
				wp_delete_file(WP_CONTENT_DIR . '/db-error.php');
				$db_error_option['db_error_added'] = false;
			}
		}
		
		update_option('fiad_db_error', $db_error_option);
	}
	
	public function fiad_remove_db_error_file(){
		// Delete db-error.php on deactivate
		if(fiad_check_db_error_file()){
			$db_error_option = get_option('fiad_db_error');
			wp_delete_file(WP_CONTENT_DIR . '/db-error.php');
			$db_error_option['db_error_added'] = false;
			update_option('fiad_db_error', $db_error_option);
		}
	}
}

new Fiber_Admin_DB_Error();