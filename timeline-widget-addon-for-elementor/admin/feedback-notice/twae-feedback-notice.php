<?php
if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

if (! class_exists('TWAEFeedbackNotice')) {
	class TWAEFeedbackNotice
	{
		/**
		 * The Constructor
		 */
		public function __construct()
		{
			// register actions

			if (is_admin()) {
				add_action('admin_notices', array($this, 'twae_admin_notice_for_reviews'));
				add_action('admin_enqueue_scripts', array($this, 'twae_load_script'));
				add_action('wp_ajax_twae_dismiss_notice', array($this, 'twae_dismiss_review_notice'));
			}
		}

		/**
		 * Load script to dismiss notices.
		 *
		 * @return void
		 */
		public function twae_load_script()
		{
			$alreadyRated = get_option('twae-alreadyRated') != false ? get_option('twae-alreadyRated') : 'no';

			// check user already rated
			if ($alreadyRated == 'yes') {
				return;
			}

			wp_register_style( 'twae-feedback-notice', TWAE_URL . 'admin/feedback-notice/assets/css/twae-feedback-notice.css', array(), TWAE_VERSION, 'all' );

			wp_register_script( 'twae-admin-notice', TWAE_URL . 'admin/feedback-notice/assets/js/twae-feedback-notice.js', array( 'jquery' ), TWAE_VERSION, true );
		}
		// ajax callback for review notice
		public function twae_dismiss_review_notice()
		{
			// Verify nonce for security
			check_ajax_referer('twae_dismiss_nonce', 'nonce');

			$rs = update_option('twae-alreadyRated', 'yes');
			echo json_encode(array('success' => 'true'));
			exit;
		}
		// admin notice
		public function twae_admin_notice_for_reviews()
		{
			if (! current_user_can('update_plugins')) {
				return;
			}
			// get installation dates and rated settings
			$installation_date = get_option('twae-installDate');
			$alreadyRated      = get_option('twae-alreadyRated') != false ? get_option('twae-alreadyRated') : 'no';

			// check user already rated
			if ($alreadyRated == 'yes') {
				return;
			}

			// grab plugin installation date and compare it with current date
			$display_date = date('Y-m-d h:i:s');
			$install_date = new DateTime($installation_date);
			$current_date = new DateTime($display_date);
			$difference   = $install_date->diff($current_date);
			$diff_days    = $difference->days;

			// check if installation days is greator then week
			if (isset($diff_days) && $diff_days >= 3) {
				wp_enqueue_style( 'twae-feedback-notice' );
            	wp_enqueue_script( 'twae-admin-notice' );
				echo $this->twae_create_notice_content();
			}
		}

		// generated review notice HTML
		function twae_create_notice_content()
		{
			$wp_nonce = wp_create_nonce('twae_dismiss_nonce');
			$ajax_url      = esc_url( admin_url( 'admin-ajax.php' ) );
			$ajax_callback      = 'twae_dismiss_notice';
			$wrap_cls           = 'notice notice-info is-dismissible';
			$img_path      = esc_url( TWAE_URL . 'assets/images/timeline-widget-logo.png' );
			$p_name             = esc_html('Timeline Widget Addon For Elementor');
			$like_it_text       =  esc_html('Rate Now! ★★★★★');
			$already_rated_text = esc_html__('Already Reviewed', 'cool-timeline');
			$not_interested     = esc_html__('Not Interested', 'ect');
			$not_like_it_text   = esc_html__('No, not good enough, i do not like to rate it!', 'cool-timeline');
			$p_link             = esc_url('https://wordpress.org/support/plugin/timeline-widget-addon-for-elementor/reviews/#new-post');
			$pro_url            = esc_url('https://cooltimeline.com/plugin/elementor-timeline-widget-pro/');

			$raw_message = "Thanks for using <b>$p_name</b> WordPress plugin. We hope it meets your expectations! <br/>Please give us a quick rating, it works as a boost for us to keep working on more <a href='https://coolplugins.net' target='_blank'><strong>Cool Plugins</strong></a>!<br/>";

			$allowed_html = array(
				'b'      => array(),
				'br'     => array(),
				'a'      => array(
					'href'   => array(),
					'target' => array(),
				),
				'strong' => array(),
			);

			$message = wp_kses($raw_message, $allowed_html);


			$html = '<div data-ajax-url="%8$s"  data-ajax-callback="%9$s" class="cool-feedback-notice-wrapper %1$s" data-wp-nonce="%12$s">
        <div class="message_container">%4$s
        <div class="callto_action">
        <ul>
            <li class="love_it"><a href="%5$s" class="like_it_btn button button-primary" target="_new" title="%6$s">%6$s</a></li>
            <li class="already_rated"><a href="javascript:void(0);" class="already_rated_btn button twae_dismiss_notice" title="%7$s">%7$s</a></li>
            <li class="already_rated"><a href="javascript:void(0);" class="already_rated_btn button twae_dismiss_notice" title="%11$s">%11$s</a></li>
           
        </ul>
        <div class="clrfix"></div>
        </div>
        </div>
        </div>';

			return sprintf(
				$html,
				$wrap_cls,
				$img_path,
				$p_name,
				$message,
				$p_link,
				$like_it_text,
				$already_rated_text,
				$ajax_url, // 8
				$ajax_callback, // 9
				$pro_url, // 10
				$not_interested,
				$wp_nonce
			);
		}
	} //class end

}
