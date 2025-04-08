// Add admin menu
add_action('admin_menu', 'bloobee_smartchat_admin_menu');

function bloobee_smartchat_admin_menu() {
    add_menu_page(
        'Bloobee SmartChat Settings', // Page title
        'Bloobee SmartChat', // Menu title
        'manage_options', // Capability
        'bloobee-smartchat', // Menu slug
        'bloobee_smartchat_admin_page', // Function to display the page
        'dashicons-format-chat', // Icon
        30 // Position
    );
}

// Create the admin page content
function bloobee_smartchat_admin_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }

    // Save settings if form is submitted
    if (isset($_POST['bloobee_save_settings'])) {
        check_admin_referer('bloobee_smartchat_settings');
        
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
        echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
    }

    // Get existing Q&A pairs
    $qa_pairs = get_option('bloobee_smartchat_qa_pairs', array());
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <form method="post" action="">
            <?php wp_nonce_field('bloobee_smartchat_settings'); ?>
            
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
                <input type="submit" name="bloobee_save_settings" class="button-primary" value="Save Settings">
            </p>
        </form>
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

register_activation_hook(__FILE__, 'bloobee_smartchat_activate');

function bloobee_smartchat_activate() {
    // Initialize empty Q&A pairs if they don't exist
    if (!get_option('bloobee_smartchat_qa_pairs')) {
        add_option('bloobee_smartchat_qa_pairs', array());
    }
} 