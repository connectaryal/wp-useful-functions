<?php
/*
 * Display add to cart option if the price is empty
 */
add_filter('woocommerce_is_purchasable', '__return_TRUE'); 

// Show only free shipping option if available
add_filter( 'woocommerce_package_rates', 'remove_free_shipping_method', 100, 2 );
function remove_free_shipping_method(){
	$terms = array( 'matrix-range-workstations', 'matrix-range-desks', 'matrix-range-chairs' );

	if( has_term( 'matrix-range-products', 'product_cat', $cart_item['product_id'] ) ) {
     	unset($rates['free_shipping:42']);
    }
}


// Custom conditional function that checks for parent product categories
function has_parent_term( $product_id ) {
    // HERE set your targeted product category SLUG
    $category_slug = 'matrix-range-products';

    // Convert category term slug to term id
    $category_id   = get_term_by('slug', $category_slug, 'product_cat')->term_id;
    $parent_term_ids = array(); // Initializing

    // Loop through the current product category terms to get only parent main category term
    foreach( get_the_terms( $product_id, 'product_cat' ) as $term ){
        if( $term->parent > 0 ){
            $parent_term_ids[] = $term->parent; // Set the parent product category
        } else {
            $parent_term_ids[] = $term->term_id;
        }
    }
    return in_array( $category_id, $parent_term_ids );
}

// Avoid add to cart others product categories when "matrix-range-products" is in cart
add_filter( 'woocommerce_add_to_cart_validation', 'specific_category_avoid_add_to_cart_others', 20, 3 );
function specific_category_avoid_add_to_cart_others( $passed, $product_id, $quantity) {
    if( WC()->cart->is_empty() || has_parent_term( $product_id ) ) {
        return $passed;
    }

    foreach( WC()->cart->get_cart() as $cart_item ){
        if( has_parent_term( $cart_item['product_id'] ) ) {
            wc_add_notice( __('Matrix range products cannot be purchased in combination with any other products ', 'woocommerce' ), 'error' ); // Display a custom error notice
            return false; // Avoid add to cart
        }
    }
    return $passed;
}

// Hide shipping method based on shipping classes
add_filter( 'woocommerce_package_rates', 'hide_shipping_method_based_on_shipping_class', 10, 2 );
function hide_shipping_method_based_on_shipping_class( $rates, $package )
{
    if ( is_admin() && ! defined( 'DOING_AJAX' ) )
        return;

    // HERE define your shipping class SLUG
    $class_slug = 'free-shipping';

    // HERE define the shipping method to hide
    $method_key_id = 'free_shipping:42';

    // Checking in cart items
    foreach( WC()->cart->get_cart() as $cart_item ){
        // If we find the shipping class
        if( $cart_item['data']->get_shipping_class() !== $class_slug ){
            	unset($rates[$method_key_id]); // Remove the targeted method
                   }
    }
    return $rates;
}

// Shipping Method only for specific Products
add_filter( 'woocommerce_package_rates', 'specific_products_shipping_methods', 10, 2 );
function specific_products_shipping_methods( $rates, $package ) {

    $product_ids = array( 20221 ); // HERE set the product IDs in the array
    $method_id = 'free_shipping:46'; // HERE set the shipping method ID
    $found = false;

    // Loop through cart items Checking for defined product IDs
    foreach( $package['contents'] as $cart_item ) {
        if ( in_array( $cart_item['product_id'], $product_ids ) ){
            $found = true;
            break;
        }
    }
    if ( $found )
        unset( $rates[$method_id] );

    return $rates;
}


// Custom conditional function that checks for parent product categories
add_filter( 'woocommerce_package_rates', 'hide_shipping_for_free_shipping', 10, 2 );
function hide_shipping_for_free_shipping( $rates, $package ) {
	$methods = array(
        'free_shipping:45',
        'free_shipping:42',
        'free_shipping:46',
        'free_shipping:56'
    );
	if( has_term( 'matrix-range-products', 'product_cat', $cart_item['product_id'] ) ) {

    // } else {
		foreach ($methods as $current_method) {
			unset( $rates[ $current_method ] );
		}
    }
    return $rates;
}

// Hide Shipping method based on shipping classes
add_filter('woocommerce_package_rates', 'wf_hide_shipping_method_based_on_shipping_class', 10, 2);
function wf_hide_shipping_method_based_on_shipping_class($available_shipping_methods, $package){
    $hide_when_shipping_class_exist = array(
        2809 => array(
            'flat_rate:9',
            'free_shipping:45',
        	'free_shipping:42',
        	'free_shipping:46',
        	'free_shipping:56'
        )
    );
    
    $hide_when_shipping_class_not_exist = array(
        2809 => array(
            'flat_rate:9',	
            'free_shipping:45',
        	'free_shipping:42',
        	'free_shipping:46',
        	'free_shipping:56'
        )
    );
    
    
    $shipping_class_in_cart = array();
    foreach(WC()->cart->get_cart_contents() as $key => $values) {
       $shipping_class_in_cart[] = $values['data']->get_shipping_class_id();
    }

    foreach($hide_when_shipping_class_exist as $class_id => $methods) {
        if(in_array($class_id, $shipping_class_in_cart)){
            foreach($methods as & $current_method) {
                unset($available_shipping_methods[$current_method]);
            }
        }
    }
    foreach($hide_when_shipping_class_not_exist as $class_id => $methods) {
        if(!in_array($class_id, $shipping_class_in_cart)){
            foreach($methods as & $current_method) {
                unset($available_shipping_methods[$current_method]);
            }
        }
    }
    return $available_shipping_methods;
}


// Offer when user buy 5 same items on the price of 4( get 1 free )
add_action( 'woocommerce_cart_calculate_fees', 'custom_discount', 10, 1 );
function custom_discount( $cart ){
  global $woocommerce;
  $targeted_id = 37771;  
  foreach ( WC()->cart->get_cart() as $cart_item ) { 
    if($cart_item['product_id'] == $targeted_id ){
      $qty =  $cart_item['quantity'];
      $price = get_post_meta($cart_item['product_id'] , '_price', true);
      break; // stop the loop if product is found
    }
  }  
  
  $free_items = intval($qty / 5);
  if( $qty >= 5 ){
    if ( is_admin() && ! defined( 'DOING_AJAX' ) )
      return;
    
    // Only for 2 items or more
    $discount = $price * $free_items;
    
    // Apply discount to 2nd item for non on sale items in cart
    if( $discount > 0 ) $cart->add_fee( sprintf( "Free %s Item", $free_items ), -$discount );
  }
}

/**
 * Dispatch Date @ WooCommerce Single Product
 */
add_action( 'woocommerce_after_add_to_cart_form', 'bbloomer_dispatch_info_single_product' );
function bbloomer_dispatch_info_single_product() {
  date_default_timezone_set( 'Europe/London' );    
  // if FRI/SAT/SUN delivery will be MON
  if ( date( 'N' ) >= 5 ) {
    $del_day = date( "l jS F", strtotime( "next monday" ) );
    $order_by = "Monday";
  }elseif ( date( 'H' ) >= 16 ) {
    // if MON/THU after 4PM delivery will be TOMORROW
    $del_day = date( "l jS F", strtotime( "tomorrow" ) );
    $order_by = "tomorrow";
  }else {
    // if MON/THU before 4PM delivery will be TODAY
    $del_day = date( "l jS F", strtotime( "today" ) );
    $order_by = "today";
  }
  $html = "<br><div class='woocommerce-message' style='clear:both'>Order by 4PM {$order_by} for delivery on {$del_day}</div>";
  echo $html;
}

/**
 * Add Prefix to Category @ WooCommerce Breadcrumb
 */
add_filter( 'woocommerce_get_breadcrumb', 'bbloomer_single_product_edit_cat_breadcrumbs', 9999, 2 );
function bbloomer_single_product_edit_cat_breadcrumbs( $crumbs, $breadcrumb ) {
  if ( is_product() ) {
    $index = count( $crumbs ) - 2; // cat is always second last item
    $value = $crumbs[$index];
    $crumbs[$index][0] = 'Category: ' . $crumbs[$index][0];
  }
  return $crumbs;
}

/**
 * Swap Product with SKU @ WooCommerce Breadcrumb
 */
add_filter( 'woocommerce_get_breadcrumb', 'bbloomer_single_product_edit_prod_name_breadcrumbs', 9999, 2 );
function bbloomer_single_product_edit_prod_name_breadcrumbs( $crumbs, $breadcrumb ) {
  if ( is_product() ) {
    global $product;
    $index = count( $crumbs ) - 1; // product name is always last item
    $value = $crumbs[$index];
    $crumbs[$index][0] = $product->get_sku();
  }
  return $crumbs;
}

/**
 * WooCommerce Product Reviews Shortcode
 */
add_shortcode( 'product_reviews', 'bbloomer_product_reviews_shortcode' );
function bbloomer_product_reviews_shortcode( $atts ) {
  if ( empty( $atts ) ) return '';
  if ( ! isset( $atts['id'] ) ) return '';
  $comments = get_comments( 'post_id=' . $atts['id'] );
  if ( ! $comments ) return '';
  $html .= '<div class="woocommerce-tabs"><div id="reviews"><ol class="commentlist">';
  foreach ( $comments as $comment ) { 
    $rating = intval( get_comment_meta( $comment->comment_ID, 'rating', true ) );
    $html .= '<li class="review">';
    $html .= get_avatar( $comment, '60' );
    $html .= '<div class="comment-text">';
    if ( $rating ) $html .= wc_get_rating_html( $rating );
    $html .= '<p class="meta"><strong class="woocommerce-review__author">';
    $html .= get_comment_author( $comment );
    $html .= '</strong></p>';
    $html .= '<div class="description">';
    $html .= $comment->comment_content;
    $html .= '</div></div>';
    $html .= '</li>';
  }
  $html .= '</ol></div></div>';
  return $html;
}

/**
 * Add Download @ My Account Page
 */
add_filter( 'woocommerce_customer_get_downloadable_products', 'bbloomer_add_custom_default_download', 9999, 1 );
function bbloomer_add_custom_default_download( $downloads ) {
  $downloads[] = array(
    'product_name' => 'Description',
    'download_name' => 'Button Label',
    'download_url' => '/wp-content/uploads/filename.txt',
  );
  return $downloads;
}

/**
 * Include WP Page @ WC Thank You
 */
add_action( 'woocommerce_thankyou', 'bbloomer_custom_thank_you_page', 5 );
function bbloomer_custom_thank_you_page() {
  $page_id = 540;
  $page_object = get_post( $page_id );
  echo $page_object->post_content;
}

/**
 * Programmatically Complete Paid WooCommerce Orders
 */
add_filter( 'woocommerce_payment_complete_order_status', 'bbloomer_autocomplete_processing_orders', 9999 );
function bbloomer_autocomplete_processing_orders() {
  return 'completed';
}

/**
 * Set Min Purchase Amount | WooCommerce Single Product
 */
// 1. Single Product Page
add_filter( 'woocommerce_quantity_input_min', 'bloomer_woocommerce_quantity_min_50_eur', 9999, 2 );
function bloomer_woocommerce_quantity_min_50_eur( $min, $product ) {  
  if ( is_product() ) {
    if ( 123 === $product->get_id() ) {
      $min = ceil( 50 / $product->get_price() );
    }
  }  
  return $min;
}
 
// 2. Cart Page
add_filter( 'woocommerce_cart_item_quantity', 'bloomer_woocommerce_quantity_min_50_eur_cart', 9999, 3 ); 
function bloomer_woocommerce_quantity_min_50_eur_cart( $product_quantity, $cart_item_key, $cart_item ) {  
  $_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );  
  $min = 0;
  if ( 123 === $product->get_id() ) {
    $min = ceil( 50 / $_product->get_price() );
  }  
  $product_quantity = woocommerce_quantity_input( array(
    'input_name'   => "cart[{$cart_item_key}][qty]",
    'input_value'  => $cart_item['quantity'],
    'max_value'    => $_product->get_max_purchase_quantity(),
    'min_value'    => $min,
    'product_name' => $_product->get_name(),
  ), $_product, false );  
  return $product_quantity;
}

/**
 * Change “Continue Shopping” Redirect
 */
add_filter( 'woocommerce_continue_shopping_redirect', 'bbloomer_change_continue_shopping' );
function bbloomer_change_continue_shopping() {
  return wc_get_page_permalink( 'shop' );
}