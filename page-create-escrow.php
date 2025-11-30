<?php
/*
Template Name: Create Escrow Transaction
Description: Page for buyers to create an escrow for a project/task and hire a seller directly (no WooCommerce).
*/

// The rest of the template code is already in plugins/mnt-escrow/templates/page-create-escrow.php
// To avoid duplication, we include it here.

$plugin_template = WP_PLUGIN_DIR . '/mnt-escrow/templates/page-create-escrow.php';
if (file_exists($plugin_template)) {
    include $plugin_template;
} else {
    echo '<div class="notice notice-error">Escrow template not found.</div>';
}
