<?php
/**
 * Remove a Sidebar Widget if @ WooCommerce Cart Page
 */ 
add_filter( 'sidebars_widgets', 'bbloomer_woocommerce_conditionally_hide_widget' );
function bbloomer_woocommerce_conditionally_hide_widget( $sidebars_widgets ) {
  if( ! is_admin() ) {
    if ( is_cart() ) {
      $key = array_search( 'woocommerce_products-2', $sidebars_widgets['sidebar-1'] );
      if( $key ) {
        unset( $sidebars_widgets['sidebar-1'][$key] );
      }
    }
  }
  return $sidebars_widgets;
}

/**
 * Split Cart by A>Z (Display Letter Above Each Section)
 * Note: you also need to use https://businessbloomer.com/woocommerce-sort-cart-items-alphabetically-az/ in 
 * order to sort your cart alphabetically first.
 */
add_action( 'wp_footer', 'bbloomer_split_cart_by_az', 9999 );
function bbloomer_split_cart_by_az(){
  if ( ! is_cart() ) return; 
  if ( WC()->cart->is_empty() ) return;
  $i = 0;
  $split = array();
  foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
    $cart_item_title = $cart_item['data']->get_title();
    $first_letter = substr( $cart_item_title, 0, 1 );
    if ( 0 == $i || ( 0 < $i && ! in_array( $first_letter, $split ) ) ) {
      $split[$i] = $first_letter;
    }
    $i++;
  } ?>
  <script type="text/javascript">
    jQuery(document).ready(function($){
      var indx = $('.woocommerce-cart-form__contents tbody tr').length;
      var rows = <?php echo json_encode($split); ?>;
      $.each(rows,function(key,value){   
        var newRow = $('<tr><td colspan="6">'+value+'</td></tr>');
        newRow.insertBefore($('.woocommerce-cart-form__contents tbody tr.woocommerce-cart-form__cart-item:nth('+key+')'));
      });
    });
  </script><?php  
}

/**
 * Sort Products Alphabetically @ WooCommerce Cart
 */
add_action( 'woocommerce_cart_loaded_from_session', 'bbloomer_sort_cart_items_alphabetically' );
function bbloomer_sort_cart_items_alphabetically() {
	// READ CART ITEMS
	$products_in_cart = array();
	foreach ( WC()->cart->get_cart_contents() as $key => $item ) {
		$products_in_cart[ $key ] = $item['data']->get_title();
	}
	// SORT CART ITEMS
	natsort( $products_in_cart );
	// ASSIGN SORTED ITEMS TO CART
	$cart_contents = array();
	foreach ( $products_in_cart as $cart_key => $product_title ) {
		$cart_contents[ $cart_key ] = WC()->cart->cart_contents[ $cart_key ];
	}
	WC()->cart->cart_contents = $cart_contents;
}


/**
 * Remove Item from Cart Automatically - In the example below, I’m targeting product ID = 282 – the snippet looks for its “cart item key” and uses remove_cart_item() function to remove it.
 */ 
add_action( 'template_redirect', 'bbloomer_remove_product_from_cart_programmatically' );
function bbloomer_remove_product_from_cart_programmatically() {
  if ( is_admin() ) return;
  $product_id = 282;
  $product_cart_id = WC()->cart->generate_cart_id( $product_id );
  $cart_item_key = WC()->cart->find_product_in_cart( $product_cart_id );
  if ( $cart_item_key ) WC()->cart->remove_cart_item( $cart_item_key );
}

/**
 * @snippet       Product Sales by ID - WooCommerce Shortcode
 * Display Product ID Total Sales Anywhere (Shortcode)
 * [sales id=”123″]
 */
add_shortcode( 'sales', 'bbloomer_sales_by_product_id' );
function bbloomer_sales_by_product_id( $atts ) {     
  $atts = shortcode_atts( array(
    'id' => ''
  ), $atts );  
  $units_sold = get_post_meta( $atts['id'], 'total_sales', true );
  return $units_sold;   
}