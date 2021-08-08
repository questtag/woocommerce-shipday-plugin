<?php 

function dokan_custom_seller_registration_required_fields( $required_fields ) {
    $required_fields['gst_id'] = __( 'Please enter your GST number', 'dokan-custom' );

    return $required_fields;
};

add_filter( 'dokan_seller_registration_required_fields', 'dokan_custom_seller_registration_required_fields' );


function dokan_custom_new_seller_created( $vendor_id, $dokan_settings ) {
    $post_data = wp_unslash( $_POST );

    $gst_id =  $post_data['gst_id'];
   
    update_user_meta( $vendor_id, 'dokan_custom_gst_id', $gst_id );
}

add_action( 'dokan_new_seller_created', 'dokan_custom_new_seller_created', 10, 2 );

  /* Add custom profile fields (call in theme : echo $curauth->fieldname;) */ 

add_action( 'dokan_seller_meta_fields', 'my_show_extra_profile_fields' );

function my_show_extra_profile_fields( $user ) { ?>

    <?php if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }
        if ( ! user_can( $user, 'dokandar' ) ) {
            return;
        }
         $gst  = get_user_meta( $user->ID, 'dokan_custom_gst_id', true );
     ?>
         <tr>
                    <th><?php esc_html_e( 'Gst Number', 'dokan-lite' ); ?></th>
                    <td>
                        <input type="text" name="gst_id" class="regular-text" value="<?php echo esc_attr($gst); ?>"/>
                    </td>
         </tr>
    <?php
 }

add_action( 'personal_options_update', 'my_save_extra_profile_fields' );
add_action( 'edit_user_profile_update', 'my_save_extra_profile_fields' );

function my_save_extra_profile_fields( $user_id ) {

if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }
    update_usermeta( $user_id, 'dokan_custom_gst_id', $_POST['gst_id'] );
}