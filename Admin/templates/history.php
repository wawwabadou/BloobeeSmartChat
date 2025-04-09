<?php
// Prevent direct access
defined('ABSPATH') or die('Access denied');
?>

<div class="wrap bloobee-admin-history">
    <h1><?php echo esc_html__('Chat History', 'bloobee-smartchat'); ?></h1>
    
    <?php if (isset($_GET['deleted']) && $_GET['deleted'] == '1'): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html__('Conversation deleted successfully.', 'bloobee-smartchat'); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['conversation']) && !empty($_GET['conversation'])): ?>
        <?php 
        $conversation_id = intval($_GET['conversation']);
        $conversation_messages = $this->get_conversation_messages($conversation_id);
        $conversation_info = $this->get_conversation_info($conversation_id);
        ?>
        
        <div class="conversation-header">
            <a href="<?php echo esc_url(admin_url('admin.php?page=bloobee-history')); ?>" class="button">&larr; <?php echo esc_html__('Back to All Conversations', 'bloobee-smartchat'); ?></a>
            
            <?php if ($conversation_info): ?>
                <div class="conversation-meta">
                    <p>
                        <strong><?php echo esc_html__('Date:', 'bloobee-smartchat'); ?></strong> 
                        <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($conversation_info->created_at))); ?>
                    </p>
                    <p>
                        <strong><?php echo esc_html__('User:', 'bloobee-smartchat'); ?></strong> 
                        <?php echo esc_html($conversation_info->user_id ? (is_numeric($conversation_info->user_id) && get_userdata($conversation_info->user_id) ? get_userdata($conversation_info->user_id)->display_name : $conversation_info->user_id) : __('Guest', 'bloobee-smartchat')); ?>
                    </p>
                    <?php if (!empty($conversation_info->subject)): ?>
                        <p>
                            <strong><?php echo esc_html__('Subject:', 'bloobee-smartchat'); ?></strong> 
                            <?php echo esc_html($conversation_info->subject); ?>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="conversation-messages">
            <?php if (!empty($conversation_messages)): ?>
                <?php foreach ($conversation_messages as $message): ?>
                    <div class="message <?php echo esc_attr($message->is_bot ? 'bot-message' : 'user-message'); ?>">
                        <div class="message-header">
                            <span class="message-sender">
                                <?php 
                                if ($message->is_bot) {
                                    echo esc_html__('Chatbot', 'bloobee-smartchat');
                                } else {
                                    echo esc_html($conversation_info->user_id ? (is_numeric($conversation_info->user_id) && get_userdata($conversation_info->user_id) ? get_userdata($conversation_info->user_id)->display_name : $conversation_info->user_id) : __('Guest', 'bloobee-smartchat'));
                                }
                                ?>
                            </span>
                            <span class="message-time">
                                <?php echo esc_html(date_i18n(get_option('time_format'), strtotime($message->created_at))); ?>
                            </span>
                        </div>
                        <div class="message-content">
                            <?php echo wp_kses_post(nl2br($message->message)); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-messages"><?php echo esc_html__('No messages found for this conversation.', 'bloobee-smartchat'); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="conversation-actions">
            <button type="button" id="export-conversation" class="button button-primary"><?php echo esc_html__('Export Conversation', 'bloobee-smartchat'); ?></button>
            <button type="button" id="delete-conversation" class="button button-secondary"><?php echo esc_html__('Delete Conversation', 'bloobee-smartchat'); ?></button>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Export conversation
            $('#export-conversation').on('click', function() {
                var conversationData = <?php echo json_encode($conversation_messages); ?>;
                var conversationInfo = <?php echo json_encode($conversation_info); ?>;
                
                // Create transcript
                var transcript = "Conversation ID: " + conversationInfo.id + "\n";
                transcript += "Date: " + conversationInfo.created_at + "\n";
                transcript += "User: " + (conversationInfo.user_id ? "<?php echo esc_js($conversation_info->user_id ? (is_numeric($conversation_info->user_id) && get_userdata($conversation_info->user_id) ? get_userdata($conversation_info->user_id)->display_name : $conversation_info->user_id) : __('Guest', 'bloobee-smartchat')); ?>" : "Guest") + "\n";
                if (conversationInfo.subject) {
                    transcript += "Subject: " + conversationInfo.subject + "\n";
                }
                transcript += "\n--- MESSAGES ---\n\n";
                
                conversationData.forEach(function(message) {
                    var sender = message.is_bot ? "Chatbot" : "User";
                    var time = new Date(message.created_at).toLocaleTimeString();
                    transcript += "[" + time + "] " + sender + ": " + message.message + "\n\n";
                });
                
                // Create blob and download
                var blob = new Blob([transcript], {type: 'text/plain'});
                var link = document.createElement('a');
                link.href = window.URL.createObjectURL(blob);
                link.download = 'conversation-' + conversationInfo.id + '.txt';
                link.click();
            });
            
            // Delete conversation
            $('#delete-conversation').on('click', function() {
                if (confirm("<?php echo esc_js(__('Are you sure you want to delete this conversation? This cannot be undone.', 'bloobee-smartchat')); ?>")) {
                    window.location.href = "<?php echo esc_url(admin_url('admin.php?page=bloobee-history&action=delete&conversation=' . $conversation_id . '&_wpnonce=' . wp_create_nonce('delete_conversation'))); ?>";
                }
            });
        });
        </script>
        
    <?php else: ?>
        <?php 
        // Get all conversations
        $conversations = $this->get_all_conversations();
        
        // Pagination
        $current_page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
        $per_page = 20;
        $total_conversations = count($conversations);
        $total_pages = ceil($total_conversations / $per_page);
        $offset = ($current_page - 1) * $per_page;
        $conversations = array_slice($conversations, $offset, $per_page);
        ?>
        
        <form method="get" action="">
            <input type="hidden" name="page" value="bloobee-history">
            <p class="search-box">
                <label class="screen-reader-text" for="conversation-search"><?php echo esc_html__('Search Conversations', 'bloobee-smartchat'); ?></label>
                <input type="search" id="conversation-search" name="s" value="<?php echo isset($_GET['s']) ? esc_attr($_GET['s']) : ''; ?>">
                <input type="submit" class="button" value="<?php echo esc_attr__('Search Conversations', 'bloobee-smartchat'); ?>">
            </p>
        </form>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php echo esc_html__('ID', 'bloobee-smartchat'); ?></th>
                    <th><?php echo esc_html__('Date', 'bloobee-smartchat'); ?></th>
                    <th><?php echo esc_html__('User', 'bloobee-smartchat'); ?></th>
                    <th><?php echo esc_html__('Messages', 'bloobee-smartchat'); ?></th>
                    <th><?php echo esc_html__('Subject', 'bloobee-smartchat'); ?></th>
                    <th><?php echo esc_html__('Actions', 'bloobee-smartchat'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($conversations)): ?>
                    <?php foreach ($conversations as $conversation): ?>
                        <tr>
                            <td><?php echo esc_html($conversation->id); ?></td>
                            <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($conversation->created_at))); ?></td>
                            <td><?php 
                                $user_id = $conversation->user_id;
                                if (is_numeric($user_id) && $user_id > 0) {
                                    $user_data = get_userdata($user_id);
                                    echo $user_data ? esc_html($user_data->display_name) : esc_html($user_id);
                                } else {
                                    echo esc_html($user_id) ?: esc_html__('Guest', 'bloobee-smartchat');
                                }
                            ?></td>
                            <td><?php echo esc_html($conversation->message_count); ?></td>
                            <td><?php echo esc_html($conversation->subject ?: __('General', 'bloobee-smartchat')); ?></td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=bloobee-history&conversation=' . $conversation->id)); ?>" class="button button-small"><?php echo esc_html__('View', 'bloobee-smartchat'); ?></a>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=bloobee-history&action=delete&conversation=' . $conversation->id . '&_wpnonce=' . wp_create_nonce('delete_conversation'))); ?>" class="button button-small" onclick="return confirm('<?php echo esc_js(__('Are you sure you want to delete this conversation?', 'bloobee-smartchat')); ?>');"><?php echo esc_html__('Delete', 'bloobee-smartchat'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6"><?php echo esc_html__('No conversations found.', 'bloobee-smartchat'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <?php if ($total_pages > 1): ?>
            <div class="tablenav">
                <div class="tablenav-pages">
                    <span class="displaying-num">
                        <?php echo sprintf(
                            _n('%s item', '%s items', $total_conversations, 'bloobee-smartchat'),
                            number_format_i18n($total_conversations)
                        ); ?>
                    </span>
                    
                    <span class="pagination-links">
                        <?php 
                        $first_disabled = $current_page <= 1 ? 'disabled' : '';
                        $prev_disabled = $current_page <= 1 ? 'disabled' : '';
                        $next_disabled = $current_page >= $total_pages ? 'disabled' : '';
                        $last_disabled = $current_page >= $total_pages ? 'disabled' : '';
                        
                        $search_params = isset($_GET['s']) ? '&s=' . urlencode($_GET['s']) : '';
                        
                        // First page
                        echo '<a class="first-page button ' . $first_disabled . '" ' . ($first_disabled ? '' : 'href="' . esc_url(admin_url('admin.php?page=bloobee-history&paged=1' . $search_params)) . '"') . '><span class="screen-reader-text">' . esc_html__('First page', 'bloobee-smartchat') . '</span><span aria-hidden="true">&laquo;</span></a>';
                        
                        // Previous page
                        echo '<a class="prev-page button ' . $prev_disabled . '" ' . ($prev_disabled ? '' : 'href="' . esc_url(admin_url('admin.php?page=bloobee-history&paged=' . max(1, $current_page - 1) . $search_params)) . '"') . '><span class="screen-reader-text">' . esc_html__('Previous page', 'bloobee-smartchat') . '</span><span aria-hidden="true">&lsaquo;</span></a>';
                        
                        // Current page
                        echo '<span class="paging-input">' . $current_page . ' of <span class="total-pages">' . $total_pages . '</span></span>';
                        
                        // Next page
                        echo '<a class="next-page button ' . $next_disabled . '" ' . ($next_disabled ? '' : 'href="' . esc_url(admin_url('admin.php?page=bloobee-history&paged=' . min($total_pages, $current_page + 1) . $search_params)) . '"') . '><span class="screen-reader-text">' . esc_html__('Next page', 'bloobee-smartchat') . '</span><span aria-hidden="true">&rsaquo;</span></a>';
                        
                        // Last page
                        echo '<a class="last-page button ' . $last_disabled . '" ' . ($last_disabled ? '' : 'href="' . esc_url(admin_url('admin.php?page=bloobee-history&paged=' . $total_pages . $search_params)) . '"') . '><span class="screen-reader-text">' . esc_html__('Last page', 'bloobee-smartchat') . '</span><span aria-hidden="true">&raquo;</span></a>';
                        ?>
                    </span>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div> 