<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function buddyforms_pay_for_submissions_admin_settings_sidebar_metabox() {
	add_meta_box( 'buddyforms_pay_for_submissions', __( "Pay For Submission", 'buddyforms-pay-for-submissions' ), 'buddyforms_pay_for_submissions_admin_settings_sidebar_metabox_html', 'buddyforms', 'normal', 'low' );
	add_filter( 'postbox_classes_buddyforms_buddyforms_pay_for_submissions', 'buddyforms_metabox_class' );
}

add_filter( 'add_meta_boxes', 'buddyforms_pay_for_submissions_admin_settings_sidebar_metabox' );

function buddyforms_pay_for_submissions_admin_settings_sidebar_metabox_html() {
	global $post;

	if ( $post->post_type != 'buddyforms' ) {
		return;
	}

	$buddyform = get_post_meta( get_the_ID(), '_buddyforms_options', true );

	if ( empty( $buddyform ) ) {
		return;
	}

	$form_setup = array();


	$pay_for_submissions = false;
	if ( isset( $buddyform['pay_for_submissions'] ) ) {
		$pay_for_submissions = $buddyform['pay_for_submissions'];
	}

	$form_setup[] = new Element_Checkbox( "<b>" . __( 'Enable Pay For Submission for this form', 'buddyforms-pay-for-submissions' ) . "</b>", "buddyforms_options[pay_for_submissions]", array( "pay_for_submissions" => __( "Pay to submit the entry", 'buddyforms-pay-for-submissions' ) ),
		array(
			'value' => $pay_for_submissions,
		)
	);

	$woo_args             = array(
		'status'  => 'publish',
		'orderby' => 'name',
	);
	$dropdown_of_products = array( '' => '' );
	/** @var WC_Product[] $woo_products */
	$woo_products = wc_get_products( $woo_args );
	if ( ! empty( $woo_products ) ) {
		$dropdown_of_products = wc_list_pluck( $woo_products, 'get_name', 'get_id' );
	}
	$pay_for_submissions_woo_product = isset( $buddyform['pay_for_submissions_woo_product'] ) ? $buddyform['pay_for_submissions_woo_product'] : '';
	$form_setup[]                    = new Element_Select( '<b>' . __( 'Select the product', 'buddyforms-pay-for-submissions' ) . '</b>', "buddyforms_options[pay_for_submissions_woo_product]",
		$dropdown_of_products
		, array(
			'value'     => $pay_for_submissions_woo_product,
			'shortDesc' => __( 'The user will be redirect to this product when submit the form.', 'buddyforms-pay-for-submissions' )
		)
	);

	$pay_for_submissions_woo_direct_checkout = isset( $buddyform['pay_for_submissions_woo_direct_checkout'] ) ? $buddyform['pay_for_submissions_woo_direct_checkout'] : '';
	$form_setup[]                            = new Element_Checkbox( "<b>" . __( 'Direct Checkout', 'buddyforms-pay-for-submissions' ) . "</b>", "buddyforms_options[pay_for_submissions_woo_direct_checkout]", array( "pay_for_submissions_woo_direct_checkout" => __( "Go direct to checkout", 'buddyforms-pay-for-submissions' ) ),
		array(
			'value' => $pay_for_submissions_woo_direct_checkout,
			'shortDesc' => __( 'Send your user direct to checkout not to the cart.', 'buddyforms-pay-for-submissions' )
		)
	);

	buddyforms_display_field_group_table( $form_setup );
}
