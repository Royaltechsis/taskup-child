<?php
/**
 * Project Invoices/Escrow Transaction History
 * 
 * CUSTOMIZED: Shows escrow transaction history for the specific project
 */
$proposal_id    = !empty($args['proposal_id']) ? intval($args['proposal_id']) : 0;
$project_id     = !empty($args['project_id']) ? intval($args['project_id']) : 0;
$seller_id      = !empty($args['seller_id']) ? intval($args['seller_id']) : 0;
$buyer_id       = !empty($args['buyer_id']) ? intval($args['buyer_id']) : 0;
$user_identity  = !empty($args['user_identity']) ? intval($args['user_identity']) : 0;
$user_type      = !empty($args['user_type']) ? esc_attr($args['user_type']) : '';

// Fetch escrow transactions for this specific project
$escrow_transactions = array();
$debug_info = array(
    'class_exists_mnt_escrow' => class_exists('MNT_Escrow\Api\Escrow'),
    'class_exists_mnt' => class_exists('MNT\Api\Escrow'),
    'user_identity' => $user_identity,
    'user_type' => $user_type,
    'project_id' => $project_id,
    'seller_id' => $seller_id,
    'buyer_id' => $buyer_id,
    'api_role' => ($user_type === 'sellers') ? 'merchant' : 'client'
);

if (class_exists('MNT\Api\Escrow')) {
    // Determine actor based on user type
    $actor = ($user_type === 'sellers') ? 'merchant' : 'client';
    
    // Get all transactions for the user
    $all_transactions = \MNT\Api\Escrow::get_all_transactions($user_identity, $actor);
    
    $debug_info['all_transactions_raw'] = $all_transactions;
    $debug_info['is_array'] = is_array($all_transactions);
    $debug_info['empty_check'] = empty($all_transactions);
    
    if (!empty($all_transactions) && is_array($all_transactions)) {
        // The API returns a direct array of transactions
        $transactions = $all_transactions;
        
        $debug_info['transactions_count'] = count($transactions);
        $debug_info['sample_transaction'] = !empty($transactions) ? reset($transactions) : 'no transactions';
        
        // Filter to only show transactions for this project
        foreach ($transactions as $transaction) {
            $trans_project_id = isset($transaction['project_id']) ? intval($transaction['project_id']) : 0;
            $debug_info['checking_project_id'][] = $trans_project_id . ' vs ' . $project_id;
            
            if ($trans_project_id === $project_id) {
                $escrow_transactions[] = $transaction;
            }
        }
        
        $debug_info['filtered_count'] = count($escrow_transactions);
    } else {
        $debug_info['error'] = 'API returned empty or non-array response';
    }
} else {
    $debug_info['error'] = 'Escrow class does not exist';
}

// Sort by date descending (newest first)
if (!empty($escrow_transactions)) {
    usort($escrow_transactions, function($a, $b) {
        $date_a = strtotime($a['created_at'] ?? '');
        $date_b = strtotime($b['created_at'] ?? '');
        return $date_b - $date_a;
    });
}
?>
<div class="tab-pane fade" id="proposal-invoices" role="tabpanel" aria-labelledby="proposal-invoices-tab">
    <div class="tk-proinvoices">
        <div class="tk-proinvoices_title">
            <h5><?php esc_html_e('Escrow Transaction History','taskbot');?></h5>
        </div>
        
        <!-- Debug Information -->
       <!--  <div style="background: #f0f0f0; padding: 15px; margin: 10px 0; border: 2px solid #333; font-size: 12px;">
            <strong>Debug Info:</strong><br>
            <pre><?php echo esc_html(print_r($debug_info, true)); ?></pre>
            <strong>Escrow Transactions:</strong><br>
            <pre><?php echo esc_html(print_r($escrow_transactions, true)); ?></pre>
        </div> -->
        
        <table class="table tk-proinvoices_table tb-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Date','taskbot');?></th>
                    <th><?php esc_html_e('Transaction ID','taskbot');?></th>
                    <th><?php esc_html_e('Description','taskbot');?></th>
                    <th><?php esc_html_e('Status','taskbot');?></th>
                    <th><?php esc_html_e('Amount','taskbot');?></th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($escrow_transactions)) {
                    foreach ($escrow_transactions as $transaction) {
                        $escrow_id = $transaction['escrow_id'] ?? '';
                        $amount = isset($transaction['amount']) ? floatval($transaction['amount']) : 0;
                        $status = $transaction['status'] ?? 'unknown';
                        $created_at = $transaction['created_at'] ?? '';
                        $milestone_key = $transaction['milestone_key'] ?? '';
                        
                        // Format date
                        $date_formatted = '';
                        if (!empty($created_at)) {
                            $timestamp = strtotime($created_at);
                            if ($timestamp) {
                                $date_formatted = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $timestamp);
                            }
                        }
                        
                        // Description
                        $description = 'Project Escrow';
                        if (!empty($milestone_key)) {
                            $description = 'Milestone #' . ($milestone_key + 1) . ' Escrow';
                        }
                        
                        // Status badge color
                        $status_class = 'tk-project-tag';
                        $status_display = ucfirst(strtolower($status));
                        switch(strtolower($status)) {
                            case 'pending':
                                $status_class .= ' tk-pending';
                                break;
                            case 'funded':
                            case 'active':
                            case 'in_escrow':
                                $status_class .= ' tk-active';
                                $status_display = 'Active';
                                break;
                            case 'completed':
                            case 'released':
                                $status_class .= ' tk-completed';
                                $status_display = 'Completed';
                                break;
                            case 'cancelled':
                            case 'refunded':
                                $status_class .= ' tk-cancelled';
                                break;
                        }
                ?>
                <tr>
                    <td data-label="<?php esc_attr_e('Date','taskbot');?>">
                        <?php echo esc_html($date_formatted); ?>
                    </td>
                    <td data-label="<?php esc_attr_e('Transaction ID','taskbot');?>">
                        <code style="font-size: 12px; background: #f5f5f5; padding: 2px 6px; border-radius: 3px;">
                            <?php echo esc_html(substr($escrow_id, 0, 16)); ?>...
                        </code>
                    </td>
                    <td data-label="<?php esc_attr_e('Description','taskbot');?>">
                        <?php echo esc_html($description); ?>
                    </td>
                    <td data-label="<?php esc_attr_e('Status','taskbot');?>">
                        <span class="<?php echo esc_attr($status_class); ?>">
                            <?php echo esc_html($status_display); ?>
                        </span>
                    </td>
                    <td data-label="<?php esc_attr_e('Amount','taskbot');?>">
                        <strong><?php taskbot_price_format($amount); ?></strong>
                    </td>
                </tr>
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 40px;">
                        <p style="color: #999; margin: 0;">
                            <?php esc_html_e('No escrow transactions found for this project.', 'taskbot'); ?>
                        </p>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>
