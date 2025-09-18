<?php
// Exit if accessed directly
if(!defined('ABSPATH')){
	exit;
}

/**
 * Duplicate post
 */
class Fiber_Admin_Duplicate{
	public function __construct(){
		// for non-hierarchy post types
		add_filter('post_row_actions', [$this, 'fiad_duplicate_link'], 10, 2);
		// for hierarchy post types
		add_filter('page_row_actions', [$this, 'fiad_duplicate_link'], 10, 2);
		
		// duplicate
		add_action('admin_action_fiad_duplicate_post_as_draft', [$this, 'fiad_duplicate_post_as_draft']);
		
		// bulk actions
		$duplicate_post_types = fiad_get_duplicate_option('post_types');
		if($duplicate_post_types){
			foreach($duplicate_post_types as $post_type){
				add_filter('bulk_actions-edit-' . $post_type, [$this, 'fiad_bulk_duplicate']);
				add_filter('handle_bulk_actions-edit-' . $post_type, [
					$this,
					'fiad_bulk_duplicate_handler',
				], 10, 3);
			}
		}
		
		// admin notices
		add_action('admin_notices', [$this, 'fiad_duplicate_admin_notice']);
	}
	
	public function fiad_duplicate_link($actions, $post){
		if(!current_user_can('edit_posts')){
			return $actions;
		}
		
		// exclude Woocommerce products
		if(is_plugin_active('woocommerce/woocommerce.php') && $post->post_type == 'product'){
			return $actions;
		}
		
		// check duplicate settings
		$duplicate_post_types = fiad_get_duplicate_option('exclude_post_types');
		$duplicate_enable     = false;
		if(empty($duplicate_post_types)){
			$duplicate_enable = true;
		}elseif(!in_array($post->post_type, $duplicate_post_types)){
			$duplicate_enable = true;
		}
		
		//check for your post type
		if($duplicate_enable){
			$url = wp_nonce_url(
				add_query_arg(
					[
						'action' => 'fiad_duplicate_post_as_draft',
						'post'   => $post->ID,
					],
					'admin.php'
				),
				basename(__FILE__),
				'duplicate_nonce'
			);
			
			$actions['duplicate'] = '<a href="' . $url . '" title="Duplicate this item" rel="permalink">Duplicate</a>';
		}
		
		return $actions;
	}
	
	public function fiad_duplicate_post($post_id){
		// post object
		$post = get_post($post_id);
		
		// post author
		$current_user    = wp_get_current_user();
		$new_post_author = $current_user->ID;
		
		// if post data exists
		if($post){
			// insert new post
			$args        = [
				'comment_status' => $post->comment_status,
				'ping_status'    => $post->ping_status,
				'post_author'    => $new_post_author,
				'post_content'   => $post->post_content,
				'post_excerpt'   => $post->post_excerpt,
				'post_name'      => $post->post_name,
				'post_parent'    => $post->post_parent,
				'post_password'  => $post->post_password,
				'post_status'    => 'draft',
				'post_title'     => $post->post_title,
				'post_type'      => $post->post_type,
				'to_ping'        => $post->to_ping,
			];
			$new_post_id = wp_insert_post($args);
			
			// copy taxonomies
			$taxonomies = get_object_taxonomies(get_post_type($post));
			if($taxonomies){
				foreach($taxonomies as $taxonomy){
					$post_terms = wp_get_object_terms($post_id, $taxonomy, ['fields' => 'slugs']);
					wp_set_object_terms($new_post_id, $post_terms, $taxonomy);
				}
			}
			
			// duplicate all post meta
			$post_meta = get_post_meta($post_id);
			if($post_meta){
				foreach($post_meta as $meta_key => $meta_values){
					if('_wp_old_slug' == $meta_key || '_crosspost_to_data' == $meta_key || strpos($meta_key, '_crosspost_to_') !== false){ // exclude special meta keys
						continue;
					}
					
					foreach($meta_values as $meta_value){
						add_post_meta($new_post_id, $meta_key, maybe_unserialize($meta_value));
					}
				}
			}
		}
	}
	
	public function fiad_duplicate_post_as_draft(){
		// check if post ID has been provided and action
		if(empty($_GET['post'])){
			wp_die('No post to duplicate has been provided!');
		}
		
		// nonce verification
		if(!isset($_GET['duplicate_nonce']) || !wp_verify_nonce($_GET['duplicate_nonce'], basename(__FILE__))){
			return;
		}
		
		// get the original post id
		$post_id = absint($_GET['post']);
		
		$post = get_post($post_id);
		
		// if post data exists
		if($post){
			
			// duplicate post
			$this->fiad_duplicate_post($post_id);
			
			// finish
			wp_safe_redirect(
				add_query_arg(
					[
						'post_type' => ('post' !== get_post_type($post) ? get_post_type($post) : false),
						'saved'     => 'fiad_post_duplication_created',
					],
					admin_url('edit.php')
				)
			);
			exit;
		}else{
			wp_die('Post creation failed, could not find original post.');
		}
		
	}
	
	public function fiad_bulk_duplicate($bulk_array){
		$bulk_array['fiad_duplicate'] = 'Duplicate';
		
		return $bulk_array;
	}
	
	public function fiad_bulk_duplicate_handler($redirect, $doaction, $object_ids){
		
		// let's remove query args first
		$redirect = remove_query_arg(['fiad_duplicate'], $redirect);
		
		// bulk duplicate
		if($doaction == 'fiad_duplicate'){
			
			foreach($object_ids as $post_id){
				$this->fiad_duplicate_post($post_id);
			}
			
			// do not forget to add query args to URL because we will show notices later
			$redirect = add_query_arg(
				'fiad_duplicate_created',
				count($object_ids),
				$redirect);
			
		}
		
		return $redirect;
	}
	
	public function fiad_duplicate_admin_notice(){
		// Get the current screen
		$screen = get_current_screen();
		
		if('edit' !== $screen->base){
			return;
		}
		
		//Checks if settings updated
		if(isset($_GET['saved']) && 'fiad_post_duplication_created' == $_GET['saved']){
			echo '<div class="notice notice-success"><p>Post duplicated.</p></div>';
		}
		
		if(!empty($_REQUEST['fiad_duplicate_created'])){
			echo '<div class="notice notice-success"><p>Bulk post duplicated.</p></div>';
		}
	}
}

new Fiber_Admin_Duplicate();