<?php
// Prevent direct access
defined('ABSPATH') or die('Access denied');
?>

<div class="wrap bloobee-admin-subjects">
    <h1><?php echo esc_html__('Subjects', 'bloobee-smartchat'); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('bloobee_subjects_nonce'); ?>
        
        <div id="subjects-container">
            <?php if (!empty($subjects)): ?>
                <?php foreach ($subjects as $index => $subject): ?>
                    <div class="subject-item">
                        <p>
                            <label for="subject-<?php echo esc_attr($index); ?>"><?php echo esc_html__('Subject', 'bloobee-smartchat'); ?>:</label>
                            <input type="text" 
                                id="subject-<?php echo esc_attr($index); ?>" 
                                name="subject[]" 
                                class="regular-text" 
                                value="<?php echo esc_attr($subject); ?>">
                            <button type="button" class="button remove-subject"><?php echo esc_html__('Remove', 'bloobee-smartchat'); ?></button>
                        </p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <template id="subject-template">
            <div class="subject-item">
                <p>
                    <label for="subject-new"><?php echo esc_html__('Subject', 'bloobee-smartchat'); ?>:</label>
                    <input type="text" id="subject-new" name="subject[]" class="regular-text">
                    <button type="button" class="button remove-subject"><?php echo esc_html__('Remove', 'bloobee-smartchat'); ?></button>
                </p>
            </div>
        </template>
        
        <button type="button" id="add-subject" class="button"><?php echo esc_html__('Add New Subject', 'bloobee-smartchat'); ?></button>
        
        <p class="submit">
            <input type="submit" name="save_subjects" class="button button-primary" value="<?php echo esc_attr__('Save Subjects', 'bloobee-smartchat'); ?>">
        </p>
    </form>

    <script>
    jQuery(document).ready(function($) {
        // Add new subject
        $('#add-subject').on('click', function() {
            var template = $('#subject-template').html();
            $('#subjects-container').append(template);
        });
        
        // Remove subject
        $(document).on('click', '.remove-subject', function() {
            $(this).closest('.subject-item').remove();
        });
    });
    </script>
</div> 