<?php
/**
 * Menus avatar dropdown items - Child theme override
 * Integrated with MNT Wallet
 *
 * @package     Taskbot
 * @subpackage  Taskbot/templates/dashboard/menus
 * @version     1.0
 * @since       1.0
*/ 

global $current_user, $wp_roles, $userdata, $post;

$reference 		 = (isset($args['ref']) && $args['ref'] <> '') ? esc_html($args['ref']) : '';
$mode 			 = (isset($args['mode']) && $args['mode'] <> '') ? esc_html($args['mode']) : '';
$title 			 = (isset($args['title']) && $args['title'] <> '') ? esc_html($args['title']) : '';
$id 			 = (isset($args['id']) && $args['id'] <> '') ? esc_attr($args['id']) : '';
$icon_class 	 = (isset($args['icon']) && $args['icon'] <> '') ? esc_html($args['icon']) : '';
$class 			 = (isset($args['class']) && $args['class'] <> '') ? esc_html($args['class']) : '';
$data_attr 			 = (isset($args['data-attr']) && $args['data-attr'] <> '') ? $args['data-attr']: array();
$user_identity 	 = $current_user->ID;

if(isset($args['submenu']) && is_array($args['submenu']) && count($args['submenu'])>0){
    $class .= ' tb-menudropdown';
}

if(empty($reference) && empty($mode)){
	$url	= '#';
} else {
	$url	= Taskbot_Profile_Menu::taskbot_profile_menu_link($reference, $user_identity, true, $mode);
}

$target 		    = '_self';
$data_attr_list     = '';

if(!empty($data_attr)){
    foreach($data_attr as $key => $data_id){
        $data_attr_list  .= $key.'='. $data_id;
    }
}

if(!empty($reference) && $reference == 'logout'){
	$url	= esc_url(wp_logout_url(home_url('/')));
} else if( !empty($reference) && $reference === 'packages'){
    $url	= taskbot_get_page_uri('package_page');
} else if( !empty($reference) && $reference === 'profile'){
    $user_type		    = apply_filters('taskbot_get_user_type', $current_user->ID );
    $linked_profile	    = taskbot_get_linked_profile_id($current_user->ID,'',$user_type);
    $url	            = get_the_permalink($linked_profile);
    $target 		    = '_blank';
} else if( !empty($reference) && $reference === 'create_project'){
    $url	= taskbot_get_page_uri('add_project_page');
}

if( !empty($id) && $id === 'wallet' ){
    // Get balance from MNT Wallet
    $user_balance = 0;
    $has_error = false;
    
    if (class_exists('MNT\Api\wallet')) {
        $wallet_result = \MNT\Api\wallet::balance($user_identity);
        if ($wallet_result && isset($wallet_result['balance'])) {
            $user_balance = $wallet_result['balance'];
        } else {
            $has_error = true;
        }
    } else {
        $has_error = true;
    }
    
    // Get billing page URL
    $billing_url = Taskbot_Profile_Menu::taskbot_profile_menu_link('dashboard', $user_identity, true, 'billing');
    ?>
    <li class="<?php echo esc_attr($class); ?>" <?php echo esc_attr($data_attr_list); ?>>
        <?php if(isset($icon_class) && !empty($icon_class)){?>
            <i class="<?php echo esc_attr($icon_class);?>"></i>
        <?php } ?>
        <span><?php echo esc_html($title)?> <strong><?php if($has_error) { echo '<span style="color: #e74c3c;" title="Network Error">⚠ Error</span>'; } else { taskbot_price_format($user_balance); } ?></strong></span>
        <a href="<?php echo esc_url($billing_url); ?>"><em class="tb-icon-credit-card"></em></a>
    </li>
<?php } else if( !empty($id) && $id === 'balance' ){
    // Get available balance from MNT Wallet
    $available_withdraw_amount = 0;
    $has_error = false;
    
    if (class_exists('MNT\Api\wallet')) {
        $wallet_result = \MNT\Api\wallet::balance($user_identity);
        if ($wallet_result && isset($wallet_result['balance'])) {
            $available_withdraw_amount = $wallet_result['balance'];
        } else {
            $has_error = true;
        }
    } else {
        $has_error = true;
    }
    
    $available_withdraw_amount = !empty($available_withdraw_amount) && $available_withdraw_amount > 0 ? $available_withdraw_amount : 0;
    
    // Get billing page URL
    $billing_url = Taskbot_Profile_Menu::taskbot_profile_menu_link('dashboard', $user_identity, true, 'billing');
    ?>
    <li class="<?php echo esc_attr($class); ?>" <?php echo esc_attr($data_attr_list); ?>>
        <?php if(isset($icon_class) && !empty($icon_class)){?>
            <i class="<?php echo esc_attr($icon_class);?>"></i>
        <?php } ?>
        <span><?php echo esc_html($title)?> <strong><?php if($has_error) { echo '<span style="color: #e74c3c;" title="Network Error">⚠ Error</span>'; } else { taskbot_price_format($available_withdraw_amount); } ?></strong></span>
        <a href="<?php echo esc_url($billing_url); ?>"><em class="tb-icon-credit-card"></em></a>
    </li>
<?php } else { ?>
    <li class="<?php echo esc_attr($class); ?>" <?php echo ($data_attr_list); ?>>
        <a href="<?php echo esc_attr( $url ); ?>" target="<?php echo esc_attr( $target ); ?>">
            <?php if(isset($icon_class) && !empty($icon_class)){?><i class="<?php echo esc_attr($icon_class);?>"></i><?php } echo esc_html($title);?>
        </a>
        <?php if(isset($args['submenu']) && is_array($args['submenu']) && count($args['submenu'])>0){ ?>
            <ul class="sub-menu">
                <?php foreach($args['submenu'] as $key => $submenu_item){
                    $submenu_item['id'] = $key;
                    $submenu_item['reference'] = $reference;
                    taskbot_get_template_part('dashboard/menus/submenu', 'list-item', $submenu_item);
                }?>
            </ul>
        <?php }?>
    </li>
<?php }
