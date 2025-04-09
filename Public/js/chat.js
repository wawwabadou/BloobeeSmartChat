jQuery(document).ready(function($) {
    // Check staff status every 30 seconds
    function checkStaffStatus() {
        $.ajax({
            url: bloobeeAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'bloobee_check_staff_status',
                nonce: bloobeeAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('.bloobee-staff-status')
                        .removeClass('offline')
                        .addClass(response.data.online ? 'online' : 'offline')
                        .attr('title', response.data.online ? 'Staff Online' : 'Staff Offline');
                }
            }
        });
    }

    // Initial check and set interval
    checkStaffStatus();
    setInterval(checkStaffStatus, 30000);

    // Chat window toggle with proper class names
    $('.bloobee-chat-button').on('click', function() {
        var chatWindow = $('.bloobee-chat-window');
        if (!chatWindow.hasClass('show')) {
            chatWindow.addClass('show').css('display', 'flex');
        } else {
            chatWindow.removeClass('show').css('display', 'none');
        }
    });

    // Close chat window with proper handling
    $('.close-chat').on('click', function(e) {
        e.stopPropagation();
        $('.bloobee-chat-window').removeClass('show').css('display', 'none');
    });

    // Send message on button click
    $('.bloobee-chat-input button').on('click', sendMessage);

    // Send message on Enter key
    $('.bloobee-chat-input input').on('keypress', function(e) {
        if (e.which === 13) {
            sendMessage();
        }
    });

    function sendMessage() {
        const input = $('.bloobee-chat-input input');
        const message = input.val().trim();

        if (message) {
            // Add user message
            addMessage(message, 'user');
            
            // Clear input
            input.val('');

            // Show typing indicator
            $('.bloobee-typing').show();

            // Make AJAX call to get bot response
            $.ajax({
                url: bloobeeAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'bloobee_get_response',
                    message: message,
                    nonce: bloobeeAjax.nonce
                },
                success: function(response) {
                    // Hide typing indicator
                    $('.bloobee-typing').hide();

                    if (response.success) {
                        // Add bot response
                        addMessage(response.data.message, 'bot');
                    } else {
                        // Add error message
                        addMessage('Sorry, there was an error processing your request.', 'bot');
                    }
                },
                error: function() {
                    // Hide typing indicator
                    $('.bloobee-typing').hide();
                    // Add error message
                    addMessage('Sorry, there was an error connecting to the server.', 'bot');
                }
            });
        }
    }

    function addMessage(message, type) {
        const messageHtml = `
            <div class="bloobee-message ${type}">
                <div class="bloobee-message-content">
                    ${message}
                </div>
            </div>
        `;

        // Add message to chat
        const messagesContainer = $('.bloobee-chat-messages');
        messagesContainer.append(messageHtml);

        // Scroll to bottom
        messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
    }
}); 