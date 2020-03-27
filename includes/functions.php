<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add 'Pending Payment' post status.
 */
function buddyforms_pay_for_submissions_custom_post_status() {
	register_post_status( 'bf-pending-payment', array(
		'label'                     => _x( 'Pending Payment', 'buddyforms-pay-for-submissions' ),
		'public'                    => false,
		'exclude_from_search'       => true,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Pending Payment <span class="count">(%s)</span>', 'Pending Payment <span class="count">(%s)</span>' ),
	) );
}

add_action( 'init', 'buddyforms_pay_for_submissions_custom_post_status' );

/**
 * Check if a form is enabled for pay for submission
 *
 * @param $form_slug
 *
 * @return bool
 */
function buddyforms_pay_for_submissions_is_enabled( $form_slug ) {
	if ( ! empty( $form_slug ) ) {
		$buddyform = buddyforms_get_form_by_slug( $form_slug );
		if ( ! empty( $buddyform ) ) {
			return ( ! empty( $buddyform['pay_for_submissions'] ) );
		}
	}

	return false;
}

/**
 * Get the form default post status
 *
 * @param $form_slug
 *
 * @return bool
 */
function buddyforms_pay_for_submissions_form_status( $form_slug ) {
	if ( ! empty( $form_slug ) ) {
		$buddyform = buddyforms_get_form_by_slug( $form_slug );
		if ( ! empty( $buddyform ) && ! empty( $buddyform['status'] ) ) {
			return $buddyform['status'];
		}
	}

	return false;
}

/**
 * Check if the entry submitted have the target status from the form setup
 *
 * @param $form_slug
 *
 * @return bool
 */
function buddyforms_pay_for_submissions_is_trigger_status( $form_slug ) {
	if ( ! empty( $form_slug ) ) {
		$setup_status = buddyforms_pay_for_submissions_form_status( $form_slug );
		if ( ! empty( $setup_status ) ) {
			//Read the status of the current entry in the request
			$post_status_action = ! empty( $_POST['status'] ) ? $_POST['status'] : 'publish';

			return $setup_status === $post_status_action;
		}
	}

	return false;
}

/**
 * Change the post status if the pay submission is enabled for this form and the current status is the status setup in the form
 *
 * @param $post_status
 * @param $form_slug
 *
 * @return mixed
 */
function buddyforms_pay_for_submissions_create_edit_form_post_status( $post_status, $form_slug ) {
	global $buddyforms;
	if ( ! empty( $buddyforms ) && ! empty( $form_slug ) ) {
		$target_status = buddyforms_pay_for_submissions_form_status( $form_slug );
		if ( buddyforms_pay_for_submissions_is_enabled( $form_slug ) && $target_status === $post_status ) {
			return 'bf-pending-payment';
		}
	}

	return $post_status;
}

add_filter( 'buddyforms_create_edit_form_post_status', 'buddyforms_pay_for_submissions_create_edit_form_post_status', 10, 2 );

function buddyforms_pay_for_submissions_trigger_mail_submission( $continue, $post_id, $form_slug ) {
	global $buddyforms;
	if ( ! empty( $buddyforms ) && ! empty( $form_slug ) ) {
		$is_trigger_status = buddyforms_pay_for_submissions_is_trigger_status( $form_slug );
		if ( buddyforms_pay_for_submissions_is_enabled( $form_slug ) && $is_trigger_status ) {
			return false;
		}
	}

	return $continue;
}

add_filter( 'buddyforms_trigger_mail_transition', 'buddyforms_pay_for_submissions_trigger_mail_submission', 10, 3 );
add_filter( 'buddyforms_trigger_mail_submission', 'buddyforms_pay_for_submissions_trigger_mail_submission', 10, 3 );


/**
 * Send the pending submission notifications when the entry transition from bf-pending-payment to other status
 *
 * @param $new_status
 * @param $old_status
 * @param $post
 */
function buddyforms_pay_for_submissions_transition_post_status( $new_status, $old_status, $post ) {
	global $form_slug, $buddyforms;

	if ( empty( $old_status ) ) {
		return;
	}

	if ( $old_status !== 'bf-pending-payment' ) {
		return;
	}

	buddyforms_switch_to_form_blog( $form_slug );

	if ( empty( $form_slug ) ) {
		$form_slug = get_post_meta( $post->ID, '_bf_form_slug', true );
	}

	if ( empty( $form_slug ) ) {
		return;
	}

	$is_trigger_status = buddyforms_pay_for_submissions_is_trigger_status( $form_slug );
	if ( buddyforms_pay_for_submissions_is_enabled( $form_slug ) && $is_trigger_status ) {

		if ( empty( $buddyforms[ $form_slug ]['mail_submissions'] ) ) {
			return;
		}

		foreach ( $buddyforms[ $form_slug ]['mail_submissions'] as $notification ) {
			buddyforms_send_mail_submissions( $notification, $post );
		}
	}

	if ( buddyforms_is_multisite() ) {
		restore_current_blog();
	}
}

add_action( 'transition_post_status', 'buddyforms_pay_for_submissions_transition_post_status', 10, 3 );
