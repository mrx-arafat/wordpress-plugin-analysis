<?php
// Exit if accessed directly
if(!defined('ABSPATH')){
	exit;
}

/**
 * Miscellaneous page
 */
class Fiber_Admin_Miscellaneous{
	public function __construct(){
	}
	
	public function fiad_miscellaneous_init(){
		register_setting(
			'fiad_miscellaneous_group',
			'fiad_miscellaneous',
			[$this, 'sanitize_text_field']
		);
		
		add_settings_section(
			'fiad_miscellaneous_section',
			'<span class="dashicons dashicons-admin-generic"></span> General',
			[$this, 'fiad_section_info'],
			'fiber-admin-miscellaneous'
		);
		
		add_settings_field(
			'enable_auto_update', // id
			'Enable auto update', // title
			[$this, 'fiad_enable_auto_update'], // callback
			'fiber-admin-miscellaneous', // page
			'fiad_miscellaneous_section' // section
		);
		
		add_settings_section(
			'fiad_content_section',
			'<span class="dashicons dashicons-editor-table"></span> Content',
			[$this, 'fiad_section_info'],
			'fiber-admin-miscellaneous'
		);
		
		add_settings_field(
			'revision_number', // id
			'Limit number of revisions', // title
			[$this, 'fiad_revision_number'], // callback
			'fiber-admin-miscellaneous', // page
			'fiad_content_section' // section
		);
		
		add_settings_field(
			'disable_email_converter', // id
			'Disable Convert Email Text to Link', // title
			[$this, 'fiad_disable_email_converter'], // callback
			'fiber-admin-miscellaneous', // page
			'fiad_content_section' // section
		);
		
		add_settings_field(
			'enable_svg', // id
			'Enable SVG', // title
			[$this, 'fiad_enable_svg'], // callback
			'fiber-admin-miscellaneous', // page
			'fiad_content_section' // section
		);
		
		add_settings_field(
			'enable_text_protection', // id
			'Enable Text Protection', // title
			[$this, 'fiad_enable_text_protection'], // callback
			'fiber-admin-miscellaneous', // page
			'fiad_content_section' // section
		);
		
		add_settings_field(
			'enable_image_protection', // id
			'Enable Image Protection', // title
			[$this, 'fiad_enable_image_protection'], // callback
			'fiber-admin-miscellaneous', // page
			'fiad_content_section' // section
		);
		
		add_settings_field(
			'enable_comments', // id
			'Enable Comments', // title
			[$this, 'fiad_enable_comments'], // callback
			'fiber-admin-miscellaneous', // page
			'fiad_content_section' // section
		);
	}
	
	public function fiad_section_info(){
	}
	
	public function fiad_enable_auto_update(){
		?>
        <fieldset>
            <label for="enable_auto_update">
                <input type="checkbox" name="fiad_miscellaneous[enable_auto_update]" id="enable_auto_update"
                       value="yes" <?php checked(esc_attr(fiad_get_miscellaneous_option('enable_auto_update')), 'yes'); ?> />
            </label>
        </fieldset>
		<?php
	}
	
	public function fiad_revision_number(){
		$revision_number = 5;
		if(fiad_get_miscellaneous_option('revision_number')){
			$revision_number = intval(esc_attr(fiad_get_miscellaneous_option('revision_number')));
		}
		?>
        <fieldset>
            <label for="revision_number">
                <input class="small-text" type="number" name="fiad_miscellaneous[revision_number]"
                       id="revision_number" min="1" max="20"
                       value="<?php echo $revision_number; ?>"/>
            </label>
        </fieldset>
		<?php
	}
	
	public function fiad_disable_email_converter(){
		?>
        <fieldset>
            <label for="disable_email_converter">
                <input type="checkbox" name="fiad_miscellaneous[disable_email_converter]"
                       id="disable_email_converter"
                       value="yes" <?php checked(esc_attr(fiad_get_miscellaneous_option('disable_email_converter')), 'yes'); ?> />
            </label>
        </fieldset>
		<?php
	}
	
	public function fiad_enable_svg(){
		?>
        <fieldset>
            <label for="enable_svg">
                <input type="checkbox" name="fiad_miscellaneous[enable_svg]"
                       id="enable_svg"
                       value="yes" <?php checked(esc_attr(fiad_get_miscellaneous_option('enable_svg')), 'yes'); ?> />
            </label>
        </fieldset>
		<?php
	}
	
	public function fiad_enable_text_protection(){
		?>
        <fieldset>
            <label for="enable_text_protection">
                <input type="checkbox" name="fiad_miscellaneous[enable_text_protection]"
                       id="enable_text_protection"
                       value="yes" <?php checked(esc_attr(fiad_get_miscellaneous_option('enable_text_protection')), 'yes'); ?> />
            </label>
        </fieldset>
		<?php
	}
	
	public function fiad_enable_image_protection(){
		?>
        <fieldset>
            <label for="enable_image_protection">
                <input type="checkbox" name="fiad_miscellaneous[enable_image_protection]"
                       id="enable_image_protection"
                       value="yes" <?php checked(esc_attr(fiad_get_miscellaneous_option('enable_image_protection')), 'yes'); ?> />
            </label>
        </fieldset>
		<?php
	}
	
	public function fiad_enable_comments(){
		?>
        <fieldset>
            <label for="enable_comments">
                <input type="checkbox" name="fiad_miscellaneous[enable_comments]"
                       id="enable_comments"
                       value="yes" <?php checked(esc_attr(fiad_get_miscellaneous_option('enable_comments')), 'yes'); ?> />
            </label>
        </fieldset>
		<?php
	}
}