<?php
namespace Falcify_Free;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class REST {

	public static function init() : void {
		add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
	}

	public static function register_routes() : void {
		register_rest_route(
			'falcify/v1',
			'/status',
			[
				'methods'             => 'GET',
				'permission_callback' => function () { return current_user_can( 'edit_posts' ); },
				'callback'            => [ __CLASS__, 'get_status' ],
			]
		);

		register_rest_route(
			'falcify/v1',
			'/generate',
			[
				'methods'             => 'POST',
				'permission_callback' => function () { return current_user_can( 'edit_posts' ); },
				'args'                => [
					'html'          => [ 'required' => true ],
					'lang'          => [ 'required' => false ],
					'require_score' => [ 'required' => false ],
					'post_id'       => [ 'required' => false, 'validate_callback' => 'absint' ],
				],
				'callback'            => [ __CLASS__, 'post_generate' ],
			]
		);
	}

	public static function get_status( \WP_REST_Request $req ) {
		$limit = (int) get_option( 'falcify_monthly_limit', 500 );
		$used  = (int) get_option( 'falcify_monthly_used', 200 );
		$plan  = get_option( 'falcify_plan', 'Gratuit' );

		return [
			'plan'  => $plan,
			'limit' => $limit,
			'used'  => min( $used, $limit ),
		];
	}

	public static function post_generate( \WP_REST_Request $req ) {
		$html          = (string) $req->get_param( 'html' );
		$require_score = (bool) $req->get_param( 'require_score' );
		$post_id       = (int) $req->get_param( 'post_id' );

		// 👉 Remplace par ton appel IA côté serveur (OpenAI/Claude/DeepL). NE PAS exposer la clé côté client.
		$text_falc = 'Texte simplifié en FALC…';
		$score     = $require_score ? 85 : null;

		if ( $post_id ) {
			update_post_meta( $post_id, '_falcify_falc', wp_kses_post( $text_falc ) );
		}

		$used = (int) get_option( 'falcify_monthly_used', 200 );
		$increment = max( 1, str_word_count( wp_strip_all_tags( $html ) ) );
		update_option( 'falcify_monthly_used', $used + $increment );

		return [
			'text_falc'     => $text_falc,
			'score_falc'    => $score,
			'saved_as_meta' => (bool) $post_id,
		];
	}
}
