<?php
class GRBBBusinessReviewCustomPost{
	public $post_type = 'grbb';

	public function __construct(){
		global $grbb_bs;

		if($grbb_bs->can_use_premium_feature()){
			add_action( 'init', [$this, 'onInit'], 20 );
			add_shortcode( 'business-review', [$this, 'onAddShortcode'], 20 );
			add_filter( 'manage_grbb_posts_columns', [$this, 'manageGRBBPostsColumns'], 10 );
			add_action( 'manage_grbb_posts_custom_column', [$this, 'manageGRBBPostsCustomColumns'], 10, 2 );
			add_action( 'use_block_editor_for_post', [$this, 'useBlockEditorForPost'], 999, 2 );
		}
	}

	function onInit(){
		$menuIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" viewBox="0 0 24 24" fill="#fff"><path d="M10.3 6.74a.75.75 0 01-.04 1.06l-2.908 2.7 2.908 2.7a.75.75 0 11-1.02 1.1l-3.5-3.25a.75.75 0 010-1.1l3.5-3.25a.75.75 0 011.06.04zm3.44 1.06a.75.75 0 111.02-1.1l3.5 3.25a.75.75 0 010 1.1l-3.5 3.25a.75.75 0 11-1.02-1.1l2.908-2.7-2.908-2.7z" /><path fillRule="evenodd" d="M1.5 4.25c0-.966.784-1.75 1.75-1.75h17.5c.966 0 1.75.784 1.75 1.75v12.5a1.75 1.75 0 01-1.75 1.75h-9.69l-3.573 3.573A1.457 1.457 0 015 21.043V18.5H3.25a1.75 1.75 0 01-1.75-1.75V4.25zM3.25 4a.25.25 0 00-.25.25v12.5c0 .138.112.25.25.25h2.5a.75.75 0 01.75.75v3.19l3.72-3.72a.75.75 0 01.53-.22h10a.25.25 0 00.25-.25V4.25a.25.25 0 00-.25-.25H3.25z" /></svg>';

		register_post_type( $this->post_type, [
			'labels'				=> [
				'name'			=> __( 'Business Review', 'business-review'),
				'singular_name'	=> __( 'Business Review', 'business-review' ),
				'add_new'		=> __( 'Add New', 'business-review' ),
				'add_new_item'	=> __( 'Add New', 'business-review' ),
				'edit_item'		=> __( 'Edit', 'business-review' ),
				'new_item'		=> __( 'New', 'business-review' ),
				'view_item'		=> __( 'View', 'business-review' ),
				'search_items'	=> __( 'Search', 'business-review'),
				'not_found'		=> __( 'Sorry, we couldn\'t find the that you are looking for.', 'business-review' )
			],
			'public'				=> false,
			'show_ui'				=> true, 		
			'show_in_rest'			=> true,							
			'publicly_queryable'	=> false,
			'exclude_from_search'	=> true,
			'menu_position'			=> 14,
			'menu_icon'				=> 'data:image/svg+xml;base64,' . base64_encode( $menuIcon ),		
			'has_archive'			=> false,
			'hierarchical'			=> false,
			'capability_type'		=> 'page',
			'rewrite'				=> [ 'slug' => 'business-review' ],
			'supports'				=> [ 'title', 'editor' ],
			'template'				=> [ ['grbb/business-review'] ],
			'template_lock'			=> 'all',
		]); // Register Post Type
	}

	function onAddShortcode( $atts ) {
		$post_id = $atts['id'];
		$post = get_post( $post_id );

		$blocks = parse_blocks( $post->post_content );

		return render_block( $blocks[0] );
	}

	function manageGRBBPostsColumns( $defaults ) {
		unset( $defaults['date'] );
		$defaults['shortcode'] = 'ShortCode';
		$defaults['date'] = 'Date';
		return $defaults;
	}

	function manageGRBBPostsCustomColumns( $column_name, $post_ID ) {
		if ( $column_name == 'shortcode' ) {
			echo "<div class='grbbFrontShortcode' id='grbbFrontShortcode-$post_ID'>
				<input value='[business-review id=$post_ID]' onclick='grbbHandleShortcode( $post_ID )'>
				<span class='tooltip'>Copy To Clipboard</span>
			</div>";
		}
	}

	function useBlockEditorForPost($use, $post){
		if ($this->post_type === $post->post_type) {
			return true;
		}
		return $use;
	}
}
new GRBBBusinessReviewCustomPost();