<?php
/*
Template Name: Create Escrow for Task
Description: Page for buyers to create an escrow for a task and hire a seller directly (no WooCommerce).
*/

if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

$user_identity = get_current_user_id();
$post_id = taskbot_get_linked_profile_id($user_identity);

// Include WordPress head for styles and scripts
wp_head();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f3f4f6;
            margin: 0;
            padding: 20px;
            color: #1f2937;
        }
        .escrow-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            .escrow-wrapper {
                padding: 20px 15px;
            }
        }
    </style>
</head>
<body>
    <div class="escrow-wrapper">
        <?php
        // Include the actual task escrow creation form
        $plugin_template = WP_PLUGIN_DIR . '/mnt-escrow/templates/page-create-escrow-task.php';
        if (file_exists($plugin_template)) {
            include $plugin_template;
        } else {
            echo '<div class="notice notice-error">Task Escrow template not found.</div>';
        }
        ?>
    </div>
    <?php wp_footer(); ?>
</body>
</html>
<?php exit;
