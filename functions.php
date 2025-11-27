<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

// BEGIN ENQUEUE PARENT ACTION
// AUTO GENERATED - Do not modify or remove comment markers above or below:

if ( !function_exists( 'chld_thm_cfg_locale_css' ) ):
    function chld_thm_cfg_locale_css( $uri ){
        if ( empty( $uri ) && is_rtl() && file_exists( get_template_directory() . '/rtl.css' ) )
            $uri = get_template_directory_uri() . '/rtl.css';
        return $uri;
    }
endif;
add_filter( 'locale_stylesheet_uri', 'chld_thm_cfg_locale_css' );

if ( !function_exists( 'chld_thm_cfg_parent_css' ) ):
    function chld_thm_cfg_parent_css() {
        wp_enqueue_style( 'chld_thm_cfg_parent', trailingslashit( get_template_directory_uri() ) . 'style.css', array(  ) );
    }
endif;
add_action( 'wp_enqueue_scripts', 'chld_thm_cfg_parent_css', 10 );

// END ENQUEUE PARENT ACTION

/**
 * Override Taskbot plugin templates with child theme templates
 * Templates should be placed in: child-theme/taskbot-templates/
 * 
 * This allows update-safe customization of Taskbot plugin templates
 */
if (!function_exists('taskup_child_override_taskbot_templates')) {
    function taskup_child_override_taskbot_templates($located, $template_name, $args) {
        // Build possible paths in child theme
        $child_theme_paths = [
            // Try in taskbot-templates directory
            get_stylesheet_directory() . '/taskbot-templates/' . $template_name,
            // Fallback to extend directory for backward compatibility
            get_stylesheet_directory() . '/extend/' . $template_name,
        ];
        
        // Check each possible path
        foreach ($child_theme_paths as $child_template) {
            if (file_exists($child_template)) {
                return $child_template;
            }
        }
        
        // Otherwise return the original template
        return $located;
    }
    add_filter('taskbot_locate_template', 'taskup_child_override_taskbot_templates', 10, 3);
}

/**
 * Additional template override for get_template_part calls
 */
if (!function_exists('taskup_child_override_get_template_part')) {
    function taskup_child_override_get_template_part($template, $slug, $name) {
        // Build the template path
        $template_file = $slug;
        if ($name) {
            $template_file .= '-' . $name;
        }
        $template_file .= '.php';
        
        // Check in child theme
        $child_template = get_stylesheet_directory() . '/taskbot-templates/' . $template_file;
        
        if (file_exists($child_template)) {
            return $child_template;
        }
        
        return $template;
    }
    add_filter('taskbot_get_template_part', 'taskup_child_override_get_template_part', 10, 3);
}

/**
 * Override Taskbot account balance with MNT Wallet balance
 */
if (!function_exists('taskup_child_use_mnt_wallet_balance')) {
    function taskup_child_use_mnt_wallet_balance($balance, $user_id) {
        // Check if MNT Escrow plugin is active and user has wallet
        if (class_exists('MNT\Api\wallet')) {
            $wallet_result = \MNT\Api\wallet::balance($user_id);
            if ($wallet_result && isset($wallet_result['balance'])) {
                return floatval($wallet_result['balance']);
            }
        }
        return $balance;
    }
    add_filter('taskbot_account_balance', 'taskup_child_use_mnt_wallet_balance', 10, 2);
}

/**
 * Display MNT wallet balance in Taskbot earnings page
 */
if (!function_exists('taskup_child_mnt_wallet_balance_display')) {
    function taskup_child_mnt_wallet_balance_display() {
        $user_id = get_current_user_id();
        
        if (class_exists('MNT\Api\wallet')) {
            $wallet_result = \MNT\Api\wallet::balance($user_id);
            $balance = $wallet_result['balance'] ?? 0;
            return floatval($balance);
        }
        
        return 0;
    }
}
