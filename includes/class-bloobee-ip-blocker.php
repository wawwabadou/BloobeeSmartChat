<?php
/**
 * Bloobee IP Blocker Class
 * Handles IP address blocking and access control
 */

// Prevent direct access
defined('ABSPATH') or die('Access denied');

class Bloobee_IP_Blocker {
    /**
     * Instance of this class
     */
    private static $instance = null;
    
    /**
     * Database table name
     */
    private $table_blocked_ips;
    
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
        $this->table_blocked_ips = $wpdb->prefix . 'bloobee_blocked_ips';
        
        // Create table if it doesn't exist
        $this->create_table();
    }
    
    /**
     * Create database table for blocked IPs
     */
    private function create_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_blocked_ips} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            ip_address varchar(45) NOT NULL,
            reason text,
            blocked_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            expires_at datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY ip_address (ip_address)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Block an IP address
     */
    public function block_ip($ip_address, $reason = '', $expires_at = null) {
        global $wpdb;
        
        // Validate IP address
        if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
            return false;
        }
        
        // Check if IP is already blocked
        if ($this->is_ip_blocked($ip_address)) {
            return true;
        }
        
        // Insert blocked IP
        return $wpdb->insert(
            $this->table_blocked_ips,
            array(
                'ip_address' => $ip_address,
                'reason' => $reason,
                'expires_at' => $expires_at
            ),
            array('%s', '%s', '%s')
        );
    }
    
    /**
     * Unblock an IP address
     */
    public function unblock_ip($ip_address) {
        global $wpdb;
        
        return $wpdb->delete(
            $this->table_blocked_ips,
            array('ip_address' => $ip_address),
            array('%s')
        );
    }
    
    /**
     * Check if an IP is blocked
     */
    public function is_ip_blocked($ip_address) {
        global $wpdb;
        
        // Get current time
        $current_time = current_time('mysql');
        
        // Check if IP is blocked and not expired
        $blocked = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_blocked_ips} 
            WHERE ip_address = %s 
            AND (expires_at IS NULL OR expires_at > %s)",
            $ip_address,
            $current_time
        ));
        
        return (bool) $blocked;
    }
    
    /**
     * Get all blocked IPs
     */
    public function get_blocked_ips() {
        global $wpdb;
        
        return $wpdb->get_results(
            "SELECT * FROM {$this->table_blocked_ips} 
            WHERE expires_at IS NULL OR expires_at > NOW() 
            ORDER BY blocked_at DESC",
            ARRAY_A
        );
    }
    
    /**
     * Get client IP address
     */
    public function get_client_ip() {
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
    
    /**
     * Check if current user's IP is blocked
     */
    public function is_current_ip_blocked() {
        $ip_address = $this->get_client_ip();
        return $this->is_ip_blocked($ip_address);
    }
} 