<?php
/**
 * Bloobee Admin IP Blocker Class
 * Handles the admin interface for IP blocking
 */

// Prevent direct access
defined('ABSPATH') or die('Access denied');

class Bloobee_Admin_IP_Blocker {
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
        // Add menu item
        add_action('admin_menu', array($this, 'add_menu_item'));
        
        // Register AJAX handlers
        add_action('wp_ajax_bloobee_block_ip', array($this, 'ajax_block_ip'));
        add_action('wp_ajax_bloobee_unblock_ip', array($this, 'ajax_unblock_ip'));
    }
    
    /**
     * Add menu item
     */
    public function add_menu_item() {
        add_submenu_page(
            'bloobee-smartchat',
            __('IP Blocking', 'bloobee-smartchat'),
            __('IP Blocking', 'bloobee-smartchat'),
            'manage_options',
            'bloobee-ip-blocking',
            array($this, 'render_page')
        );
    }
    
    /**
     * Render admin page
     */
    public function render_page() {
        // Get IP blocker instance
        $ip_blocker = Bloobee_IP_Blocker::get_instance();
        
        // Get all blocked IPs
        $blocked_ips = $ip_blocker->get_blocked_ips();
        
        // Include template
        include_once BLOOBEE_SMARTCHAT_DIR . 'templates/admin/ip-blocking.php';
    }
    
    /**
     * AJAX handler for blocking IP
     */
    public function ajax_block_ip() {
        // Check nonce
        check_ajax_referer('bloobee_smartchat_nonce', 'nonce');
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'bloobee-smartchat')));
        }
        
        // Get parameters
        $ip_address = isset($_POST['ip_address']) ? sanitize_text_field($_POST['ip_address']) : '';
        $reason = isset($_POST['reason']) ? sanitize_text_field($_POST['reason']) : '';
        $expires_at = isset($_POST['expires_at']) ? sanitize_text_field($_POST['expires_at']) : null;
        
        // Validate IP address
        if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
            wp_send_json_error(array('message' => __('Invalid IP address', 'bloobee-smartchat')));
        }
        
        // Get IP blocker instance
        $ip_blocker = Bloobee_IP_Blocker::get_instance();
        
        // Block IP
        $result = $ip_blocker->block_ip($ip_address, $reason, $expires_at);
        
        if ($result) {
            wp_send_json_success(array('message' => __('IP address blocked successfully', 'bloobee-smartchat')));
        } else {
            wp_send_json_error(array('message' => __('Failed to block IP address', 'bloobee-smartchat')));
        }
    }
    
    /**
     * AJAX handler for unblocking IP
     */
    public function ajax_unblock_ip() {
        // Check nonce
        check_ajax_referer('bloobee_smartchat_nonce', 'nonce');
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'bloobee-smartchat')));
        }
        
        // Get IP address
        $ip_address = isset($_POST['ip_address']) ? sanitize_text_field($_POST['ip_address']) : '';
        
        // Validate IP address
        if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
            wp_send_json_error(array('message' => __('Invalid IP address', 'bloobee-smartchat')));
        }
        
        // Get IP blocker instance
        $ip_blocker = Bloobee_IP_Blocker::get_instance();
        
        // Unblock IP
        $result = $ip_blocker->unblock_ip($ip_address);
        
        if ($result) {
            wp_send_json_success(array('message' => __('IP address unblocked successfully', 'bloobee-smartchat')));
        } else {
            wp_send_json_error(array('message' => __('Failed to unblock IP address', 'bloobee-smartchat')));
        }
    }
} 