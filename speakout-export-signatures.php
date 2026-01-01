<?php

/*

Plugin Name: Speakout Export Signatures

Description: Custom plugin

Author: Faraz Ahmed

Author URI: http://farazthewebguy.com

Version: 1.1

*/



// block direct access

defined('ABSPATH') or die('No script kiddies please!');



// hook for adding admin menus

add_action('admin_menu', 'export_signatures_page', 11);

function export_signatures_page() {

	// add menu page

	$hook_suffix = add_submenu_page('dk_speakout', 'Export Signatures', 'Export Signatures', 'manage_options', 'export-signatures', 'export_signatures');

	

	// enqueue google ajax api

	add_action('admin_print_scripts-'.$hook_suffix, 'export_signatures_admin_scripts');

}



// enqueue google ajax api

function export_signatures_admin_scripts() {
	// core dependency for datepicker
	wp_enqueue_script('jquery-ui-datepicker');

	// your plugin script, depends on both jquery and datepicker
	wp_enqueue_script(
		'export-signatures-js',
		plugin_dir_url(__FILE__) . 'speakout-export-signatures.js',
		array('jquery', 'jquery-ui-datepicker'),
		'1.0',
		true
	);

	// datepicker needs some CSS; WP core no longer ships a default UI theme
	wp_enqueue_style(
		'jquery-ui-css',
		'https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css',
		array(),
		'1.13.2'
	);
}


// export csv

add_action('plugins_loaded', 'export_signatures_export');

function export_signatures_export() {
	
	ob_start();

	// start export process

	if (isset($_POST['export_signatures_export'])) {
		
		set_time_limit(0);

		global $wpdb;

		global $table_prefix;

		$start_date = sanitize_text_field($_POST['start_date'].' 00:00:00');

		$end_date = sanitize_text_field($_POST['end_date'].' 23:59:59');

		
		if (isset($_POST['people'])) {
			$query = "SELECT first_name,last_name,email,postcode,petition_ip, date,COUNT(email) AS signatures FROM `wp_dk_speakout_signatures`  where date >= '$start_date' and date <= '$end_date' GROUP BY email HAVING signatures>=100";
		}
		else {
			$query = "SELECT first_name,last_name,email,postcode,petition_ip,date FROM `wp_dk_speakout_signatures`  where date >= '$start_date' and date <= '$end_date' ORDER BY `wp_dk_speakout_signatures`.`id`  DESC";
		}

		$signatures = $wpdb->get_results($query);
		
		$result[] = array('first_name', 'last_name', 'email', 'postcode', 'date', 'signatures', 'ip_address');

		foreach ($signatures as $signature) {

			$result[] = array($signature->first_name, $signature->last_name, $signature->email, $signature->postcode, $signature->date, $signature->signatures, $signature->petition_ip);
		}

		header('Content-Type: text/csv');

    	header('Content-Disposition: attachment;filename=exportsignatures'.str_replace('-', '', $_POST['start_date']).'-'.str_replace('-', '', $_POST['end_date']).'.csv');

		

		$fp = fopen('php://output', 'w');

		foreach ($result as $data) {

			fputcsv($fp, $data);

		}

		fclose($fp);

		$contLength = ob_get_length();

        header('Content-Length: '.$contLength);

		exit;

	}

}



// menu page

function export_signatures() { 

	echo '<div class="wrap">';

		echo '<h2>Export Signatures</h2>';

		echo '<div style="margin-top: 10px;">';

			echo '<form method="post" action="">';

				echo '<input type="hidden" name="export_signatures_export">';

				echo '<table class="form-table">';

					echo '<tr>';

						echo '<th scope="row"><label for="start_date">Start Date</label></th>';

						echo '<td><input type="text" class="regular-text" value="" required="" id="start_date" name="start_date"><p class="description">Time: 00:00:00</p></td>';

					echo '</tr>';

					echo '<tr>';

						echo '<th scope="row"><label for="end_date">End Date</label></th>';

						echo '<td><input type="text" class="regular-text" value="" required="" id="end_date" name="end_date"><p class="description">Time: 11:59:59</p></td>';

					echo '</tr>';
					
					echo '<tr>';

						echo '<th scope="row"><label for="end_date">Export only people with 100+ signatures within the above date range?</label></th>';

						echo '<td><input type="checkbox" class="regular-text" value="1" id="people" name="people"></td>';

					echo '</tr>';

				echo '</table>';

				echo '<p class="submit"><input type="submit" value="Export CSV" class="button button-primary" id="submit" name="submit"></p>';

			echo '</form>';

		echo '</div>';

	echo '</div>';

}