<?php
namespace Falcify_Free;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Minimal REST endpoints to validate quota before generation.
 * (No external calls here; actual OpenAI call can be added later.)
 */
class Api {

	/**
	 * Boot.
	 */
	public static function init() : void {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Register routes.
	 */
	public static function register_routes() : void {
		register_rest_route(
			'falcify-free/v1',
			'/can-generate',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'can_generate' ),
				'permission_callback' => function () {
					// Editors & admins can request generation.
					return current_user_can( 'edit_posts' );
				},
				'args' => array(
					'intended_words' => array(
						'type'              => 'integer',
						'required'          => false,
						'default'           => 1,
						'validate_callback' => function( $param ) {
							return is_numeric( $param ) && $param >= 0;
						},
					),
				),
			)
		);

		register_rest_route(
			'falcify-free/v1',
			'/add-usage',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'add_usage' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'args' => array(
					'words' => array(
						'type'              => 'integer',
						'required'          => true,
						'validate_callback' => function( $param ) {
							return is_numeric( $param ) && $param >= 0;
						},
					),
				),
			)
		);
	}

	/**
	 * Check if generation is allowed (remaining quota >= intended_words).
	 *
	 * @param \WP_REST_Request $request Request.
	 */
	public static function can_generate( $request ) {
		$intended = (int) $request->get_param( 'intended_words' );
		$intended = max( 0, $intended );
		$allowed  = Usage::can_generate( $intended );

		return rest_ensure_response( array(
			'allowed'   => $allowed,
			'remaining' => Usage::remaining(),
			'quota'     => Usage::MONTHLY_QUOTA,
			'period'    => Usage::get()['period'],
		) );
	}

	/**
	 * Add words usage after a successful generation.
	 *
	 * @param \WP_REST_Request $request Request.
	 */
	public static function add_usage( $request ) {
		$words = (int) $request->get_param( 'words' );
		$words = max( 0, $words );

		if ( ! Usage::can_generate( $words ) ) {
			return new \WP_Error(
				'falcify_quota_exceeded',
				__( 'La limite mensuelle est atteinte. La génération FALC est bloquée.', 'falcify-free' ),
				array( 'status' => 402 )
			);
		}

		Usage::add( $words );

		return rest_ensure_response( array(
			'success'   => true,
			'used'      => Usage::get()['used'],
			'remaining' => Usage::remaining(),
			'quota'     => Usage::MONTHLY_QUOTA,
			'period'    => Usage::get()['period'],
		) );
	}
}
