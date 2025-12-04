<?php
    $proposal_id    = !empty($args['proposal_id']) ? intval($args['proposal_id']) : 0;
    $project_id     = !empty($args['project_id']) ? intval($args['project_id']) : 0;
    $seller_id      = !empty($args['seller_id']) ? intval($args['seller_id']) : 0;
    $proposal_status= !empty($args['proposal_status']) ? esc_attr($args['proposal_status']) : 0;
    $proposal_meta  = !empty($args['proposal_meta']) ? ($args['proposal_meta']) : array();
    $user_identity  = !empty($args['user_identity']) ? intval($args['user_identity']) : 0;

    $hired_balance      = !empty($args['hired_balance']) ? ($args['hired_balance']) : 0;
    $earned_balance     = !empty($args['earned_balance']) ? ($args['earned_balance']) : 0;
    $remaning_balance   = !empty($args['remaning_balance']) ? ($args['remaning_balance']) : 0;
    $mileastone_array   = !empty($args['mileastone_array']) ? ($args['mileastone_array']) : array();
    $completed_mil_array= !empty($args['completed_mil_array']) ? ($args['completed_mil_array']) : array();
    $user_balance       = get_user_meta( $user_identity, '_buyer_balance', true );
    $user_balance       = !empty($user_balance) ? $user_balance : 0;
    if( !empty($user_balance) ){
        $checkout_class         = 'tb_proposal_hiring';
    } else {
        $checkout_class     = 'tb_hire_proposal';
    }

    // Fetch real-time data from escrow API
    $api_escrow_amount = 0;
    $api_amount_spent = 0;
    $milestone_total = !empty($args['milestone_total']) ? $args['milestone_total'] : 0;
    
    // Get escrow transactions for this project from API
    if (class_exists('MNT_Escrow\Api\Escrow')) {
        $escrow_transactions = \MNT_Escrow\Api\Escrow::get_all_transactions($user_identity, 'client');
        
        if (!empty($escrow_transactions) && is_array($escrow_transactions)) {
            // Handle different response formats
            $transactions = $escrow_transactions;
            if (isset($escrow_transactions['transactions']) && is_array($escrow_transactions['transactions'])) {
                $transactions = $escrow_transactions['transactions'];
            } elseif (isset($escrow_transactions['data']) && is_array($escrow_transactions['data'])) {
                $transactions = $escrow_transactions['data'];
            }
            
            // Calculate totals for this project
            foreach ($transactions as $transaction) {
                if (isset($transaction['project_id']) && intval($transaction['project_id']) === $project_id) {
                    $amount = isset($transaction['amount']) ? floatval($transaction['amount']) : 0;
                    $status = isset($transaction['status']) ? $transaction['status'] : '';
                    
                    // Total escrow amount (pending + active)
                    if (in_array($status, ['pending', 'active', 'in_escrow'])) {
                        $api_escrow_amount += $amount;
                    }
                    
                    // Amount spent (completed/released)
                    if (in_array($status, ['completed', 'released', 'paid'])) {
                        $api_amount_spent += $amount;
                    }
                }
            }
        }
    }
    
    // Calculate remaining budget: total project budget - (escrow + spent)
    $api_remaining_budget = $milestone_total - ($api_escrow_amount + $api_amount_spent);
    
    // Use API data if available, otherwise fallback to local calculations
    $display_escrow_amount = ($api_escrow_amount > 0 || $api_amount_spent > 0) ? $api_escrow_amount : $hired_balance;
    $display_amount_spent = ($api_escrow_amount > 0 || $api_amount_spent > 0) ? $api_amount_spent : $earned_balance;
    $display_remaining = ($api_escrow_amount > 0 || $api_amount_spent > 0) ? $api_remaining_budget : $remaning_balance;
    
    // Get user type to show seller-specific actions
    $user_type = apply_filters('taskbot_get_user_type', $user_identity);
?>
<div class="tk-counterinfo">
    <ul class="tk-counterinfo_list">
        <li>
            <strong class="tk-counterinfo_escrow"><i class="tb-icon-clock"></i></strong>
            <span><?php esc_html_e('Total escrow amount','taskbot');?></span>
            <h5><?php taskbot_price_format($display_escrow_amount);?> </h5>
        </li>
        <li>
            <strong class="tk-counterinfo_earned"><i class="tb-icon-briefcase"></i></strong>
            <span><?php esc_html_e('Total amount spent','taskbot');?></span>
            <h5><?php taskbot_price_format($display_amount_spent);?></h5>
        </li>
        <li>
            <strong class="tk-counterinfo_remaining"><i class="tb-icon-dollar-sign"></i></strong>
            <span><?php esc_html_e('Remaining project budget','taskbot');?></span>
            <h5><?php taskbot_price_format($display_remaining);?></h5>
        </li>
    </ul>
</div>
<?php if( !empty($mileastone_array) ){?>
    <div class="tk-projectsinfo">
        <div class="tk-projectsinfo_title">
            <h4><?php esc_html_e('Project roadmap','taskbot');?></h4>
        </div>
        <ul class="tk-projectsinfo_list">
            <?php 
                foreach($mileastone_array as $key => $value){
                    $status = !empty($value['status']) ? $value['status'] : '';
                    $price  = !empty($value['price']) ? $value['price'] : 0;
                    $title  = !empty($value['title']) ? $value['title'] : '';
                    $detail = !empty($value['detail']) ? $value['detail'] : '';
                    ?>
                    <li>
                        <div class="tk-statusview">
                            <div class="tk-statusview_head">
                                <div class="tk-statusview_title">
                                    <div class="tk-mile-title">
                                        <span><?php taskbot_price_format($price);?></span>
                                        <?php 
                                            if( isset($status) && $status != 'requested' ){
                                                do_action( 'taskbot_milestone_proposal_status_tag', $status );
                                            }
                                        ?>
                                    </div>
                                    <?php if( !empty($title) ){?>
                                        <h5><?php echo esc_html($title);?></h5>
                                    <?php } ?>
                                    <?php if( !empty($detail) ){?>
                                        <p><?php echo esc_html($detail);?></p>
                                    <?php } ?>
                                </div>
                                
                            </div>
                            <?php if( !empty($status) && $status === 'decline' && !empty($value['decline_reason'])){?>
                                <div class="tk-statusview_alert">
                                    <span><i class="tb-icon-info"></i><?php esc_html_e('The employer declined this milestone invoice. Read the comment below and try again','taskbot');?></span>
                                    <p><?php echo esc_html($value['decline_reason']);?></p>
                                </div>
                            <?php } ?>
                            <?php if( !empty($proposal_status) && $proposal_status === 'hired' ){?>
                                <?php if( !empty($status) && $status === 'requested' ){?>
                                    <div class="tk-statusview_btns">
                                        <span class="tk-btn_approve tb_update_milestone" data-status="completed" data-id="<?php echo intval($proposal_id);?>" data-key="<?php echo esc_attr($key);?>"><?php esc_html_e('Approve','taskbot');?></span>
                                        <span class="tk-btn_decline" data-bs-target="#tb_milestone_declinereason-<?php echo esc_attr($key);?>" data-bs-toggle="modal" ><?php esc_html_e('Decline','taskbot');?></span>
                                    </div>
                                    <div class="modal fade tk-declinereason" id="tb_milestone_declinereason-<?php echo esc_attr($key);?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                            <div class="tk-popup_title">
                                                <h5><?php esc_html_e('Add decline reason below','taskbot');?></h5>
                                                <a href="javascrcript:void(0)" data-bs-dismiss="modal">
                                                    <i class="tb-icon-x"></i>
                                                </a>
                                            </div>
                                            <div class="modal-body tk-popup-content">
                                                <div class="tk-themeform">
                                                    <fieldset>
                                                        <div class="tk-themeform__wrap">
                                                            <div class="form-group">
                                                                <div class="tk-placeholderholder">
                                                                    <textarea id="milestone_declinereason-<?php echo esc_attr($key);?>" class="form-control tk-themeinput" placeholder="<?php esc_attr_e("Enter description","taskbot");?>"></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="tk-popup-terms form-group">
                                                                <button type="button" data-id="<?php echo intval($proposal_id);?>" data-status="decline" data-key="<?php echo esc_attr($key);?>" class="tk-btn-solid-lg tb_decline_milestone"><?php esc_html_e('Submit question now','taskbot');?><i class="tb-icon-arrow-right"></i></button>
                                                            </div>
                                                        </div>
                                                    </fieldset>
                                                </div>
                                            </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php } else if(empty($status)){ ?>
                                    <div class="tk-statusview_btns">
                                        <?php
                                        // Redirect to create-escrow page for milestone payment
                                        $escrow_page_url = get_permalink(get_page_by_path('create-escrow'));
                                        $milestone_escrow_url = add_query_arg([
                                            'merchant_id' => $seller_id, // seller
                                            'client_id' => $user_identity, // buyer
                                            'project_id' => $project_id,
                                            'amount' => floatval($price),
                                            'proposal_id' => $proposal_id,
                                            'milestone_key' => $key,
                                            'milestone_title' => urlencode($title)
                                        ], $escrow_page_url);
                                        ?>
                                        <a href="<?php echo esc_url($milestone_escrow_url); ?>" class="tk-btn_decline">
                                            <?php esc_html_e('Escrow','taskbot');?>
                                        </a>
                                    </div>
                                <?php } ?>
                            <?php } ?>
                        </div>
                    </li>
            <?php } ?>
        </ul>
    </div>
<?php } ?>
<?php if( !empty($completed_mil_array) ){?>
    <div class="tk-projectsinfo">
        <div class="tk-projectsinfo_title">
            <h4><?php esc_html_e('Completed milestones','taskbot');?></h4>
        </div>
        <ul class="tk-projectsinfo_list">
            <?php 
                foreach($completed_mil_array as $key => $value){
                    $status = !empty($value['status']) ? $value['status'] : '';
                    $price  = !empty($value['price']) ? $value['price'] : 0;
                    $title  = !empty($value['title']) ? $value['title'] : '';
                    $detail = !empty($value['detail']) ? $value['detail'] : '';
                    ?>
                    <li>
                        <div class="tk-statusview">
                            <div class="tk-statusview_head">
                                <div class="tk-statusview_title">
                                    <div class="tk-mile-title">
                                        <span><?php taskbot_price_format($price);?></span>
                                        <?php do_action( 'taskbot_milestone_proposal_status_tag', $status );?>
                                    </div>
                                    <?php if( !empty($title) ){?>
                                        <h5><?php echo esc_html($title);?></h5>
                                    <?php } ?>
                                    <?php if( !empty($detail) ){?>
                                        <p><?php echo esc_html($detail);?></p>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </li>
            <?php } ?>
        </ul>
    </div>
<?php }

// Add Success Modal for Merchant Release Funds
?>
<div class="modal fade" id="mnt-merchant-success-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="tk-popup_title" style="background-color: #28a745; color: white; text-align: center; padding: 20px;">
                <i class="tb-icon-check-circle" style="font-size: 48px;"></i>
                <h3 style="color: white; margin-top: 10px;"><?php esc_html_e('Success!', 'taskbot'); ?></h3>
            </div>
            <div class="modal-body tk-popup-content" style="text-align: center; padding: 30px;">
                <p style="font-size: 16px; margin-bottom: 10px;"><?php esc_html_e('Funds will be released when buyer confirms', 'taskbot'); ?></p>
                <p style="color: #6c757d;"><?php esc_html_e('The buyer will be notified to confirm project completion.', 'taskbot'); ?></p>
            </div>
            <div class="modal-footer" style="justify-content: center; border-top: none;">
                <button type="button" class="tk-btn-solid-lg" data-bs-dismiss="modal"><?php esc_html_e('Close', 'taskbot'); ?></button>
            </div>
        </div>
    </div>
</div>
