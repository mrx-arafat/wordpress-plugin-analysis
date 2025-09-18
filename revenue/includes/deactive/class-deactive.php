<?php //phpcs:ignore
/**
 * Plugin Deactivation Handler.
 *
 * @since
 */

namespace REVX\Includes\Deactive;

use REVX\Includes\Durbin\DurbinClient;

defined( 'ABSPATH' ) || exit;

/**
 * Handles plugin deactivation feedback and reporting.
 */
class Deactive {

	private $plugin_slug = 'revenue';

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $pagenow;

		if ( 'plugins.php' === $pagenow ) {
			add_action( 'admin_footer', array( $this, 'get_source_data_callback' ) );
		}
		add_action( 'wp_ajax_revx_deactive_plugin', array( $this, 'send_plugin_data' ) );
	}

	/**
	 * Send plugin deactivation data to remote server.
	 *
	 * @param string|null $type Optional. Unused for now.
	 * @return void
	 */
	public function send_plugin_data() {
		DurbinClient::send( DurbinClient::DEACTIVATE_ACTION );
	}

	/**
	 * Output deactivation modal markup, CSS, and JS.
	 *
	 * @return void
	 */
	public function get_source_data_callback() {
		$this->deactive_container_css();
		$this->deactive_container_js();
		$this->deactive_html_container();
	}

	/**
	 * Get deactivation reasons and field settings.
	 *
	 * @return array[] List of deactivation options.
	 */
	public function get_deactive_settings() {
		return array(
			array(
				'id'    => 'not-working',
				'input' => false,
				'text'  => __( 'The plugin isn’t working properly.', 'revenue' ),
			),
			array(
				'id'    => 'limited-features',
				'input' => false,
				'text'  => __( 'Limited features on the free version.', 'revenue' ),
			),
			array(
				'id'          => 'better-plugin',
				'input'       => true,
				'text'        => __( 'I found a better plugin.', 'revenue' ),
				'placeholder' => __( 'Please share which plugin.', 'revenue' ),
			),
			array(
				'id'    => 'temporary-deactivation',
				'input' => false,
				'text'  => __( "It's a temporary deactivation.", 'revenue' ),
			),
			array(
				'id'          => 'other',
				'input'       => true,
				'text'        => __( 'Other.', 'revenue' ),
				'placeholder' => __( 'Please share the reason.', 'revenue' ),
			),
		);
	}

	/**
	 * Output HTML for the deactivation modal.
	 *
	 * @return void
	 */
	public function deactive_html_container() {
		?>
		<div class="revx-modal" id="revx-deactive-modal">
			<div class="revx-modal-wrap">
			
				<div class="revx-modal-header">
					<h2><?php esc_html_e( 'Quick Feedback', 'revenue' ); ?></h2>
					<button class="revx-modal-cancel"><span class="dashicons dashicons-no-alt"></span></button>
				</div>

				<div class="revx-modal-body">
					<h3><?php esc_html_e( 'If you have a moment, please let us know why you are deactivating WowRevenue:', 'revenue' ); ?></h3>
					<ul class="revx-modal-input">
						<?php foreach ( $this->get_deactive_settings() as $key => $setting ) { ?>
							<li>
								<label>
									<input type="radio" <?php echo 0 == $key ? 'checked="checked"' : ''; ?> id="<?php echo esc_attr( $setting['id'] ); ?>" name="<?php echo esc_attr( $this->plugin_slug ); ?>" value="<?php echo esc_attr( $setting['text'] ); ?>">
									<div class="revx-reason-text"><?php echo esc_html( $setting['text'] ); ?></div>
									<?php if ( isset( $setting['input'] ) && $setting['input'] ) { ?>
										<textarea placeholder="<?php echo esc_attr( $setting['placeholder'] ); ?>" class="revx-reason-input <?php echo $key == 0 ? 'revx-active' : ''; ?> <?php echo esc_html( $setting['id'] ); ?>"></textarea>
									<?php } ?>
								</label>
							</li>
						<?php } ?>
					</ul>
				</div>

				<div class="revx-modal-footer">
					<a class="revx-modal-submit revx-btn revx-btn-primary" href="#"><?php esc_html_e( 'Submit & Deactivate', 'revenue' ); ?><span class="dashicons dashicons-update rotate"></span></a>
					<a class="revx-modal-deactive" href="#"><?php esc_html_e( 'Skip & Deactivate', 'revenue' ); ?></a>
				</div>
				
			</div>
		</div>
		<?php
	}

	/**
	 * Output inline CSS for the modal.
	 *
	 * @return void
	 */
	public function deactive_container_css() {
		?>
		<style type="text/css">
			.revx-modal {
				position: fixed;
				z-index: 99999;
				top: 0;
				right: 0;
				bottom: 0;
				left: 0;
				background: rgba(0,0,0,0.5);
				display: none;
				box-sizing: border-box;
				overflow: scroll;
			}
			.revx-modal * {
				box-sizing: border-box;
			}
			.revx-modal.modal-active {
				display: block;
			}
			.revx-modal-wrap {
				max-width: 870px;
				width: 100%;
				position: relative;
				margin: 10% auto;
				background: #fff;
			}
			.revx-reason-input{
				display: none;
			}
			.revx-reason-input.revx-active{
				display: block;
			}
			.rotate{
				animation: rotate 1.5s linear infinite; 
			}
			@keyframes rotate{
				to{ transform: rotate(360deg); }
			}
			.revx-popup-rotate{
				animation: popupRotate 1s linear infinite; 
			}
			@keyframes popupRotate{
				to{ transform: rotate(360deg); }
			}
			#revx-deactive-modal {
				background: rgb(0 0 0 / 85%);
				overflow: hidden;
			}
			#revx-deactive-modal .revx-modal-wrap {
				max-width: 570px;
				border-radius: 5px;
				margin: 5% auto;
				overflow: hidden
			}
			#revx-deactive-modal .revx-modal-header {
				padding: 17px 30px;
				border-bottom: 1px solid #ececec;
				display: flex;
				align-items: center;
				background: #f5f5f5;
			}
			#revx-deactive-modal .revx-modal-header .revx-modal-cancel {
				padding: 0;
				border-radius: 100px;
				border: 1px solid #b9b9b9;
				background: none;
				color: #b9b9b9;
				cursor: pointer;
				transition: 400ms;
			}
			#revx-deactive-modal .revx-modal-header .revx-modal-cancel:focus {
				color: red;
				border: 1px solid red;
				outline: 0;
			}
			#revx-deactive-modal .revx-modal-header .revx-modal-cancel:hover {
				color: red;
				border: 1px solid red;
			}
			#revx-deactive-modal .revx-modal-header h2 {
				margin: 0;
				padding: 0;
				flex: 1;
				line-height: 1;
				font-size: 20px;
				text-transform: uppercase;
				color: #8e8d8d;
			}
			#revx-deactive-modal .revx-modal-body {
				padding: 25px 30px;
			}
			#revx-deactive-modal .revx-modal-body h3{
				padding: 0;
				margin: 0;
				line-height: 1.4;
				font-size: 15px;
			}
			#revx-deactive-modal .revx-modal-body ul {
				margin: 25px 0 10px;
			}
			#revx-deactive-modal .revx-modal-body ul li {
				display: flex;
				margin-bottom: 10px;
				color: #807d7d;
			}
			#revx-deactive-modal .revx-modal-body ul li:last-child {
				margin-bottom: 0;
			}
			#revx-deactive-modal .revx-modal-body ul li label {
				align-items: center;
				width: 100%;
			}
			#revx-deactive-modal .revx-modal-body ul li label input {
				padding: 0 !important;
				margin: 0;
				display: inline-block;
			}
			#revx-deactive-modal .revx-modal-body ul li label textarea {
				margin-top: 8px;
				width: 100% !important;
			}
			#revx-deactive-modal .revx-modal-body ul li label .revx-reason-text {
				margin-left: 8px;
				display: inline-block;
			}
			#revx-deactive-modal .revx-modal-footer {
				padding: 0 30px 30px 30px;
				display: flex;
				align-items: center;
			}
			#revx-deactive-modal .revx-modal-footer .revx-modal-submit {
				display: flex;
				align-items: center;
				padding: 12px 22px;
				border-radius: 3px;
				background: #00a464;
				color: #fff;
				font-size: 16px;
				font-weight: 600;
				text-decoration: none;
			}
			#revx-deactive-modal .revx-modal-footer .revx-modal-submit span {
				margin-left: 4px;
				display: none;
			}
			#revx-deactive-modal .revx-modal-footer .revx-modal-submit.loading span {
				display: block;
			}
			#revx-deactive-modal .revx-modal-footer .revx-modal-deactive {
				margin-left: auto;
				color: #c5c5c5;
				text-decoration: none;
			}
			.wpxpo-btn-tracking-notice {
				display: flex;
				align-items: center;
				flex-wrap: wrap;
				padding: 5px 0;
			}
			.wpxpo-btn-tracking-notice .wpxpo-btn-tracking {
				margin: 0 5px;
				text-decoration: none;
			}
		</style>
		<?php
	}

	/**
	 * Output inline JavaScript for the modal logic.
	 *
	 * @return void
	 */
	public function deactive_container_js() {
		?>
		<script type="text/javascript">
			jQuery( document ).ready( function( $ ) {
				'use strict';

				// Modal Radio Input Click Action
				$('.revx-modal-input input[type=radio]').on( 'change', function(e) {
					$('.revx-reason-input').removeClass('revx-active');
					$('.revx-modal-input').find( '.'+$(this).attr('id') ).addClass('revx-active');
				});

				// Modal Cancel Click Action
				$( document ).on( 'click', '.revx-modal-cancel', function(e) {
					$( '#revx-deactive-modal' ).removeClass( 'modal-active' );
				});
				
				$(document).on('click', function(event) {
					const $popup = $('#revx-deactive-modal');
					const $modalWrap = $popup.find('.revx-modal-wrap');

					if ( !$modalWrap.is(event.target) && $modalWrap.has(event.target).length === 0 && $popup.hasClass('modal-active')) {
						$popup.removeClass('modal-active');
					}
				});

				// Deactivate Button Click Action
				$( document ).on( 'click', '#deactivate-revenue', function(e) {
					e.preventDefault();
					e.stopPropagation();
					$( '#revx-deactive-modal' ).addClass( 'modal-active' );
					$( '.revx-modal-deactive' ).attr( 'href', $(this).attr('href') );
					$( '.revx-modal-submit' ).attr( 'href', $(this).attr('href') );
				});

				// Submit to Remote Server
				$( document ).on( 'click', '.revx-modal-submit', function(e) {
					e.preventDefault();
					
					$(this).addClass('loading');
					const url = $(this).attr('href')

					$.ajax({
						url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
						type: 'POST',
						data: { 
							action: 'revx_deactive_plugin',
							cause_id: $('#revx-deactive-modal input[type=radio]:checked').attr('id'),
							cause_title: $('#revx-deactive-modal .revx-modal-input input[type=radio]:checked').val(),
							cause_details: $('#revx-deactive-modal .revx-reason-input.revx-active').val()
						},
						success: function (data) {
							$( '#revx-deactive-modal' ).removeClass( 'modal-active' );
							window.location.href = url;
						},
						error: function(xhr) {
							console.log( 'Error occured. Please try again' + xhr.statusText + xhr.responseText );
						},
					});

				});

			});
		</script>
		<?php
	}
}
