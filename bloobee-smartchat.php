<?php
/**
 * Plugin Name: Bloobee SmartChat
 * Plugin URI: https://example.com/bloobee-smartchat
 * Description: An advanced AI-powered chatbot for WordPress with elegant Messenger-style UI and smart features.
 * Version: 2.0.0
 * Author: Updated by AI Assistant
 * Author URI: https://example.com
 * Text Domain: bloobee-smartchat
 * Domain Path: /languages
 */

// Prevent direct access
defined('ABSPATH') or die('Access denied');

// Define plugin constants
define('BLOOBEE_SMARTCHAT_VERSION', '2.0.0');
define('BLOOBEE_SMARTCHAT_DIR', plugin_dir_path(__FILE__));
define('BLOOBEE_SMARTCHAT_URL', plugin_dir_url(__FILE__));
define('BLOOBEE_SMARTCHAT_BASENAME', plugin_basename(__FILE__));

/**
 * Main Bloobee SmartChat class
 */
class BloobeeSmartChat {
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
        // Include required files
        $this->includes();
        
        // Initialize the plugin
        add_action('plugins_loaded', array($this, 'init'));
        
        // Register activation/deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    /**
     * Include required files
     */
    private function includes() {
        // Include main chatbot agent
        require_once BLOOBEE_SMARTCHAT_DIR . 'includes/bloobee-smartchat.php';
        
        // Include settings handler
        require_once BLOOBEE_SMARTCHAT_DIR . 'includes/class-bloobee-settings.php';
        
        // Include analytics
        require_once BLOOBEE_SMARTCHAT_DIR . 'includes/class-bloobee-analytics.php';
        
        // Include multilingual support
        require_once BLOOBEE_SMARTCHAT_DIR . 'includes/class-bloobee-multilingual.php';
        
        // Include admin interface
        if (is_admin()) {
            require_once BLOOBEE_SMARTCHAT_DIR . 'Admin/class-bloobee-admin.php';
        }
    }

    /**
     * Initialize the plugin
     */
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('bloobee-smartchat', false, dirname(BLOOBEE_SMARTCHAT_BASENAME) . '/languages');
        
        // Initialize settings
        $settings = Bloobee_Settings::get_instance();
        
        // Initialize components
        if (is_admin()) {
            $admin = Bloobee_Admin::get_instance();
        }
        
        // Register scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'register_public_assets'));
        
        // Register AJAX handlers
        add_action('wp_ajax_bloobee_get_response', array($this, 'handle_ajax_response'));
        add_action('wp_ajax_nopriv_bloobee_get_response', array($this, 'handle_ajax_response'));
        
        // Register analytics AJAX handlers
        add_action('wp_ajax_bloobee_track_event', array($this, 'handle_analytics_event'));
        add_action('wp_ajax_nopriv_bloobee_track_event', array($this, 'handle_analytics_event'));
        
        // Add shortcode for chatbot display
        add_shortcode('bloobee_chat', array($this, 'chatbot_shortcode'));
        
        // Add chat to footer (if enabled in settings)
        if ($settings->get_option('display_on_all_pages', true)) {
            add_action('wp_footer', array($this, 'display_chatbot'));
        }
    }

    /**
     * Register and enqueue public-facing scripts and styles
     */
    public function register_public_assets() {
        // Register styles
        wp_register_style(
            'bloobee-smartchat-styles',
            BLOOBEE_SMARTCHAT_URL . 'public/css/styles.css',
            array(),
            BLOOBEE_SMARTCHAT_VERSION
        );
        
        // Register scripts
        wp_register_script(
            'bloobee-smartchat-js',
            BLOOBEE_SMARTCHAT_URL . 'public/js/chatbot.js',
            array('jquery'),
            BLOOBEE_SMARTCHAT_VERSION,
            true
        );
        
        // Localize script with AJAX URL and settings
        wp_localize_script('bloobee-smartchat-js', 'bloobeeAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bloobee_smartchat_nonce'),
            'settings' => $this->get_frontend_settings()
        ));
    }

    /**
     * Get frontend settings
     */
    private function get_frontend_settings() {
        $settings = Bloobee_Settings::get_instance();
        
        // Get customization options
        $primary_color = $settings->get_option('primary_color', '#0084ff');
        $secondary_color = $settings->get_option('secondary_color', '#f1f0f0');
        $chat_title = $settings->get_option('chat_title', __('Chat with us', 'bloobee-smartchat'));
        $welcome_message = $settings->get_option('welcome_message', __('Hello! How can I help you today?', 'bloobee-smartchat'));
        
        return array(
            'primary_color' => $primary_color,
            'secondary_color' => $secondary_color,
            'chat_title' => $chat_title,
            'welcome_message' => $welcome_message,
            'chat_icon_url' => $settings->get_option('chat_icon_url', BLOOBEE_SMARTCHAT_URL . 'public/images/chat-icon.png'),
            'enable_typing_indicator' => $settings->get_option('enable_typing_indicator', true),
            'typing_delay' => $settings->get_option('typing_delay', 1000),
            'auto_open' => $settings->get_option('auto_open', false),
            'auto_open_delay' => $settings->get_option('auto_open_delay', 5000),
            'enable_sound' => $settings->get_option('enable_sound', true),
            'enable_analytics' => $settings->get_option('enable_analytics', true),
            'enable_sentiment_analysis' => $settings->get_option('enable_sentiment_analysis', true),
            'enable_multilingual' => $settings->get_option('enable_multilingual', true),
            'language' => $settings->get_option('language', 'auto'),
            'qa_pairs' => $this->get_qa_pairs(),
            'subjects' => $this->get_subjects()
        );
    }

    /**
     * Get Q&A pairs
     */
    private function get_qa_pairs() {
        $settings = Bloobee_Settings::get_instance();
        return $settings->get_option('qa_pairs', array());
    }

    /**
     * Get subjects
     */
    private function get_subjects() {
        $settings = Bloobee_Settings::get_instance();
        return $settings->get_option('subjects', array());
    }

    /**
     * AJAX handler for getting chatbot responses
     */
    public function handle_ajax_response() {
        // Verify nonce
        check_ajax_referer('bloobee_smartchat_nonce', 'nonce');
        
        // Get message from request
        $message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
        
        if (empty($message)) {
            wp_send_json_error(array('error' => __('Empty message', 'bloobee-smartchat')));
            return;
        }
        
        // Get subject if provided
        $subject = isset($_POST['subject']) ? sanitize_text_field($_POST['subject']) : '';
        
        // Get agent instance
        $agent = Bloobee_Agent::get_instance();
        
        // Get response
        $response = $agent->get_response($message, $subject);
        
        // Track conversation in analytics
        if (Bloobee_Settings::get_instance()->get_option('enable_analytics', true)) {
            $analytics = Bloobee_Analytics::get_instance();
            $analytics->track_conversation($message, $response);
        }
        
        wp_send_json_success(array('response' => $response));
    }

    /**
     * AJAX handler for tracking analytics events
     */
    public function handle_analytics_event() {
        // Verify nonce
        check_ajax_referer('bloobee_smartchat_nonce', 'nonce');
        
        // Get event details
        $event_type = isset($_POST['event_type']) ? sanitize_text_field($_POST['event_type']) : '';
        $event_data = isset($_POST['event_data']) ? sanitize_text_field($_POST['event_data']) : '';
        
        if (empty($event_type)) {
            wp_send_json_error(array('error' => __('Invalid event', 'bloobee-smartchat')));
            return;
        }
        
        // Track event
        $analytics = Bloobee_Analytics::get_instance();
        $analytics->track_event($event_type, $event_data);
        
        wp_send_json_success();
    }

    /**
     * Shortcode for displaying the chatbot
     */
    public function chatbot_shortcode($atts) {
        // Extract attributes
        $atts = shortcode_atts(array(
            'title' => '',
            'welcome_message' => '',
            'primary_color' => '',
            'secondary_color' => ''
        ), $atts, 'bloobee_chat');
        
        // Enqueue assets
        wp_enqueue_style('bloobee-smartchat-styles');
        wp_enqueue_script('bloobee-smartchat-js');
        
        // Set custom attributes if provided
        if (!empty($atts['title'])) {
            wp_add_inline_script('bloobee-smartchat-js', 'bloobeeAjax.settings.chat_title = "' . esc_js($atts['title']) . '";');
        }
        
        if (!empty($atts['welcome_message'])) {
            wp_add_inline_script('bloobee-smartchat-js', 'bloobeeAjax.settings.welcome_message = "' . esc_js($atts['welcome_message']) . '";');
        }
        
        if (!empty($atts['primary_color'])) {
            wp_add_inline_script('bloobee-smartchat-js', 'bloobeeAjax.settings.primary_color = "' . esc_js($atts['primary_color']) . '";');
        }
        
        if (!empty($atts['secondary_color'])) {
            wp_add_inline_script('bloobee-smartchat-js', 'bloobeeAjax.settings.secondary_color = "' . esc_js($atts['secondary_color']) . '";');
        }
        
        // Get chatbot HTML
        ob_start();
        $this->get_template('chatbot-window');
        return ob_get_clean();
    }

    /**
     * Display chatbot in footer
     */
    public function display_chatbot() {
        // Enqueue assets
        wp_enqueue_style('bloobee-smartchat-styles');
        wp_enqueue_script('bloobee-smartchat-js');
        
        // Display chatbot
        $this->get_template('chatbot-window');
    }

    /**
     * Get template
     */
    private function get_template($template_name) {
        $template_path = BLOOBEE_SMARTCHAT_DIR . 'templates/' . $template_name . '.php';
        
        if (file_exists($template_path)) {
            include $template_path;
        }
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Create necessary database tables
        Bloobee_Analytics::get_instance()->create_tables();
        
        // Set default options
        $default_settings = array(
            'primary_color' => '#0084ff',
            'secondary_color' => '#f1f0f0',
            'chat_title' => __('Chat with us', 'bloobee-smartchat'),
            'welcome_message' => __('Hello! How can I help you today?', 'bloobee-smartchat'),
            'display_on_all_pages' => true,
            'enable_typing_indicator' => true,
            'typing_delay' => 1000,
            'auto_open' => false,
            'auto_open_delay' => 5000,
            'enable_sound' => true,
            'enable_analytics' => true,
            'enable_sentiment_analysis' => true,
            'enable_multilingual' => true,
            'language' => 'auto',
            'qa_pairs' => array(
                array(
                    'question' => __('What is Bloobee SmartChat?', 'bloobee-smartchat'),
                    'answer' => __('Bloobee SmartChat is an AI-powered chatbot for WordPress sites that helps answer visitor questions and provide support.', 'bloobee-smartchat')
                ),
                array(
                    'question' => __('How do I contact support?', 'bloobee-smartchat'),
                    'answer' => __('You can contact our support team by sending an email to support@example.com or by filling out the contact form on our website.', 'bloobee-smartchat')
                )
            ),
            'subjects' => array(
                __('General Questions', 'bloobee-smartchat'),
                __('Technical Support', 'bloobee-smartchat'),
                __('Pricing & Plans', 'bloobee-smartchat')
            )
        );
        
        $settings = Bloobee_Settings::get_instance();
        
        foreach ($default_settings as $key => $value) {
            if ($settings->get_option($key) === false) {
                $settings->update_option($key, $value);
            }
        }
        
        // Set activation flag
        update_option('bloobee_smartchat_activated', true);
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Nothing to do here
    }
}

// Initialize the plugin
BloobeeSmartChat::get_instance();
