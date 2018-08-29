<?php if(!defined('ABSPATH')) { die(); } // Include in all php files, to prevent direct execution
/**
 * Plugin Name: WP Utility Script Runner
 * Description: Write, manage, and run simple "Utility Scripts" (tasks that do not need to run on every page load)
 * Version: 1.1.0
 * Author: Burlington Bytes
 * Author URI: https://www.burlingtonbytes.com
 * Text Domain: wp-util
 **/


class WPUtilScriptRunner {
	private static $_this;
	private $version = '1.1.0';
	private $utils;
	private $options;
	private $cron_fqs;
	private $capability = 'manage_options';

	public static function Instance() {
		static $instance = null;
		if ($instance === null) {
			$instance = new self();
		}
		return $instance;
	}

	private function __construct() {
		$this->utils = array(
			'active'   => array(),
			'cron'     => array(),
			'disabled' => array()
		);
		$options = array(
			'active_utils'    => array(),
			'scheduled_utils' => array()
		);
		$stored_options = get_option( "wp_util_script_runner" );
		if( !empty( $stored_options ) && is_array( $stored_options ) ) {
			$options = array_merge( $options, $stored_options );
		}
		$this->options = $options;
		$this->cron_fqs = array(
			'once'       => 'Once'
		);
		$fqs_stored = wp_get_schedules();
		foreach( $fqs_stored as $val => $fq ) {
			$this->cron_fqs[$val] = $fq['display'];
		}
		add_action( 'admin_post_wp_util_modify_activation', array( $this, "wp_util_modify_activation" ) );
		add_action( 'wp_ajax_wp_util_script_run'          , array( $this, "wp_util_script_run" ) );
		add_action( 'wp_ajax_wp_util_script_schedule_cron', array( $this, "wp_util_script_schedule_cron" ) );
		add_action( 'wp_ajax_wp_util_script_cancel_cron'  , array( $this, "wp_util_script_cancel_cron"   ) );
		add_action( 'wp_util_cron_action'                 , array( $this, 'wp_util_run_cron'), 10, 3 );
		add_action( 'wp_ajax_wp_util_script_run'          , array( $this, "wp_util_script_run" ) );
		add_filter( 'extra_wp_util_files_headers'         , array( $this, 'util_files_headers' ) );
		add_action( 'admin_enqueue_scripts'               , array( $this, "admin_enqueues" ) );
		add_action( 'admin_menu'                          , array( $this, 'add_util_page' ) );
	}
	public function util_files_headers($headers) {
		$headers[] = "Utility Name";
		$headers[] = "Utility URI";
		$headers[] = "Description";
		$headers[] = "Version";
		$headers[] = "Author";
		$headers[] = "Author URI";
		$headers[] = "License";
		$headers[] = "License URI";
		$headers[] = "Supports";
		$headers[] = "Enabled";

		return $headers;
	}

	public function admin_enqueues() {
		$screen = get_current_screen();
		if( $screen->id == 'tools_page_wp-utility-script-runner' ) {
			wp_enqueue_script( 'wp_util_FileSaver'      , plugins_url( 'js/FileSaver.min.js', __FILE__ ), array(), $this->version, true );
			wp_enqueue_script( 'wp_util_SerializeObject', plugins_url( '/js/jQuery.serializeObject.js', __FILE__ ), array('jquery'), $this->version, true );
			wp_enqueue_script( 'wp_util_core'           , plugins_url( 'js/core.js', __FILE__ ), array( 'jquery', 'jquery-ui-core', 'jquery-ui-accordion', 'jquery-ui-datepicker' ), $this->version, true );
			wp_enqueue_style( 'jquery-ui-css'           , plugins_url( 'css/jquery-ui-datepicker.css', __FILE__ ), false, "1.9.0", false );
			wp_enqueue_style( 'wp_util_core'            , plugins_url( 'css/core.css', __FILE__ ), false, $this->version, false );
		}
	}

	public function add_util_page() {
		$this->get_utils();
		add_submenu_page( 'tools.php', __( 'Utility Scripts', 'wp-util' ), __( 'Utility Scripts', 'wp-util' ), $this->capability, 'wp-utility-script-runner', array( $this, 'render_util_page' ) );
	}

	public function render_util_page() {
		$current_link = admin_url( 'tools.php?page=wp-utility-script-runner' );
		$is_disabled = "";
		$tab = "run";
		$tabs = array( 'run', 'cron', 'manage' );
		if( !empty( $_GET['tab'] ) && in_array( $_GET['tab'], $tabs ) ) {
			$tab = $_GET['tab'];
		} else {
			if( !count( $this->utils['active'] ) ) {
				$tab = "manage";
			}
		}
		?>
		<h2>Utility Scripts</h2>
		<h2 class="nav-tab-wrapper">
			<a href="<?php echo $current_link ?>&tab=run"  class="nav-tab <?php echo ( $tab == 'run' )?'nav-tab-active':''; ?>">Run (<?php echo count( $this->utils['active'] );?>)</a>
			<?php if( count( $this->utils['cron'] ) ) { ?>
			<a href="<?php echo $current_link ?>&tab=cron" class="nav-tab <?php echo ( $tab == 'cron' )?'nav-tab-active':''; ?>">Schedule (<?php echo count( $this->utils['cron'] );?>)</a>
			<?php } ?>
			<a href="<?php echo $current_link ?>&tab=manage"  class="nav-tab <?php echo ( $tab == 'manage' )?'nav-tab-active':''; ?>">Manage Scripts (<?php echo count( $this->utils['disabled'] )  + count( $this->utils['active'] );?>)</a>
		</h2>
		<?php
		$all_utils = array_merge( $this->utils['active'], $this->utils['disabled'] );
		uasort( $all_utils, array( $this, 'sort_utilities' ) );
		include( 'templates/admin-tab-' . $tab . '.php' );
	}

	public function wp_util_modify_activation() {
		$slug     = @$_GET['slug'];
		$activate = @$_GET['activate'];
		if( $slug ) {
			if( !empty( $_GET['nonce'] ) && check_ajax_referer( 'wp_utility_script_change_activation' . $slug, 'nonce' ) ) {
				if( $activate ) {
					$this->options['active_utils'][] = $slug;
					$this->options['active_utils'] = array_unique( $this->options['active_utils'] );
				} else {
					$this->options['active_utils'] = array_diff( $this->options['active_utils'], array( $slug ) );
				}
				update_option( 'wp_util_script_runner', $this->options );
			}
		}
		wp_redirect( admin_url( 'tools.php?page=wp-utility-script-runner&tab=manage' ) );
		die();
	}

	public function wp_util_script_schedule_cron() {
		$retval = array(
			'state'   => 'error',
			'message' => 'An unknown error ocurred'
		);
		header('Content-Type: application/json');
		$input = $_POST;
		$slug = false;
		$headers = false;
		if( !current_user_can( $this->capability ) ) {
			$retval['error'] = "You don't have permission to use this feature.";
			echo json_encode( $retval );
			wp_die();
		}
		if( empty( $input['slug'] ) ) {
			$retval['error'] = 'Utility Script Not Specified.';
			echo json_encode( $retval );
			wp_die();
		}
		$this->get_utils();
		$slug = $input['slug'];
		if( empty( $input['nonce'] ) || !check_ajax_referer( 'wp_utility_script_' . $slug, 'nonce' ) ) {
			$retval['error'] = 'Nonce check failed. Try reloading the page.';
			echo json_encode( $retval );
			wp_die();
		}
		if( !isset( $this->utils['active'][$slug] ) || !isset( $this->utils['active'][$slug]['file'] ) ) {
			$retval['error'] = 'Utility Script Not Found.';
			echo json_encode( $retval );
			wp_die();
		}
		$headers = $this->utils['active'][$slug];
		if( !in_array( 'cron', $headers['Supports'] ) ) {
			$retval['message'] = 'This utility does not support cron.';
			echo json_encode( $retval );
			wp_die();
		}
		$task_info = $this->get_scheduled_task_info( $slug );
		if( $task_info ) {
			$retval['message'] = 'A scheduled task already exists with this slug.';
			echo json_encode( $retval );
			wp_die();
		}
		$cron  = array();
		$atts  = array();
		$files = array();
		if( !empty( $input['atts'] ) ) {
			$atts = $input['atts'];
		}
		if( !empty( $input['files'] ) ) {
			$files = $input['files'];
		}
		if( empty( $input['cron'] ) ) {
			$retval['message'] = 'Cron configuration not set.';
			echo json_encode( $retval );
			wp_die();
		}
		$cron = $input['cron'];
		if( empty( $cron['frequency'] ) || empty( $this->cron_fqs[ $cron['frequency'] ] ) ) {
			$retval['message'] = 'Cron Frequency not set or invalid.';
			echo json_encode( $retval );
			wp_die();
		}
		$frequency = $cron['frequency'];
		if( empty( $cron['start_date'] )  || empty( $cron['start_time'] ) ) {
			$retval['message'] = 'Cron time not set.';
			echo json_encode( $retval );
			wp_die();
		}
		$start = get_gmt_from_date( $cron['start_date'] . ' ' . $cron['start_time'], 'U' );
		if( !$start || $start <= time() ) {
			$retval['message'] = 'Cron time not valid.';
			echo json_encode( $retval );
			wp_die();
		}
		$args = array(
			'slug'  => $slug,
			'atts'  => $atts,
			'files' => $files
		);
		if( $frequency == 'once' ) {
			wp_schedule_single_event( $start, 'wp_util_cron_action', $args );
			$retval['state'  ] = 'complete';
			$retval['message'] = "Successfully scheduled single event.";
		} else {
			wp_schedule_event( $start, $frequency, 'wp_util_cron_action', $args );
			$retval['state'  ] = 'complete';
			$retval['message'] = "Successfully scheduled recurring event.";
		}
		$this->options['scheduled_utils'][$slug] = array(
			'args'     => $args,
			'next_run' => $start,
			'frequency'=> $frequency
		);
		update_option( 'wp_util_script_runner', $this->options );

		echo json_encode( $retval );
		wp_die();
	}

	public function wp_util_script_cancel_cron() {
		$retval = array(
			'state'   => 'error',
			'message' => 'An unknown error ocurred'
		);
		header('Content-Type: application/json');
		$input = $_POST;
		$slug = false;
		$headers = false;
		if( !current_user_can( $this->capability ) ) {
			$retval['message'] = "You don't have permission to use this feature.";
		} elseif( !empty( $input['slug'] ) ) {
			$this->get_utils();
			$slug = $input['slug'];
			if( empty( $input['nonce'] ) || !check_ajax_referer( 'wp_utility_script_' . $slug, 'nonce' ) ) {
				$retval['message'] = 'Nonce check failed. Try reloading the page.';
			} else {
				$task = $this->get_scheduled_task_info( $slug );
				if( !$task ) {
					$retval['message'] = 'No scheduled task exists with this slug.';
				} else {
					wp_clear_scheduled_hook( 'wp_util_cron_action', $task['args'] );
					unset( $this->options['scheduled_utils'][$slug] );
					update_option( 'wp_util_script_runner', $this->options );
					$retval['state'  ] = 'complete';
					$retval['message'] = "Successfully cancelled scheduled task.";
				}
			}
		} else {
			$retval['message'] = 'Utility Script Not Specified.';
		}
		echo json_encode( $retval );
		wp_die();
	}

	public function wp_util_run_cron( $slug, $atts, $files ) {
		$this->get_utils();
		if( isset( $this->utils['active'][$slug] ) && isset( $this->utils['active'][$slug]['file'] ) ) {
			$task = $this->get_scheduled_task_info( $slug );
			if( $task ) {
				$header  = $this->utils['active'][$slug];
				$message = $this->safe_include( $slug );
				$state   = array();
				$log_array  = array();
				$start_time = time();
				do {
					$result = apply_filters( 'wp_util_script', $legacy_response, $state, $atts, $files, $slug, $header );
					$log_array[] = $result;
					if( !empty( $result['state'] ) && $result['state'] != 'complete' && $result['state'] != 'error' ) {
						$state = $result['state'];
					} else {
						if( isset( $result['state'] ) && $result['state'] == 'error' ) {
							$state = 'error';
						} else {
							$state = 'complete';
						}
					}
				} while( $state != 'error' && $state != 'complete' );
				$end_time = time();
				$log_key = $start_time . '-' . $end_time;
				if( !isset( $this->options['scheduled_utils'][$slug]['log'] ) ) {
					 $this->options['scheduled_utils'][$slug]['log'] = array();
				}
				$this->options['scheduled_utils'][$slug]['log'][$log_key] = $log_array;
			}
		}
	}

	public function wp_util_script_run() {
		header('Content-Type: application/json');
		$input = $_POST;
		ob_start();
		$slug = false;
		$headers = false;
		if( !current_user_can( $this->capability ) ) {
			$this->output_error("You don't have permission to use this feature.");
		} elseif( isset( $input['slug'] ) && $input['slug'] ) {
			$this->get_utils();
			$slug = $input['slug'];
			if( empty( $input['nonce'] ) || !check_ajax_referer( 'wp_utility_script_' . $slug, 'nonce' ) ) {
				$this->output_error('Nonce check failed. Try reloading the page.');
			} elseif( isset( $this->utils['active'][$slug] ) && isset( $this->utils['active'][$slug]['file'] ) ) {
				$headers = $this->utils['active'][$slug];
				echo $this->safe_include( $slug );
			} else {
				$this->output_error('Utility Script Not Found.');
			}
		} else {
			$this->output_error('Utility Script Not Specified.');
		}
		$message = ob_get_clean();
		$legacy_response = json_decode( $message, true );
		if( !$legacy_response ) {
			$legacy_response = array(
				'message' => ( $message ) ? $message : 'Completed Successfully!',
				'state'   => 'complete'
			);
		}
		$state = array();
		$atts  = array();
		$files = array();
		if( !empty( $input['state'] ) ) {
			$state = $input['state'];
		}
		if( !empty( $input['atts'] ) ) {
			$atts = $input['atts'];
		}
		if( !empty( $input['files'] ) ) {
			$files = $input['files'];
		}

		$this->process_util_hook( $legacy_response, $slug, $headers, $state, $atts, $files );
		wp_die();
	}

	private function get_utils() {
		$dirname = "utilities";
		$parent_theme_dir = get_template_directory();
		$child_theme_dir  = get_stylesheet_directory();
		$directories = array();
		$directories['Plugin'] = plugin_dir_path( __FILE__ ) . $dirname;
		if( $parent_theme_dir !== $child_theme_dir ) {
			$directories['Parent Theme'] = $parent_theme_dir. '/' . $dirname;
			$directories['Child Theme' ] = $child_theme_dir. '/' . $dirname;
		} else {
			$directories['Theme'] = $parent_theme_dir. '/' . $dirname;
		}
		$directories = apply_filters( 'wp_util_directories', $directories );
		foreach( $directories as $location => $dir ) {
			if( is_dir( $dir ) ) {
				$files = glob( $dir . '/*.php' );
				foreach( $files as $file ) {
					$header = get_file_data( $file, array(), "wp_util_files" );
					$header['Location'] = $location;
					if( isset( $header['Utility Name'] ) && $header['Utility Name'] ) {
						if( !empty( $header['Supports'] ) ) {
							$supports = explode( ',', $header['Supports'] );
							$supports = array_map( array( $this, 'filter_supports' ), $supports );
							$supports = array_filter( $supports );
							$header['Supports'] = $supports;
						} else {
							$header['Supports'] = array();
						}
						$slug = sanitize_title( $this->remove_prefix( $file, get_theme_root().'/' ) );
						if( empty( $header['Enabled'] ) ) {
							$header['Enabled'] = '';
						}
						$header['Enabled'] = trim( strtolower( $header['Enabled'] ) );
						$status = 'disabled';
						if( $header['Enabled'] == 'true'  ) {
							$status = 'active';
						} elseif( $header['Enabled'] != 'false'  ) {
							$header['Enabled'] = 'auto';
							$active_utils = $this->options['active_utils'];
							if( in_array( $slug, $active_utils ) ) {
								$status = 'active';
							}
						}
						$header['file'] = $file;
						$this->utils[$status][$slug] = $header;
						if( $status == 'active' && in_array( 'cron', $header['Supports'] ) ) {
							$this->utils['cron'][$slug] = $header;
						}
					}
				}
			}
		}
		uasort( $this->utils['active'  ], array( $this, 'sort_utilities' ) );
		uasort( $this->utils['disabled'], array( $this, 'sort_utilities' ) );
		uasort( $this->utils['cron'    ], array( $this, 'sort_utilities' ) );
	}

	private function sort_utilities( $a, $b ) {
		return strcmp( $a['Utility Name'], $b['Utility Name'] );
	}

	private function get_scheduled_task_info( $slug ) {
		if( !isset( $this->options['scheduled_utils'][$slug] ) ) {
			return false;
		}
		$task = $this->options['scheduled_utils'][$slug];
		$args = $task['args'];
		$next_run = wp_next_scheduled( "wp_util_cron_action", $args );
		if( !$next_run ) {
			unset( $this->options['scheduled_utils'][$slug] );
			update_option( 'wp_util_script_runner', $this->options );
			return $false;
		}
		if( $next_run != $task['next_run'] ) {
			$task['next_run'] = $next_run;
			$this->options['scheduled_utils'][$slug] = $task;
			update_option( 'wp_util_script_runner', $this->options );
		}
		// rounded to the closest 15 minutes
		$rounded_time = ceil( $next_run / 900 ) * 900;
		$task['next_date'] = get_date_from_gmt( '@' . $rounded_time, 'Y-m-d' );
		$task['next_time'] = get_date_from_gmt( '@' . $rounded_time, 'h:i A' );
		return $task;
	}

	private function get_sample_script() {
		$folder = plugin_dir_path( __FILE__ );
		return file_get_contents( $folder . 'templates/sample-utility.php' );
	}

	private function make_activation_link( $slug, $activate ) {
		$utils = array_merge( $this->utils['active'], $this->utils['disabled'] );
		if( !isset( $utils[$slug] ) ) {
			return "Error: util not found";
		}
		$util = $utils[$slug];
		if( isset( $util['Enabled'] ) ) {
			if( $util['Enabled'] == 'true' ) {
				return '<em>Forced Active</em>';
			} elseif( $util['Enabled'] == 'false' ) {
				return '<em>Forced Disabled</em>';
			}
		}
		$nonce = urlencode( wp_create_nonce( "wp_utility_script_change_activation" . $slug ) );
		$url   = admin_url( 'admin-post.php' );
		$url  .= '?action=wp_util_modify_activation';
		$url  .= '&slug=' . urlencode( $slug );
		$url  .= '&nonce=' . urlencode( $nonce );
		$text  = "Deactivate";
		if( $activate ) {
			$url .= '&activate=1';
			$text = "Activate";
		}
		$link = '<a href="' . $url . '" class="edit">' . $text . '</a>';
		return $link;
	}

	private function list_header_info( $header ) {
		$info = array();
		if( !empty( $header['Version'] ) ) {
			$info[] = 'Version: ' . $header['Version'];
		} else {
			$info[] = 'Version: N/A';
		}
		if( !empty( $header['Author'] ) ) {
			if( !empty( $header['Author URI'] ) ) {
				$info[] = 'By <a href="' . $header['Author URI'] . '" target="_blank">' . $header['Author'] . '</a>';
			} else {
				$info[] = 'By ' . $header['Author'];
			}
		}
		if( !empty( $header['Utility URI'] ) ) {
			$info[] = '<a href="' . $header['Utility URI'] . '" target="_blank">Visit utility site</a>';
		}
		$info[] = '<em>Location: ' . $header['Location'] . '</em>';

		return implode( ' | ', $info );
	}

	private function filter_supports($val) {
		return strtolower(trim($val));
	}

	private function safe_include( $slug ) {
		ob_start();
		include( $this->utils['active'][$slug]['file'] );
		return ob_get_clean();
	}

	private function output_error( $error ) {
		$ret_val = array(
			'message' => $error,
			'state'   => 'error'
		);
		echo json_encode( $ret_val );
	}

	private function process_util_hook( $legacy_response, $slug, $header, $state = array(), $atts = array(), $files = array() ) {
		$result = apply_filters( 'wp_util_script', $legacy_response, $state, $atts, $files, $slug, $header );
		remove_all_filters( 'wp_util_input_html' );
		remove_all_filters( 'wp_util_script'     );
		if( is_array( $result ) ) {
			echo json_encode( $result );
		} else {
			$ret_val = array(
				'message' => ( $result ) ? $result : 'Completed Successfully!',
				'state'   => 'complete'
			);
			echo json_encode( $ret_val );
		}
	}

	private function remove_prefix($text, $prefix) {
		if(0 === strpos($text, $prefix))
			$text = substr($text, strlen($prefix)).'';
		return $text;
	}
}
WPUtilScriptRunner::Instance();
