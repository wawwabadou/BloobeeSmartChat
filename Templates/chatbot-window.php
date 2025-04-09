<?php
/**
 * Chatbot window template
 */

// Prevent direct access
defined('ABSPATH') or die('Access denied');

// Get admin settings
$settings = Bloobee_Settings::get_instance();
$chat_title = $settings->get_option('chat_title', __('Chat with us', 'bloobee-smartchat'));
$primary_color = $settings->get_option('primary_color', '#0084ff');
$secondary_color = $settings->get_option('secondary_color', '#f1f0f0');
$enable_subject_selection = $settings->get_option('enable_subject_selection', true);
$subjects = $settings->get_option('subjects', array());
?>

<div id="bloobee-chat-container">
    <!-- Chat icon -->
    <div id="bloobee-chat-icon">
        <img src="<?php echo esc_url($settings->get_option('chat_icon_url', BLOOBEE_SMARTCHAT_URL . 'public/images/chat-icon.png')); ?>" alt="<?php echo esc_attr(__('Chat Icon', 'bloobee-smartchat')); ?>" class="bloobee-chat-icon-img">
    </div>
    
    <!-- Chat window -->
    <div id="bloobee-chat-window" class="hidden">
        <!-- Chat header -->
        <div id="bloobee-chat-header" style="background-color: <?php echo esc_attr($primary_color); ?>;">
            <div class="bloobee-chat-title"><?php echo esc_html($chat_title); ?></div>
            <div id="bloobee-chat-close">&times;</div>
        </div>
        
        <!-- Chat messages container -->
        <div id="bloobee-messages-container">
            <div id="bloobee-messages"></div>
        </div>
        
        <!-- User info form (shown before chat starts) -->
        <div id="bloobee-user-info" class="active">
            <div class="bloobee-info-heading"><?php _e('Please provide your information', 'bloobee-smartchat'); ?></div>
            <input type="text" id="bloobee-user-name" placeholder="<?php _e('Your Name', 'bloobee-smartchat'); ?>" required>
            <input type="email" id="bloobee-user-email" placeholder="<?php _e('Your Email', 'bloobee-smartchat'); ?>" required>
            
            <?php if ($enable_subject_selection && !empty($subjects)) : ?>
            <select id="bloobee-subject" required>
                <option value=""><?php _e('Select a Subject', 'bloobee-smartchat'); ?></option>
                <?php foreach ($subjects as $subject) : ?>
                <option value="<?php echo esc_attr($subject); ?>"><?php echo esc_html($subject); ?></option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>
            
            <button id="bloobee-start-chat" class="bloobee-button" style="background-color: <?php echo esc_attr($primary_color); ?>;"><?php _e('Start Chat', 'bloobee-smartchat'); ?></button>
        </div>
        
        <!-- Chat input container (hidden until chat starts) -->
        <div id="bloobee-input-container" class="hidden">
            <textarea id="bloobee-user-input" placeholder="<?php _e('Type your message...', 'bloobee-smartchat'); ?>" disabled></textarea>
            <button id="bloobee-send-message" class="bloobee-button" style="background-color: <?php echo esc_attr($primary_color); ?>;" disabled>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M22 2L11 13" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M22 2L15 22L11 13L2 9L22 2Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
    </div>
</div>

<!-- Custom inline styles for dynamic colors -->
<style>
:root {
    --bloobee-primary-color: <?php echo esc_attr($primary_color); ?>;
    --bloobee-secondary-color: <?php echo esc_attr($secondary_color); ?>;
}
</style>
