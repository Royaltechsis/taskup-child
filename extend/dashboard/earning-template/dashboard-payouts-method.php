<?php
/**
 * The template part for displaying the dashboard Payouts methods for seller
 *
 * @package     Taskbot
 * @subpackage  Taskbot/templates/dashboard/earning_template
 * @author      Amentotech <info@amentotech.com>
 * @link        https://codecanyon.net/user/amentotech/portfolio
 * @version     1.0
 * @since       1.0
*/

global $current_user, $taskbot_settings;
$user_identity      = intval($current_user->ID);
$payout_list        = taskbot_get_payouts_lists();
$contents_payout    = get_user_meta($user_identity, 'taskbot_payout_method', true);
$contents_payout    = !empty($contents_payout) ? $contents_payout : array();

$tpl_terms_conditions   = !empty( $taskbot_settings['tpl_terms_conditions'] ) ? $taskbot_settings['tpl_terms_conditions'] : '';
$tpl_privacy            = !empty( $taskbot_settings['tpl_privacy'] ) ? $taskbot_settings['tpl_privacy'] : '';
$term_link              = !empty($tpl_terms_conditions) ? '<a target="_blank" href="'.get_the_permalink($tpl_terms_conditions).'">'.get_the_title($tpl_terms_conditions).'</a>' : '';
$privacy_link           = !empty($tpl_privacy) ? '<a target="_blank" href="'.get_the_permalink($tpl_privacy).'">'.get_the_title($tpl_privacy).'</a>' : '';

?>
<div class="col-lg-4">
    <div class="tb-asideholder tb-asideholdertwo">
        <div class="tb-asidebox tb-payoutmethodwrap" id="tb_bankpayouttitle_heading">
            <h5 class="tb-banktitle tb-bankpayouttitle"><?php esc_html_e('Payouts method', 'taskbot'); ?></h5>
        </div>
        <div class="tb-asidebox">
            <div class="tb-payoutmethodholder">
                <div class="tb-themeform">
                    
                </div>
                <div class="tb-paymetdesc">
                    <p>
                        <em><?php echo sprintf(esc_html__('Visit your billing setting in your dahsboard to withdraw', 'taskbot'), $term_link, $privacy_link); ?></em>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>