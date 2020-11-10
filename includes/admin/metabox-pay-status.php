<?php

add_action('add_meta_boxes', 'buddyforms_pay_for_submissions_add_pay_status_metabox');
function buddyforms_pay_for_submissions_add_pay_status_metabox() {
	global $post, $buddyforms;

    if ( empty( $buddyforms ) || ! is_array( $buddyforms ) ) {
        return;
    }

    if ( isset( $post->ID ) && ! empty( $post->ID ) ) {

        $form_slug = get_post_meta( $post->ID, '_bf_form_slug', true );

        if ( ! current_user_can('administrator') ) {
            return;
        }

        if ( ! buddyforms_pay_for_submissions_is_enabled( $form_slug ) ) {
            return;
        }

        add_meta_box(
			'bf_pay_for_submissions_pay_status',
			__( 'Pay Status', 'buddyforms' ),
			'buddyforms_pay_for_submissions_add_pay_status_metabox_callback',
			null,
			'side',
			'high'
		);

    }
}

function buddyforms_pay_for_submissions_add_pay_status_metabox_callback( $post ) {
    
    // Add an nonce field so we can check for it later.
    wp_nonce_field( 'buddyforms_pay_for_submissions_pay_status', 'buddyforms_pay_for_submissions_pay_status_nonce' );
    
    $pay_status = (int) buddyforms_pay_for_submission_is_paid( $post->ID ) ? 'paid' : 'unpaid';
    
    echo '<p> <strong>' . __( 'Current status', 'buddyforms' ) . ': </strong> <span style="text-transform: capitalize;">' . $pay_status . '</span> </p>';
    
    if ( $pay_status === 'unpaid' ) {
        echo '<input type="checkbox" name="buddyforms_pay_for_submissions_pay_status" value="paid">';
    
        echo '<label for="buddyforms_pay_for_submissions_pay_status">';
        _e( ' Mark as paid', 'buddyforms' );
        echo '</label> ';
    } 
}

add_action( 'save_post', 'buddyforms_pay_for_save_pay_status_metadata' );
function buddyforms_pay_for_save_pay_status_metadata( $post_id ) {
    global $buddyforms;

    if ( ! is_admin() ) {
		return $post_id;
    }

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return $post_id;
    }

    if ( empty( $buddyforms ) || ! is_array( $buddyforms ) ) {
        return $post_id;
    }
    
    if ( ! isset( $_POST['buddyforms_pay_for_submissions_pay_status_nonce'] ) ) {
		return $post_id;
    }
    
    // Verify that the nonce is valid.
    $nonce = $_POST['buddyforms_pay_for_submissions_pay_status_nonce'];
	if ( ! wp_verify_nonce( $nonce, 'buddyforms_pay_for_submissions_pay_status' ) ) {
		return $post_id;
    }
        
    // Check the user's permissions.
    if ( ! current_user_can('administrator') ) {
        return $post_id;
    }

    /** OK, it's safe now */
    if ( isset( $_POST['buddyforms_pay_for_submissions_pay_status'] ) && $_POST['buddyforms_pay_for_submissions_pay_status'] === 'paid' ) {
        
        // Mark as paid
        update_post_meta( $post_id, 'bf_pay_for_submission_is_paid', 'paid' ); 
    }

	return $post_id;
}