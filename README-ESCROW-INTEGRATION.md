# Taskup Child Theme - MNT Escrow Integration

## Overview
This child theme contains all customizations for the MNT Escrow integration with Taskbot plugin. By placing template overrides in the child theme, we ensure that updates to the parent theme or Taskbot plugin won't overwrite our changes.

---

## Directory Structure

```
taskup-child/
├── functions.php                           # Child theme customizations
├── style.css                               # Child theme styles
├── taskbot-templates/                      # Taskbot plugin template overrides
│   └── dashboard/
│       └── post-project/
│           └── buyer/
│               ├── dashboard-proposals-detail.php    # Hire button with escrow
│               └── dashboard-buyer-projects.php      # Project listing with badges
└── extend/                                 # Backward compatibility (legacy)
    └── dashboard/
```

---

## What Was Changed

### 1. Template Override System
**File:** `functions.php`

Added two filter hooks to override Taskbot plugin templates:
- `taskbot_locate_template` - Primary template override
- `taskbot_get_template_part` - Fallback template override

**How It Works:**
1. WordPress/Taskbot loads a template
2. Filter checks if override exists in `taskup-child/taskbot-templates/`
3. If found, uses child theme version
4. If not found, uses original plugin version

**Template Priority:**
```
1. taskup-child/taskbot-templates/{path}/{template}.php
2. taskup-child/extend/{path}/{template}.php (legacy)
3. taskbot/templates/{path}/{template}.php (original)
```

---

### 2. Proposal Detail Template
**File:** `taskbot-templates/dashboard/post-project/buyer/dashboard-proposals-detail.php`

**Changes:**
- ✅ Replaced hire button with `mnt_escrow_hire_button()` (Line ~207)
- ✅ Replaced milestone payment buttons with escrow buttons (Line ~157)
- ✅ Added wallet balance validation
- ✅ Smart button states (login, create wallet, fund wallet, hire)

**Before:**
```php
<button class="tk-btn-solid-lg-lefticon" data-id="<?php echo $proposal_id;?>">
    Hire "Seller"
</button>
```

**After:**
```php
<?php
if (function_exists('mnt_escrow_hire_button')) {
    mnt_escrow_hire_button(
        $project_id,
        $product_author_id,
        $proposal_price,
        [
            'button_text' => sprintf(__('Hire "%s" with Secure Escrow', 'taskbot'), $user_name),
            'button_class' => 'tk-btn-solid-lg-lefticon',
            'icon' => '<i class="tb-icon-lock"></i>',
            'show_balance' => true
        ]
    );
} else {
    // Fallback to original button
    echo '<button>Hire</button>';
}
?>
```

**Benefits:**
- Secure escrow payment flow
- Automatic wallet validation
- Graceful degradation if MNT Escrow plugin disabled
- Update-safe (in child theme)

---

### 3. Buyer Projects Template
**File:** `taskbot-templates/dashboard/post-project/buyer/dashboard-buyer-projects.php`

**Changes:**
- ✅ Added escrow status badges to project listings (Line ~153)
- ✅ Shows current escrow transaction status
- ✅ Color-coded badges (FUNDED, RELEASED, CANCELLED)

**Before:**
```php
<ul class="tk-template-view">
    <?php do_action( 'taskbot_posted_date_html', $product );?>
    <?php do_action( 'taskbot_location_html', $product );?>
</ul>
```

**After:**
```php
<ul class="tk-template-view">
    <?php do_action( 'taskbot_posted_date_html', $product );?>
    <?php do_action( 'taskbot_location_html', $product );?>
    <?php 
    // Show escrow status badge if project has escrow
    if (function_exists('mnt_escrow_status_badge')) {
        mnt_escrow_status_badge($product->get_id());
    }
    ?>
</ul>
```

**Benefits:**
- Visual escrow status indicator
- Only shows if project has escrow
- Graceful degradation if plugin disabled
- Update-safe

---

### 4. Wallet Balance Override
**File:** `functions.php`

**Changes:**
- ✅ Added filter to override Taskbot wallet with MNT wallet
- ✅ Ensures single source of truth for balance
- ✅ Backward compatible

**Filter Hook:**
```php
add_filter('taskbot_account_balance', 'taskup_child_use_mnt_wallet_balance', 10, 2);
```

**Functionality:**
```php
function taskup_child_use_mnt_wallet_balance($balance, $user_id) {
    if (class_exists('MNT\Api\wallet')) {
        $wallet_result = \MNT\Api\wallet::balance($user_id);
        if ($wallet_result && isset($wallet_result['balance'])) {
            return floatval($wallet_result['balance']);
        }
    }
    return $balance;
}
```

---

## How Template Override Works

### WordPress Template Hierarchy
```
1. Child Theme: taskup-child/taskbot-templates/
   ↓ (if not found)
2. Child Theme Legacy: taskup-child/extend/
   ↓ (if not found)
3. Plugin Original: taskbot/templates/
```

### Filter Flow
```
Taskbot calls template
    ↓
Filter: taskbot_locate_template
    ↓
Check: taskup-child/taskbot-templates/{path}
    ↓
Found? → Use child theme version
    ↓
Not found? → Use plugin version
```

---

## Adding New Template Overrides

### Step 1: Identify Template
Find the original template in Taskbot plugin:
```
wp-content/plugins/taskbot/templates/dashboard/example-template.php
```

### Step 2: Copy to Child Theme
Create the same directory structure in child theme:
```
wp-content/themes/taskup-child/taskbot-templates/dashboard/example-template.php
```

### Step 3: Modify Template
Make your changes in the child theme version. Add comment at top:
```php
<?php
/**
 * Original template info here...
 * 
 * CUSTOMIZED: Child theme override for [reason]
 */
```

### Step 4: Clear Cache
Clear all caches and test:
- WordPress cache
- Browser cache
- Server cache (if any)

---

## Testing Template Overrides

### Verify Override is Active
Add this to your child theme template:
```php
<!-- CHILD THEME OVERRIDE ACTIVE -->
```

View page source and search for that comment.

### Check Which Template is Loaded
Add to functions.php temporarily:
```php
add_filter('taskbot_locate_template', function($located, $template_name) {
    error_log('Template: ' . $template_name . ' | Located: ' . $located);
    return $located;
}, 999, 2);
```

Check `wp-content/debug.log` for template paths.

---

## Update Safety

### Parent Theme Updates
✅ **SAFE** - Child theme files are never touched by parent theme updates

### Taskbot Plugin Updates
✅ **SAFE** - Our overrides are in child theme, plugin updates won't affect them

### MNT Escrow Plugin Updates
⚠️ **CHECK COMPATIBILITY** - If MNT Escrow helper functions change signature, update child theme templates

---

## Rollback / Disable Overrides

### Temporarily Disable All Overrides
In `functions.php`, comment out the filters:
```php
// add_filter('taskbot_locate_template', 'taskup_child_override_taskbot_templates', 10, 3);
// add_filter('taskbot_get_template_part', 'taskup_child_override_get_template_part', 10, 3);
```

### Disable Single Template
Rename or delete the specific file:
```bash
# Rename to disable
mv dashboard-proposals-detail.php dashboard-proposals-detail.php.backup

# Or delete
rm dashboard-proposals-detail.php
```

### Revert to Original
Delete child theme override files, Taskbot will use original versions.

---

## Troubleshooting

### Issue: Changes not showing
**Solution:**
1. Clear all caches
2. Check file permissions (644 for files, 755 for directories)
3. Verify file path matches plugin structure exactly
4. Check for PHP errors in `wp-content/debug.log`

### Issue: White screen / fatal error
**Solution:**
1. Check PHP syntax in modified template
2. Ensure all required variables are available
3. Check if MNT Escrow functions exist before calling
4. Add error logging: `error_log(print_r($variable, true));`

### Issue: Original template loading instead of override
**Solution:**
1. Verify directory structure matches exactly
2. Check filter hooks are added in functions.php
3. Test with template debug code (see Testing section)
4. Clear OPcache if enabled: `opcache_reset()`

### Issue: MNT Escrow functions not working
**Solution:**
1. Verify MNT Escrow plugin is active
2. Check function exists before calling:
```php
if (function_exists('mnt_escrow_hire_button')) {
    // Safe to use
}
```
3. Check for JavaScript console errors
4. Verify AJAX endpoints are registered

---

## Best Practices

### 1. Always Check Function Exists
```php
if (function_exists('mnt_escrow_hire_button')) {
    mnt_escrow_hire_button(...);
} else {
    // Fallback code
}
```

### 2. Preserve Original Functionality
Keep original code in `else` blocks for graceful degradation:
```php
if (function_exists('new_function')) {
    new_function();
} else {
    // Original code here
    original_function();
}
```

### 3. Document Changes
Add comments explaining customizations:
```php
// CUSTOMIZATION: Added escrow integration
// Date: 2025-11-27
// Reason: Replace WooCommerce cart with escrow
```

### 4. Version Control
Track child theme in Git:
```bash
git add taskup-child/
git commit -m "Add MNT Escrow template overrides"
```

### 5. Test After Updates
After any plugin/theme update:
- Test hire flow
- Test wallet balance display
- Check for JavaScript errors
- Verify escrow badges appear

---

## Dependencies

### Required Plugins
- ✅ Taskbot (any version with template override filters)
- ✅ MNT Escrow plugin (v1.0.0+)
- ✅ WooCommerce (3.0+)

### Required Functions (from MNT Escrow)
```php
mnt_escrow_hire_button()          # Smart hire button
mnt_get_escrow_url()              # Generate escrow URL
mnt_escrow_status_badge()         # Display status badge
mnt_user_has_wallet()             # Check wallet exists
mnt_get_wallet_balance()          # Get balance
mnt_has_sufficient_funds()        # Validate funds
mnt_project_has_escrow()          # Check escrow status
```

### Required Classes (from MNT Escrow)
```php
MNT\Api\wallet                    # Wallet operations
MNT\Api\Escrow                    # Escrow operations
```

---

## File Change Summary

### New Files
```
taskup-child/
├── taskbot-templates/dashboard/post-project/buyer/dashboard-proposals-detail.php
├── taskbot-templates/dashboard/post-project/buyer/dashboard-buyer-projects.php
└── README-ESCROW-INTEGRATION.md (this file)
```

### Modified Files
```
taskup-child/functions.php
- Added template override filters
- Added wallet balance override
- Added helper function for balance display
```

### Unchanged Files
```
taskup-child/style.css              # No CSS changes needed
taskup-child/screenshot.jpg         # Theme screenshot
```

---

## Performance Impact

### Template Loading
- **Overhead:** Minimal (~0.001s per template check)
- **Caching:** Compatible with object cache
- **Optimization:** Uses `file_exists()` for fast checks

### Database Queries
- No additional queries added
- Escrow badge queries only when project has escrow
- Balance queries cached by MNT Escrow plugin

---

## Support & Documentation

### Related Documentation
- `mnt-escrow/ESCROW-HIRING-GUIDE.md` - Complete escrow flow
- `mnt-escrow/QUICK-INTEGRATION.md` - Quick reference
- `mnt-escrow/IMPLEMENTATION-COMPLETE.md` - Implementation summary
- `mnt-escrow/TESTING-CHECKLIST.md` - Full testing guide

### Helper Functions Reference
See `mnt-escrow/includes/helpers.php` for all available functions.

### Debugging
Enable WordPress debug mode in `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Check logs at: `wp-content/debug.log`

---

## Version History

### v1.0.0 (2025-11-27)
- ✅ Initial escrow integration
- ✅ Hire button override
- ✅ Milestone payment override
- ✅ Status badges integration
- ✅ Wallet balance override
- ✅ Template override system

---

**Last Updated:** November 27, 2025  
**Compatible With:**
- Taskbot Plugin: All versions with template filters
- MNT Escrow: v1.0.0+
- WordPress: 5.8+
- PHP: 7.4+
