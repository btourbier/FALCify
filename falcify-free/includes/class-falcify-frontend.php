<?php
namespace FalcifyFree;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Frontend {

    public static function init() {
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue' ] );
        add_filter( 'the_content', [ __CLASS__, 'inject_toggle' ], 9 );
    }

    public static function enqueue() {
        wp_register_script(
            'falcify-free-js',
            FALCIFY_FREE_URL . 'assets/js/falcify-frontend.js',
            [ 'wp-i18n' ],
            FALCIFY_FREE_VERSION,
            true
        );
        wp_localize_script( 'falcify-free-js', 'FALCIFY_FREE', [
            'i18n' => [
                'read_easy' => __( 'Lire en version facile', 'falcify' ),
                'read_original' => __( 'Lire la version originale', 'falcify' ),
            ]
        ] );
    }

    /**
     * Inject toggle button + data wrapper if option enabled and FALC exists.
     */
    public static function inject_toggle( $content ) {
        if ( ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
            return $content;
        }

        $options = get_option( Admin::OPTION_KEY, [ 'enable_button' => 'yes' ] );
        $enabled = isset( $options['enable_button'] ) && $options['enable_button'] === 'yes';
        if ( ! $enabled ) return $content;

        $post_id = get_the_ID();
        $falc    = get_post_meta( $post_id, '_falcify_falc', true );

        if ( empty( $falc ) ) {
            return $content;
        }

        // Wrap both versions in a container. Front JS toggles innerHTML without page reload.
        wp_enqueue_script( 'falcify-free-js' );

        $wrapper  = '<div class="falcify-toggle" data-mode="original">';
        $wrapper .= '<button type="button" class="falcify-btn" aria-pressed="false" aria-label="'. esc_attr__( 'Lire en version facile', 'falcify' ) .'"></button>';
        $wrapper .= '<div class="falcify-content" data-original="' . esc_attr( wp_kses_post( $content ) ) . '" data-falc="' . esc_attr( wp_kses_post( wpautop( $falc ) ) ) . '">';
        $wrapper .= $content;
        $wrapper .= '</div></div>';

        // Keep original content visible by default; JS switches to FALC on click.
        return $wrapper;
    }
}
