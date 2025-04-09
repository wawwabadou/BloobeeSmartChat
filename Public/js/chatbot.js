/**
 * Bloobee SmartChat - Frontend JavaScript
 */
(function($) {
    'use strict';

    // Chat variables
    let chatOpen = false;
    let selectedSubject = '';
    let typingTimer = null;
    let chatHistory = [];
    let logoUrl = bloobeeAjax.settings.chat_icon_url || '';
    
    // DOM elements (will be initialized later)
    let $chatButton, $chatIcon, $chatWindow, $chatBody, $chatInput, $chatSend;
    
    /**
     * Initialize the chat
     */
    function initChat() {
        // Create chat container
        const $chatContainer = $('<div class="bloobee-chat-container"></div>');
        
        // Create chat button with logo
        $chatButton = $('<div class="bloobee-chat-button"><img src="' + logoUrl + '" alt="Chat" class="bloobee-chat-icon"></div>');
        
        // Create chat window
        $chatWindow = $('<div class="bloobee-chat-window"></div>');
        
        // Create chat header
        // Add staff status indicator based on settings
        const staffStatus = bloobeeAjax.settings.staff_status || 'offline';
        const $statusIndicator = '<span class="bloobee-staff-status ' + staffStatus + '"></span>';
        const $chatHeader = $('<div class="bloobee-chat-header"><h3 class="bloobee-chat-title">' + $statusIndicator + bloobeeAjax.settings.chat_title + '</h3><button class="bloobee-chat-close">×</button></div>');
        
        // Create chat body
        $chatBody = $('<div class="bloobee-chat-body"></div>');
        
        // Create chat footer
        const $chatFooter = $('<div class="bloobee-chat-footer"><input type="text" class="bloobee-chat-input" placeholder="Type your message..."><button class="bloobee-chat-send"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M22 2L11 13" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path><path d="M22 2L15 22L11 13L2 9L22 2Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg></button></div>');
        
        // Build chat elements
        $chatWindow.append($chatHeader, $chatBody, $chatFooter);
        $chatContainer.append($chatButton, $chatWindow);
        
        // Add chat container to the body
        $('body').append($chatContainer);
        
        // Store DOM elements for later use
        $chatIcon = $chatButton.find('.bloobee-chat-icon');
        $chatInput = $chatFooter.find('.bloobee-chat-input');
        $chatSend = $chatFooter.find('.bloobee-chat-send');
        
        // Set CSS variables for colors
        document.documentElement.style.setProperty('--bloobee-primary-color', bloobeeAjax.settings.primary_color);
        document.documentElement.style.setProperty('--bloobee-secondary-color', bloobeeAjax.settings.secondary_color);
        
        // Bind events
        bindEvents();
        
        // Add welcome message after a short delay
        setTimeout(function() {
            addBotMessage(bloobeeAjax.settings.welcome_message);
            
            // Add subject selection if subjects are available
            if (bloobeeAjax.settings.subjects && bloobeeAjax.settings.subjects.length > 0) {
                addSubjectSelector();
            }
        }, 500);
        
        // Auto open chat if enabled
        if (bloobeeAjax.settings.auto_open) {
            setTimeout(function() {
                toggleChat();
            }, bloobeeAjax.settings.auto_open_delay);
        }
    }
    
    /**
     * Bind events to chat elements
     */
    function bindEvents() {
        // Toggle chat on button click
        $chatButton.on('click', function() {
            toggleChat();
            
            // Remove notification if exists
            const $notification = $(this).find('.bloobee-notification');
            if ($notification.length) {
                $notification.remove();
            }
        });
        
        // Close chat on close button click
        $chatWindow.find('.bloobee-chat-close').on('click', function(e) {
            e.stopPropagation();
            toggleChat();
        });
        
        // Send message on send button click
        $chatSend.on('click', function() {
            sendMessage();
        });
        
        // Send message on Enter key press
        $chatInput.on('keypress', function(e) {
            if (e.which === 13) {
                sendMessage();
                return false;
            }
        });
        
        // Track chat events
        trackEvent('chat_initialized');
    }
    
    /**
     * Toggle chat open/closed
     */
    function toggleChat() {
        chatOpen = !chatOpen;
        
        if (chatOpen) {
            $chatWindow.addClass('show');
            $chatInput.focus();
            trackEvent('chat_opened');
        } else {
            $chatWindow.removeClass('show');
            trackEvent('chat_closed');
        }
    }
    
    /**
     * Send user message to the chatbot
     */
    function sendMessage() {
        const message = $chatInput.val().trim();
        
        if (message === '') {
            return;
        }
        
        // Add user message to chat
        addUserMessage(message);
        
        // Clear input
        $chatInput.val('');
        
        // Show typing indicator
        if (bloobeeAjax.settings.enable_typing_indicator) {
            showTypingIndicator();
        }
        
        // Send message to server
        $.ajax({
            url: bloobeeAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'bloobee_get_response',
                nonce: bloobeeAjax.nonce,
                message: message,
                subject: selectedSubject,
                user_id: getChatUserId()
            },
            success: function(response) {
                // Hide typing indicator
                hideTypingIndicator();
                
                if (response.success && response.data) {
                    // Add bot response to chat
                    addBotMessage(response.data.response);
                    
                    // Play sound if enabled
                    if (bloobeeAjax.settings.enable_sound) {
                        playMessageSound();
                    }
                } else {
                    // Add error message (from backend response)
                    const errorMessage = response.data && response.data.error ? response.data.error : 'Sorry, I encountered an error. Please try again later.';
                    addBotMessage(errorMessage);
                    // Log the backend-reported error as an event as well
                    trackEvent('backend_error', { 
                        message: message, 
                        subject: selectedSubject, 
                        error: errorMessage
                    });
                }
            },
            error: function(jqXHR, textStatus, errorThrown) { // Modified error handler
                // Hide typing indicator
                hideTypingIndicator();
                
                // Add generic error message to chat
                const genericErrorMessage = 'Sorry, there was a connection problem. Please try again later.';
                addBotMessage(genericErrorMessage);
                
                // Track the AJAX error
                trackEvent('ajax_error', {
                    message: message,
                    subject: selectedSubject,
                    status: textStatus,
                    error: errorThrown,
                    response: jqXHR.responseText // Include response text if available
                });
            }
        });
        
        // Track message sent
        trackEvent('message_sent', {
            message: message,
            subject: selectedSubject
        });
    }
    
    /**
     * Add a user message to the chat
     */
    function addUserMessage(message) {
        const $message = $('<div class="bloobee-message bloobee-message-user"></div>').text(message);
        $chatBody.append($message);
        scrollToBottom();
        
        // Add to chat history
        chatHistory.push({
            sender: 'user',
            message: message,
            timestamp: new Date().toISOString()
        });
    }
    
    /**
     * Add a bot message to the chat
     */
    function addBotMessage(message) {
        const $message = $('<div class="bloobee-message bloobee-message-bot"></div>').text(message);
        $chatBody.append($message);
        scrollToBottom();
        
        // Add to chat history
        chatHistory.push({
            sender: 'bot',
            message: message,
            timestamp: new Date().toISOString()
        });
        
        // Show notification if chat is closed
        if (!chatOpen) {
            showNotification();
        }
    }
    
    /**
     * Add subject selector to the chat
     */
    function addSubjectSelector() {
        // Create subject selector container
        const $subjectSelector = $('<div class="bloobee-subject-selector"></div>');
        const $subjectTitle = $('<div class="bloobee-subject-title">Please select a topic:</div>');
        const $subjectOptions = $('<div class="bloobee-subject-options"></div>');
        
        // Add subject options
        $.each(bloobeeAjax.settings.subjects, function(index, subject) {
            const $option = $('<button class="bloobee-subject-option"></button>').text(subject);
            
            // Bind click event
            $option.on('click', function() {
                // Remove selected class from all options
                $subjectOptions.find('.bloobee-subject-option').removeClass('selected');
                
                // Add selected class to clicked option
                $(this).addClass('selected');
                
                // Set selected subject
                selectedSubject = subject;
                
                // Add confirmation message
                addBotMessage('You selected: ' + subject + '. How can I help you with this topic?');
                
                // Remove subject selector
                $subjectSelector.remove();
                
                // Track subject selection
                trackEvent('subject_selected', {
                    subject: subject
                });
            });
            
            $subjectOptions.append($option);
        });
        
        // Build subject selector
        $subjectSelector.append($subjectTitle, $subjectOptions);
        
        // Add to chat body
        $chatBody.append($subjectSelector);
        scrollToBottom();
    }
    
    /**
     * Show typing indicator
     */
    function showTypingIndicator() {
        const $typing = $('<div class="bloobee-typing"><span class="bloobee-typing-dot"></span><span class="bloobee-typing-dot"></span><span class="bloobee-typing-dot"></span></div>');
        $chatBody.append($typing);
        scrollToBottom();
        
        // Set timer to hide typing indicator after a delay
        clearTimeout(typingTimer);
        typingTimer = setTimeout(function() {
            hideTypingIndicator();
        }, bloobeeAjax.settings.typing_delay || 2000);
    }
    
    /**
     * Hide typing indicator
     */
    function hideTypingIndicator() {
        $('.bloobee-typing').remove();
    }
    
    /**
     * Show notification indicator
     */
    function showNotification() {
        // Check if notification already exists
        if ($chatButton.find('.bloobee-notification').length) {
            return;
        }
        
        // Create notification
        const $notification = $('<span class="bloobee-notification">1</span>');
        $chatButton.append($notification);
        
        // Add bounce animation to chat button
        $chatButton.addClass('new-message');
        setTimeout(function() {
            $chatButton.removeClass('new-message');
        }, 1000);
    }
    
    /**
     * Play message sound
     */
    function playMessageSound() {
        // Create audio element if it doesn't exist
        if (!$('#bloobee-message-sound').length) {
            const $audio = $('<audio id="bloobee-message-sound" preload="auto"><source src="' + bloobeeAjax.settings.sound_url + '" type="audio/mp3"></audio>');
            $('body').append($audio);
        }
        
        // Play sound
        $('#bloobee-message-sound')[0].play().catch(function() {
            // Autoplay was prevented, we'll ignore this error
        });
    }
    
    /**
     * Scroll chat body to bottom
     */
    function scrollToBottom() {
        $chatBody.scrollTop($chatBody[0].scrollHeight);
    }
    
    /**
     * Get or create a unique user ID for the chat
     */
    function getChatUserId() {
        let userId = localStorage.getItem('bloobee_chat_user_id');
        
        if (!userId) {
            userId = 'guest_' + Math.random().toString(36).substring(2, 15);
            localStorage.setItem('bloobee_chat_user_id', userId);
        }
        
        return userId;
    }
    
    /**
     * Track analytics event
     */
    function trackEvent(eventType, eventData) {
        if (!bloobeeAjax.settings.enable_analytics) {
            return;
        }
        
        $.ajax({
            url: bloobeeAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'bloobee_track_event',
                nonce: bloobeeAjax.nonce,
                event_type: eventType,
                event_data: eventData || {}
            }
        });
    }
    
    // Initialize on document ready
    $(document).ready(function() {
        initChat();
    });
    
})(jQuery); 