<?php
  if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
  }
global $wpdb, $wpsupportplus, $current_user;

$ticket_id  = isset($_POST['ticket_id']) ? intval(sanitize_text_field($_POST['ticket_id'])) : 0 ;
$nonce      = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '' ;

$ticket = $wpdb->get_row( "select * from {$wpdb->prefix}wpsp_ticket where id=".$ticket_id );

/**
 * Check nonce
 */
  // if( !wp_verify_nonce( $nonce, $ticket_id ) || !$wpsupportplus->functions->cu_has_cap_ticket( $ticket, 'change_status' ) ){
  //  die(__('Cheating huh?', 'wp-support-plus-responsive-ticket-system-emancipatic'));
  // }

$status_id      = isset($_POST['status']) ? intval(sanitize_text_field($_POST['status'])) : 0 ;

// if( !$status_id || !$cat_id || !$priority_id ){
//    die('Either one or all of status, category and priority not set ');
// }

include_once WPSP_ABSPATH . 'template/tickets/class-ticket-operations.php';

$ticket_oprations = new WPSP_Ticket_Operations();

$ticket_oprations->change_status( $status_id, $ticket_id );

do_action('wpsp_after_change_ticket_status',$ticket_id);