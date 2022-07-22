<?php
/*
Plugin Name: Amazon product checker
Description: Amazon products avalable checker
Author: Yelpix LLC
*/

class Example_Background_Processing {

	/**
	 * @var WP_Example_Request
	 */
	protected $process_single;

	/**
	 * @var WP_Example_Process
	 */
	protected $process_all;

	/**
	 * Example_Background_Processing constructor.
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
		add_action( 'admin_bar_menu', array( $this, 'admin_bar' ), 100 );
		add_action( 'init', array( $this, 'process_handler' ) );
	}

	/**
	 * Init
	 */
	public function init() {
		require_once plugin_dir_path(__FILE__) . 'classes/wp-async-request.php';
    require_once plugin_dir_path(__FILE__) . 'classes/wp-background-process.php';
		require_once plugin_dir_path(__FILE__) . 'class-logger.php';
		require_once plugin_dir_path(__FILE__) . 'async-requests/class-example-request.php';
		require_once plugin_dir_path(__FILE__) . 'background-processes/class-example-process.php';

		$this->process_single = new WP_Example_Request();
		$this->process_all    = new WP_Example_Process();
  add_action('init', array( $this, 'apc_links_post_type' ));
		add_filter( 'manage_edit-apc_links_columns', array($this, 'my_edit_apc_links_columns') ) ;
		add_action( 'manage_apc_links_posts_custom_column', array($this, 'my_manage_apc_links_columns'));
		add_action('admin_notices', array($this, 'author_admin_notice'), 90);
		add_action( 'wp_ajax_my_action', array($this, 'my_action_callback' ));
		add_filter( 'post_row_actions', array($this, 'remove_row_actions'), 10, 1 );
	}
		public function apc_links_post_type() {
      register_post_type( 'apc_links',
      array(
          'label'             => 'Amazon products checker',
          'public'            => false,
          'show_ui'           => true,
          'show_in_nav_menus' => false,
          'menu_position'     => 6,
      'supports' => array('title'),
				'capability_type' => 'post',
  'capabilities' => array(
    'create_posts' => 'do_not_allow', 
  ),
  'map_meta_cap' => true,
      )
      );
  }

public function my_edit_apc_links_columns( $columns ) {

	$columns['code'] = 'Code';

	return $columns;
}
	
	

public function my_manage_apc_links_columns( $column ) {
	global $post;

	switch( $column ) {

		case 'code' :

			/* Get the post meta. */
			$code = get_post_meta( $post->ID, 'apc_code', true );
      echo '<a href="https://www.amazon.com/dp/' . $code . '">' . $code . '</a>';

			break;
		
	}
}
	
	
	
public function author_admin_notice(){
	if(get_current_screen()->id == 'edit-apc_links') { ?>
	<div class="notice referrals-statistics">
	<div class="total-ref">
		<div class="total-ref-ttl">
			Progress:
		</div>
		<div class="total-ref-count">
			<?php echo '<span class="current-progress">' . get_site_option( 'apc_progress' ) . '</span> / ' . get_site_option( 'apc_all_cods' ); ?>
		</div>
	</div>
	</div>
	<style>
		.referrals-statistics {
			overflow: hidden
		}
		.referrals-statistics * {
			box-sizing: border-box;
		}
		.total-ref {
			padding: 25px;
			line-height: 20px;
		}
		.total-ref-ttl {
			width: 220px;
  float: left;
  font-size: 50px;
  line-height: 60px;
		}
		.total-ref-count {
			font-size: 70px;
			font-weight: bold;
			padding: 20px 0;
			
		}
		.subsubsub .mine {
			display: none;
		}
		@media (max-width: 767px) {
			.total-ref-ttl {
				width: 100%;
				float: none;
			}
			.total-ref-ttl {
				line-height: normal;
			}
			.total-ref {
				line-height: normal;
				padding: 0;
			}
			.total-ref-count {
				font-size: 50px;
			}
		}
</style>
<script>
jQuery(function() {
	var data = {
			action: 'my_action',
		};
	setInterval(function() {
		jQuery.ajax({
  method: "POST",
  url: ajaxurl,
  data: data,
					
					success:function(response){
						jQuery('.current-progress').text(response);
    }
})
	}, 5000);
	
		jQuery('.title.column-title.has-row-actions a.row-title').each(function() {
			href = jQuery(this).parents('.title.column-title.has-row-actions').find('.edit-post-source-link').attr('href');
			jQuery(this).attr('href', href);
		})		
})
</script>
	<?php };
    
}
function remove_row_actions( $actions ) {
    if( get_post_type() === 'apc_links' ) {
			global $post;
			$url = get_post_meta( $post->ID, 'apc_post_source_edit_link', true );
			unset( $actions['inline hide-if-no-js'] );
			unset( $actions['edit'] );
			$actions['edit post source'] = '<a class="edit-post-source-link" href="'.$url.'">Edit post source</a>';
			//error_log(print_r($post, true));
			error_log(print_r($url, true));
			//error_log(print_r(get_post_meta( $post->ID, true ), true));
		}
        
    return $actions;
}

	
function my_action_callback() {
	echo get_site_option( 'apc_progress' );
	wp_die();
}
	
protected	function is_queue_empty() {
	$identifier = 'wp_example_process';
			global $wpdb;
			$table  = $wpdb->options;
			$column = 'option_name';
			if ( is_multisite() ) {
				$table  = $wpdb->sitemeta;
				$column = 'meta_key';
			}
			$key = $wpdb->esc_like( $identifier . '_batch_' ) . '%';
			$count = $wpdb->get_var( $wpdb->prepare( "
			SELECT COUNT(*)
			FROM {$table}
			WHERE {$column} LIKE %s
		", $key ) );
			return ( $count > 0 ) ? false : true;
		}
	

	/**
	 * Admin bar
	 *
	 * @param WP_Admin_Bar $wp_admin_bar
	 */
	public function admin_bar( $wp_admin_bar ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
//$this->process_all->is_queue_empty();
		$wp_admin_bar->add_menu( array(
			'id'    => 'example-plugin',
			'title' => __( 'Amazon product checker', 'example-plugin' ),
			'href'  => '#',
		) );


if($this->is_queue_empty()) {
	$title = 'Start';
		$href = wp_nonce_url( admin_url( '?process=all'), 'process' );
} else {
	$title = 'Links are now being checked. View progress';
	$href = admin_url( 'edit.php?post_type=apc_links');
}
		$wp_admin_bar->add_menu( array(
			'parent' => 'example-plugin',
			'id'     => 'example-plugin-all',
			'title'  => $title,
			'href'   => $href
		) );
		
//				$wp_admin_bar->add_menu( array(
//			'parent' => 'example-plugin',
//			'id'     => 'example-plugin-single',
//			'title'  => __( 'Stop', 'example-plugin' ),
//			'href'   => wp_nonce_url( admin_url( '?process=stop'), 'process' ),
//		) );
	}

	/**
	 * Process handler
	 */
	public function process_handler() {
		if ( ! isset( $_GET['process'] ) || ! isset( $_GET['_wpnonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'process') ) {
			return;
		}

//		if ( 'stop' === $_GET['process'] ) {
//			$this->handle_stop();
//			wp_redirect( admin_url( 'edit.php?post_type=apc_links') ); 
//			exit;
//		}

		if ( 'all' === $_GET['process'] ) {
			$this->handle_all();
			wp_redirect( admin_url( 'edit.php?post_type=apc_links') ); 
			exit;
		}
	}

	/**
	 * Handle single
	 */
//	protected function handle_single() {
//		$names = $this->get_names();
//		$rand  = array_rand( $names, 1 );
//		$name  = $names[ $rand ];
//
//		$this->process_single->data( array( 'name' => $name ) )->dispatch();
//	}

	/**
	 * Handle all
	 */
	protected function handle_all() {
		$apc_posts = new WP_Query;
	$apcPosts = $apc_posts->query(['post_status'=>'any', 'posts_per_page'=> -1, 'post_type' => 'apc_links']);
		foreach( $apcPosts as $pst ){
			wp_delete_post($pst->ID, true);
		};
		
		$allCods = $this->get_names();
$result = wp_remote_get( 'https://api.proxyscrape.com?request=displayproxies&proxytype=http&timeout=2000&limit=35' );

  $IPs = explode("\n", str_replace("\r", "", $result['body']));
	$IPs = explode("\n", str_replace("\r", "", $result['body']));
	$IPs = array_diff($IPs, array(''));
		
		update_site_option( 'ipArr', $IPs );
		update_site_option( 'apc_all_cods', count($allCods) );
		update_site_option( 'apc_progress', 0 );
		
//		error_log(print_r(get_site_option( 'apc_all_cods' ), true));
//		error_log(print_r($allCods, true));
//		$this->process_all->cancel_process();
		foreach ( $allCods as $code ) {
			$this->process_all->push_to_queue( $code );
		}

		$this->process_all->save()->dispatch();
	}
	
//protected function handle_stop() {
//	$this->process_all->cancel_process();
//}
	/**
	 * Get names
	 *
	 * @return array
	 */
	protected function get_names() {
		$my_posts = new WP_Query;
	$myposts = $my_posts->query([ 'post_status'=>'any', 'posts_per_page'=> -1 ]);
		//get shortcode regex pattern wordpress function
$pattern = get_shortcode_regex(array('amazon'));
		$allCods = [];
		
		foreach( $myposts as $pst ){
			if (   preg_match_all( '/'. $pattern .'/s', $pst->post_content, $matches ) )
{
    $keys = array();
    $result = array();
    foreach( $matches[0] as $key => $value) {
        // $matches[3] return the shortcode attribute as string
        // replace space with '&' for parse_str() function
        $get = str_replace(" ", "&" , $matches[3][$key] );
        $get = str_replace("\"", "" , $get );
        $get = str_replace("'", "" , $get );
        parse_str($get, $output);

        //get all shortcode attribute keys
        $keys = array_unique( array_merge(  $keys, array_keys($output)) );
        $result[] = $output;

    }
	$cods = array();
	foreach($result as $value) {
		$cods[]= reset($value);
	}
	//$cods = array_unique($cods);
	$cods = array_values($cods);
			foreach( $cods as $code) {
				$allCods[] = ['ID' => $pst->ID, 'code' => $code];
			};
		};
			
		
	};
		$allCods = array_map("unserialize", array_unique(array_map("serialize", $allCods)));
return $allCods;
}
}

new Example_Background_Processing();