<?php
/**
 * Plugin Name: Bloobee - The Smarty Pants Chat Agent / L'Intellagente de chat!
 * Description: Plugin de chatbot pour WordPress avec questions prédéfinies, suggestions et support en ligne.
 * Version: 1.0
 * Author: <a href="https://lestudiodansmatete.com" target="_blank">LE STUDIO dans ma tête</a> | Jean-François Brideau
 */

// Sécurité : empêcher l'accès direct
defined( 'ABSPATH' ) or die( 'Accès direct interdit' );

// Enregistrer les scripts et styles
function chatbot_enqueue_scripts() {
    wp_enqueue_style('chatbot-public-style', plugins_url('public/styles.css', __FILE__));
    wp_enqueue_script('chatbot-public-script', plugins_url('public/chatbot.js', __FILE__), ['jquery'], null, true);
    
    // Add AJAX URL and nonce to your script
    wp_localize_script('chatbot-public-script', 'bloobeeChat', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('bloobee_chat_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'chatbot_enqueue_scripts');

// Insertion du chatbot via un shortcode
function chatbot_display() {
    ob_start();
    include plugin_dir_path(__FILE__) . 'templates/chatbot-window.php';
    return ob_get_clean();
}
add_shortcode('chatbot', 'chatbot_display');

// Update the admin menu function to include sub-pages
function chatbot_admin_menu() {
    add_menu_page(
        'Bloobee SmartChat Settings',
        'Bloobee SmartChat',
        'manage_options',
        'bloobee-smartchat',
        'chatbot_config_page',
        'dashicons-format-chat',
        30
    );

    add_submenu_page(
        'bloobee-smartchat',
        'Settings',
        'Settings',
        'manage_options',
        'bloobee-smartchat'
    );

    add_submenu_page(
        'bloobee-smartchat',
        'Subjects',
        'Subjects',
        'manage_options',
        'bloobee-subjects',
        'chatbot_subjects_page'
    );

    add_submenu_page(
        'bloobee-smartchat',
        'Chat History',
        'Chat History',
        'manage_options', 
        'bloobee-history',
        'chatbot_history_page'
    );

    add_submenu_page(
        'bloobee-smartchat',
        'Live Chat',
        'Live Chat',
        'manage_options',
        'bloobee-live-chat',
        'chatbot_live_chat_page'
    );
}
add_action('admin_menu', 'chatbot_admin_menu');

// Updated Configuration Page
function chatbot_config_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }

    // Save settings if form is submitted
    if (isset($_POST['bloobee_save_settings'])) {
        check_admin_referer('bloobee_smartchat_settings');
        
        // Save Q&A pairs
        $qa_pairs = array();
        $questions = $_POST['question'] ?? array();
        $answers = $_POST['answer'] ?? array();
        
        foreach ($questions as $key => $question) {
            if (!empty($question) && !empty($answers[$key])) {
                $qa_pairs[] = array(
                    'question' => sanitize_text_field($question),
                    'answer' => sanitize_textarea_field($answers[$key])
                );
            }
        }
        
        update_option('bloobee_smartchat_qa_pairs', $qa_pairs);

        // Save notification email
        $notification_email = sanitize_email($_POST['bloobee_notification_email']);
        update_option('bloobee_notification_email', $notification_email);
        
        echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
    }

    // Get existing settings
    $qa_pairs = get_option('bloobee_smartchat_qa_pairs', array());
    $notification_email = get_option('bloobee_notification_email', get_option('admin_email'));
    ?>
    <div class="wrap bloobee-admin-wrap">
        <div class="bloobee-header">
            <div class="bloobee-header-logo">
                <img src="<?php echo plugins_url('bloobee.png', __FILE__); ?>" alt="Bloobee Logo">
            </div>
            <div class="bloobee-header-title">
                <h1>Bloobee The smarty pants Chat Agent</h1>
                <h2>Settings</h2>
            </div>
        </div>
        <div class="bloobee-form-container">
            <form method="post" action="">
                <?php wp_nonce_field('bloobee_smartchat_settings'); ?>
                
                <h2>Notification Settings</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="bloobee_notification_email">Notification Email</label>
                        </th>
                        <td>
                            <input type="email" 
                                name="bloobee_notification_email" 
                                id="bloobee_notification_email" 
                                value="<?php echo esc_attr($notification_email); ?>" 
                                class="regular-text">
                            <p class="description">Email address where chat notifications will be sent.</p>
                        </td>
                    </tr>
                </table>

                <h2>Automatic Questions and Answers</h2>
                <div id="qa-pairs-container">
                <?php
                    if (!empty($qa_pairs)) {
                        foreach ($qa_pairs as $index => $pair) {
                            ?>
                            <div class="qa-pair">
                                <p>
                                    <label>Question:</label>
                                    <input type="text" name="question[]" value="<?php echo esc_attr($pair['question']); ?>" class="regular-text">
                                </p>
                                <p>
                                    <label>Answer:</label>
                                    <textarea name="answer[]" rows="3" class="large-text"><?php echo esc_textarea($pair['answer']); ?></textarea>
                                </p>
                                <button type="button" class="button remove-pair">Remove</button>
                            </div>
                <?php
                        }
                    }
                    ?>
                </div>
                
                <button type="button" class="button" id="add-pair">Add New Q&A Pair</button>
                
                <p class="submit">
                    <input type="submit" name="bloobee_save_settings" class="button-primary bloobee-btn-primary" value="Save Settings">
                </p>
            </form>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        // Add new Q&A pair
        $('#add-pair').on('click', function() {
            var template = `
                <div class="qa-pair">
                    <p>
                        <label>Question:</label>
                        <input type="text" name="question[]" class="regular-text">
                    </p>
                    <p>
                        <label>Answer:</label>
                        <textarea name="answer[]" rows="3" class="large-text"></textarea>
                    </p>
                    <button type="button" class="button remove-pair">Remove</button>
                </div>
            `;
            $('#qa-pairs-container').append(template);
        });

        // Remove Q&A pair
        $(document).on('click', '.remove-pair', function() {
            $(this).closest('.qa-pair').remove();
        });
    });
    </script>

    <style>
    .qa-pair {
        background: #fff;
        padding: 15px;
        margin-bottom: 15px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }
    .qa-pair label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }
    .remove-pair {
        margin-top: 10px;
    }
    #add-pair {
        margin: 20px 0;
    }
    </style>
    <?php
}

// Register plugin activation hook
register_activation_hook(__FILE__, 'chatbot_activate');

function chatbot_activate() {
    // Initialize empty Q&A pairs if they don't exist
    if (!get_option('bloobee_smartchat_qa_pairs')) {
        add_option('bloobee_smartchat_qa_pairs', array());
    }
    
    // Initialize notification email with admin email if it doesn't exist
    if (!get_option('bloobee_notification_email')) {
        add_option('bloobee_notification_email', get_option('admin_email'));
    }

    // Initialize subjects if they don't exist
    if (!get_option('bloobee_chat_subjects')) {
        add_option('bloobee_chat_subjects', array());
    }

    // Initialize chat history if it doesn't exist
    if (!get_option('bloobee_chat_history')) {
        add_option('bloobee_chat_history', array());
    }
}

// Register settings
function chatbot_register_settings() {
    register_setting('chatbot_settings_group', 'bloobee_smartchat_qa_pairs');
    register_setting('chatbot_settings_group', 'bloobee_notification_email');
    register_setting('chatbot_settings_group', 'bloobee_chat_subjects');
    register_setting('chatbot_settings_group', 'bloobee_chat_history');
}
add_action('admin_init', 'chatbot_register_settings');

function chatbot_add_to_footer() {
    include plugin_dir_path(__FILE__) . 'templates/chatbot-window.php';
}
add_action('wp_footer', 'chatbot_add_to_footer');

// Add this new function to handle email notifications
function bloobee_send_notification($message, $name, $email, $subject) {
    $admin_email = get_option('bloobee_notification_email');
    $site_name = get_bloginfo('name');
    
    $email_subject = sprintf('[%s] New Chat Message - %s', $site_name, $subject);
    
    $email_body = "A new message has been received from the chat:\n\n";
    $email_body .= "Name: " . $name . "\n";
    $email_body .= "Email: " . $email . "\n";
    $email_body .= "Subject: " . $subject . "\n";
    $email_body .= "Message: " . $message . "\n";
    $email_body .= "\nYou can view this conversation in your WordPress admin panel.";
    
    $headers = array('Content-Type: text/plain; charset=UTF-8');
    
    return wp_mail($admin_email, $email_subject, $email_body, $headers);
}

// Add this new function to get automated response
function get_automated_response($subject) {
    $subjects = get_option('bloobee_chat_subjects', array());
    foreach ($subjects as $item) {
        if ($item['subject'] === $subject) {
            return $item['response'];
        }
    }
    return false;
}

// Add AJAX handler for new messages
add_action('wp_ajax_bloobee_new_message', 'handle_bloobee_new_message');
add_action('wp_ajax_nopriv_bloobee_new_message', 'handle_bloobee_new_message');

function handle_bloobee_new_message() {
    check_ajax_referer('bloobee_chat_nonce', 'nonce');
    
    $message = sanitize_text_field($_POST['message']);
    $name = sanitize_text_field($_POST['name']);
    $email = sanitize_email($_POST['email']);
    $subject = sanitize_text_field($_POST['subject']);
    $user_id = sanitize_text_field($_POST['user_id']);
    
    error_log('New message from: ' . $name . ' <' . $email . '>, subject: ' . $subject . ', message: ' . $message . ', user_id: ' . $user_id);
    
    // Check if this is a new chat (first message)
    $active_chats = get_option('bloobee_active_chats', array());
    $is_new_chat = !isset($active_chats[$user_id]);
    
    // Store in chat history
    $chat_history = get_option('bloobee_chat_history', array());
    
    // If this is a new chat, add the subject as a special message first
    if ($is_new_chat) {
        $chat_history[] = array(
            'timestamp' => time(),
            'user_id' => $user_id,
            'name' => $name,
            'email' => $email,
            'subject' => $subject,
            'message' => $subject,
            'type' => 'subject',
            'is_admin' => false
        );
    }
    
    // Add the actual message
    $chat_history[] = array(
        'timestamp' => time(),
        'user_id' => $user_id,
        'name' => $name,
        'email' => $email,
        'subject' => $subject,
        'message' => $message,
        'is_admin' => false
    );
    update_option('bloobee_chat_history', $chat_history);
    
    // Check if admin is online
    $admin_online = is_admin_online();
    error_log('Is admin online? ' . ($admin_online ? 'Yes' : 'No'));
    
    // Always update active chats regardless of admin status
    $active_chats[$user_id] = array(
        'user_id' => $user_id,
        'name' => $name,
        'email' => $email,
        'subject' => $subject,
        'timestamp' => time(),
        'has_new_message' => true,
        'last_message' => $message
    );
    update_option('bloobee_active_chats', $active_chats);
    error_log('Active chats updated: ' . print_r($active_chats, true));
    
    // Get automated response
    $automated_response = get_automated_response($subject);
    
    // Calculate queue position and wait time
    $active_chats = get_option('bloobee_active_chats', array());
    $queue_position = count($active_chats);
    $estimated_wait = $queue_position * 5; // 5 minutes per chat
    
    // Send response with initial data
    wp_send_json_success(array(
        'is_admin_online' => $admin_online,
        'automated_response' => $automated_response,
        'queue_position' => $queue_position,
        'estimated_wait' => $estimated_wait
    ));
}

// Update the subjects management function
function chatbot_subjects_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Handle form submission
    if (isset($_POST['bloobee_save_subjects'])) {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'bloobee_subjects_settings')) {
            wp_die('Security check failed');
        }
        
        $subjects = array();
        if (isset($_POST['subject']) && is_array($_POST['subject']) && 
            isset($_POST['response']) && is_array($_POST['response'])) {
            
            foreach ($_POST['subject'] as $key => $subject) {
                if (!empty($subject) && !empty($_POST['response'][$key])) {
                    $subjects[] = array(
                        'subject' => sanitize_text_field($subject),
                        'response' => sanitize_textarea_field($_POST['response'][$key])
                    );
                }
            }
        }
        
        update_option('bloobee_chat_subjects', $subjects);
        echo '<div class="notice notice-success"><p>Subjects and responses saved successfully!</p></div>';
    }

    // Get existing subjects
    $subjects = get_option('bloobee_chat_subjects', array());
    ?>
    <div class="wrap bloobee-admin-wrap">
        <div class="bloobee-header">
            <div class="bloobee-header-logo">
                <img src="<?php echo plugins_url('bloobee.png', __FILE__); ?>" alt="Bloobee Logo">
            </div>
            <div class="bloobee-header-title">
                <h1>Bloobee The smarty pants Chat Agent</h1>
                <h2>Chat Subjects and Automated Responses</h2>
            </div>
        </div>
        <div class="bloobee-form-container">
            <form method="post" action="">
                <?php wp_nonce_field('bloobee_subjects_settings'); ?>
                
                <div id="subjects-container">
                    <?php
                    if (!empty($subjects)) {
                        foreach ($subjects as $item) {
                            ?>
                            <div class="subject-item">
                                <div class="subject-fields">
                                    <div class="subject-field">
                                        <label>Subject:</label>
                                        <input type="text" 
                                            name="subject[]" 
                                            value="<?php echo esc_attr($item['subject'] ?? ''); ?>" 
                                            class="regular-text">
                                    </div>
                                    <div class="subject-field">
                                        <label>Automated Response:</label>
                                        <textarea name="response[]" 
                                                rows="3" 
                                                class="large-text"><?php echo esc_textarea($item['response'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                                <button type="button" class="button remove-subject">Remove</button>
                            </div>
                            <?php
                        }
                    }
                    ?>
                </div>
                
                <button type="button" class="button" id="add-subject">Add New Subject</button>
                
                <p class="submit">
                    <input type="submit" 
                        name="bloobee_save_subjects" 
                        class="button-primary bloobee-btn-primary" 
                        value="Save Subjects">
                </p>
            </form>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        $('#add-subject').on('click', function() {
            var template = `
                <div class="subject-item">
                    <div class="subject-fields">
                        <div class="subject-field">
                            <label>Subject:</label>
                            <input type="text" name="subject[]" class="regular-text">
                        </div>
                        <div class="subject-field">
                            <label>Automated Response:</label>
                            <textarea name="response[]" rows="3" class="large-text"></textarea>
                        </div>
                    </div>
                    <button type="button" class="button remove-subject">Remove</button>
                </div>
            `;
            $('#subjects-container').append(template);
        });

        $(document).on('click', '.remove-subject', function() {
            $(this).closest('.subject-item').remove();
        });
    });
    </script>

    <style>
    .subject-item {
        background: #fff;
        padding: 15px;
        margin-bottom: 15px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }
    .subject-fields {
        flex: 1;
    }
    .subject-field {
        margin-bottom: 10px;
    }
    .subject-field label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }
    .subject-field textarea {
        width: 100%;
    }
    .remove-subject {
        margin-top: 10px;
    }
    #add-subject {
        margin: 20px 0;
    }
    </style>
    <?php
}

// Add new function for chat history
function chatbot_history_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $chat_history = get_option('bloobee_chat_history', array());
    ?>
    <div class="wrap bloobee-admin-wrap">
        <div class="bloobee-header">
            <div class="bloobee-header-logo">
                <img src="<?php echo plugins_url('bloobee.png', __FILE__); ?>" alt="Bloobee Logo">
            </div>
            <div class="bloobee-header-title">
                <h1>Bloobee The smarty pants Chat Agent</h1>
                <h2>Chat History</h2>
            </div>
        </div>
        <div class="bloobee-form-container">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Subject</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (!empty($chat_history)) {
                        foreach ($chat_history as $chat) {
                            ?>
                            <tr>
                                <td><?php echo esc_html(date('Y-m-d H:i:s', $chat['timestamp'])); ?></td>
                                <td><?php echo esc_html($chat['name']); ?></td>
                                <td><?php echo esc_html($chat['email']); ?></td>
                                <td><?php echo esc_html($chat['subject']); ?></td>
                                <td><?php echo esc_html($chat['message']); ?></td>
                            </tr>
                            <?php
                        }
                    } else {
                        ?>
                        <tr>
                            <td colspan="5">No chat history available.</td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

// Add new AJAX handler for getting subject responses
add_action('wp_ajax_bloobee_get_subject_response', 'handle_bloobee_get_subject_response');
add_action('wp_ajax_nopriv_bloobee_get_subject_response', 'handle_bloobee_get_subject_response');

function handle_bloobee_get_subject_response() {
    check_ajax_referer('bloobee_chat_nonce', 'nonce');
    
    $subject = sanitize_text_field($_POST['subject']);
    
    // Get automated response
    $subjects = get_option('bloobee_chat_subjects', array());
    $response = '';
    
    foreach ($subjects as $item) {
        if ($item['subject'] === $subject) {
            $response = $item['response'];
            break;
        }
    }
    
    if ($response) {
        wp_send_json_success(array(
            'response' => $response
        ));
    } else {
        wp_send_json_error(array(
            'message' => 'No response found for this subject'
        ));
    }
}

function chatbot_live_chat_page() {
    ?>
    <div class="wrap bloobee-admin-wrap">
        <div class="bloobee-header">
            <div class="bloobee-header-logo">
                <img src="<?php echo plugins_url('bloobee.png', __FILE__); ?>" alt="Bloobee Logo">
            </div>
            <div class="bloobee-header-title">
                <h1>Bloobee The smarty pants Chat Agent</h1>
                <h2>Your intelligent chat assistant</h2>
            </div>
        </div>
        <div class="bloobee-form-container">
            <div id="bloobee-live-chat-container">
                <div id="chat-users-list">
                    <h3>Active Chats</h3>
                    <div id="active-chats"></div>
                </div>
                <div id="chat-window">
                    <div id="chat-header">
                        <h5 id="chat-user-name"></h5>
                        <div class="chat-actions">
                            <button id="send-transcript" class="bloobee-btn-primary">Send Transcript</button>
                            <button id="end-conversation" class="bloobee-btn-danger">End Conversation</button>
                            <button id="close-chat" class="bloobee-btn-primary">Close Chat</button>
                        </div>
                    </div>
                    <div id="chat-messages"></div>
                    <div id="chat-input">
                        <textarea id="admin-message" placeholder="Type your message here..."></textarea>
                        <button id="send-message" class="bloobee-btn-primary">Send</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Full height styles */
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }
        
        #wpcontent, #wpbody, #wpbody-content {
            height: 100%;
            padding-bottom: 0;
        }
        
        .bloobee-admin-wrap {
            height: 100vh;
            min-height: 100%;
            display: flex;
            flex-direction: column;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            overflow: hidden;
        }

        .bloobee-header {
            background-color: #96bad0;
            padding: 15px 25px;
            border-top-left-radius: 50px;
            border-top-right-radius: 50px;
            display: block;
            width: 96%;
            height: 100px;
            background-image: url(<?php echo plugins_url('corner.png', __FILE__); ?>);
            background-position: right bottom;
            background-repeat: no-repeat;
            flex-shrink: 0;
        }

        .bloobee-header-logo {
            float: left;
            margin-right: 15px;
        }

        .bloobee-header-logo img {
            width: 100px;
            height: 100px;
        }

        .bloobee-header-title h1 {
            font-size: 27px;
            font-weight: 600;
            color: #477eb6;
            margin-top: 0;
            padding-top: 15px;
        }

        .bloobee-header-title h2 {
            font-size: 19px;
            font-weight: 500;
            margin-top: 0;
            padding-top: 0;
        }
        
        .bloobee-form-container {
            padding: 25px 50px 50px 50px;
            background-color: rgba(255, 255, 255, 0.7);
            background-image: url(<?php echo plugins_url('corner.png', __FILE__); ?>);
            background-position: right bottom;
            background-repeat: no-repeat;
            width: calc(100% - 100px);
            display: block;
            flex-grow: 1;
            overflow: hidden;
        }
        
        #bloobee-live-chat-container {
            display: flex;
            gap: 20px;
            flex-grow: 1;
            position: relative;
            z-index: 2;
            overflow: hidden;
            height: calc(100% - 50px);
        }

        #chat-users-list {
            width: 30%;
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 15px;
            background: #f9f9f9;
            overflow-y: auto;
            max-height: 100%;
        }

        #chat-users-list h3 {
            color: #477eb6;
            border-bottom: 1px solid #ccc;
            padding-bottom: 8px;
            margin-top: 0;
            font-size: 19px;
            font-weight: 600;
        }

        #chat-window {
            width: 65%;
            border: 1px solid #ccc;
            border-radius: 5px;
            display: none;
            background: rgba(255, 255, 255, 0.85);
            flex-direction: column;
            height: 100%;
        }

        #chat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 15px;
            background: rgba(240, 240, 240, 0.7);
            border-bottom: 1px solid #ccc;
            flex-shrink: 0;
        }

        #chat-user-name {
            font-size: 16px;
            font-weight: 600;
            color: #477eb6;
            margin: 0;
        }

        .chat-actions {
            display: flex;
            gap: 8px;
        }

        .bloobee-btn-primary {
            background-color: #477eb6;
            color: #fff;
            padding: 8px 15px;
            font-size: 14px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .bloobee-btn-primary:hover {
            background-color: #3a6ca0;
        }

        .bloobee-btn-danger {
            background-color: #d63638;
            color: #fff;
            padding: 8px 15px;
            font-size: 14px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .bloobee-btn-danger:hover {
            background-color: #b32d2e;
        }

        #chat-messages {
            flex-grow: 1;
            padding: 15px;
            overflow-y: auto;
            background: rgba(249, 249, 249, 0.7);
        }

        #chat-input {
            display: flex;
            padding: 10px;
            background: rgba(240, 240, 240, 0.7);
            border-top: 1px solid #ccc;
            flex-shrink: 0;
            gap: 10px;
        }

        #admin-message {
            width: 75%;
            min-height: 50px;
            border: 1px solid #ccc;
            padding: 8px;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }

        #send-message {
            width: 25%;
            padding: 0;
            font-size: 13px;
            box-sizing: border-box;
        }

        .chat-user-item {
            padding: 12px;
            border: 1px solid #ccc;
            margin-bottom: 10px;
            border-radius: 5px;
            cursor: pointer;
            background: #fff;
            transition: background-color 0.2s;
        }

        .chat-user-item:hover {
            background-color: #f0f0f0;
        }

        .chat-user-item.active {
            background-color: #e6f7ff;
            border-color: #477eb6;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .chat-user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chat-user-avatar img {
            border-radius: 50%;
            width: 40px;
            height: 40px;
            border: 1px solid #ddd;
        }

        .chat-user-details {
            flex-grow: 1;
        }

        .chat-user-name {
            font-weight: bold;
            color: #477eb6;
        }

        .chat-user-email {
            font-size: 12px;
            color: #444;
        }

        .chat-user-time {
            font-size: 11px;
            color: #555;
            margin-top: 5px;
        }

        .chat-message {
            margin-bottom: 12px;
            padding: 10px 14px;
            border-radius: 15px;
            max-width: 80%;
            word-wrap: break-word;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .chat-message-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 12px;
        }

        .chat-message-sender {
            font-weight: bold;
            color: #477eb6;
        }

        .chat-message-time {
            color: #666;
        }

        .chat-message-content {
            padding: 12px;
            border-radius: 8px;
            word-break: break-word;
        }

        .user-message {
            margin-right: auto;
            animation: messageSlideInLeft 0.3s ease-out;
        }

        .user-message .chat-message-content {
            background-color: rgba(245, 245, 245, 0.9);
            border: 1px solid #e0e0e0;
            color: #333;
        }

        .admin-message {
            margin-left: auto;
            animation: messageSlideInRight 0.3s ease-out;
        }

        .admin-message .chat-message-content {
            background-color: rgba(71, 126, 182, 0.9);
            color: #fff;
            border: 1px solid #3a6ca0;
        }

        .system-message {
            text-align: center;
            margin: 10px 0;
            font-size: 12px;
            color: #555;
            animation: messagePulse 1s ease-out;
        }

        .system-message .chat-message-content {
            display: inline-block;
            background-color: rgba(230, 247, 255, 0.9);
            border: 1px solid #a4e6ff;
            color: #477eb6;
            padding: 5px 10px;
            border-radius: 15px;
            font-weight: 600;
        }

        .new-message-badge {
            background-color: #d63638;
            color: white;
            border-radius: 10px;
            padding: 2px 6px;
            font-size: 10px;
            margin-left: 10px;
            font-weight: bold;
        }

        @keyframes messageSlideInLeft {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes messageSlideInRight {
            from {
                opacity: 0;
                transform: translateX(20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes messagePulse {
            0% {
                opacity: 0;
                transform: scale(0.95);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* Make sure the chat window displays properly when shown */
        #chat-window.show {
            display: flex;
        }

        #send-message {
            padding: 0 15px;
            font-size: 13px;
        }
    </style>

    <script type="text/javascript">
        jQuery(document).ready(function($) {
            let selectedUserId = null;
            let selectedUserEmail = null;
            let realtimeChatInterval = null;
            let lastMessageTimestamp = 0;
            
            // Add the show class to fix display issue with flex
            $(document).on('click', '.chat-user-item', function() {
                $('#chat-window').addClass('show');
            });
            
            // Hide chat window properly
            $('#close-chat').on('click', function() {
                $('#chat-window').removeClass('show');
                $('.chat-user-item').removeClass('active');
                selectedUserId = null;
                selectedUserEmail = null;
                
                // Clear interval
                if (realtimeChatInterval) {
                    clearInterval(realtimeChatInterval);
                    realtimeChatInterval = null;
                }
            });
            
            // Initial load of active chats
            updateActiveChats();
            
            // Update admin online status
            updateAdminStatus();
            
            // Poll for new active chats every 30 seconds
            setInterval(updateActiveChats, 30000);
            
            // Update admin status every 30 seconds
            setInterval(updateAdminStatus, 30000);
            
            // Update admin online status
            function updateAdminStatus() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'bloobee_update_admin_status',
                        nonce: '<?php echo wp_create_nonce('bloobee_admin_chat'); ?>'
                    },
                    success: function(response) {
                        console.log('Admin status updated:', response.data.is_online);
                    }
                });
            }
            
            // Update active chats function
            function updateActiveChats() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'bloobee_poll_new_messages',
                        nonce: '<?php echo wp_create_nonce('bloobee-poll-nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success && response.data) {
                            const activeChats = response.data;
                            $('#active-chats').empty();
                            
                            if (activeChats.length === 0) {
                                $('#active-chats').html('<p>No active chats at the moment.</p>');
                                updateBadge(0);
                            } else {
                                updateBadge(activeChats.length);
                                
                                activeChats.forEach(function(chat) {
                                    const timeAgo = getTimeAgo(chat.timestamp);
                                    const gravatarUrl = chat.gravatar_url || 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y';
                                    
                                    const chatItem = $(`
                                        <div class="chat-user-item" data-user-id="${chat.user_id}" data-user-email="${chat.user_email}">
                                            <div class="chat-user-info">
                                                <div class="chat-user-avatar">
                                                    <img src="${gravatarUrl}" alt="User Avatar">
                                                </div>
                                                <div class="chat-user-details">
                                                    <div class="chat-user-name">${chat.user_name || 'Anonymous'}</div>
                                                    <div class="chat-user-email">${chat.user_email || 'No email provided'}</div>
                                                    <div class="chat-user-time">Started ${timeAgo}</div>
                                                </div>
                                                ${chat.unread ? '<span class="new-message-badge">New</span>' : ''}
                                            </div>
                                        </div>
                                    `);
                                    
                                    $('#active-chats').append(chatItem);
                                });
                                
                                // If the currently selected user is still in the list, keep them selected
                                if (selectedUserId) {
                                    $(`.chat-user-item[data-user-id="${selectedUserId}"]`).addClass('active');
                                }
                            }
                        }
                    }
                });
            }
            
            // Format time ago
            function getTimeAgo(timestamp) {
                const now = Math.floor(Date.now() / 1000);
                const seconds = now - timestamp;
                
                if (seconds < 60) {
                    return 'just now';
                } else if (seconds < 3600) {
                    const minutes = Math.floor(seconds / 60);
                    return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
                } else if (seconds < 86400) {
                    const hours = Math.floor(seconds / 3600);
                    return `${hours} hour${hours > 1 ? 's' : ''} ago`;
                } else {
                    const days = Math.floor(seconds / 86400);
                    return `${days} day${days > 1 ? 's' : ''} ago`;
                }
            }
            
            // Handle chat user selection
            $(document).on('click', '.chat-user-item', function() {
                $('.chat-user-item').removeClass('active');
                $(this).addClass('active');
                selectedUserId = $(this).data('user-id');
                selectedUserEmail = $(this).data('user-email');
                
                // Get user details
                const userName = $(this).find('.chat-user-name').text();
                const userEmail = $(this).find('.chat-user-email').text();
                
                // Update chat window header
                $('#chat-user-name').text(`${userName} (${userEmail})`);
                
                // Mark messages as read
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'bloobee_mark_messages_read',
                        nonce: '<?php echo wp_create_nonce('bloobee-admin-nonce'); ?>',
                        user_id: selectedUserId
                    }
                });
                
                // Remove new message badge
                $(this).find('.new-message-badge').remove();
                
                // Load chat history
                displayChatHistory(selectedUserId);
                
                // Start realtime chat
                startRealtimeChat(selectedUserId);
            });
            
            // Display chat history
            function displayChatHistory(userId) {
                $('#chat-messages').empty();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'bloobee_get_chat_history',
                        nonce: '<?php echo wp_create_nonce('bloobee-admin-nonce'); ?>',
                        user_id: userId
                    },
                    success: function(response) {
                        if (response.success && response.data) {
                            const chatHistory = response.data;
                            
                            if (chatHistory.length > 0) {
                                lastMessageTimestamp = chatHistory[chatHistory.length - 1].timestamp;
                                
                                chatHistory.forEach(function(message) {
                                    const time = new Date(message.timestamp * 1000);
                                    const formattedTime = time.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                                    let messageHtml;
                                    
                                    if (message.type === 'subject') {
                                        // System message for subject selection
                                        messageHtml = `
                                            <div class="chat-message system-message">
                                                <div class="chat-message-content">
                                                    User selected: ${message.message}
                                                </div>
                                            </div>
                                        `;
                                    } else if (message.is_admin) {
                                        // Admin message
                                        messageHtml = `
                                            <div class="chat-message admin-message">
                                                <div class="chat-message-header">
                                                    <span class="chat-message-sender">Admin</span>
                                                    <span class="chat-message-time">${formattedTime}</span>
                                                </div>
                                                <div class="chat-message-content">${message.message}</div>
                                            </div>
                                        `;
                                    } else if (message.is_system) {
                                        // System message
                                        messageHtml = `
                                            <div class="chat-message system-message">
                                                <div class="chat-message-content">
                                                    ${message.message}
                                                </div>
                                            </div>
                                        `;
                                    } else {
                                        // User message
                                        messageHtml = `
                                            <div class="chat-message user-message">
                                                <div class="chat-message-header">
                                                    <span class="chat-message-sender">${$('#chat-user-name').text().split(' (')[0]}</span>
                                                    <span class="chat-message-time">${formattedTime}</span>
                                                </div>
                                                <div class="chat-message-content">${message.message}</div>
                                            </div>
                                        `;
                                    }
                                    
                                    $('#chat-messages').append(messageHtml);
                                });
                                
                                // Scroll to bottom
                                scrollToBottom();
                            }
                        }
                    }
                });
            }
            
            // Start realtime chat updates
            function startRealtimeChat(userId) {
                // Clear any existing interval
                if (realtimeChatInterval) {
                    clearInterval(realtimeChatInterval);
                }
                
                // Set new interval
                realtimeChatInterval = setInterval(function() {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'bloobee_get_new_messages',
                            nonce: '<?php echo wp_create_nonce('bloobee-admin-nonce'); ?>',
                            user_id: userId,
                            last_timestamp: lastMessageTimestamp
                        },
                        success: function(response) {
                            if (response.success && response.data && response.data.messages.length > 0) {
                                const newMessages = response.data.messages;
                                lastMessageTimestamp = response.data.last_timestamp;
                                
                                newMessages.forEach(function(message) {
                                    const time = new Date(message.timestamp * 1000);
                                    const formattedTime = time.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                                    let messageHtml;
                                    
                                    if (message.is_admin) {
                                        // Admin message
                                        messageHtml = `
                                            <div class="chat-message admin-message">
                                                <div class="chat-message-header">
                                                    <span class="chat-message-sender">Admin</span>
                                                    <span class="chat-message-time">${formattedTime}</span>
                                                </div>
                                                <div class="chat-message-content">${message.message}</div>
                                            </div>
                                        `;
                                    } else if (message.is_system) {
                                        // System message
                                        messageHtml = `
                                            <div class="chat-message system-message">
                                                <div class="chat-message-content">
                                                    ${message.message}
                                                </div>
                                            </div>
                                        `;
                                    } else {
                                        // User message
                                        messageHtml = `
                                            <div class="chat-message user-message">
                                                <div class="chat-message-header">
                                                    <span class="chat-message-sender">${$('#chat-user-name').text().split(' (')[0]}</span>
                                                    <span class="chat-message-time">${formattedTime}</span>
                                                </div>
                                                <div class="chat-message-content">${message.message}</div>
                                            </div>
                                        `;
                                    }
                                    
                                    $('#chat-messages').append(messageHtml);
                                });
                                
                                // Scroll to bottom
                                scrollToBottom();
                                
                                // Mark as read
                                $.ajax({
                                    url: ajaxurl,
                                    type: 'POST',
                                    data: {
                                        action: 'bloobee_mark_messages_read',
                                        nonce: '<?php echo wp_create_nonce('bloobee-admin-nonce'); ?>',
                                        user_id: userId
                                    }
                                });
                            }
                        }
                    });
                }, 3000);
            }
            
            // Send message function
            $('#send-message').on('click', function() {
                sendAdminMessage();
            });
            
            // Send message on Enter key (Shift+Enter for new line)
            $('#admin-message').on('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendAdminMessage();
                }
            });
            
            function sendAdminMessage() {
                const message = $('#admin-message').val().trim();
                
                if (message && selectedUserId) {
                    $('#admin-message').val('');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'bloobee_admin_message',
                            nonce: '<?php echo wp_create_nonce('bloobee-admin-nonce'); ?>',
                            user_id: selectedUserId,
                            message: message
                        },
                        success: function(response) {
                            if (response.success) {
                                const time = new Date();
                                const formattedTime = time.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                                
                                const messageHtml = `
                                    <div class="chat-message admin-message">
                                        <div class="chat-message-header">
                                            <span class="chat-message-sender">Admin</span>
                                            <span class="chat-message-time">${formattedTime}</span>
                                        </div>
                                        <div class="chat-message-content">${message}</div>
                                    </div>
                                `;
                                
                                $('#chat-messages').append(messageHtml);
                                
                                // Update last message timestamp
                                lastMessageTimestamp = Math.floor(Date.now() / 1000);
                                
                                // Scroll to bottom
                                scrollToBottom();
                            }
                        }
                    });
                }
            }
            
            // Send transcript button
            $('#send-transcript').on('click', function() {
                if (selectedUserId && selectedUserEmail) {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'bloobee_send_transcript',
                            nonce: '<?php echo wp_create_nonce('bloobee-admin-nonce'); ?>',
                            user_id: selectedUserId,
                            email: selectedUserEmail
                        },
                        beforeSend: function() {
                            // Show sending message
                            $('#send-transcript').text('Sending...').prop('disabled', true);
                        },
                        success: function(response) {
                            if (response.success) {
                                // Show success message
                                alert('Transcript sent successfully!');
                                $('#send-transcript').text('Send Transcript').prop('disabled', false);
                            } else {
                                // Show error message
                                alert('Failed to send transcript: ' + response.data.message);
                                $('#send-transcript').text('Send Transcript').prop('disabled', false);
                            }
                        },
                        error: function() {
                            // Show error message
                            alert('An error occurred while sending the transcript.');
                            $('#send-transcript').text('Send Transcript').prop('disabled', false);
                        }
                    });
                } else {
                    alert('No active chat selected or no email available.');
                }
            });
            
            // End conversation button
            $('#end-conversation').on('click', function() {
                if (selectedUserId) {
                    if (confirm('Are you sure you want to end this conversation? This will remove the chat from active chats.')) {
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'bloobee_end_conversation',
                                nonce: '<?php echo wp_create_nonce('bloobee_admin_chat'); ?>',
                                user_id: selectedUserId
                            },
                            success: function(response) {
                                if (response.success) {
                                    // Add system message
                                    const messageHtml = `
                                        <div class="chat-message system-message">
                                            <div class="chat-message-content">
                                                Conversation ended by admin
                                            </div>
                                        </div>
                                    `;
                                    $('#chat-messages').append(messageHtml);
                                    scrollToBottom();
                                    
                                    // Remove from active chats list
                                    $(`.chat-user-item[data-user-id="${selectedUserId}"]`).remove();
                                    
                                    // If no more active chats, show message
                                    if ($('.chat-user-item').length === 0) {
                                        $('#active-chats').html('<p>No active chats at the moment.</p>');
                                        updateBadge(0);
                                    } else {
                                        updateBadge($('.chat-user-item').length);
                                    }
                                    
                                    // Close chat window
                                    setTimeout(function() {
                                        $('#close-chat').click();
                                    }, 1500);
                                }
                            }
                        });
                    }
                }
            });
            
            // Scroll chat to bottom
            function scrollToBottom() {
                const chatMessages = document.getElementById('chat-messages');
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
            
            // Helper function to update the admin menu notification badge
            function updateBadge(count) {
                const $menu = $('#toplevel_page_bloobee-smartchat');
                const $notification = $menu.find('.notification-count');
                
                if (count > 0) {
                    if ($notification.length) {
                        $notification.text(count);
                    } else {
                        $menu.find('.wp-menu-name').append(`<span class="notification-count">${count}</span>`);
                    }
                } else {
                    $notification.remove();
                }
            }
        });
    </script>
    <?php
}

// Register AJAX handlers for live chat
add_action('wp_ajax_bloobee_poll_new_messages', 'handle_poll_new_messages');
add_action('wp_ajax_bloobee_get_chat_history', 'handle_get_chat_history');
add_action('wp_ajax_bloobee_admin_message', 'handle_admin_message');
add_action('wp_ajax_bloobee_mark_messages_read', 'handle_mark_messages_read');
add_action('wp_ajax_bloobee_get_new_messages', 'handle_get_new_messages');
add_action('wp_ajax_bloobee_update_notification_count', 'handle_update_notification_count');

// AJAX handler to update the notification count
function handle_update_notification_count() {
    // Verify nonce
    check_ajax_referer('bloobee_admin_nonce', 'nonce');
    
    // Get and count active chats
    $active_chats = get_option('bloobee_active_chats', array());
    $chat_count = count($active_chats);
    
    // Send count as JSON response
    wp_send_json_success(array('count' => $chat_count));
}

// Add admin online status tracking
function update_admin_online_status() {
    $current_time = time();
    $online_admins = get_option('bloobee_online_admins', array());
    
    error_log('Before update - Online Admins: ' . print_r($online_admins, true));
    
    // Clean up old statuses (older than 2 minutes)
    foreach ($online_admins as $admin_id => $last_active) {
        if ($current_time - $last_active > 120) { // 2 minutes threshold
            error_log('Removing admin: ' . $admin_id . ' (inactive for ' . ($current_time - $last_active) . ' seconds)');
            unset($online_admins[$admin_id]);
        }
    }
    
    // Update current admin's status
    $current_user_id = get_current_user_id();
    error_log('Current User ID: ' . $current_user_id);
    
    if ($current_user_id && current_user_can('manage_options')) {
        error_log('Updating admin status for user: ' . $current_user_id);
        $online_admins[$current_user_id] = $current_time;
    } else {
        error_log('Not updating admin status - current user is not an admin');
    }
    
    error_log('After update - Online Admins: ' . print_r($online_admins, true));
    
    update_option('bloobee_online_admins', $online_admins);
    
    $is_admin_online = !empty($online_admins);
    error_log('Is any admin online? ' . ($is_admin_online ? 'Yes' : 'No'));
    
    return $is_admin_online;
}

// Add this to check if any admin is online
function is_admin_online() {
    $online_admins = get_option('bloobee_online_admins', array());
    $current_time = time();
    
    // Debug logging
    error_log('Online Admins: ' . print_r($online_admins, true));
    error_log('Current Time: ' . $current_time);
    
    $is_online = false;
    foreach ($online_admins as $admin_id => $last_active) {
        error_log('Admin ID: ' . $admin_id . ', Last Active: ' . $last_active . ', Difference: ' . ($current_time - $last_active));
        if ($current_time - $last_active <= 120) { // 2 minutes threshold
            $is_online = true;
            break;
        }
    }
    
    error_log('Is Admin Online: ' . ($is_online ? 'true' : 'false'));
    return $is_online;
}

// Add AJAX handler for checking admin online status
add_action('wp_ajax_bloobee_check_admin_status', 'handle_check_admin_status');
add_action('wp_ajax_nopriv_bloobee_check_admin_status', 'handle_check_admin_status');

function handle_check_admin_status() {
    check_ajax_referer('bloobee_chat_nonce', 'nonce');
    
    $is_online = is_admin_online();
    $active_chats = get_option('bloobee_active_chats', array());
    $queue_position = count($active_chats);
    $estimated_wait = $queue_position * 5; // 5 minutes per chat
    
    // Get admin info if online
    $admin_info = array();
    if ($is_online) {
        $online_admins = get_option('bloobee_online_admins', array());
        if (!empty($online_admins)) {
            $admin_id = key($online_admins); // Get first online admin
            $admin_user = get_userdata($admin_id);
            if ($admin_user) {
                $admin_info = array(
                    'name' => $admin_user->display_name,
                    'gravatar' => get_avatar_url($admin_user->user_email, array('size' => 32)),
                );
            } else {
                // Fallback to notification email
                $admin_email = get_option('bloobee_notification_email');
                $admin_info = array(
                    'name' => 'Support Staff',
                    'gravatar' => get_avatar_url($admin_email, array('size' => 32)),
                );
            }
        }
    }
    
    wp_send_json_success(array(
        'is_online' => $is_online,
        'queue_position' => $queue_position,
        'estimated_wait' => $estimated_wait,
        'admin_info' => $admin_info
    ));
}

// Add AJAX handler for updating admin status
add_action('wp_ajax_bloobee_update_admin_status', 'handle_update_admin_status');

function handle_update_admin_status() {
    check_ajax_referer('bloobee_admin_chat', 'nonce');
    
    $is_online = update_admin_online_status();
    wp_send_json_success(array('is_online' => $is_online));
}

// Add CSS styles for admin menu notifications
function add_admin_menu_notification_styles() {
    ?>
    <style type="text/css">
        /* Admin Menu Notification Styles */
        #toplevel_page_bloobee-chat .wp-menu-name .notification-count {
            display: inline-block;
            background-color: #d63638;
            color: white;
            font-size: 11px;
            line-height: 1.4;
            font-weight: 600;
            padding: 0 5px;
            border-radius: 10px;
            margin-left: 5px;
            vertical-align: middle;
        }
        
        /* Chat User Items */
        .chat-user-item {
            display: flex;
            padding: 10px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .chat-user-item:hover {
            background-color: #f9f9f9;
        }
        
        .chat-user-item.active {
            background-color: #f0f7ff;
        }
        
        .chat-user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }
        
        .chat-user-info {
            flex-grow: 1;
        }
        
        .chat-user-name {
            font-weight: 600;
            margin-bottom: 3px;
        }
        
        .chat-user-time {
            font-size: 12px;
            color: #666;
        }
        
        .chat-user-badge {
            display: inline-block;
            background-color: #d63638;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-left: 5px;
        }
        
        /* Global Bloobee Admin Styles */
        .bloobee-admin-wrap {
            min-height: 100%;
            display: flex;
            flex-direction: column;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            overflow: hidden;
        }
        
        .bloobee-header {
            background-color: #96bad0;
            padding: 15px 25px;
            border-top-left-radius: 50px;
            border-top-right-radius: 50px;
            display: block;
            width: 96%;
            height: 100px;
            background-image: url(<?php echo plugins_url('corner.png', __FILE__); ?>);
            background-position: right bottom;
            background-repeat: no-repeat;
            flex-shrink: 0;
            margin-bottom: 0;
        }
        
        .bloobee-header-logo {
            float: left;
            margin-right: 15px;
        }
        
        .bloobee-header-logo img {
            width: 100px;
            height: 100px;
        }
        
        .bloobee-header-title h1 {
            font-size: 27px;
            font-weight: 600;
            color: #477eb6;
            margin-top: 0;
            padding-top: 15px;
        }
        
        .bloobee-header-title h2 {
            font-size: 19px;
            font-weight: 500;
            margin-top: 0;
            padding-top: 0;
        }
        
        .bloobee-form-container {
            padding: 25px 50px 50px 50px;
            background-color: rgba(255, 255, 255, 0.7);
            background-image: url(<?php echo plugins_url('corner.png', __FILE__); ?>);
            background-position: right bottom;
            background-repeat: no-repeat;
            width: calc(100% - 100px);
            display: block;
            flex-grow: 1;
            overflow: hidden;
        }
        
        .bloobee-btn-primary {
            background-color: #477eb6 !important;
            color: white !important;
            border-color: #477eb6 !important;
            padding: 8px 15px;
            font-size: 14px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        
        .bloobee-btn-primary:hover {
            background-color: #3a6ca0 !important;
        }
        
        .bloobee-btn-danger {
            background-color: #d63638 !important;
            color: white !important;
            border-color: #d63638 !important;
            padding: 8px 15px;
            font-size: 14px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        
        .bloobee-btn-danger:hover {
            background-color: #b32d2e !important;
        }
        
        /* Adjust WordPress form styles to match Bloobee design */
        .bloobee-form-container .form-table th {
            color: #477eb6;
            font-weight: 600;
        }
        
        .bloobee-form-container .regular-text,
        .bloobee-form-container .large-text {
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 8px;
        }
        
        .bloobee-form-container .regular-text:focus,
        .bloobee-form-container .large-text:focus {
            border-color: #477eb6;
            box-shadow: 0 0 0 1px #477eb6;
        }
        
        .bloobee-form-container h2 {
            color: #477eb6;
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 10px;
            margin-top: 30px;
        }
        
        .bloobee-form-container table.wp-list-table th {
            background: #f0f0f0;
            color: #477eb6;
        }
        
        .bloobee-form-container .qa-pair,
        .bloobee-form-container .subject-item {
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        
        .bloobee-form-container .button {
            background: #f0f0f0;
            border: 1px solid #ccc;
            color: #333;
        }
        
        .bloobee-form-container .button:hover {
            background: #e0e0e0;
        }
    </style>
    <?php
}
add_action('admin_head', 'add_admin_menu_notification_styles');

// Update menu title to show notification for active chats
function update_live_chat_menu_title($parent_file) {
    global $menu;
    
    // Get active chats
    $active_chats = get_option('bloobee_active_chats', array());
    $chat_count = count($active_chats);
    
    // Only update if there are active chats
    if ($chat_count > 0) {
        // Find the Bloobee Chat menu item
        foreach ($menu as $key => $item) {
            if (isset($item[2]) && $item[2] === 'bloobee-chat') {
                // Update the menu title with the notification count
                $menu[$key][0] = 'Bloobee Chat <span class="notification-count">' . $chat_count . '</span>';
                break;
            }
        }
    }
    
    return $parent_file;
}
add_filter('admin_menu', 'update_live_chat_menu_title');

// Add new AJAX handler for marking messages as read
add_action('wp_ajax_bloobee_mark_messages_read', 'handle_mark_messages_read');

function handle_mark_messages_read() {
    check_ajax_referer('bloobee-admin-nonce', 'nonce');
    
    $user_id = sanitize_text_field($_POST['user_id']);
    
    // Update active chats to mark messages as read
    $active_chats = get_option('bloobee_active_chats', array());
    
    if (isset($active_chats[$user_id])) {
        $active_chats[$user_id]['has_new_message'] = false;
        update_option('bloobee_active_chats', $active_chats);
        wp_send_json_success(array('message' => 'Messages marked as read'));
    } else {
        wp_send_json_error(array('message' => 'User not found in active chats'));
    }
}

// AJAX handler to get new messages since the last timestamp
function handle_get_new_messages() {
    // Check if this is an admin or frontend user and verify appropriate nonce
    if (current_user_can('manage_options')) {
        check_ajax_referer('bloobee-admin-nonce', 'nonce');
    } else {
        check_ajax_referer('bloobee_chat_nonce', 'nonce');
    }
    
    $user_id = sanitize_text_field($_POST['user_id']);
    $last_timestamp = intval($_POST['last_timestamp']);
    
    error_log('Getting new messages for user: ' . $user_id . ' since timestamp: ' . $last_timestamp);
    
    // Get all chat history
    $chat_history = get_option('bloobee_chat_history', array());
    
    // Filter chat history for this user and new messages
    $messages = array();
    $new_last_timestamp = $last_timestamp;
    
    foreach ($chat_history as $message) {
        if ($message['user_id'] === $user_id && $message['timestamp'] > $last_timestamp) {
            // Ensure consistent message format
            $processed_message = array(
                'timestamp' => $message['timestamp'],
                'message' => $message['message'],
                'is_admin' => isset($message['is_admin']) ? $message['is_admin'] : false,
                'is_system' => isset($message['is_system']) ? $message['is_system'] : false,
                'user_id' => $user_id
            );
            
            $messages[] = $processed_message;
            
            if ($message['timestamp'] > $new_last_timestamp) {
                $new_last_timestamp = $message['timestamp'];
            }
        }
    }
    
    error_log('Found ' . count($messages) . ' new messages. New timestamp: ' . $new_last_timestamp);
    
    // If messages were found and this is an admin user, mark them as read
    if (!empty($messages) && current_user_can('manage_options')) {
        $active_chats = get_option('bloobee_active_chats', array());
        if (isset($active_chats[$user_id])) {
            $active_chats[$user_id]['has_new_message'] = false;
            update_option('bloobee_active_chats', $active_chats);
        }
    }
    
    wp_send_json_success(array(
        'messages' => $messages,
        'last_timestamp' => $new_last_timestamp
    ));
}

// Register the AJAX action for both logged in and non-logged in users
add_action('wp_ajax_bloobee_get_new_messages', 'handle_get_new_messages');
add_action('wp_ajax_nopriv_bloobee_get_new_messages', 'handle_get_new_messages');

// Add new AJAX handler for ending conversations
add_action('wp_ajax_bloobee_end_conversation', 'handle_end_conversation');

function handle_end_conversation() {
    check_ajax_referer('bloobee_admin_chat', 'nonce');
    
    $user_id = sanitize_text_field($_POST['user_id']);
    $active_chats = get_option('bloobee_active_chats', array());
    
    if (isset($active_chats[$user_id])) {
        // Add final system message to chat history
        $chat_history = get_option('bloobee_chat_history', array());
        $chat_history[] = array(
            'timestamp' => time(),
            'user_id' => $user_id,
            'message' => "Conversation ended by admin",
            'is_system' => true
        );
        update_option('bloobee_chat_history', $chat_history);
        
        // Remove from active chats
        unset($active_chats[$user_id]);
        update_option('bloobee_active_chats', $active_chats);
    }
    
    wp_send_json_success();
}

// Add script for admin chat page
function add_admin_chat_notification_script() {
    wp_enqueue_script('bloobee-admin-chat', plugin_dir_url(__FILE__) . 'admin/admin-chat.js', array('jquery'), '1.0.0', true);
    
    wp_localize_script('bloobee-admin-chat', 'bloobeeChatAdmin', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('bloobee-admin-nonce'),
        'admin_nonce' => wp_create_nonce('bloobee_admin_chat')
    ));
}
add_action('admin_enqueue_scripts', 'add_admin_chat_notification_script');

// Add AJAX handler for checking new chats from any admin page
add_action('wp_ajax_bloobee_check_new_chats', 'handle_check_new_chats');

function handle_check_new_chats() {
    check_ajax_referer('bloobee_admin_chat', 'nonce');
    
    $active_chats = get_option('bloobee_active_chats', array());
    $new_chats = 0;
    
    foreach ($active_chats as $chat) {
        if (isset($chat['has_new_message']) && $chat['has_new_message']) {
            $new_chats++;
        }
    }
    
    wp_send_json_success(array('new_chats' => $new_chats));
}

// Add notification badge to admin menu
add_action('admin_head', 'add_chat_notification_badge');

function add_chat_notification_badge() {
    $active_chats = get_option('bloobee_active_chats', array());
    $chat_count = count($active_chats);
    
    if ($chat_count < 1) {
        return;
    }
    
    // Add CSS for the notification badge
    ?>
    <style>
    .bloobee-notification-badge {
        display: inline-block;
        vertical-align: top;
        margin: 1px 0 0 5px;
        padding: 0 5px;
        min-width: 18px;
        height: 18px;
        border-radius: 9px;
        background-color: #d63638;
        color: #fff;
        font-size: 11px;
        line-height: 1.6;
        text-align: center;
        z-index: 26;
        box-sizing: border-box;
    }
    </style>
    <script>
    jQuery(document).ready(function($) {
        // Add the badge to the menu item
        function updateBadge(count) {
            // Remove any existing badges
            $('.bloobee-notification-badge').remove();
            
            if (count > 0) {
                // Add badge to main menu
                $('a.toplevel_page_bloobee-smart-chat .wp-menu-name').append('<span class="bloobee-notification-badge">' + count + '</span>');
                
                // Add badge to Live Chat submenu
                $('a[href="admin.php?page=bloobee-live-chat"]').append('<span class="bloobee-notification-badge">' + count + '</span>');
            }
        }
        
        // Initial update
        updateBadge(<?php echo $chat_count; ?>);
        
        // Update every 30 seconds
        setInterval(function() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'bloobee_update_notification_count',
                    nonce: '<?php echo wp_create_nonce('bloobee_admin_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        updateBadge(response.data.count);
                    }
                }
            });
        }, 30000);
    });
    </script>
    <?php
}

// Update the admin menu notification when chat status changes
function update_admin_menu_notification() {
    // This will be called via AJAX to refresh the notification badge
    ?>
    <script>
    function updateAdminMenuNotification(count) {
        jQuery(document).ready(function($) {
            const liveChatMenuItem = $('#adminmenu .wp-submenu li a[href="admin.php?page=bloobee-live-chat"]');
            
            // Remove existing notification badge
            liveChatMenuItem.find('.update-plugins').remove();
            
            // Add new badge if there are active chats
            if (count > 0) {
                liveChatMenuItem.append(' <span class="update-plugins count-' + count + '"><span class="update-count">' + count + '</span></span>');
            }
        });
    }
    </script>
    <?php
}
add_action('admin_head', 'update_admin_menu_notification');

// AJAX handler to get active chats
function handle_poll_new_messages() {
    check_ajax_referer('bloobee-poll-nonce', 'nonce');
    
    $active_chats = get_option('bloobee_active_chats', array());
    $formatted_chats = array();
    
    foreach ($active_chats as $user_id => $chat) {
        // Get Gravatar URL
        $gravatar_url = get_avatar_url($chat['email'] ?? '', array('size' => 40, 'default' => 'mp'));
        
        // Format chat data
        $formatted_chats[] = array(
            'user_id' => $user_id,
            'user_name' => $chat['name'] ?? 'Anonymous',
            'user_email' => $chat['email'] ?? '',
            'timestamp' => $chat['timestamp'] ?? time(),
            'subject' => $chat['subject'] ?? '',
            'unread' => $chat['has_new_message'] ?? false,
            'gravatar_url' => $gravatar_url
        );
    }
    
    // Sort by timestamp (newest first)
    usort($formatted_chats, function($a, $b) {
        return $b['timestamp'] - $a['timestamp'];
    });
    
    wp_send_json_success($formatted_chats);
}

// AJAX handler to get chat history for a specific user
function handle_get_chat_history() {
    check_ajax_referer('bloobee-admin-nonce', 'nonce');
    
    $user_id = sanitize_text_field($_POST['user_id']);
    
    // Get all chat history
    $chat_history = get_option('bloobee_chat_history', array());
    
    // Filter chat history for this user
    $user_chat_history = array();
    
    foreach ($chat_history as $message) {
        if ($message['user_id'] === $user_id) {
            // Add the initial subject message as a special 'type'
            if (isset($message['subject']) && !isset($message['type'])) {
                $user_chat_history[] = array(
                    'type' => 'subject',
                    'message' => $message['subject'],
                    'timestamp' => $message['timestamp']
                );
            }
            
            $user_chat_history[] = $message;
        }
    }
    
    // Sort by timestamp
    usort($user_chat_history, function($a, $b) {
        return $a['timestamp'] - $b['timestamp'];
    });
    
    wp_send_json_success($user_chat_history);
}

// AJAX handler for sending a message from admin to user
function handle_admin_message() {
    check_ajax_referer('bloobee-admin-nonce', 'nonce');
    
    $user_id = sanitize_text_field($_POST['user_id']);
    $message = sanitize_text_field($_POST['message']);
    $admin_id = get_current_user_id();
    $timestamp = time();
    
    error_log('Admin sending message to user: ' . $user_id . ', Message: ' . $message);
    
    // Store in global chat history
    $chat_history = get_option('bloobee_chat_history', array());
    $message_entry = array(
        'timestamp' => $timestamp,
        'user_id' => $user_id,
        'admin_id' => $admin_id,
        'message' => $message,
        'is_admin' => true
    );
    
    $chat_history[] = $message_entry;
    update_option('bloobee_chat_history', $chat_history);
    
    // Make sure the chat is still marked as active
    $active_chats = get_option('bloobee_active_chats', array());
    if (isset($active_chats[$user_id])) {
        $active_chats[$user_id]['timestamp'] = $timestamp;
        update_option('bloobee_active_chats', $active_chats);
    }
    
    error_log('Admin message stored successfully');
    
    wp_send_json_success(array(
        'message' => 'Message sent',
        'timestamp' => $timestamp,
        'message_data' => $message_entry
    ));
}

// Add AJAX handler for sending chat transcript
add_action('wp_ajax_bloobee_send_transcript', 'handle_send_transcript');

function handle_send_transcript() {
    check_ajax_referer('bloobee-admin-nonce', 'nonce');
    
    $user_id = sanitize_text_field($_POST['user_id']);
    $email = sanitize_email($_POST['email']);
    
    if (empty($email)) {
        wp_send_json_error(array('message' => 'No valid email address provided.'));
        return;
    }
    
    // Get all chat history for this user
    $chat_history = get_option('bloobee_chat_history', array());
    $user_chat_history = array();
    
    foreach ($chat_history as $message) {
        if ($message['user_id'] === $user_id) {
            $user_chat_history[] = $message;
        }
    }
    
    // Sort by timestamp
    usort($user_chat_history, function($a, $b) {
        return $a['timestamp'] - $b['timestamp'];
    });
    
    if (empty($user_chat_history)) {
        wp_send_json_error(array('message' => 'No chat history found for this user.'));
        return;
    }
    
    // Generate transcript
    $site_name = get_bloginfo('name');
    $date = date('Y-m-d H:i:s');
    $transcript = "Chat Transcript from $site_name\n";
    $transcript .= "Date: $date\n\n";
    
    $user_name = '';
    
    foreach ($user_chat_history as $message) {
        $time = date('H:i:s', $message['timestamp']);
        
        if (!empty($message['name']) && empty($user_name)) {
            $user_name = $message['name'];
        }
        
        if (isset($message['type']) && $message['type'] === 'subject') {
            $transcript .= "[$time] SUBJECT: {$message['message']}\n";
        } elseif (isset($message['is_admin']) && $message['is_admin']) {
            $transcript .= "[$time] Support: {$message['message']}\n";
        } elseif (isset($message['is_system']) && $message['is_system']) {
            $transcript .= "[$time] SYSTEM: {$message['message']}\n";
        } else {
            $transcript .= "[$time] " . ($user_name ? $user_name : 'You') . ": {$message['message']}\n";
        }
    }
    
    // Send email
    $subject = sprintf('[%s] Chat Transcript - %s', $site_name, $date);
    $headers = array('Content-Type: text/plain; charset=UTF-8');
    
    $email_sent = wp_mail($email, $subject, $transcript, $headers);
    
    if ($email_sent) {
        // Add a system message about the transcript
        $chat_history = get_option('bloobee_chat_history', array());
        $chat_history[] = array(
            'timestamp' => time(),
            'user_id' => $user_id,
            'message' => "Chat transcript was sent to your email: $email",
            'is_system' => true
        );
        update_option('bloobee_chat_history', $chat_history);
        
        wp_send_json_success(array('message' => 'Transcript sent successfully.'));
    } else {
        wp_send_json_error(array('message' => 'Failed to send email.'));
    }
}

// If not already defined, set debug constants
if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', true);
}
if (!defined('WP_DEBUG_LOG')) {
    define('WP_DEBUG_LOG', true);
}
if (!defined('WP_DEBUG_DISPLAY')) {
    define('WP_DEBUG_DISPLAY', false);
}

// Debug function to log chat history
function debug_chat_history() {
    $chat_history = get_option('bloobee_chat_history', array());
    error_log('Chat History Debug: ' . print_r($chat_history, true));
}
add_action('wp_footer', 'debug_chat_history');

// Fix chat history entries to ensure consistency
function fix_chat_history_entries() {
    $chat_history = get_option('bloobee_chat_history', array());
    $modified = false;
    
    foreach ($chat_history as $key => $message) {
        // Make sure admin messages have is_admin flag as boolean true
        if (isset($message['is_admin']) && $message['is_admin'] != false) {
            $chat_history[$key]['is_admin'] = true;
            $modified = true;
        }
        
        // Make sure system messages have is_system flag as boolean true
        if (isset($message['is_system']) && $message['is_system'] != false) {
            $chat_history[$key]['is_system'] = true;
            $modified = true;
        }
    }
    
    if ($modified) {
        update_option('bloobee_chat_history', $chat_history);
        error_log('Fixed chat history entries for consistency');
    }
}
add_action('init', 'fix_chat_history_entries');
