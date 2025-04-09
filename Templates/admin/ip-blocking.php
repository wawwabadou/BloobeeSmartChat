<?php
/**
 * IP Blocking Admin Template
 */

// Prevent direct access
defined('ABSPATH') or die('Access denied');
?>

<div class="wrap">
    <h1><?php _e('IP Blocking', 'bloobee-smartchat'); ?></h1>
    
    <div class="card">
        <h2><?php _e('Block New IP Address', 'bloobee-smartchat'); ?></h2>
        <form id="bloobee-block-ip-form" class="bloobee-form">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="ip_address"><?php _e('IP Address', 'bloobee-smartchat'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="ip_address" name="ip_address" class="regular-text" required>
                        <p class="description"><?php _e('Enter the IP address to block', 'bloobee-smartchat'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="reason"><?php _e('Reason', 'bloobee-smartchat'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="reason" name="reason" class="regular-text">
                        <p class="description"><?php _e('Optional reason for blocking this IP', 'bloobee-smartchat'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="expires_at"><?php _e('Expiration', 'bloobee-smartchat'); ?></label>
                    </th>
                    <td>
                        <input type="datetime-local" id="expires_at" name="expires_at">
                        <p class="description"><?php _e('Optional expiration date for the block', 'bloobee-smartchat'); ?></p>
                    </td>
                </tr>
            </table>
            <?php wp_nonce_field('bloobee_smartchat_nonce', 'nonce'); ?>
            <p class="submit">
                <button type="submit" class="button button-primary"><?php _e('Block IP', 'bloobee-smartchat'); ?></button>
            </p>
        </form>
    </div>
    
    <div class="card">
        <h2><?php _e('Blocked IP Addresses', 'bloobee-smartchat'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('IP Address', 'bloobee-smartchat'); ?></th>
                    <th><?php _e('Reason', 'bloobee-smartchat'); ?></th>
                    <th><?php _e('Blocked At', 'bloobee-smartchat'); ?></th>
                    <th><?php _e('Expires At', 'bloobee-smartchat'); ?></th>
                    <th><?php _e('Actions', 'bloobee-smartchat'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($blocked_ips)): ?>
                    <tr>
                        <td colspan="5"><?php _e('No blocked IP addresses found.', 'bloobee-smartchat'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($blocked_ips as $ip): ?>
                        <tr>
                            <td><?php echo esc_html($ip['ip_address']); ?></td>
                            <td><?php echo esc_html($ip['reason']); ?></td>
                            <td><?php echo esc_html($ip['blocked_at']); ?></td>
                            <td><?php echo esc_html($ip['expires_at'] ?: __('Never', 'bloobee-smartchat')); ?></td>
                            <td>
                                <button class="button bloobee-unblock-ip" data-ip="<?php echo esc_attr($ip['ip_address']); ?>">
                                    <?php _e('Unblock', 'bloobee-smartchat'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Handle block IP form submission
    $('#bloobee-block-ip-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var submitButton = form.find('button[type="submit"]');
        
        submitButton.prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bloobee_block_ip',
                nonce: form.find('#nonce').val(),
                ip_address: form.find('#ip_address').val(),
                reason: form.find('#reason').val(),
                expires_at: form.find('#expires_at').val()
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('<?php _e('An error occurred while processing your request.', 'bloobee-smartchat'); ?>');
            },
            complete: function() {
                submitButton.prop('disabled', false);
            }
        });
    });
    
    // Handle unblock IP button click
    $('.bloobee-unblock-ip').on('click', function() {
        var button = $(this);
        var ip = button.data('ip');
        
        if (!confirm('<?php _e('Are you sure you want to unblock this IP address?', 'bloobee-smartchat'); ?>')) {
            return;
        }
        
        button.prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'bloobee_unblock_ip',
                nonce: $('#nonce').val(),
                ip_address: ip
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('<?php _e('An error occurred while processing your request.', 'bloobee-smartchat'); ?>');
            },
            complete: function() {
                button.prop('disabled', false);
            }
        });
    });
});
</script> 