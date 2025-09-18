<?php //phpcs:ignore Generic.Files.LineEndings.InvalidEOLChar
/**
 * Revenue Notice
 *
 * @package Revenue
 * @since 1.0.6
 */

namespace Revenue;

/**
 * Revenue Notice Class
 */
class Revenue_Notice {
	use SingletonTrait;

	/**
	 * Contains Notice Version
	 *
	 * @var string
	 */
	private $notice_version = 'v9';

	/**
	 * Contains Notice type
	 *
	 * @var string
	 */
	private $type = '';
	/**
	 * Contains notice is force or not
	 *
	 * @var string
	 */
	private $force = '';
	/**
	 * Contain Notice content.
	 *
	 * @var string
	 */
	private $content = '';

	/**
	 * Contains Notice Heading
	 *
	 * @var string
	 */
	private $heading = '';

	/**
	 * Contains Notice Subheading
	 *
	 * @var string
	 */
	private $subheading = '';

	/**
	 * Contains Notice Days Remaining Text
	 *
	 * @var string
	 */
	private $days_remaining = '';

	/**
	 * Contains Available Notices.
	 *
	 * @var array
	 */
	private $available_notice = array();

	/**
	 * Contains Price ID.
	 *
	 * @var string
	 */
	private $price_id = false;



	/**
	 * Promotional Notice Callback
	 *
	 * @since 1.0.0
	 */
	public function init() {
		add_action( 'admin_init', array( $this, 'notice_callback' ) );
		add_action( 'admin_notices', array( $this, 'display_notices' ), 0 );
	}

	/**
	 * Display Notices
	 *
	 * @return void
	 */
	public function display_notices() {

		usort( $this->available_notice, array( $this, 'sort_notices' ) );
		$displayed_notice_count = 0;

		foreach ( $this->available_notice as $notice ) {
			if ( $this->is_valid_notice( $notice ) ) {
				if ( isset( $notice['show_if'] ) && true === $notice['show_if'] ) {
					if ( 0 !== $displayed_notice_count && false === $notice['display_with_other_notice'] ) {
						continue;
					}
					if ( isset( $notice['id'], $notice['design_type'] ) ) {
						echo $this->get_notice_content( $notice['id'], $notice['design_type'] ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

						++$displayed_notice_count;
					}
				}
			}
		}
	}

	/**
	 * Get notice by id.
	 *
	 * @param string $id Notice ID.
	 * @return array
	 */
	private function get_notice_by_id( $id ) {
		if ( isset( $this->available_notice[ $id ] ) ) {
			return $this->available_notice[ $id ];
		}
	}


	/**
	 * Notice callback
	 *
	 * @return void
	 */
	public function notice_callback() {
		$this->available_notice = array(
			'revx_ss_2025_1_' => $this->set_new_notice( 'revx_ss_2025_1_', 'promotion', 'summer_sale_1', '23-06-2025', '05-07-2025', false, 10, ! revenue()->is_pro_active() ),
			'revx_ss_2025_2'   => $this->set_new_notice( 'revx_ss_2025_2', 'promotion', 'summer_sale_2', '06-07-2025', '09-07-2025', false, 10, ! revenue()->is_pro_active() ),
		);

		if ( isset( $_GET['revx-notice-disable'] ) ) {//phpcs:ignore
			$notice_key = sanitize_text_field( $_GET['revx-notice-disable'] );//phpcs:ignore
			$notice     = $this->get_notice_by_id( $notice_key );

			if ( isset( $notice['repeat_notice_after'] ) && $notice['repeat_notice_after'] ) {
				$repeat_timestamp = time() + ( DAY_IN_SECONDS * intval( $notice['repeat_notice_after'] ) );
				$this->set_user_notice_meta( $notice_key, 'off', $repeat_timestamp );
			} else {
				$this->set_user_notice_meta( $notice_key, 'off', false );
			}
		}
	}

	/**
	 * Get Notice Content
	 *
	 * @param string $key Key.
	 * @param string $design_type Design Type.
	 * @return mixed
	 */
	public function get_notice_content( $key, $design_type ) {

		$close_url = add_query_arg( 'revx-notice-disable', $key );

		switch ( $design_type ) {
			case 'summer_sale_1':
				$icon        = REVENUE_URL . 'assets/images/revenue.png';
				$url         = 'https://www.wowrevenue.com/?utm_source=revenue-menu&utm_medium=summer-topbar&utm_campaign=wowrevenue-DB#pricing';
				$full_access = 'https://wowrevenue.com';
				ob_start();
				$this->wc_notice_css();
				?>
						<div class="revx-display-block">
						<div class="revx-notice-wrapper notice">
						<div class="revx-install-body revx-image-banner">
							<a href="<?php echo esc_url( $close_url ); ?>" class="promotional-dismiss-notice">
								<img src="<?php echo esc_url( REVENUE_URL . '/assets/images/cross.svg' ); ?>" alt="<?php esc_attr_e( 'Dismiss', 'revenue' ); ?>" />
							</a>
							<a href="<?php echo esc_url( $url ); ?>" target="_blank">
								<img class="revx-halloween-img-banner" src="<?php echo $icon; //phpcs:ignore  ?>" alt="Banner">
							</a>
						</div>
					</div>
					</div>
					<?php
				return ob_get_clean();
			case 'summer_sale_2':
				$icon        = REVENUE_URL . 'assets/images/revenue_2.png';
				$url         = 'https://www.wowrevenue.com/?utm_source=revenue-menu&utm_medium=summer-topbar&utm_campaign=wowrevenue-DB#pricing';
				$full_access = 'https://wowrevenue.com';
				ob_start();
				$this->wc_notice_css();
				?>
						<div class="revx-display-block">
						<div class="revx-notice-wrapper notice">
						<div class="revx-install-body revx-image-banner">
							<a href="<?php echo esc_url( $close_url ); ?>" class="promotional-dismiss-notice">
								<img src="<?php echo esc_url( REVENUE_URL . '/assets/images/cross.svg' ); ?>" alt="<?php esc_attr_e( 'Dismiss', 'revenue' ); ?>" />
							</a>
							<a href="<?php echo esc_url( $url ); ?>" target="_blank">
								<img class="revx-halloween-img-banner" src="<?php echo $icon; //phpcs:ignore  ?>" alt="Banner">
							</a>
						</div>
					</div>
					</div>
					<?php
				return ob_get_clean();
			default:
				// code...
				break;
		}
		return '';
	}

	/**
	 * Sort the notices based on the given priority of the notice.
	 *
	 * @since 1.5.2
	 * @param array $notice_1 First notice.
	 * @param array $notice_2 Second Notice.
	 * @return array
	 */
	public function sort_notices( $notice_1, $notice_2 ) {
		if ( ! isset( $notice_1['priority'] ) ) {
			$notice_1['priority'] = 10;
		}
		if ( ! isset( $notice_2['priority'] ) ) {
			$notice_2['priority'] = 10;
		}

		return $notice_1['priority'] - $notice_2['priority'];
	}

	/**
	 * Set New Notice
	 *
	 * @param string  $id ID.
	 * @param string  $type Type.
	 * @param string  $design_type Design Type.
	 * @param string  $start Start.
	 * @param string  $end End.
	 * @param boolean $repeat Repeat.
	 * @param integer $priority Priority.
	 * @param boolean $show_if Visibility Condition.
	 * @return array
	 */
	private function set_new_notice( $id = '', $type = '', $design_type = '', $start = '', $end = '', $repeat = false, $priority = 10, $show_if = false ) {

		return array(
			'id'                        => $id,
			'type'                      => $type,
			'design_type'               => $design_type,
			'start'                     => $start, // Start Date.
			'end'                       => $end, // End Date.
			'repeat_notice_after'       => $repeat, // Repeat after how many days.
			'priority'                  => $priority, // Notice Priority.
			'display_with_other_notice' => false, // Display With Other Notice.
			'show_if'                   => $show_if, // Notice Showing Conditions.
			'capability'                => 'manage_options', // Capability of users, who can see the notice.
		);
	}



	/**
	 * Is valid notice
	 *
	 * @param array $notice Notice array.
	 * @return boolean
	 */
	public function is_valid_notice( $notice ) {
		$is_data_collect = isset( $notice['type'] ) && 'data_collect' === $notice['type'];
		$notice_status   = self::get_user_notice( $notice['id'] );

		if ( ! current_user_can( $notice['capability'] ) || 'off' === $notice_status ) {
			return false;
		}

		$current_time = gmdate( 'U' );
		if ( $current_time > strtotime( $notice['start'] ) && $current_time < strtotime( $notice['end'] ) && isset( $notice['show_if'] ) && true === $notice['show_if'] ) { // Has Duration.
			return true;
		}
	}



	/**
	 * Set Notice key on user meta.
	 *
	 * @param string $key Key.
	 * @param string $value Value.
	 * @param string $expiration Expiration.
	 * @return void
	 */
	public function set_user_notice_meta( $key = '', $value = '', $expiration = '' ) {
		if ( $key ) {
			$user_id     = get_current_user_id();
			$meta_key    = 'revenue_notice';
			$notice_data = get_user_meta( $user_id, $meta_key, true );
			if ( ! isset( $notice_data ) || ! is_array( $notice_data ) ) {
				$notice_data = array();
			}

			$notice_data[ $key ] = $value;

			if ( $expiration ) {
				$expire_notice_key                 = 'timeout_' . $key;
				$notice_data[ $expire_notice_key ] = $expiration;
			}

			update_user_meta( $user_id, $meta_key, $notice_data );

		}
	}

	/**
	 * Get User Notice
	 *
	 * @param string $key Key.
	 * @return mixed
	 */
	public static function get_user_notice( $key = '' ) {
		if ( $key ) {
			$user_id     = get_current_user_id();
			$meta_key    = 'revenue_notice';
			$notice_data = get_user_meta( $user_id, $meta_key, true );
			if ( ! isset( $notice_data ) || ! is_array( $notice_data ) ) {
				return false;
			}

			if ( isset( $notice_data[ $key ] ) ) {
				$expire_notice_key = 'timeout_' . $key;
				$current_time      = time();
				if ( isset( $notice_data[ $expire_notice_key ] ) && $notice_data[ $expire_notice_key ] < $current_time ) {
					unset( $notice_data[ $key ] );
					unset( $notice_data[ $expire_notice_key ] );
					update_user_meta( $user_id, $meta_key, $notice_data );
					return false;
				}
				return $notice_data[ $key ];
			}
		}
		return false;
	}

	/**
	 * WooCommerce Notice Styles
	 *
	 * @since 1.0.0
	 */
	public function wc_notice_css() {
		?>
		<style type="text/css">
			.button.activated-message:before, .button.activating-message:before, .button.installed:before, .button.installing:before, .button.updated-message:before, .button.updating-message:before {
				margin: 12px 5px 0 -2px;
			}
			.button.activating-message:before, .button.installing:before, .button.updating-message:before, .import-php .updating-message:before, .update-message p:before, .updating-message p:before {
				color: #ffffff;
				content: "\f463";
			}
			/* .wholesalex-wc-install.wholesalex-pro-notice-v2 {
				padding-bottom: 0px;
			} */

			.promotional-dismiss-notice img {
				height: 14px;
				width: 14px;
			}
			.wholesalex-content-notice {
				color: white;
				background-color: #6C6CFF;
				position: relative;
				font-size: 16px;
				padding-left: 10px;
				line-height: 23px;
			}

			.wholesalex-notice-content-wrapper {
				margin-bottom: 0px !important;
				padding: 10px 5px;
			}

			.wholesalex-wc-install .wholesalex-content-notice .wholesalex-btn-notice-pro {
				margin-left: 5px;
				background-color: #3c3cb7 !important;
				border-radius: 4px;
				max-height: 30px !important;
				padding: 8px 12px !important;
				font-size: 14px;
				position: relative;
				top: -4px;
			}
			.wholesalex-wc-install .wholesalex-content-notice .wholesalex-btn-notice-pro:hover {
				background-color: #29298c !important;
			}

			.wholesalex-content-notice .content-notice-dissmiss {
				position: absolute;
				top: 0;
				right: 0;
				color: white;
				background-color: #3f3fa6;
				padding: 4px 5px 5px;
				font-size: 12px;
				line-height: 1;
				border-bottom-left-radius: 3px;
				text-decoration: none;
			}
			.wholesalex-image-banner-v2{
				padding:0;
			}
			.wholesalex-wc-install {
				display: -ms-flexbox;
				display: flex;
				align-items: center;
				background: #fff;
				margin-top: 40px;
				width: calc(100% - 30px);
				border: 1px solid #ccd0d4;
				border-left: 3px solid #46b450;
				padding: 4px;
				border-radius: 4px;
				gap:20px;
			}
			.wholesalex-wc-install img {
				margin-right: 10;
				max-width: 12%;
			}
			.wholesalex-image-banner-v2.wholesalex-wc-install-body{
				position: relative;
			}
			.wholesalex-wc-install-body {
				-ms-flex: 1;
				flex: 1;
			}
			.wholesalex-wc-install-body > div {
				max-width: 450px;
				margin-bottom: 20px;
			}
			.wholesalex-wc-install-body h3 {
				margin-top: 0;
				font-size: 24px;
				margin-bottom: 15px;
			}
			.wholesalex-install-btn {
				margin-top: 15px;
				display: inline-block;
			}
			.wholesalex-wc-install .dashicons{
				display: none;
				animation: dashicons-spin 1s infinite;
				animation-timing-function: linear;
			}
			.wholesalex-wc-install.loading .dashicons {
				display: inline-block;
				margin-top: 12px;
				margin-right: 5px;
			}
			@keyframes dashicons-spin {
				0% {
					transform: rotate( 0deg );
				}
				100% {
					transform: rotate( 360deg );
				}
			}
			.wholesalex-image-banner-v2 .wc-dismiss-notice {
				color: #fff;
				background-color: #000000;
				padding-top: 0px;
				position: absolute;
				right: 0;
				top: 0px;
				padding:5px;
				/* padding: 10px 10px 14px; */
				border-radius: 0 0 0 4px;
				display: inline-block;
				transition: 400ms;
				font-size: 12px;
			}
			.wholesalex-image-banner-v2 .wc-dismiss-notice:focus{
				outline: none;
				box-shadow: unset;
			}
			.wholesalex-btn-image:focus{
				outline: none;
				box-shadow: unset;
			}
			.wc-dismiss-notice {
				position: relative;
				text-decoration: none;
				float: right;
				right: 26px;
			}
			.wc-dismiss-notice .dashicons{
				display: inline-block;
				text-decoration: none;
				animation: none;
			}

			.wholesalex-pro-notice-v2 .wholesalex-wc-install-body h3 {
				font-size: 20px;
				margin-bottom: 5px;
			}
			.wholesalex-pro-notice-v2 .wholesalex-wc-install-body > div {
				max-width: 100%;
				margin-bottom: 10px;
			}
			.wholesalex-pro-notice-v2 .button-hero {
				padding: 8px 14px !important;
				min-height: inherit !important;
				line-height: 1 !important;
				box-shadow: none;
				border: none;
				transition: 400ms;
			}
			.wholesalex-pro-notice-v2 .wholesalex-btn-notice-pro {
				background: #2271b1;
				color: #fff;
			}
			.wholesalex-pro-notice-v2 .wholesalex-btn-notice-pro:hover,
			.wholesalex-pro-notice-v2 .wholesalex-btn-notice-pro:focus {
				background: #185a8f;
			}
			.wholesalex-pro-notice-v2 .button-hero:hover,
			.wholesalex-pro-notice-v2 .button-hero:focus {
				border: none;
				box-shadow: none;
			}
			.wc-dismiss-notice:hover {
				color:red;
			}
			.wc-dismiss-notice .dashicons{
				display: inline-block;
				text-decoration: none;
				animation: none;
				font-size: 16px;
			}
			.revx-notice-wrapper {
				display: flex;
				align-items: center;
				border: 1px solid #C3C4C7;
				background: #FFF;
				width: calc(100% - 18px);
				/* height: 80px; */
				padding: 8px 0px;
				position: relative;
				margin-top: 5px;
			}
			.revx-display-block{
				display: flex;
				width: 100%;
			}
			.wrap .revx-notice-wrapper {
				width: 99.7%;
				position: relative;
			}

			.revx-notice-type-1 .revx-notice-icon img,
			.revx-notice-type-2 .revx-notice-icon img,
			.revx-notice-type-3 .revx-notice-icon img,
			.data_collection_notice .revx-notice-icon img
			{
				max-width: 42px;
				width: 100%;
			}

			.revx-notice-type-4 .revx-notice-icon {
				display: flex;
				flex-direction: column;
				gap: 5px;
				align-items: center;
				margin-left: 10px;
			}
			.revx-notice-type-4 .revx-notice-content-wrapper {
				margin-left: 10px;
			}

			.revx-notice-type-4 img.revx-notice-icon-img {
				max-width: 33px;
			}

			.revx-notice-type-4 img.revx-notice-icon-text-img {
				max-width: 54px;
			}

			.revx-notice-type-5 .revx-notice-content-wrapper {
				flex-direction: row;
				flex-wrap: wrap;
				align-items: center;
			}


			.revx-notice-wrapper:not(:last-child) {
				margin-bottom: 15px;
			}
			span.revx-notice-close-icon.dashicons.dashicons-dismiss {
				font-size: 14px;
			}

			a.revx-notice-close {
				position: absolute;
				/* right: 8px;
				top: 8px; */
				right: 2px;
				top: 5px;
				text-decoration: unset;
				color: #b6b6b6;
				font-family: dashicons;
				font-size: 16px;
				font-style: normal;
				font-weight: 400;
				line-height: 20px;
			}

			a.revx-notice-close:hover {
				color: #b3b3b3;
			}

			.revx-notice-wrapper.revx-notice-type-1 {
				border-left: 3px solid #6C6CFF;
				padding: 6px 0px !important;
			}
			.revx-notice-wrapper.revx-notice-type-2,
			.revx-notice-wrapper.revx-notice-type-4,
			.revx-notice-wrapper.revx-notice-type-5,
			.revx-notice-wrapper.data_collection_notice
			{
				border-left: 3px solid #6C6CFF;
			}
			.revx-notice-wrapper.revx-notice-type-3{
				border-left: 3px solid #2FBF3D;
			}
			.revx-notice-wrapper.welcome_notice{
				border-left: 3px solid #FF5845;
			}
			.revx-notice-content-wrapper {
				display: flex;
				flex-direction: column;
				gap: 8px;
				font-size: 14px;
				line-height: 20px;
				margin-left: 15px;
			}

			.revx-notice-icon {
				margin-left: 15px;
			}

			.revx-notice-close-icon:hover {
				color: #706f6f;
			}

			.revx-notice-buttons {
				display: flex;
				align-items: center;
				gap: 15px;
			}

			a.revx-notice-dont-save-money {
				font-size: 12px;
			}

			.wholesalex_backend_body .components-popover.components-dropdown__content.wholesalex-form-builder-dropdown-content .components-popover__content {
				width: 100% !important;
			}

			/* Banner Image For Halloween Offer */
			.revx-install-body {
				-ms-flex: 1;
				flex: 1;
			}
			.revx-install-body.revx-image-banner{
				padding-bottom: 1px;
				padding-top: 3px;
				padding-left: 3px;
				padding-right: 3px;
			}
			.revx-install-body > div {
				max-width: 450px;
				margin-bottom: 20px;
			}
			.revx-install-body h3 {
				margin: 0;
				font-size: 20px;
				margin-bottom: 10px;
				line-height: 1;
			}
			.revx-pro-notice .wc-install-btn, .wp-core-ui .revx-wc-active-btn {
				display: inline-flex;
				align-items: center;
				padding: 3px 20px;
			}
			.revx-pro-notice.loading .wc-install-btn {
				opacity: 0.7;
				pointer-events: none;
			}
			.revx-pro-notice {
				position: relative;
				border-left: 3px solid #FF176B;
			}
			.revx-pro-notice .revx-install-body h3 {
				font-size: 20px;
				margin-bottom: 5px;
			}
			.revx-pro-notice .revx-install-body > div {
				max-width: 800px;
				margin-bottom: 0;
			}
			.revx-pro-notice .button-hero {
				padding: 8px 14px !important;
				min-height: inherit !important;
				line-height: 1 !important;
				box-shadow: none;
				border: none;
				transition: 400ms;
				background: #46b450;
			}
			.revx-pro-notice .button-hero:hover,
			.wp-core-ui .revx-pro-notice .button-hero:active {
				background: #389e41;
			}
			.revx-pro-notice .revx-btn-notice-pro {
				background: #e5561e;
				color: #fff;
			}
			.revx-pro-notice .revx-btn-notice-pro:hover,
			.revx-pro-notice .revx-btn-notice-pro:focus {
				background: #ce4b18;
			}
			.revx-pro-notice .button-hero:hover,
			.revx-pro-notice .button-hero:focus {
				border: none;
				box-shadow: none;
			}
			.revx-pro-notice .promotional-dismiss-notice {
				background-color: #000000;
				padding-top: 0px;
				position: absolute;
				right: 0;
				top: 0px;
				/* padding: 10px 10px 14px; */
				border-radius: 0 0 0 4px;
				border: 1px solid;
				display: inline-block;
				color: #fff;
			}
			.revx-install-body .promotional-dismiss-notice {
				right: 4px;
				top: 3px;
				border-radius: unset !important;
				/* padding: 2px 8px; */
				text-decoration: none;
				position: absolute;
				/* background-color: #000000; */
				color: #ffffff;
			}

			@media (max-width: 784px) {
				.revx-install-body .promotional-dismiss-notice {
					right: 2px;
					top: 2px;
					padding: 0px 4px;
					font-size: 12px;
				}
				.media-upload-form div.error, .notice{
					line-height: 19px !important;
				}
			}
			.revx-notice-wrapper{
				padding: 0px !important;
			}

			.revx-image-banner a:focus {
				box-shadow: unset !important;
			}


			.revx-halloween-img-banner {
				width: 100%;
			}
		</style>
		<?php
	}
}
