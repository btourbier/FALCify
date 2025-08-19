<?php
namespace Falcify_Free;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin area.
 */
class Admin {

	const OPTION_KEY = 'falcify_free_settings'; // array('enable_button'=>'yes'|'no').

	/**
	 * Boot hooks.
	 */
	public static function init() : void {
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'register_metabox' ) );
		add_action( 'save_post', array( __CLASS__, 'save_metabox' ), 10, 2 );
		add_action( 'admin_menu', array( __CLASS__, 'settings_page' ) );

		// Default option on first run.
		add_action(
			'admin_init',
			function() {
				$opt = get_option( self::OPTION_KEY );
				if ( ! is_array( $opt ) ) {
					update_option( self::OPTION_KEY, array( 'enable_button' => 'yes' ) );
				}
			}
		);
	}

	/**
	 * Settings registration (Settings API).
	 */
	public static function register_settings() : void {
		register_setting(
			'falcify_free_group',
			self::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_options' ),
				'default'           => array( 'enable_button' => 'yes' ),
			)
		);

		add_settings_section(
			'falcify_free_main',
			__( 'Réglages FALCify Free', 'falcify-free' ),
			function() {
				echo '<p>' . esc_html__( 'Active ou désactive le bouton “Lire en version facile” sur le site.', 'falcify-free' ) . '</p>';
			},
			'falcify_free'
		);

		add_settings_field(
			'enable_button',
			__( 'Activer le bouton sur le site', 'falcify-free' ),
			array( __CLASS__, 'render_enable_button_field' ),
			'falcify_free',
			'falcify_free_main'
		);
	}

	/**
	 * Sanitize options.
	 *
	 * @param array|string $value Raw.
	 * @return array
	 */
	public static function sanitize_options( $value ) : array {
		$enable = 'yes';
		if ( is_array( $value ) && isset( $value['enable_button'] ) ) {
			$enable = ( 'yes' === $value['enable_button'] ) ? 'yes' : 'no';
		}
		return array( 'enable_button' => $enable );
	}

	/**
	 * Render checkbox field.
	 */
	public static function render_enable_button_field() : void {
		$options = get_option( self::OPTION_KEY, array( 'enable_button' => 'yes' ) );
		$val     = isset( $options['enable_button'] ) ? $options['enable_button'] : 'yes';
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[enable_button]" value="yes" <?php checked( $val, 'yes' ); ?> />
			<?php esc_html_e( 'Afficher le bouton sur les pages et articles (si une version FALC existe).', 'falcify-free' ); ?>
		</label>
		<?php
	}

	/**
	 * Add settings page.
	 */
	public static function settings_page() : void {
		add_options_page(
			__( 'FALCify Free', 'falcify-free' ),
			__( 'FALCify Free', 'falcify-free' ),
			'manage_options',
			'falcify_free',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	/**
	 * Render settings page.
	 */
	public static function render_settings_page() : void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'FALCify Free', 'falcify-free' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'falcify_free_group' );
				do_settings_sections( 'falcify_free' );
				submit_button();
				?>
			</form>

			<hr />
			<h2><?php esc_html_e( 'Comment générer le contenu FALC ?', 'falcify-free' ); ?></h2>
			<ol>
				<li><?php esc_html_e( 'Éditez une page ou un article.', 'falcify-free' ); ?></li>
				<li><?php esc_html_e( 'Dans la métabox “FALCify”, collez la version FALC (issue de votre outil IA).', 'falcify-free' ); ?></li>
				<li><?php esc_html_e( 'Enregistrez / Mettez à jour. Le bouton apparaîtra sur le front.', 'falcify-free' ); ?></li>
			</ol>
		</div>
		<?php
	}

	/**
	 * Register editor metabox.
	 */
	public static function register_metabox() : void {
		add_meta_box(
			'falcify_free_metabox',
			__( 'FALCify', 'falcify-free' ),
			array( __CLASS__, 'render_metabox' ),
			array( 'post', 'page' ),
			'normal',
			'default'
		);
	}

	/**
	 * Render metabox.
	 *
	 * @param \WP_Post $post Post.
	 */
	public static function render_metabox( $post ) : void {
		wp_nonce_field( 'falcify_free_nonce', 'falcify_free_nonce' );
		$falc = get_post_meta( $post->ID, '_falcify_falc', true );
		echo '<p>' . esc_html__( 'Collez ici la version FALC de ce contenu. Elle sera affichée aux visiteurs si ceux-ci cliquent sur “Lire en version facile”.', 'falcify-free' ) . '</p>';
		echo '<textarea style="width:100%;min-height:160px" name="falcify_falc">' . esc_textarea( $falc ) . '</textarea>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Save metabox.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 */
	public static function save_metabox( $post_id, $post ) : void {
		if ( ! isset( $_POST['falcify_free_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['falcify_free_nonce'] ) ), 'falcify_free_nonce' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['falcify_falc'] ) ) {
			// Allow WP HTML, strip disallowed tags.
			$falc = wp_kses_post( wp_unslash( $_POST['falcify_falc'] ) );
			update_post_meta( $post_id, '_falcify_falc', $falc );
		}
	}
}
