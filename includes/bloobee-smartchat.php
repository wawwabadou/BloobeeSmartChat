<?php
/**
 * Bloobee Agent Class
 * Handles the chatbot's interactions and responses
 */

// Prevent direct access
defined('ABSPATH') or die('Access denied');

class Bloobee_Agent {
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
        // Initialize the agent
    }

    /**
     * Get response for a given message
     */
    public function get_response($message, $subject = '') {
        // Check if IP is blocked
        $ip_blocker = Bloobee_IP_Blocker::get_instance();
        if ($ip_blocker->is_current_ip_blocked()) {
            return __('Access denied. Your IP address has been blocked from using this service.', 'bloobee-smartchat');
        }

        // Check if sentiment analysis is enabled
        $settings = Bloobee_Settings::get_instance();
        $enable_sentiment = $settings->get_option('enable_sentiment_analysis', true);
        
        // Analyze sentiment if enabled
        $sentiment = $enable_sentiment ? $this->analyze_sentiment($message) : 'neutral';
        
        // Check if we should handle based on sentiment
        if ($sentiment === 'negative' && $this->should_escalate_to_human($message)) {
            return $this->get_human_escalation_response();
        }
        
        // Handle based on subject if provided
        if (!empty($subject)) {
            return $this->get_subject_response($message, $subject);
        }
        
        // Check for matching Q&A first
        $qa_response = $this->get_qa_response($message);
        if ($qa_response) {
            return $qa_response;
        }
        
        // Check for keywords
        $keyword_response = $this->get_keyword_response($message);
        if ($keyword_response) {
            return $keyword_response;
        }
        
        // Default response
        return $this->get_default_response();
    }

    /**
     * Get response from Q&A pairs
     */
    private function get_qa_response($message) {
        $settings = Bloobee_Settings::get_instance();
        $qa_pairs = $settings->get_option('qa_pairs', array());
        
        // Normalize message for comparison
        $normalized_message = strtolower(trim($message));
        
        foreach ($qa_pairs as $pair) {
            $question = strtolower(trim($pair['question']));
            
            // Check for exact match
            if ($normalized_message === $question) {
                return $pair['answer'];
            }
            
            // Check for partial match (if question is within message)
            if (strpos($normalized_message, $question) !== false) {
                return $pair['answer'];
            }
            
            // Check for similarity (simple word matching)
            $question_words = explode(' ', $question);
            $message_words = explode(' ', $normalized_message);
            $common_words = array_intersect($question_words, $message_words);
            
            // If 70% or more words match, consider it a match
            if (count($common_words) >= 0.7 * count($question_words)) {
                return $pair['answer'];
            }
        }
        
        return false;
    }

    /**
     * Get response based on keywords
     */
    private function get_keyword_response($message) {
        $keywords = array(
            'pricing' => __('Our pricing plans start at $19/month for the basic package. Would you like more details about our pricing plans?', 'bloobee-smartchat'),
            'price' => __('Our pricing plans start at $19/month for the basic package. Would you like more details about our pricing plans?', 'bloobee-smartchat'),
            'cost' => __('Our pricing plans start at $19/month for the basic package. Would you like more details about our pricing plans?', 'bloobee-smartchat'),
            'contact' => __('You can contact our support team by sending an email to support@example.com or by filling out the contact form on our website.', 'bloobee-smartchat'),
            'email' => __('You can reach us via email at support@example.com. We usually respond within 24 hours.', 'bloobee-smartchat'),
            'phone' => __('You can reach our customer service by phone at (123) 456-7890, Monday to Friday from 9 AM to 5 PM EST.', 'bloobee-smartchat'),
            'hours' => __('Our customer service is available Monday to Friday from 9 AM to 5 PM EST.', 'bloobee-smartchat'),
            'help' => __('I\'m here to help! Please let me know what you need assistance with, and I\'ll do my best to help you.', 'bloobee-smartchat'),
            'thanks' => __('You\'re welcome! Is there anything else I can help you with today?', 'bloobee-smartchat'),
            'thank you' => __('You\'re welcome! Is there anything else I can help you with today?', 'bloobee-smartchat')
        );
        
        $normalized_message = strtolower(trim($message));
        
        foreach ($keywords as $keyword => $response) {
            if (strpos($normalized_message, $keyword) !== false) {
                return $response;
            }
        }
        
        return false;
    }

    /**
     * Get response based on subject
     */
    private function get_subject_response($message, $subject) {
        $subjects_responses = array(
            __('General Questions', 'bloobee-smartchat') => __('Thank you for your general question. I\'ll do my best to help you.', 'bloobee-smartchat'),
            __('Technical Support', 'bloobee-smartchat') => __('I see you need technical support. Let me try to help, or I can connect you with our support team.', 'bloobee-smartchat'),
            __('Pricing & Plans', 'bloobee-smartchat') => __('I\'d be happy to discuss our pricing and plans with you. Our plans start at $19/month.', 'bloobee-smartchat')
        );
        
        if (isset($subjects_responses[$subject])) {
            return $subjects_responses[$subject] . ' ' . $this->get_qa_response($message);
        }
        
        return $this->get_qa_response($message) ?: $this->get_default_response();
    }

    /**
     * Get default response
     */
    private function get_default_response() {
        $default_responses = array(
            __('I\'m not sure I understand. Could you please rephrase your question?', 'bloobee-smartchat'),
            __('I don\'t have that information right now. Would you like me to connect you with a human agent?', 'bloobee-smartchat'),
            __('I\'m having trouble understanding your request. Could you try asking in a different way?', 'bloobee-smartchat'),
            __('I\'m still learning! Could you please provide more details about your question?', 'bloobee-smartchat')
        );
        
        return $default_responses[array_rand($default_responses)];
    }

    /**
     * Analyze sentiment of a message
     */
    private function analyze_sentiment($message) {
        // Simple keyword-based sentiment analysis
        $positive_keywords = array('good', 'great', 'excellent', 'awesome', 'thanks', 'thank', 'happy', 'pleased', 'love', 'like');
        $negative_keywords = array('bad', 'terrible', 'awful', 'horrible', 'hate', 'dislike', 'angry', 'upset', 'disappointed', 'frustrating', 'useless', 'stupid');
        
        $message = strtolower($message);
        
        $positive_count = 0;
        $negative_count = 0;
        
        foreach ($positive_keywords as $keyword) {
            if (strpos($message, $keyword) !== false) {
                $positive_count++;
            }
        }
        
        foreach ($negative_keywords as $keyword) {
            if (strpos($message, $keyword) !== false) {
                $negative_count++;
            }
        }
        
        if ($positive_count > $negative_count) {
            return 'positive';
        } elseif ($negative_count > $positive_count) {
            return 'negative';
        } else {
            return 'neutral';
        }
    }

    /**
     * Determine if a message should escalate to human
     */
    private function should_escalate_to_human($message) {
        // Escalate if message contains certain keywords
        $escalation_keywords = array('speak to human', 'real person', 'agent', 'representative', 'supervisor', 'manager', 'support team');
        
        $message = strtolower($message);
        
        foreach ($escalation_keywords as $keyword) {
            if (strpos($message, $keyword) !== false) {
                return true;
            }
        }
        
        // Also check for multiple question marks or exclamation marks (signs of frustration)
        if (substr_count($message, '?') > 2 || substr_count($message, '!') > 2) {
            return true;
        }
        
        return false;
    }

    /**
     * Get human escalation response
     */
    private function get_human_escalation_response() {
        return __('I understand this might be frustrating. Would you like me to connect you with a human support agent? You can also email us at support@example.com or call us at (123) 456-7890.', 'bloobee-smartchat');
    }
}
