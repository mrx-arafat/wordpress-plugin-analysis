<?php
// Exit if accessed directly
if(!defined('ABSPATH')){
	exit;
}

/**
 * DB Error Page
 */
class Fiber_Admin_DB_Error_Settings{
	
	public function __construct(){
	}
	
	public function fiad_db_error_page_init(){
		register_setting(
			'fiad_db_error_group',
			'fiad_db_error',
			[$this, 'sanitize_text_field']
		);
		
		add_settings_section(
			'fiad_db_error_section',
			'<span class="dashicons dashicons-admin-generic"></span> General',
			[$this, 'fiad_section_info'],
			'fiber-admin-db-error'
		);
		
		add_settings_field(
			'db_error_enable', // id
			'Activate', // title
			[$this, 'fiad_db_error_enable'], // callback
			'fiber-admin-db-error', // page
			'fiad_db_error_section' // section
		);
		
		add_settings_field(
			'db_error_title',
			'Title',
			[$this, 'fiad_db_error_title'],
			'fiber-admin-db-error',
			'fiad_db_error_section'
		);
		
		add_settings_field(
			'db_error_logo',
			'Logo',
			[$this, 'fiad_db_error_logo'],
			'fiber-admin-db-error',
			'fiad_db_error_section'
		);
		
		add_settings_field(
			'db_error_logo_size',
			'Logo size',
			[$this, 'fiad_db_error_logo_size'],
			'fiber-admin-db-error',
			'fiad_db_error_section'
		);
		
		add_settings_field(
			'db_error_bg_color',
			'Background Color',
			[$this, 'fiad_db_error_bg'],
			'fiber-admin-db-error',
			'fiad_db_error_section'
		);
		
		add_settings_field(
			'db_error_message',
			'Error Message',
			[$this, 'fiad_db_error_message'],
			'fiber-admin-db-error',
			'fiad_db_error_section'
		);
		
		add_settings_field(
			'db_error_extra_css',
			'Extra CSS',
			[$this, 'fiad_db_error_extra_css'],
			'fiber-admin-db-error',
			'fiad_db_error_section'
		);
	}
	
	public function fiad_section_info(){
	}
	
	public function fiad_db_error_enable(){
		?>
        <fieldset>
            <label for="db_error_enable">
                <input type="checkbox" name="fiad_db_error[db_error_enable]" id="db_error_enable"
                       value="yes" <?php checked(esc_attr(fiad_get_db_error_option('db_error_enable')), 'yes'); ?> />
            </label>
        </fieldset>
		<?php
	}
	
	public function fiad_db_error_title(){
		$title = fiad_get_db_error_option('db_error_title') ? fiad_get_db_error_option('db_error_title') : get_bloginfo('name') . ' - Database Error';
		?>
        <fieldset>
            <label for="db_error_title">
                <input class="regular-text" type="text" name="fiad_db_error[db_error_title]"
                       value="<?php echo esc_attr($title); ?>"/>
            </label>
        </fieldset>
		<?php
	}
	
	public function fiad_db_error_logo(){
		$logo = fiad_get_db_error_option('db_error_logo');
		?>
        <fieldset class="fiber-admin-input__img">
            <div class="fiber-admin-preview">
                <img src="<?php echo esc_url($logo); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>"/>
            </div>
            <label>
                <input class="regular-text" type="text" name="fiad_db_error[db_error_logo]"
                       placeholder="<?php echo __('Input / Choose your logo image', 'fiber-admin'); ?>"
                       value="<?php echo esc_url($logo); ?>"/>
            </label>
            <button class="button fiber-admin-upload"><?php echo __('Insert / Replace Image', 'fiber-admin'); ?></button>
        </fieldset>
		<?php
	}
	
	public function fiad_db_error_logo_size(){
		?>
        <fieldset class="fiber-admin-input__multiples">
            <label class="fiber-admin-input__label"
                   for="db_error_logo_width"><?php echo __('Width', 'fiber-admin'); ?></label>
            <input class="small-text" type="number" name="fiad_db_error[db_error_logo_width]" id="db_error_logo_width"
                   value="<?php echo esc_attr(fiad_get_db_error_option('db_error_logo_width')); ?>"/> px
            <br/>
            <label class="fiber-admin-input__label"
                   for="db_error_logo_height"><?php echo __('Height', 'fiber-admin'); ?></label>
            <input class="small-text" type="number" name="fiad_db_error[db_error_logo_height]" id="db_error_logo_height"
                   value="<?php echo esc_attr(fiad_get_db_error_option('db_error_logo_height')); ?>"/> px
        </fieldset>
		<?php
	}
	
	public function fiad_db_error_bg(){
		?>
        <fieldset>
            <label>
                <input class="fiber-color-field" name="fiad_db_error[db_error_bg]" type="text"
                       value="<?php echo esc_attr(fiad_get_db_error_option('db_error_bg')); ?>"/>
            </label>
        </fieldset>
		<?php
	}
	
	public function fiad_db_error_message(){
		?>
        <fieldset class="fiber-admin-editor">
			<?php
			$db_error_message      = stripslashes(fiad_get_db_error_option('db_error_message'));
			$default_error_message = "<h4 style='text-align: center;'>503 Service Temporarily Unavailable</h4><p style='text-align: center;'>We're currently experiencing technical issues connecting to the database. Please check back soon.</p>";
			if(empty($db_error_message)){
				$db_error_message = $default_error_message;
			}
			wp_editor($db_error_message, 'db_error_message', [
				'default_editor' => 'tinymce',
				'textarea_name'  => 'fiad_db_error[db_error_message]',
				'media_buttons'  => false,
				'textarea_rows'  => 5,
			]);
			?>
        </fieldset>
		<?php
	}
	
	public function fiad_db_error_extra_css(){
		$id = "db-error-extra-css";
		fiad_code_editor('text/css', $id);
		?>
        <fieldset>
            <textarea
                    id=<?= $id; ?>
                    name="fiad_db_error[db_error_extra_css]"><?php echo esc_html(fiad_get_db_error_option('db_error_extra_css')); ?></textarea>
        </fieldset>
		<?php
	}
}