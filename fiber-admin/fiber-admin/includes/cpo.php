<?php
// Exit if accessed directly
if(!defined('ABSPATH')){
	exit;
}

/**
 * Custom Post Order
 */
class Fiber_Admin_CPO{
	public function __construct(){
		add_action('load-edit.php', [$this, 'fiad_cpo_scripts']);
		add_action('admin_enqueue_scripts', [$this, 'fiad_cpo_scripts']);
		
		add_action('wp_insert_post', [$this, 'fiad_cpo_insert'], 10, 3);
		add_action("wp_ajax_fiad_cpo_update", [$this, 'fiad_cpo_update']);
		add_action("wp_ajax_nopriv_fiad_cpo_update", [$this, 'fiad_cpo_update']);
		
		add_action("wp_ajax_fiad_cpo_tax_update", [$this, 'fiad_cpo_tax_update']);
		add_action("wp_ajax_nopriv_fiad_cpo_tax_update", [$this, 'fiad_cpo_tax_update']);
		
		add_action('pre_get_posts', [$this, 'fiad_cpo_update_order']);
		add_filter('create_term', [$this, 'fiad_cpo_create_term_order'], 10, 3);
		add_filter('get_terms_orderby', [$this, 'fiad_cpo_update_term_order'], 10, 3);
		add_filter('get_terms_args', [$this, 'fiad_get_terms_args'], 10, 2);
	}
	
	public function fiad_cpo_scripts(){
		if(fiad_is_screen_sortable()){
			// styles
			wp_enqueue_style('fiber-admin', FIBERADMIN_ASSETS_URL . 'css/fiber-admin.css', false, FIBERADMIN_VERSION);
			
			// scripts
			$suffix = '';
			if(!FIBERADMIN_DEV_MODE){
				$suffix = '.min';
			}
			wp_enqueue_script('fiber-admin-cpo', FIBERADMIN_ASSETS_URL . 'js/fiber-cpo' . $suffix . '.js', ['jquery-ui-sortable'], FIBERADMIN_VERSION, true);
			wp_localize_script(
				'fiber-admin-cpo',
				'fiad_cpo',
				['ajax_url' => admin_url('admin-ajax.php')]
			);
		}
	}
	
	public function fiad_cpo_update(){
		
		if(!$_POST || (!$_POST['cpo_data'] && !$_POST['post_type'])){
			return false;
		}
		
		parse_str($_POST['cpo_data'], $cpo_order);
		
		global $wpdb;
		
		$post_type = $_POST['post_type'];
		
		$post_status = $_POST['post_status'] ? : 'publish';
		$post_status = $post_status == 'all' ? 'any' : $post_status;
		
		$post_ids = $cpo_order['post'];
		
		if($post_ids){
			$order_start = 0;
			
			// Get minimum post order
			$pre_posts_args  = [
				'post_type'        => $post_type,
				'posts_per_page'   => 1,
				'post_status'      => $post_status,
				'orderby'          => 'menu_order',
				'order'            => 'ASC',
				'post__in'         => $post_ids,
				'suppress_filters' => false,
				'fields'           => 'ids',
			];
			$pre_posts_query = new WP_Query($pre_posts_args);
			if($pre_posts_query->have_posts()){
				$order_start_id   = $pre_posts_query->posts[0];
				$order_start_post = get_post($order_start_id);
				$order_start      = $order_start_post->menu_order;
			}
			
			// Update post order
			$update_posts_args  = [
				'post_type'        => $post_type,
				'posts_per_page'   => - 1,
				'post_status'      => $post_status,
				'orderby'          => 'post__in',
				'order'            => 'ASC',
				'post__in'         => $post_ids,
				'suppress_filters' => false,
				'fields'           => 'ids',
			];
			$update_posts_query = new WP_Query($update_posts_args);
			if($update_posts_query->have_posts()){
				foreach($update_posts_query->posts as $id){
					$wpdb->update($wpdb->posts, ['menu_order' => $order_start], ['ID' => intval($id)]);
					$order_start ++;
				}
			}
		}
		
		die();
	}
	
	public function fiad_cpo_insert($post_id, $post, $update){
		$post_types = fiad_get_cpo_option('post_types');
		if(!$update && $post_types){
			$current_post_type = $post->post_type;
			if(in_array($current_post_type, $post_types)){
				global $wpdb;
				$order_start = 0;
				
				$wpdb->update($wpdb->posts, ['menu_order' => $order_start], ['ID' => intval($post_id)]);
				
				$update_posts_args  = [
					'post_type'        => $post->post_type,
					'posts_per_page'   => - 1,
					'post_status'      => 'publish',
					'orderby'          => 'menu_order',
					'order'            => 'ASC',
					'post__not_in'     => [$post_id],
					'suppress_filters' => false,
					'fields'           => 'ids',
				];
				$update_posts_query = new WP_Query($update_posts_args);
				if($update_posts_query->have_posts()){
					foreach($update_posts_query->posts as $index => $id){
						$wpdb->update($wpdb->posts, ['menu_order' => intval($index + 1)], ['ID' => intval($id)]);
						$order_start ++;
					}
				}
			}
		}
	}
	
	public function fiad_cpo_update_order($query){
		if($query->is_main_query()){
			if(is_admin() && !isset($_GET['orderby'])){
				// Change post order by default in admin
				if(function_exists('get_current_screen')){
					$screen = get_current_screen();
					if(fiad_is_screen_sortable() && !$screen->taxonomy){
						$query->set('orderby', 'menu_order');
						$query->set('order', 'ASC');
					}
				}
			}else{
				if(fiad_get_cpo_option('override_default_query') && fiad_get_cpo_option('post_types')){
					if(is_home() || in_array($query->get('post_type'), fiad_get_cpo_option('post_types'))){
						$query->set('orderby', 'menu_order');
						$query->set('order', 'ASC');
					}
				}
			}
		}
	}
	
	public function fiad_cpo_tax_update(){
		
		if(!$_POST || (!$_POST['cpo_data'] && !$_POST['taxonomy'])){
			return false;
		}
		
		global $wpdb;
		
		parse_str($_POST['cpo_data'], $taxonomy_order);
		
		$term_ids = $taxonomy_order['tag'];
		$taxonomy = $_POST['taxonomy'];
		
		if($term_ids && in_array($taxonomy, fiad_get_cpo_option('taxonomies'))){
			$order_start = 0;
			
			// Get minimum item
			$pre_terms_args = [
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'number'     => 1,
				'orderby'    => 'term_order',
				'order'      => 'ASC',
				'include'    => $term_ids,
			];
			
			$pre_terms = get_terms($pre_terms_args);
			if(!empty($pre_terms) && !is_wp_error($pre_terms)){
				$order_start = $pre_terms[0]->term_order;
			}
			
			// Update term order
			$update_term_args = [
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'orderby'    => 'include',
				'order'      => 'ASC',
				'include'    => $term_ids,
				'fields'     => 'ids',
			];
			$update_terms     = get_terms($update_term_args);
			if(!empty($update_terms) && !is_wp_error($update_terms)){
				foreach($update_terms as $term_id){
					$wpdb->update($wpdb->terms, ['term_order' => $order_start], ['term_id' => $term_id]);
					$order_start ++;
				}
			}
		}
		
		die();
	}
	
	public function fiad_cpo_update_term_order($orderby, $query_vars, $taxonomies){
		if(is_admin()){
			// Change taxonomy order by default in admin
			if(function_exists('get_current_screen') && !isset($_GET['orderby'])){
				$screen = get_current_screen();
				if(fiad_is_screen_sortable() && $screen->taxonomy){
					return 't.term_order';
				}
			}
		}elseif(fiad_get_cpo_option('override_default_tax_query') && fiad_get_cpo_option('taxonomies')){
			if($taxonomies){
				foreach($taxonomies as $taxonomy){
					if(in_array($taxonomy, fiad_get_cpo_option('taxonomies'))){
						return 't.term_order';
					}
				}
			}
		}
		
		return $query_vars['orderby'] == 'term_order' ? 't.term_order' : $orderby;
	}
	
	public function fiad_get_terms_args($args, $taxonomies){
		if(is_admin()){
			// Change taxonomy order by default in admin
			if(function_exists('get_current_screen')){
				$screen = get_current_screen();
				if(fiad_is_screen_sortable() && $screen->taxonomy){
					$args['orderby'] = 'term_order';
				}
			}
		}elseif(fiad_get_cpo_option('override_default_tax_query') && fiad_get_cpo_option('taxonomies')){
			if($taxonomies){
				foreach($taxonomies as $taxonomy){
					if(in_array($taxonomy, fiad_get_cpo_option('taxonomies'))){
						$args['orderby'] = 'term_order';
					}
				}
			}
		}
		
		return $args;
	}
	
	public function fiad_cpo_create_term_order($term_id, $tt_id, $taxonomy){
		if(!fiad_get_cpo_option('taxonomies')){
			return false;
		}
		
		global $wpdb;
		
		if(in_array($taxonomy, fiad_get_cpo_option('taxonomies'))){
			$order_start = 0;
			
			// Get minimum item
			$terms_args = [
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'number'     => 1,
				'orderby'    => 'term_order',
				'order'      => 'DESC',
				'exclude'    => [$term_id],
			];
			
			$terms = get_terms($terms_args);
			if(!empty($terms) && !is_wp_error($terms)){
				$order_start = $terms[0]->term_order;
			}
			
			$wpdb->update($wpdb->terms, ['term_order' => $order_start + 1], ['term_id' => $term_id]);
		}
		
		return false;
	}
}

new Fiber_Admin_CPO();