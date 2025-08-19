<?php
namespace Falcify_Free;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Front-end rendering.
 */
class Frontend {

	/**
	 * Boot hooks.
	 */
	public static function init() : void {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_filter( 'the_content', array( __CLASS__, 'inject_toggle' ), 9 );
	}

	/**
	 * Enqueue assets.
	 */
	public static function enqueue() : void {
		wp_register_script(
			'falcify-free-js',
			FALCIFY_FREE_URL . 'assets/js/falcify-frontend.js',
			array(),
			FALCIFY_FREE_VERSION,
			true
		);

		wp_localize_script(
			'falcify-free-js',
			'FALCIFY_FREE',
			array(
				'i18n' => array(
					'read_easy'     => __( 'Lire en version facile', 'falcify-free' ),
					'read_original' => __( 'Lire la version originale', 'falcify-free' ),
				),
			)
		);

		wp_register_style(
			'falcify-free-css',
			FALCIFY_FREE_URL . 'assets/css/falcify-frontend.css',
			array(),
			FALCIFY_FREE_VERSION
		);
	}

	/**
	 * Inject toggle wrapper and button if enabled and FALC exists.
	 *
	 * @param string $content Content.
	 * @return string
	 */
	public static function inject_toggle( $content ) {
		if ( ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$options = get_option( Admin::OPTION_KEY, array( 'enable_button' => 'yes' ) );
		$enabled = isset( $options['enable_button'] ) && 'yes' === $options['enable_button'];
		if ( ! $enabled ) {
			return $content;
		}

		$post_id = get_the_ID();
		$falc    = get_post_meta( $post_id, '_falcify_falc', true );
		if ( empty( $falc ) ) {
			return $content;
		}

		wp_enqueue_script( 'falcify-free-js' );
		wp_enqueue_style( 'falcify-free-css' );

		$wrapper  = '<div class="falcify-toggle" data-mode="original">';
		$wrapper .= '<button type="button" class="falcify-btn" aria-pressed="false" aria-label="' . esc_attr__( 'Lire en version facile', 'falcify-free' ) . '"></button>';
		$wrapper .= '<div class="falcify-content" data-original="' . esc_attr( wp_kses_post( $content ) ) . '" data-falc="' . esc_attr( wp_kses_post( wpautop( $falc ) ) ) . '">';
		$wrapper .= $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- content is filtered by 'the_content' earlier.
		$wrapper .= '</div></div>';

		return $wrapper;
	}
}
