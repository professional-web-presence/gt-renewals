<?php

/*
Plugin Name: GT Site Renewal Service
Version: 1.0
Description: Provides mechanism for having site owners renew their sites on a periodic basis.
Author: Professional Web Presence, Kevin Pittman
Network: true
*/


require_once(__DIR__ . DIRECTORY_SEPARATOR . 'gt-renewals-options-page.php');

function gt_renewals_add_network_dashboard_widget() {

	wp_add_dashboard_widget( 'gt_renewals', 'Site Renewal Status ', 'gt_renewals_network' );

        if ($flag) {

	 	// Globalize the metaboxes array, this holds all the widgets for wp-admin
	 
	 	global $wp_meta_boxes;
	
	 	// Get the regular dashboard widgets array 
	 	// (which has our new widget already but at the end)
	 
	 	$normal_dashboard = $wp_meta_boxes['dashboard']['normal']['core'];
	 	
	 	// Backup and delete our new dashboard widget from the end of the array
	 
	 	$example_widget_backup = array( 'gt_renewals' => $normal_dashboard['gt_renewals'] );
	 	unset( $normal_dashboard['gt_renewals'] );
	 
	 	// Merge the two arrays together so our widget is at the beginning
	 
	 	$sorted_dashboard = array_merge( $example_widget_backup, $normal_dashboard );
	 
	 	// Save the sorted array back into the original metaboxes 
	 
	 	$wp_meta_boxes['dashboard']['normal']['core'] = $sorted_dashboard;
	}
}


function gt_renewals_network() {
	global $wpdb;

	$siteList = $wpdb->get_results("SELECT meta_value FROM $wpdb->sitemeta WHERE meta_key LIKE 'gt_%_renewed'");
	$output = array();
	foreach ($siteList as $data) {
		$values = explode('|', $data -> meta_value);
		$output[$values[0]] = "      <tr><th scope='row'>" . $values[0] . "</th><td>" . $values[1] . "</td><td>" . $values[2] . "</td></tr>\n";
	}
	
	// Truncating to last 10 renewed sites
	while(count($output) > 10) array_shift($output);
	
	// Sorting by key
	ksort($output);

	print "    <p class=\"gt-button-gold\">" . count($siteList) . " sites have been renewed.  <a href=\"/wp-admin/network/settings.php?page=gt-renewals-config\">Settings</a></p>\n\n";
	print "    <h3>Last 10 website renewals</h3>\n";

	print "  <table border=1 cellpadding=4>\n";
	print "    <tr><th align=left scope='col'>Site ID</th><th align=left scope='col'>Date Renewed</th><th align=left scope='col'>Renewed By</th></tr>\n";
	print implode("\n", $output);
	print "  </table>\n";

	#print "<pre>" . print_r($siteList, 1) . "</pre>\n";
	
}


function gt_renewals_add_dashboard_widget() {

        $flag = 0;
        $blogID = get_current_blog_id();
        $option = 'gt_' . $blogID . '_site_renewed';
		$renewed = get_site_option($option, '');

        if (!$renewed && !isset($_REQUEST['gt-renew-site'])) {
 		wp_add_dashboard_widget( 'gt_renewals', 'Time to Renew Your Site', 'gt_renewals_content' );
        	$flag = 1;
        
	// For when renewal is first submitted - save and display a thank you message
        } else if (($renewed == '') && isset($_REQUEST['gt-renew-site']) && ($_REQUEST['gt-renew-site'] == 'Y')) {
		$user = wp_get_current_user();
		update_site_option($option, $blogID . '|' . date('Y-m-d H:i:s') . "|" . $user -> user_login);
 		wp_add_dashboard_widget( 'gt_renewals', 'Thank You for Renewing Your Site', 'gt_renewals_content' );
        	$flag = 1;

        // If renewal was previously submitted, show that it's already in the system
        }  else if ($renewed) {
        	wp_add_dashboard_widget( 'gt_renewals', 'Site Renewal Confirmed', 'gt_renewals_content_confirmed' );
		$flag = 1;
        }

        if ($flag) {

	 	// Globalize the metaboxes array, this holds all the widgets for wp-admin
	 
	 	global $wp_meta_boxes;
	
	 	// Get the regular dashboard widgets array 
	 	// (which has our new widget already but at the end)
	 
	 	$normal_dashboard = $wp_meta_boxes['dashboard']['normal']['core'];
	 	
	 	// Backup and delete our new dashboard widget from the end of the array
	 
	 	$example_widget_backup = array( 'gt_renewals' => $normal_dashboard['gt_renewals'] );
	 	unset( $normal_dashboard['gt_renewals'] );
	 
	 	// Merge the two arrays together so our widget is at the beginning
	 
	 	$sorted_dashboard = array_merge( $example_widget_backup, $normal_dashboard );
	 
	 	// Save the sorted array back into the original metaboxes 
	 
	 	$wp_meta_boxes['dashboard']['normal']['core'] = $sorted_dashboard;
	}
}


function gt_renewals_content() {

	if (isset($_REQUEST['gt-renew-site']) && ($_REQUEST['gt-renew-site'] == 'Y')) {
		print "<p>If you should decide you no longer need this site in the future, you can delete it at any time by using the <strong>Delete Site</strong> option on the <strong>Tools</strong sub-menu of the left-hand administrative menu bar.</p>\n";

	} else {
		$options = get_network_option(1, 'gt_renewals_config', array('enabled' => 'N', 'end_date' => ''));

		print "<p>Periodically, we ask users to renew their sites so that we know which ones are still being used.</p>\n";
		print "<p>You have until " . $options['end_date'] . " to renew your site.  If you do not renew by then, your site may be deleted after that date.</p>\n";
		print "<p class=\"gt-button-gold\" style=\"text-align: center;\">\n";
	        print "  <a href=\"?gt-renew-site=Y\">Renew this Site</a>\n";
		print "  <a href=\"ms-delete-site.php\">Delete this Site</a>\n";
	        print "</p>\n";
	}

}

function gt_renewals_content_confirmed() {
	global $wpdb;
	
	
	$blogID = get_current_blog_id();
	
	if ($renewal = $wpdb->get_results( "SELECT meta_value FROM wp_sitemeta WHERE meta_key LIKE 'gt_". $blogID . "_site_renewed'" )) {
        	$details = explode('|', $renewal[0] -> meta_value);
		if ($user = $wpdb->get_results( "SELECT display_name FROM wp_users WHERE user_login LIKE '" . $details[2] . "'" )) {
            		$userName = $user[0] -> display_name;
          	}
		$rawDate = strtotime($details[1]);

		print "<p style='font-size: 120%;'>Thank you for renewing your PWP website!</p>\n";
                print "<p style='font-size: 120%;'>  Your site was renewed on <strong>" .  date('M j, Y', $rawDate) . "</strong> by <strong>" . (isset($userName) ? $userName : $details[2]) . "</strong>.</p>";
			
	} else {
		print "<p>Error: Unable to find site renewal details!</p>\n";
	}
}


/*****************************************************
------------------------------------------------------
function gt_renewals_columns
------------------------------------------------------

Shows column on 'All Sites'.
******************************************************/
function gt_renewals_columns( $columns ) {
	$columns[ 'renewal' ] = __( 'Renewal' );
	return $columns;
}

add_filter( 'wpmu_blogs_columns', 'gt_renewals_columns' );


/*****************************************************
------------------------------------------------------
function gt_renewals_field 
------------------------------------------------------

Processes the renewal column on {WPMU}/Sites/All Sites
******************************************************/
function gt_renewals_field( $column, $blog_id ) {
	global $wpdb;
	static $gtrenewal = false;
	
	if ( $column == 'renewal' ) {
		if ( $gtrenewal === false ) {
			
			// Query to pull renewal data from DB
			$wpdb->gtrenewtable = $wpdb->base_prefix . 'sitemeta';
			$work = $wpdb->get_results( "SELECT meta_value FROM {$wpdb->gtrenewtable} WHERE meta_key LIKE 'gt_%_renewed'" );

			$gtrenewal = array();
			
			if($work){
				foreach ($work as $data) {
					$values = explode('|', $data -> meta_value);
					// values[0] - blog ID
					// values[1] - renewal date
					// values[2] - user account
					$gtrenewal[ $values[0] ][] = $values[1];
				}
			}
		}
		
		if( !empty( $gtrenewal[ $blog_id ] ) && is_array( $gtrenewal[ $blog_id ] ) ) {
			foreach( $gtrenewal[ $blog_id ] as $datestamp ) {
				echo $datestamp . '<br />';
			}
		}
	}
}
add_action( 'manage_blogs_custom_column', 'gt_renewals_field', 1, 3 );
add_action( 'manage_sites_custom_column', 'gt_renewals_field', 1, 3 );


/*****************************************************
------------------------------------------------------
function gt_renewals_user_columns
------------------------------------------------------

Shows column on 'All Sites' for which user.
******************************************************/
function gt_renewals_user_columns( $columns ) {
	$columns[ 'renewal-user' ] = __( 'Renewal User' );
	return $columns;
}

add_filter( 'wpmu_blogs_columns', 'gt_renewals_user_columns' );


/*****************************************************
------------------------------------------------------
function gt_renewals_field 
------------------------------------------------------

Processes the renewal column on {WPMU}/Sites/All Sites
******************************************************/
function gt_renewals_user_field( $column, $blog_id ) {
	global $wpdb;
	static $gtrenewal = false;
	
	if ( $column == 'renewal-user' ) {
		if ( $gtrenewal === false ) {
			
			// Query to pull renewal data from DB
			$wpdb->gtrenewtable = $wpdb->base_prefix . 'sitemeta';
			$work = $wpdb->get_results( "SELECT meta_value FROM {$wpdb->gtrenewtable} WHERE meta_key LIKE 'gt_%_renewed'" );

			$gtrenewal = array();
			
			if($work){
				foreach ($work as $data) {
					$values = explode('|', $data -> meta_value);
					// values[0] - blog ID
					// values[1] - renewal date
					// values[2] - user account
					$gtrenewal[ $values[0] ][] = $values[2];
				}
			}
		}
		
		if( !empty( $gtrenewal[ $blog_id ] ) && is_array( $gtrenewal[ $blog_id ] ) ) {
			foreach( $gtrenewal[ $blog_id ] as $username ) {
				$user = get_user_by( 'login', $username );
				echo '<a href="'. get_edit_user_link( $user->ID ) .'">'. esc_attr( $username ) .'</a>';
			}
		}
	}
}
add_action( 'manage_blogs_custom_column', 'gt_renewals_user_field', 1, 3 );
add_action( 'manage_sites_custom_column', 'gt_renewals_user_field', 1, 3 );



function gt_renewals_enqueue() {

  wp_enqueue_style('gt_renewals_css', plugins_url('gt-renewals.css', __FILE__));

}

function gt_renewals_init() {

  $options = get_network_option(1, 'gt_renewals_config', array('enabled' => 'N', 'end_date' => ''));

  new gt_renewals_optionspage('gt_renewals_config', 'gt-renewals-config', $options);

  add_action('admin_enqueue_scripts', 'gt_renewals_enqueue');

  if ($options['enabled'] == 'Y') {
    add_action('wp_dashboard_setup', 'gt_renewals_add_dashboard_widget');
  }

  add_action('wp_network_dashboard_setup', 'gt_renewals_add_network_dashboard_widget');

}


gt_renewals_init();


?>
