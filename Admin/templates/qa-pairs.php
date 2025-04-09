<?php
// Prevent direct access
defined('ABSPATH') or die('Access denied');
?>

<div class="wrap bloobee-admin-qa">
    <h1><?php echo esc_html__('Q&A Pairs', 'bloobee-smartchat'); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('bloobee_qa_nonce'); ?>
        
        <div id="qa-pairs-container">
            <?php if (!empty($qa_pairs)): ?>
                <?php foreach ($qa_pairs as $index => $pair): ?>
                    <div class="qa-pair">
                        <p>
                            <label for="question-<?php echo esc_attr($index); ?>"><?php echo esc_html__('Question', 'bloobee-smartchat'); ?>:</label>
                            <input type="text" 
                                id="question-<?php echo esc_attr($index); ?>" 
                                name="question[]" 
                                class="regular-text" 
                                value="<?php echo esc_attr($pair['question']); ?>">
                        </p>
                        <p>
                            <label for="answer-<?php echo esc_attr($index); ?>"><?php echo esc_html__('Answer', 'bloobee-smartchat'); ?>:</label>
                            <textarea 
                                id="answer-<?php echo esc_attr($index); ?>" 
                                name="answer[]" 
                                class="large-text" 
                                rows="3"><?php echo esc_textarea($pair['answer']); ?></textarea>
                        </p>
                        <button type="button" class="button remove-pair"><?php echo esc_html__('Remove', 'bloobee-smartchat'); ?></button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <template id="qa-template">
            <div class="qa-pair">
                <p>
                    <label for="question-new"><?php echo esc_html__('Question', 'bloobee-smartchat'); ?>:</label>
                    <input type="text" id="question-new" name="question[]" class="regular-text">
                </p>
                <p>
                    <label for="answer-new"><?php echo esc_html__('Answer', 'bloobee-smartchat'); ?>:</label>
                    <textarea id="answer-new" name="answer[]" class="large-text" rows="3"></textarea>
                </p>
                <button type="button" class="button remove-pair"><?php echo esc_html__('Remove', 'bloobee-smartchat'); ?></button>
            </div>
        </template>
        
        <button type="button" id="add-pair" class="button"><?php echo esc_html__('Add New Q&A Pair', 'bloobee-smartchat'); ?></button>
        
        <p class="submit">
            <input type="submit" name="save_qa" class="button button-primary" value="<?php echo esc_attr__('Save Q&A Pairs', 'bloobee-smartchat'); ?>">
        </p>
    </form>

    <script>
    jQuery(document).ready(function($) {
        // Add new Q&A pair
        $('#add-pair').on('click', function() {
            var template = $('#qa-template').html();
            $('#qa-pairs-container').append(template);
        });
        
        // Remove Q&A pair
        $(document).on('click', '.remove-pair', function() {
            $(this).closest('.qa-pair').remove();
        });
    });
    </script>
</div> 