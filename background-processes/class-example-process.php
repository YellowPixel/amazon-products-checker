<?php

class WP_Example_Process extends WP_Background_Process {

	use WP_Example_Logger;

	/**
	 * @var string
	 */
	protected $action = 'example_process';

	/**
	 * Task
	 *
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @param mixed $item Queue item to iterate over
	 *
	 * @return mixed
	 */
	protected function task( $item ) {
		$message = $this->get_message( $item );

		//$this->really_long_running_task();
		if($message) {
//			error_log(get_edit_post_link($item['ID']));
//			error_log($item['ID']);
			$i = get_site_option( 'apc_progress' );
			$i++;
			update_site_option( 'apc_progress', $i );
			 $this->log($i . '.   ' . $item['ID'] . ': ' . $item['code'] . ': ' . $message );
			
			if($message == '404') {
				$post_data = array(
	'post_title'    => get_the_title($item['ID']) . ' - ' . $item['code'] . ' - ' . $message,
	'post_status'   => 'publish',
	'post_type'     => 'apc_links',
		'meta_input'     => array(
			'apc_code'=> $item['code'],
			'apc_post_title'=> get_the_title($item['ID']),
			'apc_post_source_edit_link'=> admin_url(  'post.php?post=' . $item['ID'] . '&action=edit' ),
		),
);

// Вставляем запись в базу данных
$post_id = wp_insert_post( $post_data );
			//	error_log($post_id);
			}
			return false;
		} else {
			return $item;
		}
		
	}

	/**
	 * Complete
	 *
	 * Override if applicable, but ensure that the below actions are
	 * performed, or, call parent::complete().
	 */
	protected function complete() {
		parent::complete();

		// Show notice to user or perform some other arbitrary task...
	}

}