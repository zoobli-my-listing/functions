//Enable ACF Custom Fields page in WP Admin//
add_filter( 'acf/settings/show_admin', '__return_true', 50 );
// Add term and conditions check box on registration form
add_action( 'woocommerce_register_form', 'add_terms_and_conditions_to_registration', 20 );
function add_terms_and_conditions_to_registration() {
    if ( wc_get_page_id( 'terms' ) > 0 && is_account_page() ) {
        ?>
        <p class="form-row terms wc-terms-and-conditions">
            <label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
                <input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" name="terms" <?php checked( apply_filters( 'woocommerce_terms_is_checked_default', isset( $_POST['terms'] ) ), true ); ?> id="terms" /> <span><?php printf( __( 'Ik heb de <a href="%s" target="_blank" class="woocommerce-terms-and-conditions-link">algemene voorwaarden gelezen en goedgekeurd</a>', 'woocommerce' ), esc_url( wc_get_page_permalink( 'terms' ) ) ); ?></span> <span class="required">*</span>
            </label>
            <input type="hidden" name="terms-field" value="1" />
        </p>
    <?php
    }
}
// Validate required term and conditions check box
add_action( 'woocommerce_register_post', 'terms_and_conditions_validation', 20, 3 );
function terms_and_conditions_validation( $username, $email, $validation_errors ) {
    if ( ! isset( $_POST['terms'] ) )
        $validation_errors->add( 'terms_error', __( 'Algemene Voorwaarden zijn niet aangevinkt', 'woocommerce' ) );
    return $validation_errors;
}
// WP DASHBOARD - USERS - ADD REGISTERED DATE COLUMN
// ADD THE NEW COLUMN
add_filter( 'manage_users_columns', 'custom_modify_user_table' );
function custom_modify_user_table( $columns ) {
	$columns['registration_date'] = 'Registration Date'; // add new 
	return $columns; 
}
// FILL THE NEW COLUMN
add_filter( 'manage_users_custom_column', 'custom_modify_user_table_row', 10, 3 );
function custom_modify_user_table_row( $row_output, $column_id_attr, $user ) { 
	$date_format = 'j M, Y H:i'; 
	switch ( $column_id_attr ) {
		case 'registration_date' :
			return date( $date_format, strtotime( get_the_author_meta( 'registered', $user ) ) );
			break;
		default:
	}
 	return $row_output;
 }
// MAKE THE NEW COLUMN SORTABLE
add_filter( 'manage_users_sortable_columns', 'custom_make_registered_column_sortable' );
function custom_make_registered_column_sortable( $columns ) {
	return wp_parse_args( array( 'registration_date' => 'registered' ), $columns );
}
// CHANGE $0 TO FREE
function my_wc_custom_get_price_html( $price, $product ) {
    if ( $product->get_price() == 0 ) {
        if ( $product->is_on_sale() && $product->get_regular_price() ) {
            $regular_price = wc_get_price_to_display( $product, array( 'qty' => 1, 'price' => $product->get_regular_price() ) );
            $price = wc_format_price_range( $regular_price, __( 'Free!', 'woocommerce' ) );
        } else {
            $price = '<span class="amount">' . __( 'FREE', 'woocommerce' ) . '</span>';
        }
    }
    return $price;
}
add_filter( 'woocommerce_get_price_html', 'my_wc_custom_get_price_html', 10, 2 );
// AUTOCOMPLETE WOOCOMMERCE ORDERS
add_action( 'woocommerce_thankyou', 'custom_woocommerce_auto_complete_order' );
function custom_woocommerce_auto_complete_order( $order_id ) {
if ( ! $order_id ) {
return;
}
$order = wc_get_order( $order_id );
$order->update_status( 'completed' );
}
// WOOCOMMERCE - COMBINE BILLING ADDRESS AND PAYMENT METHODS
add_filter( 'woocommerce_account_menu_items', function( $items ) {
unset($items['edit-address']);
unset($items['payment-methods']);
return $items;}, 999 );
add_action( 'woocommerce_account_edit-account_endpoint', 
'woocommerce_account_payment_methods' );
add_action( 'woocommerce_account_edit-account_endpoint', 
'woocommerce_account_edit_address' );
// BYPASS WOOCOMMERCE LOGOUT CONFIRMATION
function wc_bypass_logout_confirmation() {
global $wp;
if ( isset( $wp->query_vars['customer-logout'] ) ) {
wp_redirect( str_replace( '&amp;', '&', wp_logout_url( wc_get_page_permalink( 'myaccount' ) ) ) );
exit;
}
}
add_action( 'template_redirect', 'wc_bypass_logout_confirmation' );
// HOLD ALL REVIEWS FOR MODERATION AND NOTIFY WEBSITE ADMIN
add_filter( 'pre_comment_approved', function( $commentstatus, $commentdata ) {
    $commentstatus = 0;
    return $commentstatus;
}, 10, 2 );

add_filter( 'wp_update_comment_data', function( $data, $comment, $commentarr ) {
 
    if ( isset( $data['comment_approved'] ) ) {
        $data['comment_approved'] = 0;
    } 
    return $data; 
}, 99, 3 );
// HIDE THE LEAVE REVIEW QUICK ACTION FROM THE LISTING OWNER
add_filter( 'body_class', function( $classes ) {
    if ( is_singular( 'job_listing' ) ) {
        global $post;
        if ( absint( get_current_user_id() ) === absint( $post->post_author ) ) {
            $classes[] = 'is-listing-author';
        }
    }
    return $classes;
} );
//Hide Category & Tag @ Single Product Page//
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
//Allow to “Pay for Order” Without Login//
add_filter( 'user_has_cap', 'order_pay_without_login', 9999, 3 );
 
function order_pay_without_login( $allcaps, $caps, $args ) {
   if ( isset( $caps[0], $_GET['key'] ) ) {
      if ( $caps[0] == 'pay_for_order' ) {
         $order_id = isset( $args[2] ) ? $args[2] : null;
         $order = wc_get_order( $order_id );
         if ( $order ) {
            $allcaps['pay_for_order'] = true;
         }
      }
   }
   return $allcaps;
}
//Checkbox to Disable Related Products Conditionally//
// Add new checkbox product edit page
add_action( 'woocommerce_product_options_general_product_data', 'add_related_checkbox_products' );
function add_related_checkbox_products() {           
woocommerce_wp_checkbox( array( 
   'id' => 'hide_related', 
   'class' => '', 
   'label' => 'Gerelateerde producten verbergen?'
   ) 
);      
}
// Save checkbox into custom field
add_action( 'save_post_product', 'save_related_checkbox_products' );
function save_related_checkbox_products( $product_id ) {
   global $pagenow, $typenow;
   if ( 'post.php' !== $pagenow || 'product' !== $typenow ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( isset( $_POST['hide_related'] ) ) {
      update_post_meta( $product_id, 'hide_related', $_POST['hide_related'] );
    } else delete_post_meta( $product_id, 'hide_related' );
}
//image @ Checkout Page//
add_action( 'woocommerce_review_order_after_submit', 'trust_place_order' );
function trust_place_order() {
    echo '<img src="https://zoobli.eu/wp-content/uploads/2020/08/Secure-payments-powered-by-Mollie-Cards-iDeal-PayPal__2x.png" style="margin: 1em auto">';
}
//Hide “Thanks for shopping with us” etc. @ Emails//
add_filter( 'gettext', 'translate_woocommerce_strings_emails', 999 );
function translate_woocommerce_strings_emails( $translated ) {
   // Get strings and translate them into empty string >>> ''
   $translated = str_ireplace( 'Thanks for shopping with us.', '', $translated );
   $translated = str_ireplace( 'We hope to see you again soon.', '', $translated );
   return $translated;
}
//Allow Users to Edit Processing Orders//
// 1. Allow Order Again for Processing Status
add_filter( 'woocommerce_valid_order_statuses_for_order_again', 'bbloomer_order_again_statuses' );
function order_again_statuses( $statuses ) {
    $statuses[] = 'processing';
    return $statuses;
}
// Add Order Actions @ My Account
add_filter( 'woocommerce_my_account_my_orders_actions', 'add_edit_order_my_account_orders_actions', 50, 2 );
function add_edit_order_my_account_orders_actions( $actions, $order ) {
    if ( $order->has_status( 'processing' ) ) {
        $actions['edit-order'] = array(
            'url'  => wp_nonce_url( add_query_arg( array( 'order_again' => $order->get_id(), 'edit_order' => $order->get_id() ) ), 'woocommerce-order_again' ),
            'name' => __( 'Bestelling aanpassen', 'woocommerce' )
        );
    }
    return $actions;
}
// Detect Edit Order Action and Store in Session
add_action( 'woocommerce_cart_loaded_from_session', 'detect_edit_order' );
function detect_edit_order( $cart ) {
    if ( isset( $_GET['edit_order'], $_GET['_wpnonce'] ) && is_user_logged_in() && wp_verify_nonce( wp_unslash( $_GET['_wpnonce'] ), 'woocommerce-order_again' ) ) WC()->session->set( 'edit_order', absint( $_GET['edit_order'] ) );
}
// Display Cart Notice re: Edited Order
add_action( 'woocommerce_before_cart', 'show_me_session' );
function show_me_session() {
    if ( ! is_cart() ) return;
    $edited = WC()->session->get('edit_order');
    if ( ! empty( $edited ) ) {
        $order = new WC_Order( $edited );
        $credit = $order->get_total();
        wc_print_notice( 'Een credit van ' . wc_price($credit) . ' is toegepast op deze nieuwe bestelling. Voel je vrij om producten toe te voegen of andere details zoals de leveringsdatum te wijzigen.', 'notice' );
    }
}
// Calculate New Total if Edited Order
add_action( 'woocommerce_cart_calculate_fees', 'use_edit_order_total', 20, 1 );
function use_edit_order_total( $cart ) {
  if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
  $edited = WC()->session->get('edit_order');
  if ( ! empty( $edited ) ) {
      $order = new WC_Order( $edited );
      $credit = -1 * $order->get_total();
      $cart->add_fee( 'Credit', $credit );
  }
}
// Save Order Action if New Order is Placed
add_action( 'woocommerce_checkout_update_order_meta', 'save_edit_order' );
function save_edit_order( $order_id ) {
    $edited = WC()->session->get( 'edit_order' );
    if ( ! empty( $edited ) ) {
        // update this new order
        update_post_meta( $order_id, '_edit_order', $edited );
        $neworder = new WC_Order( $order_id );
        $oldorder_edit = get_edit_post_link( $edited );
        $neworder->add_order_note( 'Bestelling geplaatst na aanpassing. Oud bestelnummer: <a href="' . $oldorder_edit . '">' . $edited . '</a>' );
        // cancel previous order
        $oldorder = new WC_Order( $edited );
        $neworder_edit = get_edit_post_link( $order_id );
        $oldorder->update_status( 'cancelled', 'Bestelling geannuleerd na aanpassing. Nieuw bestelnummer: <a href="' . $neworder_edit . '">' . $order_id . '</a> -' );
        WC()->session->set( 'edit_order', null );
    }
}
//Move or Remove Coupon Form @ Cart & Checkout//
add_action( 'woocommerce_proceed_to_checkout', 'display_coupon_form_below_proceed_checkout', 25 );
function display_coupon_form_below_proceed_checkout() {
   ?> 
      <form class="woocommerce-coupon-form" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
         <?php if ( wc_coupons_enabled() ) { ?>
            <div class="coupon under-proceed">
               <input type="text" name="coupon_code" class="input-text" id="coupon_code" value="" placeholder="<?php esc_attr_e( 'Code kortingsbon', 'woocommerce' ); ?>" style="width: 100%" /> 
               <button type="submit" class="button" name="apply_coupon" value="<?php esc_attr_e( 'Kortingsbon toepassen', 'woocommerce' ); ?>" style="width: 100%"><?php esc_attr_e( 'Kortingsbon toepassen', 'woocommerce' ); ?></button>
            </div>
         <?php } ?>
      </form>
   <?php
}
//Remove “Have a Coupon?” Form @ WooCommerce Checkout Page//
remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );
//Remove Product Links @ Cart Page//
add_filter( 'woocommerce_cart_item_permalink', '__return_null' );
//Exclude Hidden Products from Mini-Cart Counter//
add_filter( 'woocommerce_cart_contents_count', 'exclude_hidden_minicart_counter' );
function exclude_hidden_minicart_counter( $quantity ) {
  $hidden = 0;
  foreach( WC()->cart->get_cart() as $cart_item ) {
    $product = $cart_item['data'];
    if ( $product->get_catalog_visibility() == 'hidden' ) $hidden += $cart_item['quantity'];
  }
  $quantity -= $hidden;
  return $quantity;
}
//Hide Checkout Billing Fields if Virtual Product @ Cart//
add_filter( 'woocommerce_checkout_fields' , 'simplify_checkout_virtual' );
function simplify_checkout_virtual( $fields ) {
   $only_virtual = true;
   foreach( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
      // Check if there are non-virtual products
      if ( ! $cart_item['data']->is_virtual() ) $only_virtual = false;   
   }
    if( $only_virtual ) {
       unset($fields['billing']['billing_address_1']);
       unset($fields['billing']['billing_address_2']);
       unset($fields['billing']['billing_city']);
       unset($fields['billing']['billing_postcode']);
       unset($fields['billing']['billing_country']);
       unset($fields['billing']['billing_state']);
       unset($fields['billing']['billing_phone']);
       add_filter( 'woocommerce_enable_order_notes_field', '__return_false' );
     }
     
     return $fields;
}
//Disable Customer Order Email for Free Orders//
add_filter( 'woocommerce_email_recipient_customer_completed_order', 'disable_customer_order_email_if_free', 10, 2 );
function disable_customer_order_email_if_free( $recipient, $order ) {
    $page = $_GET['page'] = isset( $_GET['page'] ) ? $_GET['page'] : '';
    if ( 'wc-settings' === $page ) {
        return $recipient; 
    }
    if ( (float) $order->get_total() === '0.00' ) $recipient = '';
    return $recipient;
}
//Save “Terms & Conditions” Acceptance @ Checkout//
// 1. Save T&C as Order Meta
add_action( 'woocommerce_checkout_update_order_meta', 'save_terms_conditions_acceptance' );
function save_terms_conditions_acceptance( $order_id ) {
if ( $_POST['terms'] ) update_post_meta( $order_id, 'terms', esc_attr( $_POST['terms'] ) );
}
// Display T&C @ Single Order Page
add_action( 'woocommerce_admin_order_data_after_billing_address', 'display_terms_conditions_acceptance' );
function display_terms_conditions_acceptance( $order ) {
if ( get_post_meta( $order->get_id(), 'terms', true ) == 'on' ) {
echo '<p><strong>Algemene Voorwaarden: </strong>geaccepteerd</p>';
} else echo '<p><strong>Algemene Voorwaarden: </strong>NVT</p>';
}
//Add Content Under “Place Order” Button @ Checkout//
add_action( 'woocommerce_review_order_after_submit', 'privacy_message_below_checkout_button' );
function privacy_message_below_checkout_button() {
   echo '<p><small>Uw persoonlijke gegevens helpen ons om uw account aan te maken en om uw gebruikerservaring op deze website te ondersteunen. Neem een kijkje op onze <a href="https://www.zoobli.eu/nl/privacycentrum/privacybeleid/" target=_blank">Privacybeleid</a> voor meer informatie over hoe wij uw persoonlijke gegevens gebruiken.</small></p>';
}
//Slashed Cart Subtotal if Coupon @ Cart//
add_filter( 'woocommerce_cart_subtotal', 'slash_cart_subtotal_if_discount', 99, 3 );
function slash_cart_subtotal_if_discount( $cart_subtotal, $compound, $obj ){
global $woocommerce;
if ( $woocommerce->cart->get_cart_discount_total() <> 0 ) {
$new_cart_subtotal = wc_price( WC()->cart->subtotal - $woocommerce->cart->get_cart_discount_tax_total() - $woocommerce->cart->get_cart_discount_total() );
$cart_subtotal = sprintf( '<del>%s</del> <b>%s</b>', $cart_subtotal , $new_cart_subtotal );
}
return $cart_subtotal;
}
//Display Categories Under Product Name @ Cart//
add_filter( 'woocommerce_cart_item_name', 'cart_item_category', 99, 3);
function cart_item_category( $name, $cart_item, $cart_item_key ) {
$product_item = $cart_item['data'];
// get parent product if variation
if ( $product_item->is_type( 'variation' ) ) {
$product_item = wc_get_product( $product_item->get_parent_id() );
}
$cat_ids = $product_item->get_category_ids();
// if product has categories, concatenate cart item name with them
if ( $cat_ids ) $name .= '</br>' . wc_get_product_category_list( $product_item->get_id(), ', ', '<span class="posted_in">' . _n( 'Category:', 'Categories:', count( $cat_ids ), 'woocommerce' ) . ' ', '</span>' );
return $name;
}
//Add “Confirm Email Address” Field @ Checkout//
// 1) Make original email field half width
// 2) Add new confirm email field
add_filter( 'woocommerce_checkout_fields' , 'add_email_verification_field_checkout' );
function add_email_verification_field_checkout( $fields ) {
$fields['billing']['billing_email']['class'] = array( 'form-row-first' );
$fields['billing']['billing_em_ver'] = array(
    'label' => 'Bevestig e-mailadres',
    'required' => true,
    'class' => array( 'form-row-last' ),
    'clear' => true,
    'priority' => 999,
);
  
return $fields;
}
// Generate error message if field values are different
add_action('woocommerce_checkout_process', 'matching_email_addresses');
function matching_email_addresses() { 
    $email1 = $_POST['billing_email'];
    $email2 = $_POST['billing_em_ver'];
    if ( $email2 !== $email1 ) {
        wc_add_notice( 'Uw e-mailadressen komen niet overeen', 'error' );
    }
}
//Deny Checkout if User Has Pending Orders//
add_action('woocommerce_after_checkout_validation', 'deny_checkout_user_pending_orders');
function deny_checkout_user_pending_orders( $posted ) {
global $woocommerce;
$checkout_email = $posted['billing_email'];
$user = get_user_by( 'email', $checkout_email );
if ( ! empty( $user ) ) {
$customer_orders = get_posts( array(
        'numberposts' => -1,
        'meta_key'    => '_customer_user',
        'meta_value'  => $user->ID,
        'post_type'   => 'shop_order', // WC orders post type
        'post_status' => 'wc-pending' // Only orders with status "completed"
) );
foreach ( $customer_orders as $customer_order ) {
        $count++;
}
if ( $count > 0 ) {
   wc_add_notice( 'Sorry gelieve eerst uw openstaande bestellingen te betalen door in te loggen op uw dashboard.', 'error');
}
}
}
//Hide Hidden Products From Cart, Order, Emails//
add_filter( 'woocommerce_cart_item_visible', 'hide_hidden_product_from_cart' , 10, 3 );
add_filter( 'woocommerce_widget_cart_item_visible', 'hide_hidden_product_from_cart', 10, 3 );
add_filter( 'woocommerce_checkout_cart_item_visible', 'hide_hidden_product_from_cart', 10, 3 );
add_filter( 'woocommerce_order_item_visible', 'hide_hidden_product_from_order_woo333', 10, 2 );
function hide_hidden_product_from_cart( $visible, $cart_item, $cart_item_key ) {
    $product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
    if ( $product->get_catalog_visibility() == 'hidden' ) {
        $visible = false;
    }
    return $visible;
}
function hide_hidden_product_from_order_woo333( $visible, $order_item ) {
    $product = $order_item->get_product();
    if ( $product->get_catalog_visibility() == 'hidden' ) {
        $visible = false;
    }
    return $visible;
}
//Remove Link to Product @ Order Table//
add_filter( 'woocommerce_order_item_permalink', '__return_false' );
//Display Regular & Sale Price @ Cart Table//
add_filter( 'woocommerce_cart_item_price', 'change_cart_table_price_display', 30, 3 );
function change_cart_table_price_display( $price, $values, $cart_item_key ) {
   $slashed_price = $values['data']->get_price_html();
   $is_on_sale = $values['data']->is_on_sale();
   if ( $is_on_sale ) {
      $price = $slashed_price;
   }
   return $price;
}
//Display Total Discount / Savings @ Cart//
function wc_discount_total_30() {
    global $woocommerce;
    $discount_total = 0;
    foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $values) {
   $_product = $values['data'];
        if ( $_product->is_on_sale() ) {
        $regular_price = $_product->get_regular_price();
        $sale_price = $_product->get_sale_price();
        $discount = ($regular_price - $sale_price) * $values['quantity'];
        $discount_total += $discount;
        }
    }
    if ( $discount_total > 0 ) {
    echo '<tr class="cart-discount">
    <th>'. __( 'Je spaarde', 'woocommerce' ) .'</th>
    <td data-title=" '. __( 'Je spaarde', 'woocommerce' ) .' ">'
    . wc_price( $discount_total + $woocommerce->cart->discount_cart ) .'</td>
    </tr>';
    }
}
// Hook values to the Basket and Checkout pages
add_action( 'woocommerce_cart_totals_after_order_total', 'wc_discount_total_30', 99);
add_action( 'woocommerce_review_order_after_order_total', 'wc_discount_total_30', 99);
//Show Published Date @ Single Product//
add_action( 'woocommerce_single_product_summary','echo_product_date',25 );
function echo_product_date() {
if ( is_product() ) {
echo the_date('', '<span class="date_published">Gepubliceerd op: ', '</span>', false);
}
}
//Display Stock Quantity/Status @ WooCommerce Shop Page//
add_action( 'woocommerce_after_shop_loop_item', 'show_stock_shop', 10 );
function show_stock_shop() {
   global $product;
   echo wc_get_stock_html( $product );
}
//Hide Coupon Code @ Cart & Checkout Page//
add_filter( 'woocommerce_cart_totals_coupon_label', 'bbloomer_hide_coupon_code', 99, 2 );
function bbloomer_hide_coupon_code( $label, $coupon ) {
    return 'Kortingsbon toegepast!'; 
}
//Remove Shipping Labels @ Cart (e.g. “Flat Rate”)//
add_filter( 'woocommerce_cart_shipping_method_full_label', 'remove_shipping_label', 9999, 2 );
function remove_shipping_label( $label, $method ) {
    $new_label = preg_replace( '/^.+:/', '', $label );
    return $new_label;
}
//Translate “Shipping” @ Cart Totals//
add_filter( 'woocommerce_shipping_package_name', 'bbloomer_new_shipping_title' );
function bbloomer_new_shipping_title() {
   return "Levering";
}
//Add Payment Method to Order Emails//
add_action( 'woocommerce_email_after_order_table', 'display_payment_type_name_emails', 15 );
function display_payment_type_name_emails( $order ) {
   echo '<h2>Betalingsmethode:</h2><p> ' . $order->get_payment_method_title() . '</p>';
}
//Keep expired listings accessible via direct link//
add_action( 'init', function() {
    global $wp_post_statuses;
    if ( ! is_array( $wp_post_statuses ) ) {
        $wp_post_statuses = [];
    }
    if ( ! empty( $wp_post_statuses['expired'] ) ) {
        $wp_post_statuses['expired']->public = true;
    }
}, 99 );
//notify admin on user registration//
add_action( 'woocommerce_created_customer', function( $id ) {
	wp_new_user_notification( $id, null, 'admin' );
} );
//Hide past events//
add_action( 'mylisting/schedule:hourly', function() {
    global $wpdb;
    // Change status to expired.
    $listing_ids = $wpdb->get_col(
        $wpdb->prepare( "
            SELECT postmeta.post_id FROM {$wpdb->postmeta} as postmeta
            LEFT JOIN {$wpdb->posts} as posts ON postmeta.post_id = posts.ID
            WHERE postmeta.meta_key = '_job_date'
            AND postmeta.meta_value > 0
            AND postmeta.meta_value < %s
            AND posts.post_status = 'publish'
            AND posts.post_type = 'job_listing'",
            date( 'Y-m-d', current_time( 'timestamp' ) )
        )
    );
    if ( $listing_ids ) {
        foreach ( $listing_ids as $listing_id ) {
            $data                = [];
            $data['ID']          = $listing_id;
            $data['post_status'] = 'expired';
            wp_update_post( $data );
        }
    }
} );
// WOOCOMMERCE REDIRECT AFTER LOGIN//
function wc_custom_user_redirect( $redirect, $user ) {
	// Get the first of all the roles assigned to the user
	$role = $user->roles[0];
	$dashboard = admin_url();
	$myaccount = get_permalink( wc_get_page_id( 'myaccount' ) );
	if( $role == 'administrator' ) {
		//Redirect administrators to the admin dashboard
		$redirect = $dashboard;
	} elseif ( $role == 'customer_alt' ) {
		//Redirect busines users to my account
		$redirect = $myaccount;
	} else {
		//Redirect any other role to homepage
		$redirect = home_url();
	}
	return $redirect;
}
add_filter( 'woocommerce_login_redirect', 'wc_custom_user_redirect', 10, 2 );
// WOOCOMMERCE REDIRECT AFTER registration//
add_filter('woocommerce_registration_redirect', 'wcs_register_redirect');
function wcs_register_redirect( $redirect ) {
     $redirect = 'https://zoobli.eu/';
     return $redirect;
}
// WOOCOMMERCE REDIRECT AFTER CHECKOUT//
add_action( 'woocommerce_welcome', 'redirectcustom');
function redirectcustom( $order_id ){
    $order = wc_get_order( $order_id );
    $url = 'https://zoobli.eu/';
    if ( ! $order->has_status( 'failed' ) ) {
        wp_safe_redirect( $url );
        exit;
    }
}
//Minimum Comment Length//
add_filter( 'preprocess_comment', 'minimal_comment_length' );
function minimal_comment_length( $commentdata ) {
    $minimalCommentLength = 50;
    if ( strlen( trim( $commentdata['comment_content'] ) ) < $minimalCommentLength ){
    wp_die( 'Alle reacties moeten ten minste ' . $minimalCommentLength . ' tekens lang zijn.' );
    }
    return $commentdata;
}
//Hide Related Products @ WooCommerce Single Product Page//
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
//Show Additional Content @ WooCommerce My Account Page//
add_action( 'woocommerce_login_form_start','add_login_text' );
function add_login_text() {
   if ( is_checkout() ) return;
   echo '<h3 class="bb-login-subtitle">Gerigistreerde gebruikers</h3><p class="bb-login-description">Als je al reeds een account hebt op ons platform kan je hieronder inloggen met je gebruikersnaam en wachtwoord of via een sociaal netwerk naar keuze<br><br></p>';
}
add_action( 'woocommerce_register_form_start','add_reg_text' );
function add_reg_text() {
   echo '<h3 class="bb-register-subtitle">Nieuwe gebruikers</h3><p class="bb-register-description">Door een account aan te maken bij ons platform, kunt u uw favorieten opslaan, contact opnemen met andere gebruikers via chat, uw bestellingen bekijken en volgen en meer.<br><br></p>';
}
//Display Custom Product Badge (Conditionally)//
// Add new checkbox to product edit page (General tab)
add_action( 'woocommerce_product_options_general_product_data', 'add_badge_checkbox_to_products' );
function add_badge_checkbox_to_products() {           
woocommerce_wp_checkbox( array( 
'id' => 'custom_badge', 
'class' => '', 
'label' => 'Digitale adoptie?'
) 
);      
}
// Save checkbox via custom field
add_action( 'save_post', 'save_badge_checkbox_to_post_meta' );
function save_badge_checkbox_to_post_meta( $product_id ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
        return;
    if ( isset( $_POST['custom_badge'] ) ) {
            update_post_meta( $product_id, 'custom_badge', $_POST['custom_badge'] );
    } else delete_post_meta( $product_id, 'custom_badge' );
}
// Display badge @ single product page if checkbox checked
add_action( 'woocommerce_single_product_summary', 'display_badge_if_checkbox', 6 );
function display_badge_if_checkbox() {
    global $product;     
    if ( get_post_meta( $product->get_id(), 'custom_badge', true ) ) {
        echo '
<div class="woocommerce-message">Digitale adoptie!</div>
';
    }
}
//Add Next/Previous @ Single Product Page//
add_action( 'woocommerce_before_single_product', 'prev_next_product' );
function prev_next_product(){
echo '<div class="prev_next_buttons">';
   // 'product_cat' will make sure to return next/prev from current category
        $previous = next_post_link('%link', '&larr; Vorige', TRUE, ' ', 'product_cat');
   $next = previous_post_link('%link', 'Volgende &rarr;', TRUE, ' ', 'product_cat');
   echo $previous;
   echo $next;
echo '</div>';
}
//Change Number of Products Per Page//
add_filter( 'loop_shop_per_page', 'redefine_products_per_page', 9999 );
function redefine_products_per_page( $per_page ) {
  $per_page = 8;
  return $per_page;
}
//Create Custom WooCommerce Product Tabs with Advanced Custom Fields//
if (class_exists('acf')) {
	add_action('acf/init', function() {
		$fields = [
			[
				'key' => 'field_custom_tabs_repeater',
				'label' => __('Aangepaste tabs', 'txtdomain'),
				'name' => 'custom_tabs_repeater',
				'type' => 'repeater',
				'layout' => 'row',
				'button_label' => __('Voeg een nieuwe tab toe', 'txtdomain'),
				'sub_fields' => [
					[
						'key' => 'field_tab_title',
						'label' => __('Tab title', 'txtdomain'),
						'name' => 'tab_title',
						'type' => 'text',
					],
					[
						'key' => 'field_tab_contents',
						'label' => __('Inhoud tab', 'txtdomain'),
						'name' => 'tab_contents',
						'type' => 'wysiwyg',
						'tabs' => 'all',
						'toolbar' => 'full',
						'media_upload' => 1,
						'delay' => 0,
					],
				],
			],
		];
 
		acf_add_local_field_group([
			'key' => 'group_custom_woocommerce_tabs',
			'title' => __('Custom Tabs', 'txtdomain'),
			'fields' => $fields,
			'label_placement' => 'top',
			'menu_order' => 0,
			'style' => 'default',
			'position' => 'normal',
			'location' => [
				[
					[
						'param' => 'post_type',
						'operator' => '==',
						'value' => 'product'
					]
				]
			],
		]);
	});
}
//Add CF7 to the Single Product Page//
add_action( 'woocommerce_single_product_summary', 'woocommerce_cf7_single_product', 30 );
function woocommerce_cf7_single_product() {
echo '<button type="submit" id="trigger_cf" class="single_add_to_cart_button button alt">Product vraag</button>';
echo '<div id="product_inq" style="display:none">';
echo do_shortcode('[contact-form-7 id="2989" title="Vraag product"]');
echo '</div>';
}
// 2. Echo Javascript: 
// a) on click, display CF7
// b) and populate CF7 subject with Product Name
// c) and change CF7 button to "Close"
add_action( 'woocommerce_single_product_summary', 'on_click_show_cf7_and_populate', 40 );
function on_click_show_cf7_and_populate() {
  ?>
    <script type="text/javascript">
        jQuery('#trigger_cf').on('click', function(){
      if ( jQuery(this).text() == 'Product vraag' ) {
                   jQuery('#product_inq').css("display","block");
                   jQuery('input[name="your-subject"]').val('<?php the_title(); ?>');
         jQuery("#trigger_cf").html('Sluiten'); 
      } else {
         jQuery('#product_inq').hide();
         jQuery("#trigger_cf").html('Product vraag'); 
      }
        });
    </script>
   <?php   
}
//Truncate Short Description With “Read More” Toggle//
add_action( 'woocommerce_after_single_product', 'woocommerce_short_description_truncate_read_more' );
function woocommerce_short_description_truncate_read_more() { 
   wc_enqueue_js('
      var show_char = 25;
      var ellipses = "... ";
      var content = $(".woocommerce-product-details__short-description").html();
      if (content.length > show_char) {
         var a = content.substr(0, show_char);
         var b = content.substr(show_char - content.length);
         var html = a + "<span class=\'truncated\'>" + ellipses + "<a class=\'read-more\'>Lees meer</a></span><span class=\'truncated\' style=\'display:none\'>" + b + "</span>";
         $(".woocommerce-product-details__short-description").html(html);
      }
      $(".read-more").click(function(e) {
         e.preventDefault();
         $(".woocommerce-product-details__short-description .truncated").toggle();
      });
   ');
}
//Create a New Product Type//
// #1 Add New Product Type to Select Dropdown
add_filter( 'product_type_selector', 'add_custom_product_type' );
function add_custom_product_type( $types ){
    $types[ 'custom' ] = 'Advertentie';
    return $types;
}
// #2 Add New Product Type Class
add_action( 'init', 'create_custom_product_type' );
function create_custom_product_type(){
    class WC_Product_Custom extends WC_Product {
      public function get_type() {
         return 'custom';
      }
    }
}
// #3 Load New Product Type Class
add_filter( 'woocommerce_product_class', 'woocommerce_product_class', 10, 2 );
function woocommerce_product_class( $classname, $product_type ) {
    if ( $product_type == 'custom' ) {
        $classname = 'WC_Product_Custom';
    }
    return $classname;
}
//“Is This a Gift?” Checkbox @ Single Product Page//
// 1. Display Is this a Gift checkbox 
add_action( 'woocommerce_before_add_to_cart_quantity', 'is_this_gift_add_cart', 35 );
function is_this_gift_add_cart() {     
      ?>
      <p class="">
      <label class="checkbox">
      <input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" name="is-gift" id="is-gift" value="Yes"><span>Is dit een geschenk?</span>
      </label>
      </p>
 
      <?php
}
// 2. Add the custom field to $cart_item
add_filter( 'woocommerce_add_cart_item_data', 'store_gift', 10, 2 );
function store_gift( $cart_item, $product_id ) {
if( isset( $_POST['is-gift'] ) && $_POST['is-gift'] == "Yes" ) {
   $cart_item['is-gift'] = $_POST['is-gift'];
}
return $cart_item; 
}
// 3. Preserve the custom field in the session
add_filter( 'woocommerce_get_cart_item_from_session', 'get_cart_items_from_session', 10, 2 );
function get_cart_items_from_session( $cart_item, $values ) {
if ( isset( $values['is-gift'] ) ){
   $cart_item['is-gift'] = $values['is-gift'];
}
return $cart_item;
}
// 4. If gift in cart, edit checkout behavior:
// a) open shipping by default
// b) rename shipping title
add_action ( 'woocommerce_before_checkout_form', 'gift_checkout' );
function gift_checkout() {
   $itsagift = false;
    
   foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
      if ( isset( $cart_item['is-gift'] ) ) {
         $itsagift = true;
         break;
      }
   } 
   if ( $itsagift == true ) {
      add_filter( 'woocommerce_ship_to_different_address_checked', '__return_true' );
      add_filter( 'gettext', 'translate_shipping_gift' );
   }  
}
function translate_shipping_gift( $translated ) {
$translated = str_ireplace( 'Ship to a different address?', 'Voor wie is dit geschenk?', $translated );
return $translated;
}
