<?php
/**
 * Bloobee Staff Class
 * Handles staff online status
 */

// Prevent direct access
defined('ABSPATH') or die('Access denied');

class Bloobee_Staff {
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
        add_action('wp_ajax_bloobee_check_staff_status', array($this, 'check_staff_status'));
        add_action('wp_ajax_nopriv_bloobee_check_staff_status', array($this, 'check_staff_status'));
        
        // Update staff status when they log in/out
        add_action('wp_login', array($this, 'update_staff_status_login'), 10, 2);
        add_action('wp_logout', array($this, 'update_staff_status_logout'));
        
        // Periodically update staff status
        add_action('init', array($this, 'schedule_status_check'));
        add_action('bloobee_check_staff_status_event', array($this, 'update_all_staff_status'));
    }
    
    /**
     * Schedule status check
     */
    public function schedule_status_check() {
        if (!wp_next_scheduled('bloobee_check_staff_status_event')) {
            wp_schedule_event(time(), 'every_minute', 'bloobee_check_staff_status_event');
        }
    }
    
    /**
     * Check if any staff member is online
     */
    public function is_staff_online() {
        $staff_roles = array('administrator', 'editor', 'shop_manager', 'customer_support');
        $logged_in_users = get_transient('online_status');
        
        if (!$logged_in_users) {
            return false;
        }
        
        foreach ($logged_in_users as $user_id => $last_activity) {
            $user = get_user_by('id', $user_id);
            if ($user && array_intersect($staff_roles, $user->roles)) {
                // Check if the last activity was within 5 minutes
                if (time() - $last_activity < 300) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Update staff status on login
     */
    public function update_staff_status_login($user_login, $user) {
        $logged_in_users = get_transient('online_status');
        if (!is_array($logged_in_users)) {
            $logged_in_users = array();
        }
        $logged_in_users[$user->ID] = time();
        set_transient('online_status', $logged_in_users, 7200); // Store for 2 hours
    }
    
    /**
     * Update staff status on logout
     */
    public function update_staff_status_logout() {
        $user_id = get_current_user_id();
        $logged_in_users = get_transient('online_status');
        if (is_array($logged_in_users) && isset($logged_in_users[$user_id])) {
            unset($logged_in_users[$user_id]);
            set_transient('online_status', $logged_in_users, 7200);
        }
    }
    
    /**
     * Update all staff status
     */
    public function update_all_staff_status() {
        $logged_in_users = get_transient('online_status');
        if (!is_array($logged_in_users)) {
            $logged_in_users = array();
        }
        
        // Update current user's status
        if (is_user_logged_in()) {
            $logged_in_users[get_current_user_id()] = time();
        }
        
        // Remove old entries (inactive for more than 5 minutes)
        foreach ($logged_in_users as $user_id => $last_activity) {
            if (time() - $last_activity > 300) {
                unset($logged_in_users[$user_id]);
            }
        }
        
        set_transient('online_status', $logged_in_users, 7200);
    }
    
    /**
     * AJAX handler for checking staff status
     */
    public function check_staff_status() {
        check_ajax_referer('bloobee_smartchat_nonce', 'nonce');
        
        wp_send_json_success(array(
            'online' => $this->is_staff_online()
        ));
    }
} 