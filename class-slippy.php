<?php
/**
Plugin Name: Slippy
Plugin URI: https://www.slippy.app/
Description: Turn WordPress into a Slip Box Note Taker
Version: 1.0.4
Author: LMNHQ
Author URI: https://www.lmnhq.com/
Author Email: slippy@lmnhq.com
License: GPLv2 or later

@package Slippy
@version 1.0.4
 */

namespace LMNHQ\WP\Plugin;

/**
 * Slip Notes for WordPress
 */
class Slippy {

	/**
	 * Plugin Name
	 *
	 * @var String
	 */
	protected $plugin_name = 'slippy';

	/**
	 * Post Type
	 *
	 * @var String
	 */
	protected $post_type = 'slipnote';


	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'save_post_slipnote', array( $this, 'save_post_slipnote' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'restrict_manage_posts', array( $this, 'restrict_manage_posts' ) );
		add_filter( 'manage_slipnote_posts_columns', array( $this, 'manage_slipnote_posts_columns' ) );
		add_action( 'manage_slipnote_posts_custom_column', array( $this, 'manage_slipnote_posts_custom_column' ) );
		add_action( 'wp_ajax_linked_note_search', array( $this, 'linked_note_search' ) );

		add_filter( 'get_terms_args', array( $this, 'get_term_args' ) );
		add_filter( 'dashboard_glance_items', array( $this, 'dashboard_glance_items' ) );
		add_action( 'wp_dashboard_setup', array( $this, 'wp_dashboard_setup' ) );
	}

	/**
	 * Register Post Type
	 *
	 * @return void
	 */
	public function init(): void {
		$labels = array(
			'name'               => _x( 'Slip Notes', 'Post Type General Name', 'slippy' ),
			'singular_name'      => _x( 'Slip Note', 'Post Type Singular Name', 'slippy' ),
			'menu_name'          => __( 'Slip Notes', 'slippy' ),
			'parent_item_colon'  => __( 'Parent Slip Note:', 'slippy' ),
			'all_items'          => __( 'All Slip Notes', 'slippy' ),
			'view_item'          => __( 'View Slip Note', 'slippy' ),
			'add_new_item'       => __( 'Add New Slip Note', 'slippy' ),
			'add_new'            => __( 'Add New', 'slippy' ),
			'edit_item'          => __( 'Edit Slip Note', 'slippy' ),
			'update_item'        => __( 'Update Slip Note', 'slippy' ),
			'search_items'       => __( 'Search Slip Note', 'slippy' ),
			'not_found'          => __( 'Not found', 'slippy' ),
			'not_found_in_trash' => __( 'Not found in Trash', 'slippy' ),
		);
		$args   = array(
			'label'               => __( 'Slip Note', 'slippy' ),
			'labels'              => $labels,
			'description'         => __( 'Slip Notes', 'slippy' ),
			'public'              => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_nav_menus'   => false,
			'show_in_menu'        => true,
			'show_in_admin_bar'   => true,
			'menu_position'       => 20,
			'hierarchical'        => false,
			'supports'            => array( 'editor', 'revisions' ),
			'taxonomies'          => array( 'category', 'post_tag' ),
			'has_archive'         => true,
			'rewrite'             => array( 'slug' => $this->post_type ),
			// show_in_rest = true = Gutenberg.
			'show_in_rest'        => false,
			'can_export'          => true,
			'menu_icon'           => 'data:image/svg+xml;base64,PHN2ZyB2ZXJzaW9uPSIxLjEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeD0iMCIgeT0iMCIgdmlld0JveD0iMCAwIDUxMiA1MTIiIHhtbDpzcGFjZT0icHJlc2VydmUiIGZpbGw9ImJsYWNrIj48cGF0aCBkPSJNMzc2IDQyMnY4MS4yMjZMNDU3LjIyNiA0MjJ6Ii8+PHBhdGggZD0iTTQ1MSA2MEgyNTZ2MTA1YzAgNS4xMzYtLjUyOSAxMC4xNTEtMS41MTggMTVIMzYxYzguMjkxIDAgMTUgNi43MDkgMTUgMTVzLTYuNzA5IDE1LTE1IDE1SDI0MWMtLjExNyAwLS4yMTItLjA2NC0uMzI4LS4wNjZDMjI2Ljk3IDIyOC4wNzggMjA1LjQzNSAyNDAgMTgxIDI0MGMtNDEuMzUzIDAtNzUtMzMuNjQ3LTc1LTc1di0zMGMwLTI0LjgxNCAyMC4xODYtNDUgNDUtNDUgNS4yNTkgMCAxMC4zMTMuOTA4IDE1IDIuNTYzVjYwaC0zMGMwLTE2LjUzOCAxMy40NjItMzAgMzAtMzBzMzAgMTMuNDYyIDMwIDMwdjEwNWMwIDguMjc2LTYuNzI0IDE1LTE1IDE1cy0xNS02LjcyNC0xNS0xNXYtMzBjMC04LjI5MS02LjcwOS0xNS0xNS0xNXMtMTUgNi43MDktMTUgMTV2MzBjMCAyNC44MTQgMjAuMTg2IDQ1IDQ1IDQ1czQ1LTIwLjE4NiA0NS00NVY2MGMwLTMzLjA5MS0yNi45MDktNjAtNjAtNjBzLTYwIDI2LjkwOS02MCA2MEg2MWMtOC4yOTEgMC0xNSA2LjcwOS0xNSAxNXY0MjJjMCA4LjI5MSA2LjcwOSAxNSAxNSAxNWgyODVWNDA3YzAtOC4yODQgNi43MTYtMTUgMTUtMTVoMTA1Vjc1YzAtOC4yOTEtNi43MDktMTUtMTUtMTV6TTMwMSAzOTJIMTUxYy04LjI5MSAwLTE1LTYuNzA5LTE1LTE1czYuNzA5LTE1IDE1LTE1aDE1MGM4LjI5MSAwIDE1IDYuNzA5IDE1IDE1cy02LjcwOSAxNS0xNSAxNXptNjAtOTBIMTUxYy04LjI5MSAwLTE1LTYuNzA5LTE1LTE1czYuNzA5LTE1IDE1LTE1aDIxMGM4LjI5MSAwIDE1IDYuNzA5IDE1IDE1cy02LjcwOSAxNS0xNSAxNXoiLz48L3N2Zz4=',
		);
		register_post_type( $this->post_type, $args );
	}

	/**
	 * Register Dashboard Widget
	 *
	 * @return void
	 */
	public function wp_dashboard_setup(): void {
		wp_add_dashboard_widget( 'slippy_dashboard_widget', 'Random Slip Note', array( $this, 'slippy_dashboard_widget' ) );
	}

	/**
	 * Draw Dashboard Widget
	 *
	 * @return void
	 */
	public function slippy_dashboard_widget(): void {
		$args = array(
			'posts_per_page' => 1,
			'post_type'      => $this->post_type,
			'orderby'        => 'rand',
		);

		$query = new \WP_Query( $args );

		while ( $query->have_posts() ) {
			$query->the_post();

			echo sprintf(
				'<a href="%s">Note #%d - %s</a>',
				esc_url( get_edit_post_link() ),
				esc_attr( get_the_ID() ),
				esc_attr( get_the_date() )
			);

			the_content();
		}
	}

	/**
	 * Add note count to the 'At a Glance' dashboard widget
	 *
	 * @param array $items Glance Items.
	 * @return array
	 */
	public function dashboard_glance_items( array $items = array() ): array {
		$num_posts = wp_count_posts( $this->post_type );
		if ( $num_posts ) {
			$published = intval( $num_posts->publish );
			$post_type = get_post_type_object( $this->post_type );
			// translators: name and singular_name, from register_post_type above.
			$text = _n(
				'%s Slip Note',
				'%s Slip Notes',
				$published,
				'slippy'
			);
			$text = sprintf( $text, number_format_i18n( $published ) );
			echo '<li class="event-count slipnote-count">';
			if ( current_user_can( $post_type->cap->edit_posts ) ) {
				echo '<a href="edit.php?post_type=slipnote">' . esc_html( $text ) . '</a>';
			} else {
				echo '<span>' . esc_html( $text ) . '</span>';
			}
			echo '</li>';
		}

		return $items;
	}

	/**
	 * Save Slip Note
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function save_post_slipnote( int $post_id = 0 ): void {
		$title_length  = 140;
		$add_hellipsis = false;
		$post          = get_post( $post_id );
		$title         = wp_strip_all_tags( $post->post_content );
		if ( strlen( $title ) > $title_length ) {
			$add_hellipsis = true;
		}
		$title = substr( $title, 0, $title_length );
		if ( $add_hellipsis ) {
			$title .= '…';
		}

		remove_action( 'save_post_slipnote', array( $this, 'save_post_slipnote' ) );
		wp_update_post(
			array(
				'ID'         => $post_id,
				'post_title' => $title,
			)
		);
		$this->handle_linked_notes( $post_id );
		add_action( 'save_post_slipnote', array( $this, 'save_post_slipnote' ) );
	}

	/**
	 * Add admin styles and scripts
	 *
	 * @return void
	 */
	public function admin_enqueue_scripts(): void {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/admin.css', array(), '1.0' );

		wp_register_script( 'linked-search-autocomplete', plugin_dir_url( __FILE__ ) . 'js/linked-search-autocomplete.js', array( 'jquery', 'jquery-ui-autocomplete' ), '1.0', false );
		wp_localize_script( 'linked-search-autocomplete', 'LinkedSearchAutocomplete', array( 'url' => admin_url( 'admin-ajax.php' ) ) );
		wp_enqueue_script( 'linked-search-autocomplete' );
	}

	/**
	 * Add Meta Boxes
	 *
	 * @return void
	 */
	public function add_meta_boxes(): void {
		add_meta_box(
			$this->plugin_name . '_related_notes',
			__( 'Related Notes', 'slippy' ),
			array( $this, 'related_notes_display_callback' ),
			$this->post_type,
			'normal',
			'high'
		);

		add_meta_box(
			$this->plugin_name . '_linked_notes',
			__( 'Linked Notes', 'slippy' ),
			array( $this, 'linked_notes_display_callback' ),
			$this->post_type,
			'advanced',
			'high'
		);
	}

	/**
	 * Display Related Notes
	 *
	 * @param \WP_Post $post Post Object.
	 * @return void
	 */
	public function related_notes_display_callback( \WP_Post $post ): void {
		$tags = wp_get_post_tags( $post->ID );
		$cats = wp_get_post_categories( $post->ID );

		if ( ! $tags ) {
			echo 'Add some tags to find related posts.';
			return;
		}

		$tag_ids = array();
		foreach ( $tags as $individual_tag ) {
			$tag_ids[] = $individual_tag->term_id;
		}

		$cat_ids = array();
		foreach ( $cats as $category ) {
			$cat_ids[] = $category->slug;
		}

		$args = array(
			'tag__in'        => $tag_ids,
			'cat_in'         => $cat_ids,
			'post__not_in'   => array( $post->ID ),
			'posts_per_page' => -1,
			'post_type'      => $this->post_type,
		);

		$query = new \WP_Query( $args );

		while ( $query->have_posts() ) {
			$query->the_post();

			echo sprintf(
				'<a href="%s">Note #%d - %s</a>',
				esc_url( get_edit_post_link() ),
				esc_attr( get_the_ID() ),
				esc_attr( get_the_date() )
			);

			the_content();
		}
		wp_reset_postdata();
	}

	/**
	 * Display Linked Notes
	 *
	 * @param \WP_Post $post Post Object.
	 * @return void
	 */
	public function linked_notes_display_callback( \WP_Post $post ): void {
		$linked_notes = $this->get_linked_notes( $post->ID );

		echo '<input type="text" autocomplete="off" name="linked_notes_search" id="linked_notes_search" placeholder="Search Notes..." />';

		echo '<div id="linked_notes">';

		$choices   = 0;
		$admin_url = get_admin_url();

		foreach ( $linked_notes as $note ) {
			$choices++;

			echo sprintf(
				'<label><input type="checkbox" name="note_ids[]" value="%d" checked="checked" /><a href="%spost.php?post=%d&action=edit">%s</a></label>',
				esc_attr( $note->ID ),
				esc_url( $admin_url ),
				esc_attr( $note->ID ),
				esc_attr( $note->post_title )
			);
		}

		if ( $choices < 0 ) {
			$choice_block = '<p>No Linked Notes</p>';
		}

		echo '</div>';
	}

	/**
	 * Add filter to 'All Slip Notes' screen
	 *
	 * @param string $post_type Post Type.
	 * @return void
	 */
	public function restrict_manage_posts( string $post_type = '' ): void {
		$this->filter_tags();

		if ( $this->post_type !== $post_type ) {
			return;
		}

		$selected = '';
		if ( isset( $_GET['view_type'] ) && 'full' === $_GET['view_type'] ) {
			$selected = ' selected="selected"';
		}

		echo '<select id="view_type" name="view_type">';
		echo sprintf(
			'<option value="default">%s</option>',
			esc_attr( __( 'Default View', 'slippy' ) )
		);
		echo sprintf(
			'<option value="full"%s>%s</option>',
			esc_attr( $selected ),
			esc_attr( __( 'Full View', 'slippy' ) )
		);
		echo '</select>';
	}

	/**
	 * Remove unnecessary columns from custom view
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public function manage_slipnote_posts_columns( array $columns = array() ): array {
		if ( ! isset( $_GET['view_type'] ) || 'default' === $_GET['view_type'] ) {
			return $columns;
		}

		$columns                  = array();
		$columns['slipnote_note'] = __( 'Note', 'slippy' );

		return $columns;
	}

	/**
	 * Generate custom view
	 *
	 * @param string $column Column Name.
	 * @param int    $post_id Post ID.
	 * @return type
	 */
	public function manage_slipnote_posts_custom_column(
		string $column = '',
		int $post_id = 0
	) {
		if ( ! isset( $_GET['view_type'] ) || 'default' === $_GET['view_type'] ) {
			return;
		}

		if ( ! 'slipnote_note' === $column ) {
			return;
		}

		$columns = array();

		$post = get_post();

		echo sprintf(
			'<p class="slipnote_note"><a href="%s">Note #%s - %s</a></p>',
			esc_url( get_edit_post_link( $post->ID ) ),
			esc_attr( $post->ID ),
			esc_attr( get_the_date( '', $post->ID ) )
		);

		$content = $post->post_content;
		echo wp_kses_post( apply_filters( 'the_content', $content ) );

		echo '<p class="slipnote_note">Tags: ';
		$posttags = get_the_tags( $post_id );
		$content  = '';
		if ( ! $posttags ) {
			echo 'No Tags!';
		} else {
			$admin_url = get_admin_url();
			foreach ( $posttags as $tag ) {
				$content .= sprintf(
					'<a href="%sedit.php?post_type=%s&view_type=full&tag=%s\">%s</a>, ',
					esc_url( $admin_url ),
					esc_attr( $this->post_type ),
					esc_attr( $tag->slug ),
					esc_attr( $tag->name )
				);
			}
			$content = substr( $content, 0, strlen( $content ) - 2 );
		}
		echo wp_kses_post( $content );
		echo '</p>';
	}

	/**
	 * Linked note search, accessible via AJAX
	 *
	 * @return void
	 */
	public function linked_note_search(): void {
		if ( ! isset( $_GET['term'] ) ) {
			return;
		}

		$term        = sanitize_text_field( wp_unslash( $_GET['term'] ) );
		$term        = strtolower( $term );
		$suggestions = array();

		$args  = array(
			'post_type'      => $this->post_type,
			'posts_per_page' => -1,
			'order'          => 'ASC',
			'orderby'        => 'title',
			's'              => $term,
		);
		$query = new \WP_Query( $args );

		while ( $query->have_posts() ) {
			$query->the_post();
			$suggestion          = array();
			$suggestion['label'] = get_the_title();
			$suggestion['id']    = get_the_ID();

			$suggestions[] = $suggestion;
		}

		wp_reset_postdata();

		echo wp_json_encode( $suggestions );

		exit();
	}

	/**
	 * Do not limit tag cloud count in notes
	 *
	 * @param array $args Term Arguments.
	 * @return array
	 */
	public function get_term_args( array $args = array() ): array {
		if (
			defined( 'DOING_AJAX' ) &&
			DOING_AJAX &&
			isset( $_POST['action'] ) &&
			'get-tagcloud' === $_POST['action']
		) {
			$qs = wp_parse_url( wp_get_referer(), PHP_URL_QUERY );
			parse_str( $qs, $query_string );

			$post_type = 'post';
			if ( ! empty( $query_string['post_type'] ) ) {
				$post_type = $query_string['post_type'];
			} elseif ( ! empty( $query_string['post'] ) ) {
				$post_type = get_post_type( $query_string['post'] );
			}

			if ( $post_type === $this->post_type ) {
				$args['number'] = null;
			}
		}

		return $args;
	}

	/**
	 * Get linked notes
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	protected function get_linked_notes( int $post_id = 0 ): array {
		$linked_notes = array();
		if ( 0 < $post_id ) {
			$matches = get_post_meta( $post_id, '_linked_note_id', false );
			foreach ( $matches as $note_id ) {
				$linked_notes[] = get_post( $note_id );
			}
		}

		return $linked_notes;
	}

	/**
	 * Tag filter on 'All Slip Notes' screen
	 *
	 * @global type $typenow
	 * @return type
	 */
	protected function filter_tags() {
		global $typenow;
		if ( $typenow !== $this->post_type ) {
			return;
		}

		$tags = get_tags();
		if ( ! $tags ) {
			return;
		}

		$options = '';
		echo '<select name="tag" id="tag"><option value="">Show All Tags</option>';
		foreach ( $tags as $tag ) {
			$selected = '';
			if ( isset( $_GET['tag'] ) && $_GET['tag'] === $tag->slug ) {
				$selected = ' selected="selected"';
			}

			echo sprintf(
				'<option value="%s" %s>%s (%d)</option>',
				esc_attr( $tag->slug ),
				esc_attr( $selected ),
				esc_attr( $tag->name ),
				esc_attr( $tag->count )
			);
		}
		echo '</select>';
	}

	/**
	 * Save Linked Notes
	 *
	 * It might seem silly to remove all linked notes, then add them again
	 * but order may be important, and this was the easiest way to preserve
	 * the order of the linked notes, which can be dragged about in the UI.
	 *
	 * @param int $post_id Post ID.
	 */
	protected function handle_linked_notes( int $post_id = 0 ) {
		$linked_notes    = $this->get_linked_notes( $post_id );
		$linked_note_ids = array();

		foreach ( $linked_notes as $note ) {
			$linked_note_ids[] = $note->ID;
		}

		foreach ( $linked_note_ids as $note_id ) {
			delete_post_meta( $post_id, '_linked_note_id', $note_id );
		}

		if ( ! isset( $_POST['note_ids'] ) ) {
			return;
		}

		$tags = array_map( 'esc_attr', wp_unslash( $_POST['note_ids'] ) );
		foreach ( $tags as $note_id ) {
			// 4th parameter to false allows linking more than one note
			add_post_meta( $post_id, '_linked_note_id', $note_id, false );
		}
	}
}

if ( is_admin() ) {
	new Slippy();
}
