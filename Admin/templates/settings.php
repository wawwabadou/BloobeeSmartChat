<?php
// Prevent direct access
defined('ABSPATH') or die('Access denied');
?>

<div class="wrap bloobee-admin-settings">
    <h1><?php echo esc_html__('Bloobee SmartChat Settings', 'bloobee-smartchat'); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('bloobee_settings_nonce'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="primary_color"><?php echo esc_html__('Primary Color', 'bloobee-smartchat'); ?></label>
                </th>
                <td>
                    <input type="color" 
                        id="primary_color" 
                        name="primary_color" 
                        value="<?php echo esc_attr($settings->get_option('primary_color', '#0084ff')); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="secondary_color"><?php echo esc_html__('Secondary Color', 'bloobee-smartchat'); ?></label>
                </th>
                <td>
                    <input type="color" 
                        id="secondary_color" 
                        name="secondary_color" 
                        value="<?php echo esc_attr($settings->get_option('secondary_color', '#f1f0f0')); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="chat_title"><?php echo esc_html__('Chat Title', 'bloobee-smartchat'); ?></label>
                </th>
                <td>
                    <input type="text" 
                        id="chat_title" 
                        name="chat_title" 
                        class="regular-text"
                        value="<?php echo esc_attr($settings->get_option('chat_title', __('Chat with us', 'bloobee-smartchat'))); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="welcome_message"><?php echo esc_html__('Welcome Message', 'bloobee-smartchat'); ?></label>
                </th>
                <td>
                    <textarea id="welcome_message" 
                        name="welcome_message" 
                        class="large-text" 
                        rows="3"><?php echo esc_textarea($settings->get_option('welcome_message', __('Hello! How can I help you today?', 'bloobee-smartchat'))); ?></textarea>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="display_on_all_pages"><?php echo esc_html__('Display on All Pages', 'bloobee-smartchat'); ?></label>
                </th>
                <td>
                    <input type="checkbox" 
                        id="display_on_all_pages" 
                        name="display_on_all_pages" 
                        value="1" 
                        <?php checked($settings->get_option('display_on_all_pages', true)); ?>>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <input type="submit" name="save_settings" class="button button-primary" value="<?php echo esc_attr__('Save Settings', 'bloobee-smartchat'); ?>">
        </p>
    </form>
</div> 