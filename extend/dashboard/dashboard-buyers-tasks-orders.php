<?php
/**
 * Dashboard Buyer Orders Listing
 *
 * @package     Taskbot
 * @subpackage  Taskbot/templates/dashboard
 * @version     1.1
 */

global $current_user;

$ref               = !empty($_GET['ref']) ? esc_html($_GET['ref']) : '';
$mode              = !empty($_GET['mode']) ? esc_html($_GET['mode']) : '';
$user_identity     = !empty($_GET['identity']) ? intval($_GET['identity']) : 0;
$current_page_link = Taskbot_Profile_Menu::taskbot_profile_menu_link($ref, $user_identity, true, '');
$current_page_link = !empty($current_page_link) ? $current_page_link : '';

if (!class_exists('WooCommerce')) return;

$page_title_key = !empty($_GET['order_type']) ? $_GET['order_type'] : 'any';
$page_title_key = $page_title_key === 'hired' ? esc_html__('Ongoing','taskbot') : ($page_title_key === 'any' ? esc_html__('All','taskbot') : $page_title_key);

$page_title  = sprintf('%s %s', $page_title_key, esc_html__('order listings', 'taskbot'));
$show_posts  = get_option('posts_per_page') ?: 10;
$pg_page     = get_query_var('page') ?: 1;
$pg_paged    = get_query_var('paged') ?: 1;
$paged       = max($pg_page, $pg_paged);
$order_type  = !empty($_GET['order_type']) ? esc_attr($_GET['order_type']) : 'any';
$menu_order  = taskbot_list_tasks_order_status_filter();
$order       = 'DESC';
$sorting     = 'ID';
$order_status = array('wc-completed','wc-pending','wc-on-hold','wc-cancelled','wc-refunded','wc-processing');
$page_url    = Taskbot_Profile_Menu::taskbot_profile_menu_link($ref, $user_identity, true, $mode);

// Search keyword
$search_keyword = !empty($_GET['search_keyword']) ? sanitize_text_field($_GET['search_keyword']) : '';

// Prepare meta query
$meta_query_args = array(
    'relation' => 'AND',
    array(
        'key'     => 'payment_type',
        'value'   => 'tasks',
        'compare' => '='
    ),
    array(
        'key'     => 'buyer_id',
        'value'   => $user_identity,
        'compare' => '='
    )
);

// Filter by task status if specified
if($order_type != 'any'){
    $meta_query_args[] = array(
        'key'     => '_task_status',
        'value'   => $order_type,
        'compare' => '='
    );
}

// Search by task/product name
if(!empty($search_keyword)){
    $product_ids = get_posts(array(
        'post_type'   => 'product',
        'fields'      => 'ids',
        'post_status' => 'any',
        's'           => $search_keyword,
        'tax_query'   => array(
            array(
                'taxonomy' => 'product_type',
                'field'    => 'slug',
                'terms'    => 'tasks',
            )
        )
    ));
    if(empty($product_ids)) $product_ids = array(-1);

    $meta_query_args[] = array(
        'key'     => 'task_product_id',
        'value'   => $product_ids,
        'compare' => 'IN'
    );
}

// Fetch buyer orders

 // Query buyer orders using MNT helper (fallbacks handle DB edge-cases)
 if (class_exists('\MNT\UI\Init')) {
     $order_ids = \MNT\UI\Init::mnt_get_buyer_task_orders($user_identity, $order_type);
     $count_post = !empty($order_ids) ? count($order_ids) : 0;
     // Manual pagination
     $offset = ($paged - 1) * $show_posts;
     $paged_ids = array_slice($order_ids, $offset, $show_posts);
    $posts = [];
    if (!empty($paged_ids)) {
        foreach ($paged_ids as $oid) {
            $p = get_post($oid);
            if ($p) $posts[] = $p;
        }
    }

    // Provide a WP_Query-like object so existing pagination helper works
    $orders_list = new WP_Query();
    $orders_list->posts = $posts;
    $orders_list->found_posts = $count_post;
    $orders_list->post_count = count($posts);
    $orders_list->max_num_pages = $count_post ? ceil($count_post / $show_posts) : 1;
 } else {
     // Fallback to WP_Query if helper not available
     $args = array(
         'post_type'      => 'shop_order',
         'posts_per_page' => $show_posts,
         'paged'          => $paged,
         'orderby'        => 'ID',
         'order'          => 'DESC',
         'post_status'    => array('wc-pending','wc-processing','wc-on-hold','wc-completed','wc-cancelled','wc-refunded'),
         'meta_query'     => $meta_query_args,
     );
     $orders_query = new WP_Query($args);
     $orders_list  = $orders_query->posts;
     $count_post   = $orders_query->found_posts;
 }
?>

<div class="tb-dhb-mainheading">
    <h2><?php esc_html_e('All orders','taskbot');?></h2>
    <div class="tb-sortby">
        <div class="tb-actionselect tb-actionselect2">
            <span><?php esc_html_e('Filter by:','taskbot');?></span>
            <div class="tb-select">
                <select id="tb_order_type" name="order_type" class="form-control tk-selectv">
                    <?php foreach($menu_order as $key => $val): 
                        $selected = ($order_type == $key) ? 'selected' : ''; ?>
                        <option data-url="<?php echo esc_url($page_url);?>&order_type=<?php echo esc_attr($key);?>" value="<?php echo esc_attr($key);?>" <?php echo esc_attr($selected);?>>
                            <?php echo esc_html($val);?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
</div>

<div class="tb-dhbtabs tb-tasktabs">
    <div class="tab-content tab-taskcontent">
        <div class="tab-pane fade active show">
            <div class="tb-tabtasktitle">
                <h5><?php echo esc_html($page_title);?></h5>
                <form class="tb-themeform" action="<?php echo esc_url($current_page_link); ?>">
                    <input type="hidden" name="ref" value="<?php echo esc_attr($ref); ?>">
                    <input type="hidden" name="identity" value="<?php echo esc_attr($user_identity); ?>">
                    <fieldset>
                        <div class="tb-themeform__wrap">
                            <div class="form-group wo-inputicon">
                                <i class="tb-icon-search"></i>
                                <input type="text" name="search_keyword" class="form-control" value="<?php echo esc_attr($search_keyword);?>" placeholder="<?php esc_attr_e('Search orders here','taskbot');?>">
                            </div>
                        </div>
                    </fieldset>
                </form>
            </div>

            <?php if(!empty($orders_list)): ?>
                <div class="tb-tasklist">
                    <?php foreach ($orders_list->posts as $order_obj):
                        if ( is_object($order_obj) ) {
                            if ( property_exists($order_obj, 'ID') ) {
                                $order_id = intval($order_obj->ID);
                            } else {
                                $order_id = 0;
                            }
                        } else {
                            $order_id = intval($order_obj);
                        }
                        $order       = wc_get_order($order_id);
                        if(!$order) continue;

                        $task_id     = get_post_meta($order_id, 'task_product_id', true) ?: 0;
                        $task_title  = $task_id ? get_the_title($task_id) : '';
                        $order_price = $order->get_total() ?: 0;
                        if(function_exists('wmc_revert_price')) $order_price = wmc_revert_price($order_price, $order->get_currency());

                        $seller_id   = get_post_meta($order_id, 'seller_id', true) ?: 0;
                        $buyer_id    = get_post_meta($order_id, 'buyer_id', true) ?: 0;
                        $product_data= get_post_meta($order_id, 'cus_woo_product_data', true) ?: array();
                        $downloadable= get_post_meta($task_id, '_downloadable', true) ?: 0;

                        $order_url   = Taskbot_Profile_Menu::taskbot_profile_menu_link('tasks-orders', $user_identity, true, 'detail', $order_id);
                    ?>
                        <div class="tb-tabfilteritem">
                            <div class="tb-tabbitem__list">
                                <div class="tb-deatlswithimg">
                                    <div class="tb-icondetails">
                                        <?php do_action('taskbot_task_order_status', $order_id);?>
                                        <?php if($task_id) echo do_action('taskbot_task_categories', $task_id, 'product_cat'); ?>
                                        <?php if($task_title): ?>
                                            <a href="<?php echo esc_url($order_url); ?>">
                                                <h5><?php echo esc_html($task_title); ?></h5>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php if($order_price): ?>
                                    <div class="tb-itemlinks">
                                        <div class="tb-startingprice">
                                            <i><?php esc_html_e('Total task budget','taskbot'); ?></i>
                                            <span><?php taskbot_price_format($order_price);?></span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php do_action('taskbot_task_complete_html', $order_id);?>

                            <div class="tb-extras">
                                <?php do_action('taskbot_task_author', $seller_id, 'sellers');?>
                                <?php do_action('taskbot_order_date', $order_id);?>
                                <?php do_action('taskbot_delivery_date', $order_id);?>
                                <?php do_action('taskbot_subtasks_count', $product_data);?>
                                <?php do_action('taskbot_price_plan', $order_id);?>
                                <?php if($downloadable === 'yes') do_action('taskbot_task_download_file', $task_id, $order_id); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if($count_post > $show_posts) taskbot_paginate($orders_list); ?>

            <?php else: ?>
                <?php do_action('taskbot_empty_listing', esc_html__('No orders found','taskbot')); ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php wp_reset_postdata(); ?>

<?php
$script = "
jQuery(document).on('ready', function(){
    jQuery(document).on('change', '#tb_order_type', function () {
        let page_url = jQuery(this).find(':selected').data('url');
        window.location.replace(page_url);
    });
});
";
wp_add_inline_script('taskbot', $script, 'after');
?>
