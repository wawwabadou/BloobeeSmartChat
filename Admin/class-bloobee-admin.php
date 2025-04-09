<?php
/**
 * Bloobee Admin Class
 * Handles admin interface and settings
 */

// Prevent direct access
defined('ABSPATH') or die('Access denied');

class Bloobee_Admin {
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
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'register_admin_assets'));
        
        // Add settings link to plugin page
        add_filter('plugin_action_links_' . BLOOBEE_SMARTCHAT_BASENAME, array($this, 'add_settings_link'));
        
        // Register AJAX handlers for admin
        add_action('wp_ajax_bloobee_save_settings', array($this, 'ajax_save_settings'));
        add_action('wp_ajax_bloobee_get_analytics', array($this, 'ajax_get_analytics'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            __('Bloobee SmartChat', 'bloobee-smartchat'),
            __('Bloobee SmartChat', 'bloobee-smartchat'),
            'manage_options',
            'bloobee-smartchat',
            array($this, 'render_settings_page'),
            'dashicons-format-chat',
            30
        );
        
        // Settings submenu
        add_submenu_page(
            'bloobee-smartchat',
            __('Settings', 'bloobee-smartchat'),
            __('Settings', 'bloobee-smartchat'),
            'manage_options',
            'bloobee-smartchat',
            array($this, 'render_settings_page')
        );
        
        // Q&A submenu
        add_submenu_page(
            'bloobee-smartchat',
            __('Q&A Pairs', 'bloobee-smartchat'),
            __('Q&A Pairs', 'bloobee-smartchat'),
            'manage_options',
            'bloobee-qa',
            array($this, 'render_qa_page')
        );
        
        // Subjects submenu
        add_submenu_page(
            'bloobee-smartchat',
            __('Subjects', 'bloobee-smartchat'),
            __('Subjects', 'bloobee-smartchat'),
            'manage_options',
            'bloobee-subjects',
            array($this, 'render_subjects_page')
        );
        
        // Analytics submenu
        add_submenu_page(
            'bloobee-smartchat',
            __('Analytics', 'bloobee-smartchat'),
            __('Analytics', 'bloobee-smartchat'),
            'manage_options',
            'bloobee-analytics',
            array($this, 'render_analytics_page')
        );
        
        // Chat History submenu
        add_submenu_page(
            'bloobee-smartchat',
            __('Chat History', 'bloobee-smartchat'),
            __('Chat History', 'bloobee-smartchat'),
            'manage_options',
            'bloobee-history',
            array($this, 'render_history_page')
        );
    }
    
    /**
     * Register admin scripts and styles
     */
    public function register_admin_assets($hook) {
        // Only load on plugin pages
        if (strpos($hook, 'bloobee-smartchat') === false) {
            return;
        }
        
        // Register styles
        wp_enqueue_style(
            'bloobee-admin-styles',
            BLOOBEE_SMARTCHAT_URL . 'admin/css/admin.css',
            array(),
            BLOOBEE_SMARTCHAT_VERSION
        );
        
        // Register scripts
        wp_enqueue_script(
            'bloobee-admin-js',
            BLOOBEE_SMARTCHAT_URL . 'admin/js/admin.js',
            array('jquery', 'wp-color-picker'),
            BLOOBEE_SMARTCHAT_VERSION,
            true
        );
        
        // Add color picker
        wp_enqueue_style('wp-color-picker');
        
        // Localize script
        wp_localize_script('bloobee-admin-js', 'bloobeeAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bloobee_admin_nonce'),
            'strings' => array(
                'saveSuccess' => __('Settings saved successfully!', 'bloobee-smartchat'),
                'saveError' => __('Error saving settings. Please try again.', 'bloobee-smartchat'),
                'confirmDelete' => __('Are you sure you want to delete this item?', 'bloobee-smartchat'),
                'confirmReset' => __('Are you sure you want to reset all settings to default? This cannot be undone.', 'bloobee-smartchat')
            )
        ));
        
        // Enqueue Chart.js for analytics page
        if ($hook === 'bloobee-smartchat_page_bloobee-smartchat-analytics') {
            wp_enqueue_script(
                'chart-js',
                'https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js',
                array(),
                '3.7.1',
                true
            );
        }
    }
    
    /**
     * Add settings link to plugin page
     */
    public function add_settings_link($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=bloobee-smartchat') . '">' . __('Settings', 'bloobee-smartchat') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        // Get settings
        $settings = Bloobee_Settings::get_instance();
        
        // Get current settings
        $primary_color = $settings->get_option('primary_color', '#0084ff');
        $secondary_color = $settings->get_option('secondary_color', '#f1f0f0');
        $chat_title = $settings->get_option('chat_title', __('Chat with us', 'bloobee-smartchat'));
        $welcome_message = $settings->get_option('welcome_message', __('Hello! How can I help you today?', 'bloobee-smartchat'));
        $display_on_all_pages = $settings->get_option('display_on_all_pages', true);
        $enable_typing_indicator = $settings->get_option('enable_typing_indicator', true);
        $typing_delay = $settings->get_option('typing_delay', 1000);
        $auto_open = $settings->get_option('auto_open', false);
        $auto_open_delay = $settings->get_option('auto_open_delay', 5000);
        $enable_sound = $settings->get_option('enable_sound', true);
        $enable_analytics = $settings->get_option('enable_analytics', true);
        $enable_sentiment_analysis = $settings->get_option('enable_sentiment_analysis', true);
        $enable_multilingual = $settings->get_option('enable_multilingual', true);
        $language = $settings->get_option('language', 'auto');
        $chat_icon_url = $settings->get_option('chat_icon_url', BLOOBEE_SMARTCHAT_URL . 'public/images/chat-icon.png');
        
        // Get available languages
        $available_languages = Bloobee_Multilingual::get_instance()->get_available_languages();
        
        // Include settings template
        include BLOOBEE_SMARTCHAT_DIR . 'Admin/templates/settings.php';
    }
    
    /**
     * Render Q&A page
     */
    public function render_qa_page() {
        // Get settings
        $settings = Bloobee_Settings::get_instance();
        
        // Get Q&A pairs
        $qa_pairs = $settings->get_option('qa_pairs', array());
        
        // Include Q&A template
        include BLOOBEE_SMARTCHAT_DIR . 'Admin/templates/qa-pairs.php';
    }
    
    /**
     * Render subjects page
     */
    public function render_subjects_page() {
        // Get settings
        $settings = Bloobee_Settings::get_instance();
        
        // Get subjects
        $subjects = $settings->get_option('subjects', array());
        $enable_subject_selection = $settings->get_option('enable_subject_selection', true);
        
        // Include subjects template
        include BLOOBEE_SMARTCHAT_DIR . 'Admin/templates/subjects.php';
    }
    
    /**
     * Render analytics page
     */
    public function render_analytics_page() {
        // Check if analytics is enabled
        $settings = Bloobee_Settings::get_instance();
        $enable_analytics = $settings->get_option('enable_analytics', true);
        
        if (!$enable_analytics) {
            echo '<div class="wrap"><h1>' . __('Bloobee SmartChat Analytics', 'bloobee-smartchat') . '</h1>';
            echo '<div class="notice notice-warning"><p>' . __('Analytics is currently disabled. Enable it in the Settings page to collect and view data.', 'bloobee-smartchat') . '</p></div>';
            echo '<p><a href="' . admin_url('admin.php?page=bloobee-smartchat') . '" class="button button-primary">' . __('Go to Settings', 'bloobee-smartchat') . '</a></p>';
            echo '</div>';
            return;
        }
        
        // Get analytics data
        $analytics = Bloobee_Analytics::get_instance();
        $period = isset($_GET['period']) ? sanitize_text_field($_GET['period']) : '7days';
        $analytics_data = $analytics->get_analytics_data($period);
        
        // Include analytics template
        include BLOOBEE_SMARTCHAT_DIR . 'Admin/templates/analytics.php';
    }
    
    /**
     * Render chat history page
     */
    public function render_history_page() {
        // Check if analytics is enabled
        $settings = Bloobee_Settings::get_instance();
        $enable_analytics = $settings->get_option('enable_analytics', true);
        
        if (!$enable_analytics) {
            echo '<div class="wrap"><h1>' . __('Bloobee SmartChat History', 'bloobee-smartchat') . '</h1>';
            echo '<div class="notice notice-warning"><p>' . __('Analytics is currently disabled. Enable it in the Settings page to collect and view chat history.', 'bloobee-smartchat') . '</p></div>';
            echo '<p><a href="' . admin_url('admin.php?page=bloobee-smartchat') . '" class="button button-primary">' . __('Go to Settings', 'bloobee-smartchat') . '</a></p>';
            echo '</div>';
            return;
        }
        
        // Handle delete action
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['conversation'])) {
            $this->delete_conversation(intval($_GET['conversation']));
            wp_redirect(admin_url('admin.php?page=bloobee-history&deleted=1'));
            exit;
        }
        
        // Include history template
        include BLOOBEE_SMARTCHAT_DIR . 'Admin/templates/history.php';
    }
    
    /**
     * Delete a conversation
     */
    private function delete_conversation($conversation_id) {
        // Check nonce
        check_admin_referer('delete_conversation');
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            return false;
        }
        
        global $wpdb;
        $table_conversations = $wpdb->prefix . 'bloobee_conversations';
        
        // Get conversation info
        $conversation = $this->get_conversation_info($conversation_id);
        
        if (!$conversation) {
            return false;
        }
        
        // Delete all messages for this user from the same day
        $date = date('Y-m-d', strtotime($conversation->created_at));
        return $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_conversations WHERE user_id = %s AND DATE(created_at) = %s",
            $conversation->user_id, $date
        ));
    }
    
    /**
     * AJAX handler for saving settings
     */
    public function ajax_save_settings() {
        // Check nonce
        check_ajax_referer('bloobee_admin_nonce', 'nonce');
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to access this page.', 'bloobee-smartchat')));
            return;
        }
        
        // Get settings data
        $settings_data = isset($_POST['settings']) ? $_POST['settings'] : array();
        
        if (empty($settings_data)) {
            wp_send_json_error(array('message' => __('No settings data received.', 'bloobee-smartchat')));
            return;
        }
        
        // Sanitize and save settings
        $settings = Bloobee_Settings::get_instance();
        $sanitized_settings = array();
        
        foreach ($settings_data as $key => $value) {
            switch ($key) {
                case 'primary_color':
                case 'secondary_color':
                    $sanitized_settings[$key] = sanitize_hex_color($value);
                    break;
                
                case 'chat_title':
                case 'welcome_message':
                    $sanitized_settings[$key] = sanitize_text_field($value);
                    break;
                
                case 'display_on_all_pages':
                case 'enable_typing_indicator':
                case 'auto_open':
                case 'enable_sound':
                case 'enable_analytics':
                case 'enable_sentiment_analysis':
                case 'enable_multilingual':
                case 'enable_subject_selection':
                    $sanitized_settings[$key] = (bool) $value;
                    break;
                
                case 'typing_delay':
                case 'auto_open_delay':
                    $sanitized_settings[$key] = absint($value);
                    break;
                
                case 'language':
                    $sanitized_settings[$key] = sanitize_text_field($value);
                    break;
                
                case 'chat_icon_url':
                    $sanitized_settings[$key] = esc_url_raw($value);
                    break;
                
                case 'qa_pairs':
                    $sanitized_settings[$key] = array();
                    if (is_array($value)) {
                        foreach ($value as $pair) {
                            if (isset($pair['question']) && isset($pair['answer'])) {
                                $sanitized_settings[$key][] = array(
                                    'question' => sanitize_text_field($pair['question']),
                                    'answer' => wp_kses_post($pair['answer'])
                                );
                            }
                        }
                    }
                    break;
                
                case 'subjects':
                    $sanitized_settings[$key] = array();
                    if (is_array($value)) {
                        foreach ($value as $subject) {
                            $sanitized_settings[$key][] = sanitize_text_field($subject);
                        }
                    }
                    break;
                
                default:
                    // Skip unknown settings
                    break;
            }
        }
        
        // Save settings
        $success = $settings->update_options($sanitized_settings);
        
        if ($success) {
            wp_send_json_success(array('message' => __('Settings saved successfully!', 'bloobee-smartchat')));
        } else {
            wp_send_json_error(array('message' => __('Error saving settings. Please try again.', 'bloobee-smartchat')));
        }
    }
    
    /**
     * AJAX handler for getting analytics data
     */
    public function ajax_get_analytics() {
        // Check nonce
        check_ajax_referer('bloobee_admin_nonce', 'nonce');
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to access this page.', 'bloobee-smartchat')));
            return;
        }
        
        // Get period
        $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : '7days';
        
        // Get analytics data
        $analytics = Bloobee_Analytics::get_instance();
        $analytics_data = $analytics->get_analytics_data($period);
        
        wp_send_json_success($analytics_data);
    }

    /**
     * Get all conversations
     */
    public function get_all_conversations() {
        global $wpdb;
        $table_conversations = $wpdb->prefix . 'bloobee_conversations';
        
        // Check if a search term is provided
        if (isset($_GET['s']) && !empty($_GET['s'])) {
            $search_term = '%' . $wpdb->esc_like($_GET['s']) . '%';
            $conversations = $wpdb->get_results($wpdb->prepare(
                "SELECT id, user_id, subject, created_at, COUNT(*) as message_count
                FROM $table_conversations
                WHERE user_message LIKE %s OR bot_response LIKE %s
                GROUP BY user_id, DATE(created_at)
                ORDER BY created_at DESC",
                $search_term, $search_term
            ));
        } else {
            // Get conversations grouped by user and date
            $conversations = $wpdb->get_results(
                "SELECT id, user_id, subject, created_at, COUNT(*) as message_count
                FROM $table_conversations
                GROUP BY user_id, DATE(created_at)
                ORDER BY created_at DESC"
            );
        }
        
        return $conversations;
    }
    
    /**
     * Get conversation messages
     */
    public function get_conversation_messages($conversation_id) {
        global $wpdb;
        $table_conversations = $wpdb->prefix . 'bloobee_conversations';
        
        // Get first message info
        $first_message = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_conversations WHERE id = %d",
            $conversation_id
        ));
        
        if (!$first_message) {
            return array();
        }
        
        // Get all messages for this user on the same day
        $date = date('Y-m-d', strtotime($first_message->created_at));
        $messages = $wpdb->get_results($wpdb->prepare(
            "SELECT id, user_message as message, created_at, 0 as is_bot
            FROM $table_conversations
            WHERE user_id = %s AND DATE(created_at) = %s
            UNION ALL
            SELECT id, bot_response as message, created_at, 1 as is_bot
            FROM $table_conversations
            WHERE user_id = %s AND DATE(created_at) = %s
            ORDER BY created_at ASC",
            $first_message->user_id, $date, $first_message->user_id, $date
        ));
        
        return $messages;
    }
    
    /**
     * Get conversation info
     */
    public function get_conversation_info($conversation_id) {
        global $wpdb;
        $table_conversations = $wpdb->prefix . 'bloobee_conversations';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT id, user_id, subject, created_at
            FROM $table_conversations
            WHERE id = %d",
            $conversation_id
        ));
    }
    
    /**
     * Get recent conversations 
     */
    public function get_recent_conversations($limit = 5) {
        global $wpdb;
        $table_conversations = $wpdb->prefix . 'bloobee_conversations';
        
        // Get conversations grouped by user and date
        $conversations = $wpdb->get_results($wpdb->prepare(
            "SELECT id, user_id, subject, created_at, COUNT(*) as message_count
            FROM $table_conversations
            GROUP BY user_id, DATE(created_at)
            ORDER BY created_at DESC
            LIMIT %d",
            $limit
        ));
        
        return $conversations;
    }
}
