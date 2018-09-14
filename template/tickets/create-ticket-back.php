<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if(!isset($_REQUEST['action'])) :

    global $wpsupportplus, $current_user, $wpdb;

    $user_id = $current_user->ID;
    if( $user_id ){
        $user = get_userdata($user_id);
        $user_name  = $user->display_name;
        $user_email = $user->user_email;
    }

    $nonce = wp_create_nonce();

    $form_fields = $wpdb->get_results("select * from {$wpdb->prefix}wpsp_ticket_form_order ORDER BY load_order");

    include_once WPSP_ABSPATH . 'template/tickets/class-ticket-form.php';
    
    $mostrar_formulario = false;
	
   
	    $ticket_form = new WPSP_Create_Ticket_Form();
	   ?>
       
      <div class="container">
  
          <h2 class="page-header"><?php _e('Create New Ticket', 'wp-support-plus-responsive-ticket-system-emancipatic')?></h2>
  
          <?php do_action( 'wpsp_before_create_ticket' );?>
  
          <form id="frm_create_ticket" method="post" onsubmit="return validate_user_create_ticket();">
  
              <?php
              /**
               * Create ticket for other user. Available to agent only
               */
              if( $current_user->has_cap('wpsp_agent') || $current_user->has_cap('wpsp_supervisor') || $current_user->has_cap('wpsp_administrator') ):
  
                  $default_create_ticket_as_type = apply_filters( 'wpsp_default_create_ticket_as_type', 1 );
                  if(!$default_create_ticket_as_type){
                      $user_id = 0;
                  }
                  ?>
                  <div class="form-group col-md-4">
                      <label class="label label-default"><?php _e('Create Ticket As', 'wp-support-plus-responsive-ticket-system-emancipatic')?></label><br>
                      <select class="form-control" id="create_ticket_as" name="create_ticket_as" onchange="change_create_ticket_as_type(this,<?php echo $current_user->ID?>,'<?php echo $current_user->display_name?>')">
                          <option <?php echo ($default_create_ticket_as_type==1)?'selected="selected"' : ''?> value="1"><?php _e('Registered User', 'wp-support-plus-responsive-ticket-system-emancipatic')?></option>
                          <option <?php echo ($default_create_ticket_as_type==0)?'selected="selected"' : ''?> value="0"><?php _e('Guest', 'wp-support-plus-responsive-ticket-system-emancipatic')?></option>
                      </select>
                  </div>
                  <div class="form-group regi-field col-md-8" style="<?php echo !($default_create_ticket_as_type)?'display:none;':''?>">
                      <label class="label label-default"><?php _e('Choose User', 'wp-support-plus-responsive-ticket-system-emancipatic')?></label><br>
                      <input id="regi_user_autocomplete" type="text" class="form-control" value="<?php echo $current_user->display_name?>" autocomplete="off" placeholder="<?php _e('Search user ...', 'wp-support-plus-responsive-ticket-system-emancipatic')?>" />
                  </div>
                  <div data-field ="text" id="guest_name" class="form-group guest-field col-md-4 <?php echo (!$default_create_ticket_as_type)?'wpsp_require':''?>" style="<?php echo ($default_create_ticket_as_type)?'display:none;':''?>">
                      <label class="label label-default"><?php _e('Guest Name', 'wp-support-plus-responsive-ticket-system-emancipatic')?></label>  <span class="fa fa-snowflake-o"></span><br>
                      <input type="text" class="form-control" name="guest_name"/>
                  </div>
                  <div data-field ="email" id="guest_email" class="form-group guest-field col-md-4 <?php echo (!$default_create_ticket_as_type)?'wpsp_require':''?>" style="<?php echo ($default_create_ticket_as_type)?'display:none;':''?>">
                      <label class="label label-default"><?php _e('Guest Email', 'wp-support-plus-responsive-ticket-system-emancipatic')?></label>  <span class="fa fa-snowflake-o"></span><br>
                      <input type="text" class="form-control" name="guest_email"/>
                  </div>
  
                  <?php
  
              endif;
  
              /**
               * Start actual ticket form
               */
              foreach( $form_fields as $field ){
  
                  if($field->status){
                      $ticket_form->print_field($field);
                  }
              }
  
              ?>
  
              <input type="hidden" id="user_id" name="user_id" value="<?php echo $user_id?>" />
              <input type="hidden" name="agent_created" value="<?php echo $current_user->ID?>" />
              <input type="hidden" id="wpsp_nonce" name="nonce" value="<?php echo wp_create_nonce()?>" />
              <input type="hidden" name="action" value="create_ticket" />
              <input type="file" id="image_upload" class="hidden" onchange="">
              <input type="file" id="attachment_upload" class="hidden" onchange="">
  
              <div class="form-group col-md-12 wpsp_form_btn_bottom">
  
                  <div class="form-group col-md-2 inner_control">
                      <button type="submit" class="btn btn-success form-control"><?php _e('Submit Ticket', 'wp-support-plus-responsive-ticket-system-emancipatic')?></button>
                  </div>
  
                  <div class="form-group col-md-2 inner_control">
                      <button type="button" class="btn btn-default form-control" onclick="reset_create_ticket();"><?php _e('Reset Form', 'wp-support-plus-responsive-ticket-system-emancipatic')?></button>
                  </div>
  
              </div>
  
          </form>
  
      </div>
    <?php endif; ?>
    
<?php

endif;
?><div id="wpsp-admin-popup-wait-load-thank" style="display:none; text-align: center;">
		<img src="<?php echo WPSP_PLUGIN_URL.'asset/images/ajax-loader@2x.gif'?>" />
</div>
<?php
if( isset($_REQUEST['action'])  && $_REQUEST['action'] == 'create_ticket' ) :

    global $wpdb, $current_user, $wpsupportplus;

    $wpsp_user_session = $wpsupportplus->functions->get_current_user_session();

    $user_id            = isset($_POST['user_id']) ? sanitize_text_field($_POST['user_id']) : 0;
    $guest_name         = isset($_POST['guest_name']) ? sanitize_text_field($_POST['guest_name']) : '';
    $guest_email        = isset($_POST['guest_email']) ? sanitize_text_field($_POST['guest_email']) : '';
    $agent_created      = isset($_POST['agent_created']) ? intval(sanitize_text_field($_POST['agent_created'])) : 0;
    $subject            = isset($_POST['subject']) ? wp_kses_post($_POST['subject']) : apply_filters( 'wpsp_create_ticket_subject', __('No Subject', 'wp-support-plus-responsive-ticket-system-emancipatic') );
    $description        = isset($_POST['description']) ? wp_kses_post($_POST['description']) : apply_filters( 'wpsp_create_ticket_description', __('No Description', 'wp-support-plus-responsive-ticket-system-emancipatic') );
    $category           = isset($_POST['category']) ? intval(sanitize_text_field($_POST['category'])) : $wpsupportplus->functions->get_default_category();
    $priority           = isset($_POST['priority']) ? intval(sanitize_text_field($_POST['priority'])) : $wpsupportplus->functions->get_default_priority();
    $status             = $wpsupportplus->functions->get_default_status();
    $time               = current_time('mysql', 1);
    $type               = $user_id ? 'user' : 'guest' ;
    $ticket_user        = get_userdata($user_id);

    /**
     * Check nonce
     */
    $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : 0;
    if( !wp_verify_nonce($nonce) ){
        die(__('Cheating huh?', 'wp-support-plus-responsive-ticket-system-emancipatic'));
    }

    /**
     * If agent created is other than current user, don't allow
     */
    if( $current_user->ID != $agent_created ){
        die(__('Cheating huh?', 'wp-support-plus-responsive-ticket-system-emancipatic'));
    }

    /**
     * Apply user name and email to guest
     */
    if( !$guest_email || !$guest_name ){
        $guest_email    = $wpsp_user_session['email'];
        $guest_name     = $wpsp_user_session['name'];
    }

    if( $user_id ){
        $user = get_userdata($user_id);
        $guest_name  = $user->display_name;
        $guest_email = $user->user_email;
    }

    /**
     * If ticket is created by current user, agent created should not come into picture and should be 0
     */
    if( $user_id == $agent_created ){
        $agent_created = 0;
    }

    $values = array(
        'subject'       => htmlspecialchars($subject, ENT_QUOTES),
        'created_by'    => $user_id,
        'updated_by'    => $current_user->ID,
        'guest_name'    => $guest_name,
        'guest_email'   => $guest_email,
        'status_id'     => $status,
        'cat_id'        => $category,
        'priority_id'   => $priority,
        'type'          => $type,
        'agent_created' => $agent_created,
        'create_time'   => $time,
        'update_time'   => $time
    );

    if( !$wpsupportplus->functions->get_ticket_id_sequence() ){

        $id = 0;
        do {
            $id = rand(11111111, 99999999);
            $sql = "select id from {$wpdb->prefix}wpsp_ticket where id=" . $id;
            $result = $wpdb->get_var($sql);
        } while ($result);

        $values['id'] = $id;
    }

    /**
     * Insert custom fields to DB
     */
    $sql = "SELECT f.field_key as id, c.field_type as type, c.field_categories as categories "
            . "FROM {$wpdb->prefix}wpsp_ticket_form_order f "
            . "INNER JOIN {$wpdb->prefix}wpsp_custom_fields c ON f.field_key = c.id "
            . "WHERE f.status = 1 ";
    $form_fields = $wpdb->get_results($sql);
    foreach ( $form_fields as $field ){

        $categories = explode(',', $field->categories);
        if( in_array(0, $categories) || in_array($category, $categories) ){

            if( isset($_POST['cust_'.$field->id]) && is_array($_POST['cust_'.$field->id]) ){

                $save_value = array();

                foreach ( $_POST['cust_'.$field->id] as $key => $val ){
                    $save_value[$key] = sanitize_text_field($val);
                }

                if( $field->type == 8 && $save_value ){

                    foreach ( $save_value as $key => $attachment_id ){

                        $attachment_id = intval(sanitize_text_field($attachment_id));
                        if($attachment_id){
                            $wpdb->update($wpdb->prefix . 'wpsp_attachments', array('active' => 1), array('id' => $attachment_id));
                        } else {
                            unset($save_value[$key]);
                        }
                    }

                }

                if($save_value){
                    $values['cust'.$field->id] = implode('|||', $save_value);
                }

            }

            if( isset($_POST['cust_'.$field->id]) && !is_array($_POST['cust_'.$field->id]) ){

                $save_value = sanitize_text_field($_POST['cust_'.$field->id]);

                if( $field->type == 5 && $save_value ){

                    $save_value = wp_kses_post($_POST['cust_'.$field->id]);

                }

                if( $field->type == 6 && $save_value ){

                    $format = str_replace('dd','d',$wpsupportplus->functions->get_date_format());
                    $format = str_replace('mm','m',$format);
                    $format = str_replace('yy','Y',$format);

                    $date       = date_create_from_format($format, $save_value);
                    $save_value = $date->format('Y-m-d H:i:s');

                }

                $values['cust'.$field->id] = $save_value;

            }
        }
    }

    $values = apply_filters( 'wpsp_create_ticket_values', $values );

    include_once WPSP_ABSPATH . 'template/tickets/class-ticket-operations.php';

    $ticket_oprations = new WPSP_Ticket_Operations();

    $ticket_id = $ticket_oprations->create_new_ticket($values);

    /**
     * Attachments for description
     */
    $attachments = isset($_POST['desc_attachment']) && is_array($_POST['desc_attachment']) ? $_POST['desc_attachment'] : array();
    foreach ($attachments as $key => $attachment_id) {

        $attachment_id = intval(sanitize_text_field($attachment_id));
        if ($attachment_id) {
            $wpdb->update($wpdb->prefix . 'wpsp_attachments', array('active' => 1), array('id' => $attachment_id));
        } else {
            unset($attachments[$key]);
        }
    }
    $attachments = implode(',', $attachments);

    /**
     * Insert thread to DB
     */
		 $signature = get_user_meta($user_id,'wpsp_agent_signature',true);
		 if($signature){
		 	$signature='<br>---<br>' . stripcslashes(htmlspecialchars_decode($signature, ENT_QUOTES));
		 	$description.= $signature;
		 }
		 
    $values = array(
        'ticket_id'         => $ticket_id,
        'body'              => htmlspecialchars($description, ENT_QUOTES),
        'attachment_ids'    => $attachments,
        'create_time'       => $time,
        'created_by'        => $user_id,
        'guest_name'        => $guest_name,
        'guest_email'       => $guest_email
    );
    $values = apply_filters('wpsp_create_ticket_thread_values', $values);

    $ticket_oprations->create_new_thread($values);

    do_action( 'wpsp_after_create_ticket', $ticket_id );

		$thankyou_url = $wpsupportplus->functions->get_support_page_url(array('page'=>'tickets','section'=>'create-ticket','action'=>'thankyou','ticket_id'=>$ticket_id));
    ?>
    <script>
    jQuery(document).ready(function(){
        window.location.href = '<?php echo $thankyou_url?>';
    });
    </script>
    <?php

endif;

if( isset($_REQUEST['action']) && $_REQUEST['action'] == 'thankyou' ) :

    $ticket_id = isset($_REQUEST['ticket_id']) ? intval($_REQUEST['ticket_id']) : 0;

    if($ticket_id){
				$ticket = $wpdb->get_row("select * from {$wpdb->prefix}wpsp_ticket WHERE id=".$ticket_id);
				?>
        <div class="container-fluid" id="wpsp_thank_you_page_container">
            <?php
            $thank_you_page_title=stripcslashes($wpsupportplus->functions->get_thank_you_page_title());
            echo '<h1>'.$thank_you_page_title.'</h1>';

            $thank_you_page_body = wpautop(stripcslashes($wpsupportplus->functions->get_thank_you_page_body()));

            $thank_you_page_body = $wpsupportplus->functions->replace_template_tags( $thank_you_page_body, $ticket );

            echo '<div id="wpsp_thank_you_page_body"> '.$thank_you_page_body.'</div>';
            ?>

            <div style="clear: both;padding:15px 0">
                <a href="<?php echo $wpsupportplus->functions->get_support_page_url(array('page'=>'tickets','section'=>'ticket-list','action'=>'open-ticket','id'=>$ticket_id));?>"><button class="btn btn-sm btn-info" type="submit"><?php _e('View Ticket','wp-support-plus-responsive-ticket-system-emancipatic');?></button></a>&nbsp;&nbsp;&nbsp;
                <a href="<?php echo $wpsupportplus->functions->get_support_page_url(array('page'=>'tickets','section'=>'ticket-list'));?>"><button class="btn btn-sm btn-info" type="submit"><?php _e('Ticket List','wp-support-plus-responsive-ticket-system-emancipatic');?></button></a>&nbsp;&nbsp;&nbsp;
                <a href="<?php echo $wpsupportplus->functions->get_support_page_url(array('page'=>'tickets','section'=>'create-ticket'));?>"><button class="btn btn-sm btn-info" type="submit"><?php _e('Create New Ticket','wp-support-plus-responsive-ticket-system-emancipatic');?></button></a>
            </div>

        </div>
    <?php

    }

endif;
