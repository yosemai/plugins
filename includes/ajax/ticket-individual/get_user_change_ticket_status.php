<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

global $wpdb, $wpsupportplus, $current_user;

$ticket_id  = isset($_POST['ticket_id']) ? intval(sanitize_text_field($_POST['ticket_id'])) : 0 ;
$nonce      = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '' ;

/**
 * Check nonce
 */
if( !wp_verify_nonce( $nonce, $ticket_id ) ){
    die(__('Cheating huh?', 'wp-support-plus-responsive-ticket-system-emancipatic'));
}

$ticket = $wpdb->get_row( "select * from {$wpdb->prefix}wpsp_ticket where id=".$ticket_id );

$modal_title = __('Change Ticket Status','wp-support-plus-responsive-ticket-system-emancipatic');

ob_start();

?>

    <form id="frm_change_ticket_status">
        <div class="row">
            <div class="col-md-12">
                <strong><?php _e('Status','wp-support-plus-responsive-ticket-system-emancipatic');?>:</strong>
            </div>
            <div class="col-md-12">
                <select class="form-control" name="status">
                    <?php
                    // foreach ( $wpsupportplus->functions->get_wpsp_statuses() as $status ) :

                       // $selected = $ticket->status_id == $status->id ? 'selected="selected"' : '';
                       // echo '<option '.$selected.' value="'.$status->id.'">'.$status->name.'</option>';

                    // endforeach;
                    
                    echo '<option selected="selected" value="3">Cerrado</option>';
                    
                    ?>
                </select>
            </div>
        </div>

        <input type="hidden" name="action" value="wpsp_set_user_change_ticket_status" />
        <input type="hidden" name="ticket_id" value="<?php echo $ticket_id?>" />
        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce($ticket_id)?>" />

    </form>

<?php

$modal_body = ob_get_clean();

ob_start();

?>

    <div class="row">
        <div class="col-md-12" style="text-align: right;">
            <button type="button" class="btn btn-default" onclick="wpsp_ajax_modal_cancel();"><?php _e('Cancel','wp-support-plus-responsive-ticket-system-emancipatic');?></button>
            <button type="button" class="btn btn-primary" onclick="wpsp_set_user_change_ticket_status();"><?php _e('Save Changes','wp-support-plus-responsive-ticket-system-emancipatic');?></button>
        </div>
    </div>

<?php

$modal_footer = ob_get_clean();

$response = array(
    'title'     => $modal_title,
    'body'      => $modal_body,
    'footer'    => $modal_footer
);

echo json_encode($response);
