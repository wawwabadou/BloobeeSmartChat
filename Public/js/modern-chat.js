/**
 * BloobeeSmartChat - Modern Chat Interactions
 * Enhanced with ES6+ features and smooth animations
 */

// Use strict mode for better error catching
'use strict';

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', () => {
  // Initialize chat functionality
  initBloobeeChat();
});

/**
 * Initialize chat functionality
 */
function initBloobeeChat() {
  // Cache DOM elements for better performance
  const elements = {
    container: document.getElementById('bloobee-chat-container'),
    icon: document.getElementById('bloobee-chat-icon'),
    window: document.getElementById('bloobee-chat-window'),
    header: document.getElementById('bloobee-chat-header'),
    close: document.getElementById('bloobee-chat-close'),
    messages: document.getElementById('bloobee-messages'),
    messagesContainer: document.getElementById('bloobee-messages-container'),
    userInfo: document.getElementById('bloobee-user-info'),
    userName: document.getElementById('bloobee-user-name'),
    userEmail: document.getElementById('bloobee-user-email'),
    subject: document.getElementById('bloobee-subject'),
    startChat: document.getElementById('bloobee-start-chat'),
    inputContainer: document.getElementById('bloobee-input-container'),
    userInput: document.getElementById('bloobee-user-input'),
    sendMessage: document.getElementById('bloobee-send-message')
  };
  
  // User session data
  let sessionData = {
    userId: getUserId(),
    userName: '',
    userEmail: '',
    subject: '',
    chatStarted: false
  };
  
  // Set up event listeners
  setupEventListeners();
  
  /**
   * Set up event listeners
   */
  function setupEventListeners() {
    // Toggle chat window on icon click
    elements.icon.addEventListener('click', () => {
      toggleChatWindow(true);
    });
    
    // Close chat window
    elements.close.addEventListener('click', () => {
      toggleChatWindow(false);
    });
    
    // Start chat button
    if (elements.startChat) {
      elements.startChat.addEventListener('click', () => {
        startChat();
      });
    }
    
    // Handle user input submission
    if (elements.userInput) {
      elements.userInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
          e.preventDefault();
          sendUserMessage();
        }
      });
    }
    
    // Send message button
    if (elements.sendMessage) {
      elements.sendMessage.addEventListener('click', () => {
        sendUserMessage();
      });
    }
    
    // User info form validation
    if (elements.userName && elements.userEmail) {
      elements.userName.addEventListener('input', validateUserInfo);
      elements.userEmail.addEventListener('input', validateUserInfo);
      
      if (elements.subject) {
        elements.subject.addEventListener('change', validateUserInfo);
      }
    }
  }
  
  /**
   * Toggle chat window visibility
   */
  function toggleChatWindow(show) {
    if (!elements.window) return;
    
    if (show) {
      elements.window.classList.remove('hidden');
      
      // Add entrance animation class
      elements.window.classList.add('chat-entrance');
      setTimeout(() => {
        elements.window.classList.remove('chat-entrance');
      }, 500);
    } else {
      elements.window.classList.add('hidden');
    }
  }
  
  /**
   * Start the chat after collecting user info
   */
  function startChat() {
    if (!elements.userInfo || !elements.inputContainer) return;
    
    // Validate user info
    if (!isUserInfoComplete()) {
      showValidationError('Please fill in all required fields.');
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
    addMessage('Hello! How can I help you today?', 'bot');
  }
  
  /**
   * Send user message
   */
  function sendUserMessage() {
    if (!elements.userInput || !sessionData.chatStarted) return;
    
    const messageText = elements.userInput.value.trim();
    
    if (!messageText) {
      return;
    }
    
    // Add user message to chat
    addMessage(messageText, 'user');
    
    // Clear input
    elements.userInput.value = '';
    elements.userInput.focus();
    
    // Show typing indicator
    showTypingIndicator();
    
    // Send to server via AJAX
    getBotResponse(messageText);
  }
  
  /**
   * Get bot response via AJAX
   */
  function getBotResponse(message) {
    const data = new FormData();
    data.append('action', 'wp_ajax_bloobee_get_response');
    data.append('message', message);
    data.append('user_id', sessionData.userId);
    data.append('user_name', sessionData.userName);
    data.append('user_email', sessionData.userEmail);
    
    if (sessionData.subject) {
      data.append('subject', sessionData.subject);
    }
    
    // Simulate typing delay for more natural conversation
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
        } else {
          handleError(data.error || 'An error occurred. Please try again.');
        }
      })
      .catch(error => {
        // Hide typing indicator
        hideTypingIndicator();
        
        // Handle error
        console.error('Bloobee SmartChat Error:', error);
        handleError('Connection error. Please try again.');
      });
    }, 1500); // Typing delay of 1.5 seconds
  }
  
  /**
   * Add message to chat
   */
  function addMessage(message, type) {
    if (!elements.messages) return;
    
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
  }
  
  /**
   * Show typing indicator
   */
  function showTypingIndicator() {
    if (!elements.messages) return;
    
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
    addMessage(`<span style="color: #e53935;">${errorMessage}</span>`, 'bot');
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
      newErrorEl.style.color = '#e53935';
      newErrorEl.style.fontSize = '13px';
      newErrorEl.style.marginBottom = '12px';
      newErrorEl.style.textAlign = 'center';
      elements.userInfo.insertBefore(newErrorEl, elements.startChat);
    }
    
    // Set error message
    document.getElementById('bloobee-validation-error').textContent = message;
    
    // Highlight invalid fields
    if (!elements.userName.value.trim()) {
      elements.userName.style.borderColor = '#e53935';
    }
    
    if (!elements.userEmail.value.trim() || !isValidEmail(elements.userEmail.value)) {
      elements.userEmail.style.borderColor = '#e53935';
    }
    
    if (elements.subject && elements.subject.value === '') {
      elements.subject.style.borderColor = '#e53935';
    }
  }
  
  /**
   * Validate user info
   */
  function validateUserInfo() {
    // Remove error styling
    this.style.borderColor = '';
    
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
    const nameValid = elements.userName && elements.userName.value.trim() !== '';
    const emailValid = elements.userEmail && 
                      elements.userEmail.value.trim() !== '' && 
                      isValidEmail(elements.userEmail.value);
    
    // Check subject if required
    let subjectValid = true;
    if (elements.subject) {
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
    if (elements.messagesContainer) {
      elements.messagesContainer.scrollTop = elements.messagesContainer.scrollHeight;
    }
  }
} 