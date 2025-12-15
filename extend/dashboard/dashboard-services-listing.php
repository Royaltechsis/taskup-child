<?php
/**
 * Seller task listings
 *
 * @package     Taskbot
 * @subpackage  Taskbot/templates/dashboard
 * @author      Amentotech <info@amentotech.com>
 * @link        https://codecanyon.net/user/amentotech/portfolio
 * @version     1.0
 * @since       1.0
*/

global $post, $current_user,$taskbot_settings;
$ref                = !empty($_GET['ref']) ? esc_html($_GET['ref']) : '';
$mode               = !empty($_GET['mode']) ? esc_html($_GET['mode']) : '';
$user_identity      = !empty($_GET['identity']) ? intval($_GET['identity']) : 0;

$user_type         = apply_filters('taskbot_get_user_type', $current_user->ID );
$task_allowed      = taskbot_task_create_allowed($current_user->ID);
$package_detail    = taskbot_get_package($current_user->ID);

$order_type     = !empty($_GET['order_type']) ? $_GET['order_type'] : 'any';
$menu_order     = taskbot_list_tasks_status_filter();
$page_url       = Taskbot_Profile_Menu::taskbot_profile_menu_link($ref, $user_identity, true, $mode);

$package_option               = !empty($taskbot_settings['package_option']) && in_array($taskbot_settings['package_option'],array('paid','buyer_free')) ? true : false;
$taskbot_add_service_page_url = '';
$taskbot_add_service_page_url = !empty($taskbot_settings['tpl_add_service_page']) ? get_permalink($taskbot_settings['tpl_add_service_page']) : '';
?>
<div class="tb-dhb-mainheading">
    <div class="tb-dhb-mainheading__rightarea">
        <em><?php esc_html_e('Add task for each service you offer to increase chances of getting hired', 'taskbot');?></em>
        <a href="<?php echo esc_url($taskbot_add_service_page_url);?>" class="tb-btn">
            <?php esc_html_e('Add new', 'taskbot');?>
            <span class="rippleholder tb-jsripple"><em class="ripplecircle"></em></span>
        </a>
    </div>
</div>
<div class="tb-dhb-mainheading">
    <h2><?php esc_html_e('Manage task', 'taskbot');?></h2>
    <?php do_action('taskbot_service_listing_notice');?>
    <div class="tb-sortby">
        <div class="tb-actionselect tb-actionselect2">
            <span><?php esc_html_e('Filter by:','taskbot');?></span>
            <div class="tb-select">
                <select id="tb_order_type" name="order_type" class="form-control tk-selectv">
                    <?php foreach($menu_order as $key => $val ){
                        $selected   = '';

                        if( !empty($order_type) && $order_type == $key ){
                            $selected   = 'selected';
                        }
                        ?>
                        <option data-url="<?php echo esc_url($page_url);?>&order_type=<?php echo esc_attr($key);?>" value="<?php echo esc_attr($key);?>" <?php echo esc_attr($selected);?>>
                            <?php echo esc_html($val);?>
                        </option>
                    <?php } ?>
                </select>
            </div>
        </div>
    </div>
</div>
<?php
// Seller counters: In Queue, Completed, Cancelled
$seller_id = ! empty( $user_identity ) ? intval( $user_identity ) : intval( $current_user->ID );
$count_inqueue = $count_completed = $count_cancelled = 0;
// Treat several statuses as "ongoing" so In Queue reflects active/hired tasks
$ongoing_statuses = array( 'inqueue', 'hired', 'in_progress', 'pending' );
if ( class_exists('\MNT\UI\Init') ) {
    // Collect IDs for multiple statuses and combine them (remove duplicates)
    $inqueue_ids = (array) \MNT\UI\Init::mnt_get_seller_task_orders( $seller_id, 'inqueue' );
    $hired_ids = (array) \MNT\UI\Init::mnt_get_seller_task_orders( $seller_id, 'hired' );
    $inprog_ids = (array) \MNT\UI\Init::mnt_get_seller_task_orders( $seller_id, 'in_progress' );
    $pending_ids = (array) \MNT\UI\Init::mnt_get_seller_task_orders( $seller_id, 'pending' );
    $ongoing_ids = array_values( array_unique( array_merge( $inqueue_ids, $hired_ids, $inprog_ids, $pending_ids ) ) );
    $count_inqueue   = intval( count( $ongoing_ids ) );
    $count_completed = intval( count( \MNT\UI\Init::mnt_get_seller_task_orders( $seller_id, 'completed' ) ) );
    $count_cancelled = intval( count( \MNT\UI\Init::mnt_get_seller_task_orders( $seller_id, 'cancelled' ) ) );
    // Admin debug: expose counts for each status to help diagnose mismatches
    if ( current_user_can('manage_options') ) {
        $debug_counts = array(
            'inqueue' => intval( count( (array) \MNT\UI\Init::mnt_get_seller_task_orders( $seller_id, 'inqueue' ) ) ),
            'hired' => intval( count( (array) \MNT\UI\Init::mnt_get_seller_task_orders( $seller_id, 'hired' ) ) ),
            'in_progress' => intval( count( (array) \MNT\UI\Init::mnt_get_seller_task_orders( $seller_id, 'in_progress' ) ) ),
            'pending' => intval( count( (array) \MNT\UI\Init::mnt_get_seller_task_orders( $seller_id, 'pending' ) ) ),
            'completed' => intval( count( (array) \MNT\UI\Init::mnt_get_seller_task_orders( $seller_id, 'completed' ) ) ),
            'cancelled' => intval( count( (array) \MNT\UI\Init::mnt_get_seller_task_orders( $seller_id, 'cancelled' ) ) ),
        );
        echo '<div class="tb-admin-debug" style="font-size:12px;color:#666;margin-bottom:8px;">Status counts: ' . esc_html( json_encode($debug_counts) ) . '</div>';
    }
        // Build per-product maps for faster per-task counts in the loop (use combined ongoing IDs)
        $map_inqueue = $map_completed = $map_cancelled = array();
        if ( is_array( $ongoing_ids ) ) {
            foreach ( $ongoing_ids as $o ) {
                $pid = get_post_meta( $o, '_mnt_task_id', true ) ?: get_post_meta( $o, 'task_product_id', true );
                $pid = intval( $pid );
                if ( $pid ) $map_inqueue[ $pid ] = ( ! empty( $map_inqueue[ $pid ] ) ? $map_inqueue[ $pid ] : 0 ) + 1;
            }
        }
        $orders = \MNT\UI\Init::mnt_get_seller_task_orders( $seller_id, 'completed' );
        if ( is_array( $orders ) ) {
            foreach ( $orders as $o ) {
                $pid = get_post_meta( $o, '_mnt_task_id', true ) ?: get_post_meta( $o, 'task_product_id', true );
                $pid = intval( $pid );
                if ( $pid ) $map_completed[ $pid ] = ( ! empty( $map_completed[ $pid ] ) ? $map_completed[ $pid ] : 0 ) + 1;
            }
        }
        $orders = \MNT\UI\Init::mnt_get_seller_task_orders( $seller_id, 'cancelled' );
        if ( is_array( $orders ) ) {
            foreach ( $orders as $o ) {
                $pid = get_post_meta( $o, '_mnt_task_id', true ) ?: get_post_meta( $o, 'task_product_id', true );
                $pid = intval( $pid );
                if ( $pid ) $map_cancelled[ $pid ] = ( ! empty( $map_cancelled[ $pid ] ) ? $map_cancelled[ $pid ] : 0 ) + 1;
            }
        }
} else {
    // Legacy fallback: count by meta
    $base_args = array(
        'post_type'      => 'shop_order',
        'post_status'    => 'any',
        'fields'         => 'ids',
        'posts_per_page' => -1,
        'meta_query'     => array(
            'relation' => 'AND',
            array( 'key' => 'payment_type', 'value' => 'tasks', 'compare' => '=' ),
            array( 'relation' => 'OR',
                array( 'key' => 'seller_id', '_compare' => '=', 'value' => $seller_id ),
                array( 'key' => '_seller_id', '_compare' => '=', 'value' => $seller_id ),
            ),
        ),
    );
    // Inqueue (treat multiple statuses as ongoing/hired)
    $args = $base_args;
    $args['meta_query'][] = array(
        'relation' => 'OR',
        array( 'key' => '_task_status', 'value' => 'inqueue', 'compare' => '=' ),
        array( 'key' => '_task_status', 'value' => 'hired', 'compare' => '=' ),
        array( 'key' => '_task_status', 'value' => 'in_progress', 'compare' => '=' ),
        array( 'key' => '_task_status', 'value' => 'pending', 'compare' => '=' ),
    );
    $posts = get_posts( $args );
    $map_inqueue = array();
    if ( is_array( $posts ) ) {
        $count_inqueue = count( $posts );
        foreach ( $posts as $o ) {
            $pid = get_post_meta( $o, '_mnt_task_id', true ) ?: get_post_meta( $o, 'task_product_id', true );
            $pid = intval( $pid );
            if ( $pid ) $map_inqueue[ $pid ] = ( ! empty( $map_inqueue[ $pid ] ) ? $map_inqueue[ $pid ] : 0 ) + 1;
        }
    }
    // Completed
    $args = $base_args;
    $args['meta_query'][] = array( 'key' => '_task_status', 'value' => 'completed', 'compare' => '=' );
    $posts = get_posts( $args );
    $map_completed = array();
    if ( is_array( $posts ) ) {
        $count_completed = count( $posts );
        foreach ( $posts as $o ) {
            $pid = get_post_meta( $o, '_mnt_task_id', true ) ?: get_post_meta( $o, 'task_product_id', true );
            $pid = intval( $pid );
            if ( $pid ) $map_completed[ $pid ] = ( ! empty( $map_completed[ $pid ] ) ? $map_completed[ $pid ] : 0 ) + 1;
        }
    }
    // Cancelled
    $args = $base_args;
    $args['meta_query'][] = array( 'key' => '_task_status', 'value' => 'cancelled', 'compare' => '=' );
    $posts = get_posts( $args );
    $map_cancelled = array();
    if ( is_array( $posts ) ) {
        $count_cancelled = count( $posts );
        foreach ( $posts as $o ) {
            $pid = get_post_meta( $o, '_mnt_task_id', true ) ?: get_post_meta( $o, 'task_product_id', true );
            $pid = intval( $pid );
            if ( $pid ) $map_cancelled[ $pid ] = ( ! empty( $map_cancelled[ $pid ] ) ? $map_cancelled[ $pid ] : 0 ) + 1;
        }
    }
}
?>

<?php
$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
$taskbot_args = array(
    'post_type'         => 'product',
    'post_status'       => 'any',
    'posts_per_page'    => get_option('posts_per_page'),
    'paged'             => $paged,
    'author'            => $current_user->ID,
    'orderby'           => 'date',
    'order'             => 'DESC',
    'tax_query'         => array(
        array(
            'taxonomy' => 'product_type',
            'field'    => 'slug',
            'terms'    => 'tasks',
        ),
    ),
);
if(!empty($order_type) && $order_type!= 'any' ){

    $taskbot_args['post_status'] = $order_type;
}

$taskbot_query = new WP_Query( apply_filters('taskbot_service_listings_args', $taskbot_args) );

if ( $taskbot_query->have_posts() ) :   
    ?>
    <ul class="tb-savelisting">
        <?php do_action('taskbot_service_listing_before');?>
        <?php
        while ( $taskbot_query->have_posts() ) : $taskbot_query->the_post();
            $product = wc_get_product( $post->ID );
            $taskbot_add_service_page_edit_url = 'javascript:void(0);';
            
            if($taskbot_add_service_page_url){
                $taskbot_add_service_page_edit_url = add_query_arg( array(
                    'post'    => $post->ID,
                    'step'    => 1,
                ), $taskbot_add_service_page_url );
            }

            $taskbot_featured   = $product->get_featured();
            $task_order_url     = get_the_permalink($post->ID);
            ?>
            <li id="post-<?php the_ID(); ?>" <?php post_class('tb-tabbitem'); ?>>
                <?php do_action('taskbot_service_item_before', $product);?>
                <div class="tb-tabbitem__list tb-tabbitem__listtwo">
                    <div class="tb-deatlswithimg">
                        <figure>
                            <?php
                                echo woocommerce_get_product_thumbnail('woocommerce_thumbnail');
                                do_action('taskbot_service_featured_item', $product);
                            ?>
                        </figure>
                        <div class="tb-icondetails">
                            <?php echo do_action('taskbot_task_categories', $post->ID, 'product_cat');?>
                            <h6><a href="<?php the_permalink();?>"><?php the_title();?></a></h6>
                            <ul class="tb-rateviews tb-rateviews2">
                                <?php
                                    do_action('taskbot_service_rating_count', $product);
                                    do_action('taskbot_service_item_views', $product);
                                    do_action('taskbot_service_item_reviews', $product);
                                    do_action('taskbot_service_item_status', $post->ID);
                                ?>
                            </ul>
                            <ul class="tb-profilestatus">
                                <?php
                                    // Per-product counters: use maps built from helper results
                                    $product_id = $product->get_id();
                                    $orders_link = Taskbot_Profile_Menu::taskbot_profile_menu_link('tasks-orders', $seller_id, true, 'listing');
                                    $p_inqueue = ! empty( $map_inqueue[ $product_id ] ) ? $map_inqueue[ $product_id ] : 0;
                                    $p_completed = ! empty( $map_completed[ $product_id ] ) ? $map_completed[ $product_id ] : 0;
                                    $p_cancelled = ! empty( $map_cancelled[ $product_id ] ) ? $map_cancelled[ $product_id ] : 0;
                                ?>
                                <li class="tb-status-inqueue"><?php esc_html_e('In Queue','taskbot'); ?> (<?php echo esc_html( $p_inqueue ); ?>)</li>
                                <li class="tb-status-completed"><?php esc_html_e('Completed','taskbot'); ?> (<?php echo esc_html( $p_completed ); ?>)</li>
                                <li class="tb-status-cancelled"><?php esc_html_e('Cancelled','taskbot'); ?> (<?php echo esc_html( $p_cancelled ); ?>)</li>
                                <?php if ( ( defined('WP_DEBUG') && WP_DEBUG ) || current_user_can('manage_options') ) :
                                    // Debug: list actual order IDs contributing to these counts for this product
                                    $debug_in = $debug_comp = $debug_can = array();
                                    if ( class_exists('\MNT\\UI\\Init') ) {
                                        // Debug: check multiple statuses that count as "In Queue"/ongoing
                                        $ords = array_merge(
                                            (array) \MNT\UI\Init::mnt_get_seller_task_orders( $seller_id, 'inqueue' ),
                                            (array) \MNT\UI\Init::mnt_get_seller_task_orders( $seller_id, 'hired' ),
                                            (array) \MNT\UI\Init::mnt_get_seller_task_orders( $seller_id, 'in_progress' ),
                                            (array) \MNT\UI\Init::mnt_get_seller_task_orders( $seller_id, 'pending' )
                                        );
                                        if ( is_array( $ords ) ) {
                                            foreach ( $ords as $o ) {
                                                $opid = get_post_meta( $o, '_mnt_task_id', true ) ?: get_post_meta( $o, 'task_product_id', true );
                                                if ( intval( $opid ) === $product_id ) $debug_in[] = $o . '=>'.intval($opid);
                                            }
                                        }
                                        $ords = \MNT\UI\Init::mnt_get_seller_task_orders( $seller_id, 'completed' );
                                        if ( is_array( $ords ) ) {
                                            foreach ( $ords as $o ) {
                                                $opid = get_post_meta( $o, '_mnt_task_id', true ) ?: get_post_meta( $o, 'task_product_id', true );
                                                if ( intval( $opid ) === $product_id ) $debug_comp[] = $o . '=>'.intval($opid);
                                            }
                                        }
                                        $ords = \MNT\UI\Init::mnt_get_seller_task_orders( $seller_id, 'cancelled' );
                                        if ( is_array( $ords ) ) {
                                            foreach ( $ords as $o ) {
                                                $opid = get_post_meta( $o, '_mnt_task_id', true ) ?: get_post_meta( $o, 'task_product_id', true );
                                                if ( intval( $opid ) === $product_id ) $debug_can[] = $o . '=>'.intval($opid);
                                            }
                                        }
                                    } else {
                                        // Legacy fallback: scan matching posts
                                        $scan_args = array('post_type'=>'shop_order','post_status'=>'any','fields'=>'ids','posts_per_page'=>-1,'meta_query'=>array(array('key'=>'payment_type','value'=>'tasks','compare'=>'='),array('relation'=>'OR',array('key'=>'seller_id','value'=>$seller_id,'compare'=>'='),array('key'=>'_seller_id','value'=>$seller_id,'compare'=>'='))));
                                        $all = get_posts($scan_args);
                                        foreach((array)$all as $o){
                                            $ts = get_post_meta($o,'_task_status',true);
                                            $opid = get_post_meta( $o, '_mnt_task_id', true ) ?: get_post_meta( $o, 'task_product_id', true );
                                            if ( intval($opid) !== $product_id ) continue;
                                            if ( in_array($ts, array('inqueue','hired','in_progress','pending')) ) $debug_in[] = $o . '=>'.intval($opid);
                                            if ( $ts === 'completed' ) $debug_comp[] = $o . '=>'.intval($opid);
                                            if ( $ts === 'cancelled' ) $debug_can[] = $o . '=>'.intval($opid);
                                        }
                                    }
                                    echo '<li class="tb-debug" style="font-size:11px;color:#666;margin-top:6px">';
                                    echo 'Debug orders - InQueue: [' . implode(',', $debug_in) . '] Completed: [' . implode(',', $debug_comp) . '] Cancelled: [' . implode(',', $debug_can) . ']';
                                    echo '</li>';
                                endif; ?>
                            </ul>
                        </div>
                    </div>
                    <div class="tb-itemlinks">
                        <?php do_action('taskbot_service_item_starting_price', $product);?>
                        <?php if($product->get_status() == 'publish' || $product->get_status() == 'private' ){?>
                            <div class="tb-switchservice">
                                <span><?php esc_html_e('Task on / off', 'taskbot');?></span>
                                <div class="tb-onoff">
                                    <input type="checkbox" id="service-enable-switch-<?php echo intval($post->ID);?>" data-id="<?php echo (int)$post->ID;?>" name="service-enable-disable" <?php if($product->get_status() == 'publish'){echo do_shortcode('checked="checked"');}?>>
                                    <label for="service-enable-switch-<?php echo intval($post->ID);?>"><em><i></i></em><span class="tb-enable"></span><span class="tb-disable"></span></label>
                                </div>
                            </div>
                        <?php } ?>
                        <?php if(!empty($package_option) ){?>
                            <div class="tb-switchservice">
                                <span><?php esc_html_e('Featured Task', 'taskbot');?></span>
                                <div class="tb-onoff">
                                    <input type="checkbox" id="service-featured-switch-<?php echo intval($post->ID);?>" data-id="<?php echo (int)$post->ID;?>" name="service-featured-disable" <?php if(!empty($taskbot_featured)){echo do_shortcode('checked="checked"');}?>>
                                    <label for="service-featured-switch-<?php echo intval($post->ID);?>"><em><i></i></em><span class="tb-enable"></span><span class="tb-disable"></span></label>
                                </div>
                            </div>
                        <?php } ?>
                        <ul class="tb-tabicon">
                            <li data-class="tb-tooltip-data" id="tb-tooltip-10<?php echo esc_attr($post->ID) ?>" data-tippy-interactive="true" data-tippy-placement="top" data-tippy-content="<?php esc_html_e('Edit','taskbot'); ?>"><a href="<?php echo esc_url($taskbot_add_service_page_edit_url);?>"><span class="tb-icon-edit-2"></span></a> </li>
                            <li data-class="tb-tooltip-data" id="tb-tooltip-20<?php echo esc_attr($post->ID) ?>" data-tippy-interactive="true" data-tippy-placement="top" data-tippy-content="<?php esc_html_e('Delete','taskbot'); ?>" class="tb-delete"> <a href="javascript:void(0);"  class="taskbot-service-delete" data-id="<?php echo (int)$post->ID;?>"><span class="tb-icon-trash-2 bg-redheart"></span></a> </li>
                            <li data-class="tb-tooltip-data" id="tb-tooltip-30<?php echo esc_attr($post->ID) ?>" data-tippy-interactive="true" data-tippy-placement="top" data-tippy-content="<?php esc_html_e('View','taskbot'); ?>"><a href="<?php echo esc_url( $task_order_url );?>"><span class="tb-icon-external-link bg-gray"></span></a></li>
                        </ul>
                    </div>
                </div>
                <?php do_action('taskbot_service_item_after', $product);?>
            </li>
            <?php
        endwhile;
        do_action('taskbot_service_listing_after');
        ?>
    </ul>
    <?php
    taskbot_paginate($taskbot_query);
else:
    $image_url = !empty($taskbot_settings['empty_listing_image']['url']) ? $taskbot_settings['empty_listing_image']['url'] : TASKBOT_DIRECTORY_URI . 'public/images/empty.png';
    ?>
    <div class="tb-submitreview tb-submitreviewv3">
        <figure>
            <img src="<?php echo esc_url($image_url)?>" alt="<?php esc_attr_e('add task','taskbot');?>">
        </figure>
        <h4><?php esc_html_e( 'Add your new Task and start getting orders', 'taskbot'); ?></h4>
        <h6><a href="<?php echo esc_url($taskbot_add_service_page_url);?>"> <?php esc_html_e('Add new task', 'taskbot'); ?> </a></h6>
    </div>
    <?php
endif;
wp_reset_postdata();
$script = "
jQuery(document).on('ready', function(){
    jQuery(document).on('change', '#tb_order_type', function (e) {
        let _this       = jQuery(this);
        let page_url = _this.find(':selected').data('url');
		window.location.replace(page_url);
    });
});
";
wp_add_inline_script( 'taskbot', $script, 'after' );