<?php
/**
 * @package Hello_Dolly
 * @version 1.7.2
 */
/*
Plugin Name: Root Password Validation
Version: 1.0
*/

add_action(
    'admin_init', function () {
        if (! class_exists('SW_WAPF\Includes\Classes\Field_Groups')) {
            add_action(
                'admin_notices', function () {
                    echo '<div class="notice notice-warning is-dismissible">
                <p><code>Root Password Validation</code> plugin needs "Advanced Product Fields for Woocommerce" active to work.</p>
         </div>';
                }
            );
        }
    }
);

add_filter(
    'woocommerce_add_to_cart_validation', function ($passed, $product_id, $qty, $variation_id = null) {

        if (! class_exists('SW_WAPF\Includes\Classes\Field_Groups')) {
            die('hi me');
            return $passed;
        }

        if(!isset($_REQUEST['wapf_field_groups'])) {
            return $passed;
        }

        $field_groups = SW_WAPF\Includes\Classes\Field_Groups::get_field_groups_of_product($product_id);
        if(empty($field_groups)) {
            return $passed;
        }

        $field_group_ids = explode(',', sanitize_text_field($_REQUEST['wapf_field_groups']));
        foreach ($field_groups as $fg) {
            if(!in_array($fg->id, $field_group_ids)) {
                wc_add_notice(esc_html(__('Error adding product to cart.', 'sw-wapf')), 'error');
                return false;
            }
        }

        foreach($field_groups as $group) {
            foreach($group->fields as $field) {

                if (strpos($field->class, 'root-password') === false) {
                    die('no class');
                    continue;
                }

                $value = SW_WAPF\Includes\Classes\Fields::get_raw_field_value_from_request($field, 0, true);

                $valid = rpv_validate_password($value);

                if ($valid !== true) {
                    wc_add_notice($valid, 'error');
                    return false;
                }

                if(empty($value)) {
                      wc_add_notice(sprintf(__('The field "%s" is required.', 'advanced-product-fields-for-woocommerce'), esc_html($field->label)), 'error');
                      return false;
                }

            }
        }

        return $passed;
    }, 10, 4
);

function rpv_validate_password($value, $valid = true)
{
    if (strlen($value) < 8) {
        return "Root Password is too short.";
    }

    $matches = preg_match('/^[A-Z]/', $value); // starts with a cap

    if ($matches) {
        return "Root Password must not start with a capital letter.";
    }

    $matches = preg_match('/[A-Z]$/', $value); // ends with a cap

    if ($matches) {
        return "Root Password must not end with a capital letter.";
    }

    $matches = preg_match('/[A-Z]/', $value); // Has a cap

    if (!$matches) {
        return 'Root Password must have 1 capital letter.';
    }

    $matches = preg_match('/[a-z]$/', $value); // ends with a letter

    if (!$matches) {
        return "Root Password must not end with a number or special character.";
    }

    $matches = preg_match('/\d/', $value); // has a number

    if (!$matches) {
        return "Root Password must contain at least 1 number.";
    }

    return $valid;
}
