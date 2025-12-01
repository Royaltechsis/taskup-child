<?php
// [mnt_external_transaction_history] shortcode implementation
add_shortcode('mnt_external_transaction_history', function($atts) {
    if (!is_user_logged_in()) return '';
    $user_id = get_current_user_id();
    if (!class_exists('MNT_Escrow_Api_Escrow')) return '';
    $external_transactions = MNT_Escrow_Api_Escrow::get_all_transactions($user_id);
    ob_start();
    echo '<div class="tb-dhb-box-wrapper tb-transaction-history-section">';
    echo '<h3 style="margin-top:30px;">External Transaction History</h3>';
    if (!empty($external_transactions) && is_array($external_transactions)) {
        echo '<div style="overflow-x:auto;"><table class="table table-bordered" style="width:100%;margin-bottom:20px;">';
        echo '<thead><tr>';
        echo '<th>Date</th><th>Amount</th><th>Status</th><th>Transaction ID</th><th>Finalized At</th>';
        echo '</tr></thead><tbody>';
        foreach ($external_transactions as $txn) {
            $date = !empty($txn['created_at']) ? esc_html($txn['created_at']) : '-';
            $amount = isset($txn['amount']) ? esc_html($txn['amount']) : '-';
            $status = !empty($txn['status']) ? esc_html($txn['status']) : '-';
            $txid = !empty($txn['id']) ? esc_html($txn['id']) : '-';
            $finalized = !empty($txn['finalized_at']) ? esc_html($txn['finalized_at']) : '-';
            echo '<tr>';
            echo '<td>' . $date . '</td>';
            echo '<td>' . $amount . '</td>';
            echo '<td>' . $status . '</td>';
            echo '<td>' . $txid . '</td>';
            echo '<td>' . $finalized . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table></div>';
    } else {
        echo '<p>No external transactions found.</p>';
    }
    echo '</div>';
    return ob_get_clean();
});