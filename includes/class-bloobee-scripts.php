<?php
/**
 * Bloobee Scripts Class
 * Handles script and style enqueuing
 */

// Prevent direct access
defined('ABSPATH') or die('Access denied');

class Bloobee_Scripts {
    /**
     * Instance of this class
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        // Enqueue jQuery
        wp_enqueue_script('jquery');
        
        // Enqueue chat script
        wp_enqueue_script(
            'bloobee-chat',
            BLOOBEE_SMARTCHAT_URL . 'Public/js/chat.js',
            array('jquery'),
            BLOOBEE_SMARTCHAT_VERSION,
            true
        );
        
        // Enqueue styles
        wp_enqueue_style(
            'bloobee-chat-styles',
            BLOOBEE_SMARTCHAT_URL . 'Public/css/styles.css',
            array(),
            BLOOBEE_SMARTCHAT_VERSION
        );
        
        // Localize script
        wp_localize_script(
            'bloobee-chat',
            'bloobeeAjax',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('bloobee_smartchat_nonce'),
                'settings' => array(
                    'chat_icon_url' => BLOOBEE_SMARTCHAT_URL . 'Public/images/bloobee.png',
                    'sound_url' => BLOOBEE_SMARTCHAT_URL . 'Public/sounds/message.mp3'
                )
            )
        );
    }
} 