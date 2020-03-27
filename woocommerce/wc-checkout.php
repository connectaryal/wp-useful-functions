<?php 
/**
 * Conditionally Hide / Show a Checkout Field Based on Whether Another Field is Empty
 */
add_action( 'woocommerce_after_checkout_form', 'bbloomer_conditionally_hide_show_checkout_field', 9999 );
function bbloomer_conditionally_hide_show_checkout_field() {
  wc_enqueue_js( "
    jQuery('#billing_company').keyup(function() {
      if (jQuery(this).val().length == 0) {
        jQuery('#order_comments_field').hide();
      } else {
        jQuery('#order_comments_field').show();
      }
    }).keyup();"
  );
}

/**
 * Allow Customer to “Pay for Order” if Logged Out (WooCommerce Checkout)
 */
add_filter( 'user_has_cap', 'bbloomer_order_pay_without_login', 9999, 3 );
function bbloomer_order_pay_without_login( $allcaps, $caps, $args ) {
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