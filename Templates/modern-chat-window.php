<?php
/**
 * Template Name: Modern Chat Window
 * 
 * This template provides a modern Messenger-style chat interface
 * with enhanced user experience and animations.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get plugin settings
$settings = get_option('bloobee_smartchat_settings', array());
$primary_color = isset($settings['primary_color']) ? $settings['primary_color'] : '#2196F3';
$secondary_color = isset($settings['secondary_color']) ? $settings['secondary_color'] : '#1976D2';
$chat_title = isset($settings['chat_title']) ? $settings['chat_title'] : 'Chat with us';
$welcome_message = isset($settings['welcome_message']) ? $settings['welcome_message'] : 'Hello! How can we help you today?';
$require_subject = isset($settings['require_subject']) ? (bool)$settings['require_subject'] : false;
?>

<div id="bloobee-chat-container" class="bloobee-chat-container">
    <!-- Chat Icon -->
    <div id="bloobee-chat-icon" class="bloobee-chat-icon">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#ffffff">
            <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z"/>
        </svg>
    </div>

    <!-- Chat Window -->
    <div id="bloobee-chat-window" class="bloobee-chat-window hidden">
        <!-- Chat Header -->
        <div id="bloobee-chat-header" class="bloobee-chat-header">
            <div class="bloobee-chat-title"><?php echo esc_html($chat_title); ?></div>
            <button id="bloobee-chat-close" class="bloobee-chat-close" aria-label="Close chat">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                </svg>
            </button>
        </div>

        <!-- Messages Container -->
        <div id="bloobee-messages-container" class="bloobee-messages-container">
            <div id="bloobee-messages" class="bloobee-messages">
                <!-- Messages will be dynamically added here -->
            </div>
        </div>

        <!-- User Information Form -->
        <div id="bloobee-user-info" class="bloobee-user-info active">
            <div class="bloobee-form-group">
                <label for="bloobee-user-name">Name</label>
                <input type="text" id="bloobee-user-name" class="bloobee-input" placeholder="Your name" required>
            </div>
            <div class="bloobee-form-group">
                <label for="bloobee-user-email">Email</label>
                <input type="email" id="bloobee-user-email" class="bloobee-input" placeholder="Your email" required>
            </div>
            <?php if ($require_subject): ?>
            <div class="bloobee-form-group">
                <label for="bloobee-subject">Subject</label>
                <select id="bloobee-subject" class="bloobee-select" required>
                    <option value="">Select a subject</option>
                    <option value="general">General Inquiry</option>
                    <option value="support">Technical Support</option>
                    <option value="billing">Billing Question</option>
                    <option value="feedback">Feedback</option>
                </select>
            </div>
            <?php endif; ?>
            <button id="bloobee-start-chat" class="bloobee-button">Start Chat</button>
        </div>

        <!-- Chat Input -->
        <div id="bloobee-input-container" class="bloobee-input-container hidden">
            <div class="bloobee-input-wrapper">
                <textarea id="bloobee-user-input" class="bloobee-user-input" placeholder="Type your message..." disabled></textarea>
                <button id="bloobee-send-message" class="bloobee-send-button" disabled>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Dynamic styles based on settings */
:root {
    --bloobee-primary: <?php echo esc_attr($primary_color); ?>;
    --bloobee-secondary: <?php echo esc_attr($secondary_color); ?>;
}
</style> 