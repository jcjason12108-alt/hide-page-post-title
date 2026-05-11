<?php
/*
Plugin Name: Hide Page & Post Title
Plugin URI: https://github.com/jcjason12108-alt/hide-page-post-title
Description: Per-post checkbox to hide the theme-rendered title. Removes core/post-title on block themes and uses scoped CSS for classic themes—does not touch content you typed in the editor.
Version: 1.3.4
Author: Jason Cox
License: GPLv2 or later
Requires at least: 5.8
Tested up to: 6.9.4
Requires PHP: 7.0
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( file_exists( __DIR__ . '/plugin-update-checker/plugin-update-checker.php' ) ) {
	require_once __DIR__ . '/plugin-update-checker/plugin-update-checker.php';

	$hpt_update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
		'https://github.com/jcjason12108-alt/hide-page-post-title/',
		__FILE__,
		'hide-page-post-title'
	);
	$hpt_update_checker->setBranch( 'main' );

	$hpt_github_token = defined( 'HPT_UPDATE_GITHUB_TOKEN' ) ? HPT_UPDATE_GITHUB_TOKEN : getenv( 'HPT_UPDATE_GITHUB_TOKEN' );
	if ( ! empty( $hpt_github_token ) ) {
		$hpt_update_checker->setAuthentication( $hpt_github_token );
	}
}

if ( ! class_exists( 'HPT_Hide_Title_Safe' ) ) {

	class HPT_Hide_Title_Safe {
		private $meta_key = 'hpt_headertitle';
		private $box_id   = 'hpt_headertitle_metabox';

		public function __construct() {
			// Admin
			add_action( 'add_meta_boxes', [ $this, 'add_metabox' ] );
			add_action( 'save_post', [ $this, 'save_metabox' ] );
			add_action( 'delete_post', [ $this, 'delete_meta' ] );

			// Front-end
			if ( ! is_admin() ) {
				add_filter( 'the_title', [ $this, 'maybe_hide_theme_title' ], 10, 2 );

				// Block themes: completely remove the Post Title block output
				add_filter( 'render_block', [ $this, 'maybe_strip_post_title_block' ], 10, 2 );

				// Classic themes: scoped CSS that only targets header/title areas
				add_filter( 'body_class', [ $this, 'add_body_class' ] );
				add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_inline_css' ] );
			}

			load_plugin_textdomain( 'hpt' );
		}

		/* ---------------- Admin UI ---------------- */

		public function add_metabox() {
			$types = [ 'post', 'page' ];
			$public_cpts = get_post_types( [ 'public' => true, '_builtin' => false ], 'names' );
			if ( is_array( $public_cpts ) ) {
				$types = array_unique( array_merge( $types, $public_cpts ) );
			}
			foreach ( $types as $type ) {
				add_meta_box(
					$this->box_id,
					__( 'Hide Page and Post Title', 'hpt' ),
					[ $this, 'render_metabox' ],
					$type,
					'side',
					'default'
				);
			}
		}

		public function render_metabox( $post ) {
			$checked = (bool) get_post_meta( $post->ID, $this->meta_key, true );
			wp_nonce_field( $this->meta_key . '_dononce', $this->meta_key . '_noncename' );
			?>
			<label>
				<input type="checkbox" name="<?php echo esc_attr( $this->meta_key ); ?>" <?php checked( $checked ); ?> />
				<?php esc_html_e( 'Hide the title.', 'hpt' ); ?>
			</label>
			<?php
		}

		public function save_metabox( $post_id ) {
			if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
				|| wp_is_post_autosave( $post_id )
				|| wp_is_post_revision( $post_id )
			) {
				return;
			}

			$nonce_name = $this->meta_key . '_noncename';
			$nonce      = isset( $_POST[ $nonce_name ] )
				? sanitize_text_field( wp_unslash( $_POST[ $nonce_name ] ) )
				: '';

			if ( ! wp_verify_nonce( $nonce, $this->meta_key . '_dononce' ) ) {
				return;
			}

			$post_type = get_post_type( $post_id );
			$can_edit  = ( 'page' === $post_type )
				? current_user_can( 'edit_page', $post_id )
				: current_user_can( 'edit_post', $post_id );

			if ( ! $can_edit ) {
				return;
			}

			if ( isset( $_POST[ $this->meta_key ] ) ) {
				update_post_meta( $post_id, $this->meta_key, '1' );
			} else {
				delete_post_meta( $post_id, $this->meta_key );
			}
		}

		public function delete_meta( $post_id ) {
			delete_post_meta( $post_id, $this->meta_key );
		}

		/* ---------------- Front-end helpers ---------------- */

		private function is_hide_enabled_for_current(): bool {
			if ( ! is_singular() ) {
				return false;
			}
			$post = get_queried_object();
			if ( empty( $post ) || empty( $post->ID ) ) {
				return false;
			}
			return (bool) get_post_meta( $post->ID, $this->meta_key, true );
		}

		/**
		 * Fallback for themes that render the title through the_title() instead
		 * of a Post Title block or recognizable title markup.
		 */
		public function maybe_hide_theme_title( $title, $post_id = 0 ) {
			if ( ! is_singular() || is_admin() || is_feed() ) {
				return $title;
			}

			$queried_id = (int) get_queried_object_id();
			if ( $queried_id < 1 || (int) $post_id !== $queried_id ) {
				return $title;
			}

			return $this->is_hide_enabled_for_current() ? '' : $title;
		}

		/**
		 * Block themes: remove the Post Title block output.
		 * This avoids any CSS guesswork and only removes the theme-rendered title.
		 */
		public function maybe_strip_post_title_block( $block_content, $block ) {
			if ( ! $this->is_hide_enabled_for_current() ) {
				return $block_content;
			}
			if ( isset( $block['blockName'] ) && $block['blockName'] === 'core/post-title' ) {
				return '';
			}
			return $block_content;
		}

		public function add_body_class( $classes ) {
			if ( $this->is_hide_enabled_for_current() ) {
				$classes[] = 'hpt-hide-title-enabled';
			}
			return $classes;
		}

		/**
		 * Classic themes: inject scoped CSS targeting only typical title locations.
		 * Editor headings are content blocks, so they are not affected by these selectors.
		 */
		public function enqueue_inline_css() {
			if ( ! $this->is_hide_enabled_for_current() ) {
				return;
			}

			$handle = 'hpt-hide-title-inline';
			wp_register_style( $handle, false, [], null );
			wp_enqueue_style( $handle );

			/*
			 * Carefully chosen selectors:
			 * - Scoped by a body class that is only added when this post is enabled.
			 * - Targets theme title regions and top-level article title classes.
			 * - Avoids generic "h1" so body headings remain visible.
			 */
			$css = '
				body.hpt-hide-title-enabled .entry-header .entry-title,
				body.hpt-hide-title-enabled .page-header .page-title,
				body.hpt-hide-title-enabled .post-header .entry-title,
				body.hpt-hide-title-enabled .post-title,
				body.hpt-hide-title-enabled .single-post-title,
				body.hpt-hide-title-enabled .blog-post-title,
				body.hpt-hide-title-enabled article > .entry-title,
				body.hpt-hide-title-enabled article > .post-title,
				body.hpt-hide-title-enabled article > header .entry-title,
				body.hpt-hide-title-enabled article > header .post-title,
				body.hpt-hide-title-enabled main > .entry-title,
				body.hpt-hide-title-enabled main > .post-title,
				body.hpt-hide-title-enabled main > .page-title,
				body.hpt-hide-title-enabled .wp-block-post-title {
					display: none !important;
				}
			';

			wp_add_inline_style( $handle, $css );
		}
	}

	new HPT_Hide_Title_Safe();
}
