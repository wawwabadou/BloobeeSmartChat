jQuery(document).ready(function($) {
    // Chat window toggle
    $('#bloobee-chat-icon').on('click', function() {
        $('#bloobee-chat-window').removeClass('hidden');
    });

    $('#bloobee-close-chat').on('click', function() {
        $('#bloobee-chat-window').addClass('hidden');
    });

    // User ID management
    let userId = getUserId();
    let lastMessageTimestamp = 0;

    function getUserId() {
        let id = sessionStorage.getItem('bloobee_chat_user_id');
        if (!id) {
            id = 'user_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            sessionStorage.setItem('bloobee_chat_user_id', id);
        }
        return id;
    }

    // Check if user info is complete
    function isUserInfoComplete() {
        const name = $('#bloobee-user-name').val().trim();
        const email = $('#bloobee-user-email').val().trim();
        return name !== '' && email !== '' && isValidEmail(email);
    }

    // Email validation
    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    // Enable/disable subject dropdown based on user info
    function updateSubjectDropdown() {
        const isComplete = isUserInfoComplete();
        $('#bloobee-subject').prop('disabled', !isComplete);
        
        if (!isComplete) {
            $('#bloobee-subject').val('');
            $('#bloobee-user-input').prop('disabled', true);
            $('#bloobee-send-message').prop('disabled', true);
        }

        if (!isComplete && $('#bloobee-subject').val()) {
            addMessage('Please fill in your name and email first.', 'system');
        }
    }

    // Handle input changes
    $('#bloobee-user-name, #bloobee-user-email').on('input', function() {
        updateSubjectDropdown();
        
        if ($(this).attr('id') === 'bloobee-user-email' && $(this).val().trim() !== '') {
            if (!isValidEmail($(this).val().trim())) {
                $(this).addClass('invalid');
            } else {
                $(this).removeClass('invalid');
            }
        }
    });

    // Handle subject selection
    $('#bloobee-subject').on('change', function() {
        const subject = $(this).val();
        if (!subject) return;

        if (!isUserInfoComplete()) {
            $(this).val('');
            addMessage('Please fill in your name and email first.', 'system');
            return;
        }

        // Get the automated response for the selected subject
        $.ajax({
            url: bloobeeChat.ajaxurl,
            type: 'POST',
            data: {
                action: 'bloobee_get_subject_response',
                nonce: bloobeeChat.nonce,
                subject: subject
            },
            success: function(response) {
                if (response.success && response.data.response) {
                    addMessage(`You've selected: ${subject}`, 'system');
                    setTimeout(() => {
                        addMessage(response.data.response, 'bot');
                    }, 500);
                    
                    // Enable chat input after subject is selected and response is shown
                    $('#bloobee-user-input').prop('disabled', false);
                    $('#bloobee-send-message').prop('disabled', false);
                    
                    // Hide user info section to give more room for chat
                    $('.chat-user-info').slideUp(300);
                    $('#bloobee-messages').css('height', 'calc(100% - 110px)');
                    
                    // Start checking for admin status
                    checkAdminStatus();
                }
            }
        });
    });

    let chatInterval = null;
    let statusInterval = null;

    function startLiveChat() {
        console.log('Starting live chat polling');
        
        // Check admin status and queue position
        checkAdminStatus();
        
        // Start polling for admin messages and status updates
        if (chatInterval) {
            clearInterval(chatInterval);
            console.log('Cleared existing chat interval');
        }
        if (statusInterval) {
            clearInterval(statusInterval);
            console.log('Cleared existing status interval');
        }
        
        // Check for messages immediately
        checkAdminMessages();
        
        // Then set up interval polling
        chatInterval = setInterval(checkAdminMessages, 3000);
        statusInterval = setInterval(checkAdminStatus, 30000);
        
        console.log('Chat polling started - checking every 3 seconds');
        
        // Hide user info section if not already hidden
        if ($('.chat-user-info').is(':visible')) {
            $('.chat-user-info').slideUp(300);
            $('#bloobee-messages').css('height', 'calc(100% - 110px)');
        }
    }

    function checkAdminStatus() {
        $.ajax({
            url: bloobeeChat.ajaxurl,
            type: 'POST',
            data: {
                action: 'bloobee_check_admin_status',
                nonce: bloobeeChat.nonce,
                user_id: userId
            },
            success: function(response) {
                if (response.success) {
                    updateAdminStatus(response.data);
                    updateQueueStatus(response.data);
                }
            }
        });
    }

    function updateAdminStatus(data) {
        const statusElement = $('.admin-status');
        if (data.is_online) {
            statusElement.removeClass('offline').addClass('online');
            $('.status-text').text('Support Online');
            
            // Update admin avatar and name if available
            if (data.admin_info && data.admin_info.gravatar) {
                $('.admin-avatar').attr('src', data.admin_info.gravatar);
                
                if (data.admin_info.name) {
                    $('.status-text').text(data.admin_info.name + ' is online');
                }
            }
        } else {
            statusElement.removeClass('online').addClass('offline');
            $('.status-text').text('Support Offline');
        }
    }

    function updateQueueStatus(data) {
        let statusElement = $('#queue-status-message');
        if (!statusElement.length) {
            statusElement = $('<div id="queue-status-message" class="system-message"></div>');
            $('#bloobee-messages').append(statusElement);
        }
        
        if (data.is_online) {
            let message = 'An agent will be with you shortly. ';
            if (data.queue_position > 1) {
                message += `You are #${data.queue_position} in the queue. `;
                message += `Estimated wait time: ${data.estimated_wait} minutes.`;
            }
            statusElement.text(message).show();
        } else {
            statusElement.text('No agents are currently online. Please leave a message and we will get back to you.').show();
        }
    }

    function sendMessage() {
        const message = $('#bloobee-user-input').val().trim();
        if (!message) return;
        
        // Clear input
        $('#bloobee-user-input').val('');
        
        // Display user message immediately
        addMessage(message, 'user');
        
        const name = $('#bloobee-user-name').val().trim();
        const email = $('#bloobee-user-email').val().trim();
        const subject = $('#bloobee-subject').val();
        
        console.log('Sending message:', {
            message,
            name,
            email,
            subject,
            user_id: userId
        });
        
        // Send message to server
        $.ajax({
            url: bloobeeChat.ajaxurl,
            type: 'POST',
            data: {
                action: 'bloobee_new_message',
                nonce: bloobeeChat.nonce,
                message: message,
                name: name,
                email: email,
                subject: subject,
                user_id: userId
            },
            success: function(response) {
                console.log('Message sent response:', response);
                if (response.success) {
                    // Start checking for admin responses
                    startLiveChat();
                    
                    // Log whether admin is online
                    console.log('Admin online status:', response.data.is_admin_online);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error sending message:', status, error);
            }
        });
    }

    function addMessage(message, type) {
        const messageContainer = $('#bloobee-messages');
        const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        let messageHTML = '';
        
        switch(type) {
            case 'user':
                messageHTML = `
                    <div class="message user-message">
                        <div class="message-content">${message}</div>
                        <div class="message-time">${time}</div>
                    </div>
                `;
                break;
            case 'bot':
                messageHTML = `
                    <div class="message bot-message">
                        <div class="message-content">${message}</div>
                        <div class="message-time">${time}</div>
                    </div>
                `;
                break;
            case 'admin':
                messageHTML = `
                    <div class="message admin-message">
                        <div class="message-content">${message}</div>
                        <div class="message-time">${time} - Support</div>
                    </div>
                `;
                break;
            case 'system':
                messageHTML = `
                    <div class="system-message">
                        <div class="message-content">${message}</div>
                    </div>
                `;
                break;
        }
        
        messageContainer.append(messageHTML);
        messageContainer.scrollTop(messageContainer[0].scrollHeight);
    }

    function checkAdminMessages() {
        console.log('Checking for new admin messages since timestamp:', lastMessageTimestamp);
        $.ajax({
            url: bloobeeChat.ajaxurl,
            type: 'POST',
            data: {
                action: 'bloobee_get_new_messages',
                nonce: bloobeeChat.nonce,
                user_id: userId,
                last_timestamp: lastMessageTimestamp
            },
            success: function(response) {
                console.log('New messages response:', response);
                if (response.success && response.data && response.data.messages && response.data.messages.length > 0) {
                    response.data.messages.forEach(message => {
                        console.log('Processing message:', message);
                        
                        // Check if message has is_admin flag set to true
                        if (message.is_admin === true) {
                            console.log('Adding admin message to chat:', message.message);
                            addMessage(message.message, 'admin');
                        } 
                        // Check if message has is_system flag set to true
                        else if (message.is_system === true) {
                            console.log('Adding system message to chat:', message.message);
                            addMessage(message.message, 'system');
                            
                            // If conversation was ended by admin, stop checking
                            if (message.message.includes('ended by admin')) {
                                if (chatInterval) clearInterval(chatInterval);
                                if (statusInterval) clearInterval(statusInterval);
                            }
                        }
                        // Handle regular user messages if any
                        else if (!message.is_admin && !message.is_system) {
                            console.log('Adding user message to chat:', message.message);
                            // We don't usually need to display the user's own messages here
                            // as they are added immediately when sent
                        }
                    });
                    
                    lastMessageTimestamp = response.data.last_timestamp;
                    console.log('Updated last message timestamp to:', lastMessageTimestamp);
                    
                    // Force scroll to bottom after adding new messages
                    const messageContainer = $('#bloobee-messages');
                    messageContainer.scrollTop(messageContainer[0].scrollHeight);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error checking admin messages:', status, error);
            }
        });
    }

    // Handle suggestion clicks
    $('.suggestion-btn').on('click', function() {
        const question = $(this).text();
        
        if (!isUserInfoComplete() || !$('#bloobee-subject').val()) {
            addMessage('Please fill in your details and select a subject first.', 'system');
            return;
        }
        
        addMessage(question, 'user');
        
        // Send the question as a message
        $.ajax({
            url: bloobeeChat.ajaxurl,
            type: 'POST',
            data: {
                action: 'bloobee_new_message',
                nonce: bloobeeChat.nonce,
                message: question,
                name: $('#bloobee-user-name').val().trim(),
                email: $('#bloobee-user-email').val().trim(),
                subject: $('#bloobee-subject').val(),
                user_id: userId
            },
            success: function(response) {
                if (response.success) {
                    // Start checking for admin responses
                    startLiveChat();
                }
            }
        });
    });

    // Handle send button click
    $('#bloobee-send-message').on('click', function() {
        sendMessage();
    });

    // Handle enter key
    $('#bloobee-user-input').on('keypress', function(e) {
        if (e.which === 13 && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    // Initial state
    updateSubjectDropdown();
});
