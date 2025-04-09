<?php
/**
 * Bloobee Multilingual Class
 * Handles multilingual support for chatbot
 */

// Prevent direct access
defined('ABSPATH') or die('Access denied');

class Bloobee_Multilingual {
    /**
     * Instance of this class
     */
    private static $instance = null;
    
    /**
     * Available languages
     */
    private $available_languages = array(
        'en' => 'English',
        'es' => 'Español',
        'fr' => 'Français',
        'de' => 'Deutsch',
        'it' => 'Italiano',
        'pt' => 'Português',
        'nl' => 'Nederlands',
        'ru' => 'Русский',
        'ja' => '日本語',
        'zh' => '中文',
        'ar' => 'العربية',
        'hi' => 'हिन्दी',
        'ko' => '한국어',
        'pl' => 'Polski',
        'tr' => 'Türkçe'
    );
    
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
        // Add filters for multilingual support
        add_filter('bloobee_pre_response', array($this, 'process_language'), 10, 2);
        
        // Register AJAX handlers for language detection
        add_action('wp_ajax_bloobee_detect_language', array($this, 'ajax_detect_language'));
        add_action('wp_ajax_nopriv_bloobee_detect_language', array($this, 'ajax_detect_language'));
    }
    
    /**
     * Get available languages
     */
    public function get_available_languages() {
        return $this->available_languages;
    }
    
    /**
     * Process language for responses
     */
    public function process_language($response, $message) {
        $settings = Bloobee_Settings::get_instance();
        
        // If multilingual support is disabled, return original response
        if (!$settings->get_option('enable_multilingual', true)) {
            return $response;
        }
        
        // Get language setting
        $language = $settings->get_option('language', 'auto');
        
        // If language is set to auto, detect language from message
        if ($language === 'auto') {
            $detected_language = $this->detect_language($message);
            
            // If language is detected and is not English, translate response
            if ($detected_language && $detected_language !== 'en') {
                return $this->translate_text($response, 'en', $detected_language);
            }
        }
        // If a specific language is set and it's not English, translate response
        elseif ($language !== 'en') {
            return $this->translate_text($response, 'en', $language);
        }
        
        return $response;
    }
    
    /**
     * Detect language from text
     */
    public function detect_language($text) {
        // This is a simple placeholder implementation
        // In a real-world scenario, you would integrate with a language detection API
        // such as Google Cloud Translation API, Microsoft Translator, or others
        
        // For this example, we'll use a simple approach based on common language patterns
        $text = strtolower($text);
        
        // Spanish
        if (preg_match('/(hola|gracias|buenos dias|como estas|adios|por favor)/i', $text)) {
            return 'es';
        }
        
        // French
        if (preg_match('/(bonjour|merci|au revoir|comment allez-vous|s\'il vous plait)/i', $text)) {
            return 'fr';
        }
        
        // German
        if (preg_match('/(hallo|danke|auf wiedersehen|wie geht es ihnen|bitte)/i', $text)) {
            return 'de';
        }
        
        // Italian
        if (preg_match('/(ciao|grazie|arrivederci|come stai|per favore)/i', $text)) {
            return 'it';
        }
        
        // Default to English
        return 'en';
    }
    
    /**
     * Translate text from one language to another
     */
    public function translate_text($text, $source_language, $target_language) {
        // This is a placeholder implementation
        // In a real-world scenario, you would integrate with a translation API
        // such as Google Cloud Translation API, Microsoft Translator, or others
        
        // For this example, we'll return the original text with a note
        return $text . ' [Translated to ' . $this->available_languages[$target_language] . ']';
    }
    
    /**
     * AJAX handler for language detection
     */
    public function ajax_detect_language() {
        // Verify nonce
        check_ajax_referer('bloobee_smartchat_nonce', 'nonce');
        
        // Get message from request
        $message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
        
        if (empty($message)) {
            wp_send_json_error(array('error' => __('Empty message', 'bloobee-smartchat')));
            return;
        }
        
        // Detect language
        $language = $this->detect_language($message);
        
        wp_send_json_success(array(
            'language_code' => $language,
            'language_name' => isset($this->available_languages[$language]) ? $this->available_languages[$language] : 'Unknown'
        ));
    }
}
