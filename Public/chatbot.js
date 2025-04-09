toggleChatWindow(true);
trackEvent('chat_opened');

// Close chat window
elements.close.addEventListener('click', () => {
toggleChatWindow(false);
trackEvent('chat_closed');
});

// Start chat button
elements.startChat.addEventListener('click', () => {
startChat();
});

// Handle user input submission
elements.userInput.addEventListener('keypress', (e) => {
if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    sendUserMessage();
}
});

// Send message button
elements.sendMessage.addEventListener('click', () => {
sendUserMessage();
});

// User info form validation
elements.userName.addEventListener('input', validateUserInfo);
elements.userEmail.addEventListener('input', validateUserInfo);
if (elements.subject) {
elements.subject.addEventListener('change', validateUserInfo);
}

/**
* Toggle chat window visibility
*/
function toggleChatWindow(show) {
if (show) {
elements.window.classList.remove('hidden');
playSound('open');

// Add entrance animation class
elements.window.classList.add('chat-entrance');
setTimeout(() => {
    elements.window.classList.remove('chat-entrance');
}, 500);
} else {
elements.window.classList.add('hidden');
playSound('close');
}

/**
* Start the chat after collecting user info
*/
function startChat() {
// Validate user info
if (!isUserInfoComplete()) {
showValidationError(__('Please fill in all required fields.'));
return;
}

// Get user information
sessionData.userName = elements.userName.value.trim();
sessionData.userEmail = elements.userEmail.value.trim();
if (elements.subject) {
sessionData.subject = elements.subject.value;
}

// Hide user info form and show chat
elements.userInfo.classList.remove('active');
elements.userInfo.classList.add('hidden');
elements.inputContainer.classList.remove('hidden');
elements.inputContainer.classList.add('active');

// Enable input
elements.userInput.disabled = false;
elements.sendMessage.disabled = false;
elements.userInput.focus();

// Set chat as started
sessionData.chatStarted = true;

// Add welcome message
setTimeout(() => {
addMessage(settings.welcome_message, 'bot');
}, 500);

// Track chat start
trackEvent('chat_started', {
subject: sessionData.subject
});
}

/**
* Send user message
*/
function sendUserMessage() {
const messageText = elements.userInput.value.trim();

if (!messageText) {
return;
}

// Add user message to chat
addMessage(messageText, 'user');

// Clear input
elements.userInput.value = '';
elements.userInput.focus();

// Track message sent
trackEvent('message_sent', {
count: ++sessionData.messagesCount
});

// Show typing indicator
if (settings.enable_typing_indicator) {
showTypingIndicator();
}

// Get bot response
getBotResponse(messageText);
}

/**
* Get bot response via AJAX
*/
function getBotResponse(message) {
// Prepare data
const data = new FormData();
data.append('action', 'bloobee_get_response');
data.append('nonce', bloobeeAjax.nonce);
data.append('message', message);
data.append('user_id', sessionData.userId);
data.append('user_name', sessionData.userName);
data.append('user_email', sessionData.userEmail);

if (sessionData.subject) {
data.append('subject', sessionData.subject);
}

// Simulate typing delay for more natural conversation
const typingDelay = settings.enable_typing_indicator ? settings.typing_delay : 0;

// Send request
setTimeout(() => {
fetch(bloobeeAjax.ajaxurl, {
    method: 'POST',
    credentials: 'same-origin',
    body: data
})
.then(response => response.json())
.then(data => {
    // Hide typing indicator
    hideTypingIndicator();
    
    // Add bot response
    if (data.success) {
        addMessage(data.response, 'bot');
        
        // Track response received
        trackEvent('response_received');
    } else {
        handleError(data.error || __('An error occurred. Please try again.'));
    }
})
.catch(error => {
    // Hide typing indicator
    hideTypingIndicator();
    
    // Handle error
    console.error('Bloobee SmartChat Error:', error);
    handleError(__('Connection error. Please try again.'));
    
    // Track error
    trackEvent('error', {
        type: 'ajax_error'
    });
});
}, typingDelay);
}

/**
* Add message to chat
*/
function addMessage(message, type) {
// Create message element
const messageEl = document.createElement('div');
messageEl.className = `bloobee-message ${type}-message`;
messageEl.innerHTML = `<div class="bloobee-message-content">${message}</div>`;

// Add timestamp
const timestamp = document.createElement('div');
timestamp.className = 'bloobee-message-time';
timestamp.textContent = getCurrentTime();
messageEl.appendChild(timestamp);

// Add to messages container
elements.messages.appendChild(messageEl);

// Scroll to bottom
scrollToBottom();

// Play sound
playSound(type);
}

/**
* Show typing indicator
*/
function showTypingIndicator() {
// Check if typing indicator already exists
if (document.querySelector('.typing-indicator')) {
return;
}

// Create typing indicator
const typingIndicator = document.createElement('div');
typingIndicator.className = 'typing-indicator';
typingIndicator.innerHTML = `
<div class="typing-dot"></div>
<div class="typing-dot"></div>
<div class="typing-dot"></div>
`;

// Add to messages container
elements.messages.appendChild(typingIndicator);

// Scroll to bottom
scrollToBottom();
}

/**
* Hide typing indicator
*/
function hideTypingIndicator() {
const typingIndicator = document.querySelector('.typing-indicator');
if (typingIndicator) {
typingIndicator.remove();
}
}

/**
* Handle error
*/
function handleError(errorMessage) {
// Show error message as bot message
addMessage(`<span class="bloobee-error">${errorMessage}</span>`, 'bot');
}

/**
* Show validation error
*/
function showValidationError(message) {
const errorEl = document.getElementById('bloobee-validation-error');

// Create error element if it doesn't exist
if (!errorEl) {
const newErrorEl = document.createElement('div');
newErrorEl.id = 'bloobee-validation-error';
newErrorEl.className = 'bloobee-error';
elements.userInfo.insertBefore(newErrorEl, elements.startChat);
}

// Set error message
document.getElementById('bloobee-validation-error').textContent = message;

// Highlight invalid fields
if (!elements.userName.value.trim()) {
elements.userName.classList.add('invalid');
}

if (!elements.userEmail.value.trim() || !isValidEmail(elements.userEmail.value)) {
elements.userEmail.classList.add('invalid');
}

if (elements.subject && elements.subject.value === '') {
elements.subject.classList.add('invalid');
}
}

/**
* Validate user info
*/
function validateUserInfo() {
// Remove invalid class on input
this.classList.remove('invalid');

// Enable/disable start button based on validation
elements.startChat.disabled = !isUserInfoComplete();

// Clear error message if all fields are valid
if (isUserInfoComplete()) {
const errorEl = document.getElementById('bloobee-validation-error');
if (errorEl) {
    errorEl.textContent = '';
}
}
}

/**
* Check if user info is complete
*/
function isUserInfoComplete() {
// Check name and email
const nameValid = elements.userName.value.trim() !== '';
const emailValid = elements.userEmail.value.trim() !== '' && isValidEmail(elements.userEmail.value);

// Check subject if required
let subjectValid = true;
if (elements.subject && settings.enable_subject_selection) {
subjectValid = elements.subject.value !== '';
}

return nameValid && emailValid && subjectValid;
}

/**
* Validate email format
*/
function isValidEmail(email) {
const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
return emailRegex.test(email);
}

/**
* Get or create user ID
*/
function getUserId() {
let userId = sessionStorage.getItem('bloobee_user_id');

if (!userId) {
userId = 'user_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
sessionStorage.setItem('bloobee_user_id', userId);
}

return userId;
}

/**
* Get current time formatted
*/
function getCurrentTime() {
const now = new Date();
let hours = now.getHours();
let minutes = now.getMinutes();
const ampm = hours >= 12 ? 'PM' : 'AM';

hours = hours % 12;
hours = hours ? hours : 12;
minutes = minutes < 10 ? '0' + minutes : minutes;

return `${hours}:${minutes} ${ampm}`;
}

/**
* Scroll messages container to bottom
*/
function scrollToBottom() {
elements.messagesContainer.scrollTop = elements.messagesContainer.scrollHeight;
}

/**
* Play sound effect
*/
function playSound(type) {
if (!settings.enable_sound) {
return;
}

let soundUrl = '';

switch (type) {
case 'user':
    soundUrl = BLOOBEE_SMARTCHAT_URL + 'public/sounds/message-sent.mp3';
    break;
case 'bot':
    soundUrl = BLOOBEE_SMARTCHAT_URL + 'public/sounds/message-received.mp3';
    break;
case 'open':
    soundUrl = BLOOBEE_SMARTCHAT_URL + 'public/sounds/chat-open.mp3';
    break;
case 'close':
    soundUrl = BLOOBEE_SMARTCHAT_URL + 'public/sounds/chat-close.mp3';
    break;
}

if (soundUrl) {
const sound = new Audio(soundUrl);
sound.volume = 0.5;
sound.play().catch(error => {
    // Ignore autoplay errors
    console.debug('Sound playback error:', error);
});
}
}

/**
* Track analytics event
*/
function trackEvent(eventType, eventData = {}) {
if (!settings.enable_analytics) {
return;
}

// Add default event data
const data = {
userId: sessionData.userId,
userName: sessionData.userName,
userEmail: sessionData.userEmail,
subject: sessionData.subject,
timestamp: Date.now(),
...eventData
};

// Send tracking request
const formData = new FormData();
formData.append('action', 'bloobee_track_event');
formData.append('nonce', bloobeeAjax.nonce);
formData.append('event_type', eventType);
formData.append('event_data', JSON.stringify(data));

// Use navigator.sendBeacon for better performance and to ensure
// events are sent even if page is unloaded
if (navigator.sendBeacon) {
navigator.sendBeacon(bloobeeAjax.ajaxurl, formData);
} else {
// Fallback to fetch
fetch(bloobeeAjax.ajaxurl, {
    method: 'POST',
    credentials: 'same-origin',
    body: formData
}).catch(error => {
    console.debug('Tracking error:', error);
});
}
}

/**
* Translate text
*/
function __(text) {
// This is a simple placeholder for translation
// In a real implementation, this would use a translation system
return text;
}
}
