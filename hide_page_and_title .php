<?php
/*
Plugin Name: Hide Page & Post Title
Description: Per-post checkbox to hide the theme-rendered title. Removes core/post-title on block themes and uses scoped CSS for classic themes—does not touch content you typed in the editor.
Version: 1.2.0
Author: Jason Cox
License: GPLv2 or later
*/

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
				// Block themes: completely remove the Post Title block output
				add_filter( 'render_block', [ $this, 'maybe_strip_post_title_block' ], 10, 2 );

				// Classic themes: scoped CSS that only targets header/title areas
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
				|| ! isset( $_POST[ $this->meta_key . '_noncename' ] )
				|| ! wp_verify_nonce( $_POST[ $this->meta_key . '_noncename' ], $this->meta_key . '_dononce' )
			) {
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

		/**
		 * Classic themes: inject scoped CSS targeting only typical title locations.
		 * We DO NOT filter `the_title`, so headings inside the content are untouched.
		 */
		public function enqueue_inline_css() {
			if ( ! $this->is_hide_enabled_for_current() ) {
				return;
			}

			$post_id = (int) get_queried_object_id();

			$handle = 'hpt-hide-title-inline';
			wp_register_style( $handle, false, [], null );
			wp_enqueue_style( $handle );

			/* 
			 * Carefully chosen selectors:
			 * - .entry-title, .wp-block-post-title, .page-title: common theme title classes
			 * - .entry-header .entry-title / .page-header .page-title: header-scoped only
			 * - Avoids generic "h1" so body headings remain visible
			 * - Scoped to .postid-{ID} so only the current singular is affected
			 */
			$css = sprintf(
				'.postid-%1$d .entry-title,
				 .postid-%1$d .wp-block-post-title,
				 .postid-%1$d .page-title,
				 .postid-%1$d .entry-header .entry-title,
				 .postid-%1$d .page-header .page-title { display: none !important; }',
				$post_id
			);

			wp_add_inline_style( $handle, $css );
		}
	}

	new HPT_Hide_Title_Safe();
}