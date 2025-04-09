<?php
// Prevent direct access
defined('ABSPATH') or die('Access denied');

// Extract values from analytics data
$total_conversations = isset($analytics_data['total_conversations']) ? $analytics_data['total_conversations'] : 0;
$total_messages = isset($analytics_data['total_conversations']) ? $analytics_data['total_conversations'] * 2 : 0; // Estimate
$avg_messages_per_conversation = $total_conversations > 0 ? number_format($total_messages / $total_conversations, 1) : 0;

// Extract chart data
$conversations_labels = isset($analytics_data['conversations_by_day']) ? array_column($analytics_data['conversations_by_day'], 'date') : [];
$conversations_data = isset($analytics_data['conversations_by_day']) ? array_column($analytics_data['conversations_by_day'], 'count') : [];

// Extract topics data
$topics_labels = isset($analytics_data['subject_distribution']) ? array_column($analytics_data['subject_distribution'], 'subject') : [];
$topics_data = isset($analytics_data['subject_distribution']) ? array_column($analytics_data['subject_distribution'], 'count') : [];

// Get recent conversations
$recent_conversations = $this->get_recent_conversations();
?>

<div class="wrap bloobee-admin-analytics">
    <h1><?php echo esc_html__('Chatbot Analytics', 'bloobee-smartchat'); ?></h1>
    
    <div class="analytics-dashboard">
        <div class="analytics-box">
            <h2><?php echo esc_html__('Total Conversations', 'bloobee-smartchat'); ?></h2>
            <div class="analytics-number"><?php echo esc_html($total_conversations); ?></div>
        </div>
        
        <div class="analytics-box">
            <h2><?php echo esc_html__('Total Messages', 'bloobee-smartchat'); ?></h2>
            <div class="analytics-number"><?php echo esc_html($total_messages); ?></div>
        </div>
        
        <div class="analytics-box">
            <h2><?php echo esc_html__('Average Messages/Conversation', 'bloobee-smartchat'); ?></h2>
            <div class="analytics-number"><?php echo esc_html($avg_messages_per_conversation); ?></div>
        </div>
    </div>
    
    <div class="analytics-charts">
        <div class="analytics-chart-container">
            <h2><?php echo esc_html__('Conversations by Day', 'bloobee-smartchat'); ?></h2>
            <canvas id="conversations-chart"></canvas>
        </div>
        
        <div class="analytics-chart-container">
            <h2><?php echo esc_html__('Popular Topics', 'bloobee-smartchat'); ?></h2>
            <canvas id="topics-chart"></canvas>
        </div>
    </div>
    
    <div class="analytics-table-container">
        <h2><?php echo esc_html__('Recent Conversations', 'bloobee-smartchat'); ?></h2>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php echo esc_html__('Date', 'bloobee-smartchat'); ?></th>
                    <th><?php echo esc_html__('User', 'bloobee-smartchat'); ?></th>
                    <th><?php echo esc_html__('Messages', 'bloobee-smartchat'); ?></th>
                    <th><?php echo esc_html__('Subject', 'bloobee-smartchat'); ?></th>
                    <th><?php echo esc_html__('Actions', 'bloobee-smartchat'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($recent_conversations)): ?>
                    <?php foreach ($recent_conversations as $conversation): ?>
                        <tr>
                            <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($conversation->created_at))); ?></td>
                            <td><?php echo esc_html($conversation->user_id ? (is_numeric($conversation->user_id) && get_userdata($conversation->user_id) ? get_userdata($conversation->user_id)->display_name : $conversation->user_id) : __('Guest', 'bloobee-smartchat')); ?></td>
                            <td><?php echo esc_html($conversation->message_count); ?></td>
                            <td><?php echo esc_html($conversation->subject ?: __('General', 'bloobee-smartchat')); ?></td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=bloobee-history&conversation=' . $conversation->id)); ?>" class="button button-small"><?php echo esc_html__('View', 'bloobee-smartchat'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5"><?php echo esc_html__('No conversations yet.', 'bloobee-smartchat'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (!empty($conversations_data) && !empty($topics_data)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
jQuery(document).ready(function($) {
    // Conversations chart
    var conversationsCtx = document.getElementById('conversations-chart').getContext('2d');
    var conversationsChart = new Chart(conversationsCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($conversations_labels); ?>,
            datasets: [{
                label: '<?php echo esc_js(__('Conversations', 'bloobee-smartchat')); ?>',
                data: <?php echo json_encode($conversations_data); ?>,
                backgroundColor: 'rgba(0, 132, 255, 0.2)',
                borderColor: 'rgba(0, 132, 255, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // Topics chart
    var topicsCtx = document.getElementById('topics-chart').getContext('2d');
    var topicsChart = new Chart(topicsCtx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($topics_labels); ?>,
            datasets: [{
                label: '<?php echo esc_js(__('Topics', 'bloobee-smartchat')); ?>',
                data: <?php echo json_encode($topics_data); ?>,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.2)',
                    'rgba(54, 162, 235, 0.2)',
                    'rgba(255, 206, 86, 0.2)',
                    'rgba(75, 192, 192, 0.2)',
                    'rgba(153, 102, 255, 0.2)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
});
</script>
<?php endif; ?> 