<?php
$is_admin_online = is_admin_online();
$admin_email = get_option('bloobee_notification_email');
$admin_gravatar = get_avatar_url($admin_email, array('size' => 32));
?>
<div id="bloobee-chat-container">
    <!-- Chat Icon -->
    <div id="bloobee-chat-icon">
        <img src="<?php echo plugins_url('bloobee.png', dirname(__FILE__)); ?>" alt="Bloobee Chat">
    </div>

    <!-- Chat Window -->
    <div id="bloobee-chat-window" class="hidden">
        <div class="chat-header">
            <div class="admin-status <?php echo $is_admin_online ? 'online' : 'offline'; ?>">
                <img src="<?php echo esc_url($admin_gravatar); ?>" alt="Admin avatar" class="admin-avatar">
                <span class="status-text">
                    <?php echo $is_admin_online ? 'Support Online' : 'Support Offline'; ?>
                </span>
            </div>
            <h3>Bloobee SmartChat</h3>
            <button id="bloobee-close-chat">×</button>
        </div>
        
        <div class="chat-user-info">
            <input type="text" id="bloobee-user-name" placeholder="Your Name" required>
            <input type="email" id="bloobee-user-email" placeholder="Your Email" required>
            <select id="bloobee-subject" required>
                <option value="">Select a Subject</option>
                <?php
                $subjects = get_option('bloobee_chat_subjects', array());
                foreach ($subjects as $subject) {
                    echo '<option value="' . esc_attr($subject['subject']) . '">' . esc_html($subject['subject']) . '</option>';
                }
                ?>
            </select>
        </div>

        <div class="chat-messages" id="bloobee-messages">
            <div class="message bot-message">
                Hello! Please fill in your details above to start the chat.
            </div>
        </div>

        <div class="chat-suggestions" id="bloobee-suggestions">
            <?php
            $qa_pairs = get_option('bloobee_smartchat_qa_pairs', array());
            foreach ($qa_pairs as $pair) {
                echo '<button class="suggestion-btn">' . esc_html($pair['question']) . '</button>';
            }
            ?>
        </div>

        <div class="chat-input">
            <input type="text" id="bloobee-user-input" placeholder="Type your message..." disabled>
            <button id="bloobee-send-message" disabled>Send</button>
        </div>
    </div>
</div>

<style>
.admin-status {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 5px 10px;
    border-radius: 15px;
    background: #f1f1f1;
}

.admin-status.online {
    background: #e8f5e9;
    color: #2e7d32;
}

.admin-status.offline {
    background: #fafafa;
    color: #666;
}

.admin-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
}

.status-text {
    font-size: 14px;
    font-weight: 500;
}

.admin-status.online .status-text::before {
    content: '';
    display: inline-block;
    width: 8px;
    height: 8px;
    background: #2e7d32;
    border-radius: 50%;
    margin-right: 5px;
}
</style>
