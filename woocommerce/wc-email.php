<?php
/**
 * Add To: Recipient @ WooCommerce Completed Order Email
 */ 
add_filter( 'woocommerce_email_recipient_customer_completed_order', 'bbloomer_order_completed_email_add_to', 9999, 3 );
function bbloomer_order_completed_email_add_to( $email_recipient, $email_object, $email ) {
   $email_recipient .= ', your@email.com';
   return $email_recipient;
}

/**
 * Add Cc: or Bcc: Recipient @ WooCommerce Completed Order Email
 */
add_filter( 'woocommerce_email_headers', 'bbloomer_order_completed_email_add_cc_bcc', 9999, 3 );
function bbloomer_order_completed_email_add_cc_bcc( $headers, $email_id, $order ) {
  if ( 'customer_completed_order' == $email_id ) {
    $headers .= "Cc: Name <your@email.com>" . "\r\n"; // del if not needed
    $headers .= "Bcc: Name <your@email.com>" . "\r\n"; // del if not needed
  }
  return $headers;
}

