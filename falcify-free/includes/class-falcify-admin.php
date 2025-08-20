<?php
namespace FalcifyFree;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Admin {

    const OPTION_KEY = 'falcify_free_settings'; // array: [enable_button => yes/no]

    public static function init() {
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
        add_action( 'add_meta_boxes', [ __CLASS__, 'register_metabox' ] );
        add_action( 'save_post', [ __CLASS__, 'save_metabox' ], 10, 2 );
        add_action( 'admin_menu', [ __CLASS__, 'settings_page' ] );

        // On première activation, valeurs par défaut
        add_action( 'admin_init', function () {
            $opt = get_option( self::OPTION_KEY );
            if ( ! is_array( $opt ) ) {
                update_option( self::OPTION_KEY, [ 'enable_button' => 'yes' ] );
            }
        } );
    }

    public static function register_settings() {
        register_setting( 'falcify_free_group', self::OPTION_KEY );

        add_settings_section(
            'falcify_free_main',
            __( 'Réglages FALCify Free', 'falcify' ),
            function () {
                echo '<p>' . esc_html__( 'Active ou désactive le bouton "Lire en version facile" sur le site.', 'falcify' ) . '</p>';
            },
            'falcify_free'
        );

        add_settings_field(
            'enable_button',
            __( 'Activer le bouton sur le site', 'falcify' ),
            [ __CLASS__, 'render_enable_button_field' ],
            'falcify_free',
            'falcify_free_main'
        );
    }

    public static function render_enable_button_field() {
        $options = get_option( self::OPTION_KEY, [ 'enable_button' => 'yes' ] );
        $val = isset( $options['enable_button'] ) ? $options['enable_button'] : 'yes';
        ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[enable_button]" value="yes" <?php checked( $val, 'yes' ); ?> />
            <?php esc_html_e( 'Afficher le bouton sur les pages et articles (si une version FALC existe).', 'falcify' ); ?>
        </label>
        <?php
    }

    public static function settings_page() {
        add_options_page(
            __( 'FALCify Free', 'falcify' ),
            __( 'FALCify Free', 'falcify' ),
            'manage_options',
            'falcify_free',
            [ __CLASS__, 'render_settings_page' ]
        );
    }

    public static function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'FALCify Free', 'falcify' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'falcify_free_group' );
                do_settings_sections( 'falcify_free' );
                submit_button();
                ?>
            </form>

            <hr />
            <h2><?php esc_html_e( 'Comment générer le contenu FALC ?', 'falcify' ); ?></h2>
            <ol>
                <li><?php esc_html_e( 'Édite une page ou un article.', 'falcify' ); ?></li>
                <li><?php esc_html_e( 'Dans la métabox "FALCify", colle ta version FALC (générée par ton outil IA).', 'falcify' ); ?></li>
                <li><?php esc_html_e( 'Enregistre / Met à jour. Le bouton apparaîtra sur le front.', 'falcify' ); ?></li>
            </ol>
        </div>
        <?php
    }

    public static function register_metabox() {
        add_meta_box(
            'falcify_free_metabox',
            __( 'FALCify', 'falcify' ),
            [ __CLASS__, 'render_metabox' ],
            [ 'post', 'page' ],
            'normal',
            'default'
        );
    }

    public static function render_metabox( $post ) {
        wp_nonce_field( 'falcify_free_nonce', 'falcify_free_nonce' );
        $falc  = get_post_meta( $post->ID, '_falcify_falc', true );
        echo '<p>' . esc_html__( 'Colle ici la version FALC de ce contenu. Elle sera affichée aux visiteurs si ceux-ci cliquent sur "Lire en version facile".', 'falcify' ) . '</p>';
        echo '<textarea style="width:100%;min-height:160px" name="falcify_falc">'. esc_textarea( $falc ) .'</textarea>';
    }

    public static function save_metabox( $post_id, $post ) {
        if ( ! isset( $_POST['falcify_free_nonce'] ) || ! wp_verify_nonce( $_POST['falcify_free_nonce'], 'falcify_free_nonce' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) return;

        if ( isset( $_POST['falcify_falc'] ) ) {
            $falc = wp_kses_post( $_POST['falcify_falc'] );
            update_post_meta( $post_id, '_falcify_falc', $falc );
        }
    }

    public static function init() : void {
	add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
	add_action( 'add_meta_boxes', array( __CLASS__, 'register_metabox' ) );
	add_action( 'save_post', array( __CLASS__, 'save_metabox' ), 10, 2 );
	add_action( 'admin_menu', array( __CLASS__, 'settings_page' ) );

	// 👉 Sidebar Gutenberg
	add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue_editor_assets' ) );
}

public static function enqueue_editor_assets() : void {
	wp_enqueue_script(
		'falcify-editor-sidebar',
		FALCIFY_FREE_URL . 'admin/editor-sidebar.js',
		array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-editor', 'wp-api-fetch', 'wp-wordcount' ),
		FALCIFY_FREE_VERSION,
		true
	);

	wp_enqueue_style(
		'falcify-editor-sidebar',
		FALCIFY_FREE_URL . 'admin/editor-sidebar.css',
		array(),
		FALCIFY_FREE_VERSION
	);

	wp_localize_script(
		'falcify-editor-sidebar',
		'falcifyEditorVars',
		array(
			'logo'       => esc_url( FALCIFY_FREE_URL . 'assets/logo-falcify.svg' ),
			'upgradeUrl' => esc_url( apply_filters( 'falcify_free_upgrade_url', 'https://falcify.tech/upgrade' ) ),
		)
	);
}

}
