<?php
/**
 * Wallet settings
 *
 * @package     Taskbot
 * @subpackage  Taskbot/templates/dashboard
 * @author      Amentotech <info@amentotech.com>
 * @link        https://codecanyon.net/user/amentotech/portfolio
 * @version     1.0
 * @since       1.0
*/

global $current_user;

$user_identity = intval($current_user->ID);
?>
<div class="tb-dhb-wallet-settings">
	<div class="tb-dhb-mainheading">
		<h2><?php esc_html_e('Wallet Management','taskbot');?></h2>
	</div>
	
	<div class="tb-profile-settings-box tb-wallet-wrapper">
		<div class="tb-dhb-box-wrapper">
			<?php 
			// Display wallet shortcodes
			if (shortcode_exists('mnt_create_wallet')) {
				echo do_shortcode('[mnt_create_wallet]');
				echo do_shortcode('[mnt_wallet_dashboard]');
			} else {
				echo '<p style="color: red;">Wallet functionality is not available. Please check if the MNT Escrow plugin is active.</p>';
			}
			?>
		</div>
	</div>
</div>
