<?php
/*
Plugin Name: Notification Bar
Plugin URI: https://bubububu.com/
Description: nope nope nope notification bar.
Version: 1.0.0
Author: liquuid
Author URI: https://automattic.com/wordpress-plugins/
License: GPLv2 or later
Text Domain: notificaton-bar
*/

add_action('admin_menu', 'snb_general_settings_page');

function snb_general_settings_page(){
    add_submenu_page(
        'options-general.php',
        __('Notification Bar', 'notification-bar'),
        __('Notifications', 'notification-bar'),
        'manage_options',
        'snb_notification_bar',
        'snb_render_settings_page'
    );
}

function snb_render_settings_page(){
    ?>
    <div class="wrap">
    <h2><?php _e('Notification Bar settings', 'notification-bar') ?></h2>

    <form method="post" action="options.php">

    <?php
    settings_fields('snd_general_settings');
    do_settings_sections('snd_general_settings');

    submit_button();
    ?>
    </form>
    </div>
    <?php
}

add_action('admin_init', 'snb_initialize_settings');
function snb_initialize_settings(){
    add_settings_section(
        'general_section',
        __('General Settings', 'notification-bar'),
        'general_settings_callback',
        'snb_general_settings'
    );

    add_settings_field(
        'notification_text',
        __('Notification Text', 'notification-bar'),
        'text_input_callback',
        'snb_general_settings',
        'general_section',
        array(
            'label_for' => 'notification_text',
            'option_group' => 'snb_general_settings',
            'option_id' => 'notification_text'
        )

    );

    add_settings_field(
        'display_location',
        __('Where will the notification bar display?', 'notification-bar'),
        'radio_input_callback',
        'snb_general_settings',
        'general_section',
        array(
            'label_for' => 'display_location',
            'option_group' => 'snb_general_settings',
            'option_id' => 'display_location',
            'option_description' => 'Display notification bar on bottom of the site',
            'radio_options' => array(
                'display_none' => 'Do not display notification bar',
                'display_top' => 'Display notification bar on the top of site',
                'display_bottom' => 'Display notification bar on the bottom of the site'
            )
        )

    );

    register_setting(
        'snb_general_settings',
        'snb_general_settings'
    );
   
}

function general_settings_callback(){
    _e('Notification Settings', 'notification-bar');
}

function text_input_callback( $text_input){
    $option_group = $text_input['option_group'];
    $option_id = $text_input['option_id'];
    $option_name = "{$option_group}[{$option_id}]";

    $options = get_option($option_group);
    $option_value = isset($options[$option_id]) ? $option[$option_id]: "";

    echo "<input type='text' size='50' id='{$option_id}' name='{$option_name}' value='{$option_value}' />";
}

function radio_input_callback( $text_input){
    $option_group = $radion_input['option_group'];
    $option_id = $radion_input['option_id'];
    $radio_options = $radio_input['radio_options'];
    $option_name = "{$option_group}[{$option_id}]";

    $options = get_option($option_group);
    $option_value = isset($options[$option_id]) ? $option[$option_id]: "";

    $input = '';
    foreach ($radio_options as $radio_option_id => $radio_option_value){
        $input .= "<input type='radio' id='{$radio_option_id}' name='{$option_name}' value='{$radio_option_id}' />";
        $input .= "<label for='{$radio_option_id}'>{$radio_option_value}</label><br />"; 
    }

    echo $input;
}

add_action('wp_footer', 'snb_display_notification_bar');
function snb_display_notification_bar(){
    //if(!null == get_option('snb_general_settings')){
        $options = get_option('snb_general_settings');
        ?>
        <div class="snb-notification-bar <?php echo $options['display_location'];?>">
            <div class="snb-notification-text"><?php echo $options['notification_text']; ?></div>
        </div>
        <?php
    //1}
}