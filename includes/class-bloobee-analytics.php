<?php
/**
 * Bloobee Analytics Class
 * Tracks and stores chat analytics data
 */

// Prevent direct access
defined('ABSPATH') or die('Access denied');

class Bloobee_Analytics {
    /**
     * Instance of this class
     */
    private static $instance = null;
    
    /**
     * Database table name
     */
    private $table_events;
    private $table_conversations;
    
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
        global $wpdb;
        $this->table_events = $wpdb->prefix . 'bloobee_events';
        $this->table_conversations = $wpdb->prefix . 'bloobee_conversations';
    }
    
    /**
     * Create database tables for analytics
     */
    public function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Events table
        $sql_events = "CREATE TABLE IF NOT EXISTS {$this->table_events} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            event_type varchar(50) NOT NULL,
            event_data longtext NOT NULL,
            user_id varchar(50) NOT NULL,
            ip_address varchar(45) NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY event_type (event_type),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Conversations table
        $sql_conversations = "CREATE TABLE IF NOT EXISTS {$this->table_conversations} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id varchar(50) NOT NULL,
            user_message text NOT NULL,
            bot_response text NOT NULL,
            subject varchar(100) DEFAULT NULL,
            sentiment varchar(20) DEFAULT NULL,
            ip_address varchar(45) NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY subject (subject),
            KEY sentiment (sentiment),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_events);
        dbDelta($sql_conversations);
    }
    
    /**
     * Track an event
     */
    public function track_event($event_type, $event_data = array()) {
        global $wpdb;
        
        // Get user ID from event data or generate a new one
        $user_id = isset($event_data['userId']) ? $event_data['userId'] : 'anonymous_' . time() . '_' . rand(1000, 9999);
        
        // Convert event data to JSON
        $event_data_json = json_encode($event_data);
        
        // Get IP address
        $ip_address = $this->get_client_ip();
        
        // Insert event
        return $wpdb->insert(
            $this->table_events,
            array(
                'event_type' => $event_type,
                'event_data' => $event_data_json,
                'user_id' => $user_id,
                  'ip_address' => $ip_address,
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Track conversation
     */
    public function track_conversation($user_message, $bot_response, $subject = '', $sentiment = 'neutral') {
        global $wpdb;
        
        // Get user ID from POST data or generate a new one
        $user_id = isset($_POST['user_id']) ? sanitize_text_field($_POST['user_id']) : 'anonymous_' . time() . '_' . rand(1000, 9999);
        
        // Get subject from POST data if not provided
        if (empty($subject) && isset($_POST['subject'])) {
            $subject = sanitize_text_field($_POST['subject']);
        }
        
        // Get IP address
        $ip_address = $this->get_client_ip();
        
        // Insert conversation
        return $wpdb->insert(
            $this->table_conversations,
            array(
                'user_id' => $user_id,
                'user_message' => $user_message,
                'bot_response' => $bot_response,
                'subject' => $subject,
                'sentiment' => $sentiment,
                'ip_address' => $ip_address,
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Get analytics data
     */
    public function get_analytics_data($period = '7days') {
        global $wpdb;
        
        // Calculate date range
        $end_date = current_time('mysql');
        $start_date = '';
        
        switch ($period) {
            case 'today':
                $start_date = date('Y-m-d 00:00:00', strtotime('today'));
                break;
            case 'yesterday':
                $start_date = date('Y-m-d 00:00:00', strtotime('yesterday'));
                $end_date = date('Y-m-d 23:59:59', strtotime('yesterday'));
                break;
            case '7days':
                $start_date = date('Y-m-d 00:00:00', strtotime('-6 days'));
                break;
            case '30days':
                $start_date = date('Y-m-d 00:00:00', strtotime('-29 days'));
                break;
            case 'this_month':
                $start_date = date('Y-m-01 00:00:00');
                break;
            case 'last_month':
                $start_date = date('Y-m-01 00:00:00', strtotime('first day of last month'));
                $end_date = date('Y-m-t 23:59:59', strtotime('last day of last month'));
                break;
            default:
                $start_date = date('Y-m-d 00:00:00', strtotime('-6 days'));
        }
        
        // Get total conversations
        $total_conversations = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_conversations} WHERE created_at BETWEEN %s AND %s",
            $start_date, $end_date
        ));
        
        // Get total unique users
        $total_users = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT user_id) FROM {$this->table_events} WHERE created_at BETWEEN %s AND %s",
            $start_date, $end_date
        ));
        
        // Get chat sessions
        $chat_sessions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_events} WHERE event_type = 'chat_started' AND created_at BETWEEN %s AND %s",
            $start_date, $end_date
        ));
        
        // Get sentiment distribution
        $sentiment_distribution = $wpdb->get_results($wpdb->prepare(
            "SELECT sentiment, COUNT(*) as count FROM {$this->table_conversations} 
            WHERE created_at BETWEEN %s AND %s GROUP BY sentiment",
            $start_date, $end_date
        ), ARRAY_A);
        
        // Get subject distribution
        $subject_distribution = $wpdb->get_results($wpdb->prepare(
            "SELECT subject, COUNT(*) as count FROM {$this->table_conversations} 
            WHERE subject != '' AND created_at BETWEEN %s AND %s GROUP BY subject",
            $start_date, $end_date
        ), ARRAY_A);
        
        // Get conversation by day
        $conversations_by_day = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(created_at) as date, COUNT(*) as count FROM {$this->table_conversations} 
            WHERE created_at BETWEEN %s AND %s GROUP BY DATE(created_at) ORDER BY date ASC",
            $start_date, $end_date
        ), ARRAY_A);
        
        // Return compiled data
        return array(
            'total_conversations' => $total_conversations,
            'total_users' => $total_users,
            'chat_sessions' => $chat_sessions,
            'sentiment_distribution' => $sentiment_distribution,
            'subject_distribution' => $subject_distribution,
            'conversations_by_day' => $conversations_by_day,
            'period' => $period,
            'start_date' => $start_date,
            'end_date' => $end_date
        );
    }
    
    /**
     * Get conversation history for a specific user
     */
    public function get_user_conversation_history($user_id) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_conversations} WHERE user_id = %s ORDER BY created_at ASC",
            $user_id
        ), ARRAY_A);
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_address = '';
        
        // Check for shared internet/ISP IP
        if (!empty($_SERVER['HTTP_CLIENT_IP']) && filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP)) {
            $ip_address = $_SERVER['HTTP_CLIENT_IP'];
        }
        
        // Check for IP addresses passing through proxies
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Check if multiple IPs exist in HTTP_X_FORWARDED_FOR
            $iplist = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            foreach ($iplist as $ip) {
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    $ip_address = $ip;
                    break;
                }
            }
        }
        
        // Check for the remote address
        elseif (!empty($_SERVER['REMOTE_ADDR']) && filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)) {
            $ip_address = $_SERVER['REMOTE_ADDR'];
        }
        
        // If no valid IP is found, return a placeholder
        if (empty($ip_address) || !filter_var($ip_address, FILTER_VALIDATE_IP)) {
            $ip_address = 'UNKNOWN';
        }
        
        return $ip_address;
    }
}