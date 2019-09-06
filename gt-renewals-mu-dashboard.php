<?php

/**
 * gt_renewals_add_network_dashboard_widget()
 *
 * Sets up network dashboard widget.
 *
 * @return void
 */
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

/**
 * gt_renewals_network()
 *
 * Prints out network list after ksorting.
 *
 * @return void
 */
function gt_renewals_network() {
	global $wpdb;

	# Convert into WP_Meta_Query https://developer.wordpress.org/reference/classes/wp_meta_query/?
	$siteList = $wpdb->get_results("SELECT meta_value FROM $wpdb->sitemeta WHERE meta_key LIKE 'gt_%_renewed'");
	if($siteList) {
		$siteListClean = array();
		foreach ($siteList as $data) {
			// array[id][values], to sort by array[sid][1]
			$values = explode('|', $data -> meta_value);
			$siteListClean[$values[0]] = $values;
		}

		// sort by date descending $array[1]
		usort($siteListClean, function ($a, $b) {

			if ($a[1] == $b[1]) {
				return 0;
			}
			return ($a[1] > $b[1]) ? -1 : 1;

		});

		// truncate to last 10
		while(count($siteListClean) > 10) array_shift($siteListClean);

		// initializing output
		$output = array();

		// build table body from $output
		foreach ($siteListClean as $data) {
			array_push($output,"<tr><th scope='row'>" . $data[0] . "</th><td>" . $data[1] . "</td><td>" . $data[2] . "</td></tr>\n");
		}

		// table output
		print "<p>So far, <strong>" . count($siteList) . "</strong> out of " . get_blog_count() . " websites have been renewed during this renewal period.</p>\n";
		print "<h3><strong>Last 10 website renewals</strong></h3>\n";
		print "<table border=1 cellpadding=4 width=100%>\n";
		print "<tr><th align=left scope='col'>Site ID</th><th align=left scope='col'>Date</th><th align=left scope='col'>User</th></tr>\n";
		print implode("\n", $output);
		print "</table>\n";
		print "<p class=\"gt-button-gold\"><a href=\"/wp-admin/network/settings.php?page=gt-renewals-config\">Settings</a></p>\n\n";
		#print "<pre>" . print_r($siteList, 1) . "</pre>\n";
	}
	else {
		print "<p>So far, <strong>0</strong> out of " . get_blog_count() . " websites have been renewed during this renewal period.</p>\n";

	}
}

/**
 * gt_renewals_network_columns()
 *
 * Adds columns to network -> sites.
 *
 * @param [type] $columns
 * @return void
 */
function gt_renewals_network_columns( $columns ) {
	$columns[ 'renewal' ] = __( 'Renewal' );
	return $columns;
}

add_filter( 'wpmu_blogs_columns', 'gt_renewals_network_columns' );

/**
 * gt_renewals_network_renewed()
 *
 * Adds field to Network -> Sites.
 *
 * @param [type] $column
 * @param [type] $blog_id
 * @return void
 */
function gt_renewals_network_renewed( $column, $blog_id ) {
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
add_action( 'manage_blogs_custom_column', 'gt_renewals_network_renewed', 1, 3 );
add_action( 'manage_sites_custom_column', 'gt_renewals_network_renewed', 1, 3 );

/**
 * gt_renewals_network_user_columns()
 *
 * Attaches which user renewed in its own column.
 *
 * @param [type] $columns
 * @return void
 */
function gt_renewals_network_user_columns( $columns ) {
	$columns[ 'renewal-user' ] = __( 'Renewal User' );
	return $columns;
}

add_filter( 'wpmu_blogs_columns', 'gt_renewals_network_user_columns' );

/**
 * gt_renewals_user_field()
 *
 * Processes the renewal column on {WPMU}/Sites/All Sites
 *
 * @param [type] $column
 * @param [type] $blog_id
 * @return void
 */
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

?>