<?php
/**
 * Bloobee Settings Class
 * Handles plugin settings and options
 */

// Prevent direct access
defined('ABSPATH') or die('Access denied');

class Bloobee_Settings {
    /**
     * Instance of this class
     */
    private static $instance = null;
    
    /**
     * Option name in the database
     */
    private $option_name = 'bloobee_smartchat_settings';
    
    /**
     * Cached settings
     */
    private $settings = null;
    
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
        // Load settings
        $this->settings = get_option($this->option_name, array());
    }
    
    /**
     * Get all settings
     */
    public function get_settings() {
        return $this->settings;
    }
    
    /**
     * Get specific option
     */
    public function get_option($key, $default = false) {
        if (isset($this->settings[$key])) {
            return $this->settings[$key];
        }
        return $default;
    }
    
    /**
     * Update specific option
     */
    public function update_option($key, $value) {
        $this->settings[$key] = $value;
        return $this->save_settings();
    }
    
    /**
     * Update multiple options
     */
    public function update_options($options) {
        foreach ($options as $key => $value) {
            $this->settings[$key] = $value;
        }
        return $this->save_settings();
    }
    
    /**
     * Delete option
     */
    public function delete_option($key) {
        if (isset($this->settings[$key])) {
            unset($this->settings[$key]);
            return $this->save_settings();
        }
        return true;
    }
    
    /**
     * Save settings to database
     */
    private function save_settings() {
        return update_option($this->option_name, $this->settings);
    }
}
