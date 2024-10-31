<?php
/**
 * My Bible Reading
 *
 * @package		my-bible-reading
 * @author		Jerry Simmons <jerry@ferventsolutions.com>
 * @copyright	2017 Jerry Simmons
 * @license		GPL-2.0+
 *
 **/

if ( ! defined( 'ABSPATH' ) ) { exit; }


/**
 * Register Tool Page
 **/
function jswj_register_toolpage() {
	$hook = add_management_page(
		'My Bible Reading Settings',
		'My Bible Reading',
		'manage_options',
		'jswj_mbr_menu_slug',
		'jswj_display_toolpage', '' );
	add_action( "load-$hook", 'jswj_mbr_defaults_load' );
}
add_action( 'admin_menu', 'jswj_register_toolpage' );


/**
 * Display Tool Page
 **/
function jswj_display_toolpage() {

	echo '<h1>My Bible Reading - Settings</h1>';

	// Sanitize & Save POST Data
	if( isset( $_POST['mbr_settings_form'] ) ) {
		jswj_save_toolpage( $_POST );
	}

	$mbr = get_option('jswj-my-bible-reading');
	$mbr['versions'][$mbr['default_version']]['is_selected'] = ' selected';
	$mbr['plans'][$mbr['default_plan']]['is_checked'] = ' checked';
	$mbr['reminder_frequencies'][$mbr['reminder_frequency']]['is_selected'] = ' selected';

	/**
	 * Display My Bible Reading Options Form
	 **/
	echo '<div class="wj_getsettings_form">';
		echo '<form action="" method="POST">';
			echo '<input type="hidden" value="true" name="mbr_settings_form" id="mbr_settings_form" />';
			echo '<table class="form-table">';

				// REMINDER MESSAGE
				echo '<tr>';
					echo '<th style="text-align: right">'
						.'<label>Reminder Announcement Message</label></th>';
					echo '<td>';
						echo '<textarea name="mbr_reminder_message" cols="80">'
							. esc_textarea( $mbr['mbr_reminder_message'] ). '</textarea>';
					echo '</td>';
				echo '</tr>';

				// CRON FREQUENCY
				echo '<tr>';
					echo '<th style="text-align: right">'
						.'<label>Cron Job Frequency</label></th>';
					echo '<td>';

						echo '<select name="mbr_reminder_frequency" id="mbr_settings_form">';

						foreach( $mbr['reminder_frequencies'] as $key => $frequency ) {
							echo '<option value="'.$key.'"' . $frequency['is_selected'] . '>'
								. esc_html( $frequency['name'] ) . '</option>';
						}
						echo '</select>';
						echo '<br>Select the frequency to check for reminders.';
						echo '<br>WordPress cron jobs are not exact, consider using a cron service if you want more accurate reminders.';

					echo '</td>';
				echo '</tr>';


				// DEFAULT VALUES
				echo '<tr>';
					echo '<th colspan="2"><h3>Set Default Values</h3></th>';
				echo '</tr>';
				echo '<tr>';
					echo '<th style="text-align: right"><label for="mbr_bible_version">Default Bible Version</label></th>';
					echo '<td>';

						echo '<select name="mbr_bible_version" id="mbr_settings_form">';

						foreach( $mbr['versions'] as $key => $version ) {
							echo '<option value="'.$key.'"' . $version['is_selected'] . '>'
								. esc_html( $version['name'] ) . '</option>';
						}
						echo '</select>';

					echo '</td>';
				echo '</tr>';

				echo '<tr>';
					echo '<th style="text-align: right"><label for="mbr_plan">Default Reading Plan</label></th>';
					echo '<td>';

						foreach( $mbr['plans'] as $key => $plan ) {
							echo '<input type="radio" name="mbr_plan" value="'
								. $key . '"' . $plan['is_checked'] . '> <span class="mbr_default_plan">'
								. esc_html( $plan['name'] ) . '</span><br>';
						}

					echo '</td>';
				echo '</tr>';

				echo '<tr>';
					echo '<th style="text-align: right"><label for="mbr_start_date">Default Start Date</label></th>';
					echo '<td>';

						echo '<input type="date" name="mbr_start_date" value="'
							. esc_html( $mbr['mbr_start_date'] ) . '"> <br>';

						$datetime1 = date_create($mbr['mbr_start_date']);
						$datetime2 = date_create('now');
						$interval = date_diff($datetime1, $datetime2);
						$diff = intval($interval->format('%R%a'));
						if( 0 > $diff ) {
							echo esc_html( 'Starts in ' . $diff * -1 . ' days' );
						} else {
							echo esc_html( 'Started ' . $diff . ' days ago' );
						}


					echo '</td>';
				echo '</tr>';

				// CUSTOM READING PLAN INFO
				echo '<tr>';
					echo '<th colspan="2"><h3>Custom Reading Plan Information</h3></th>';
				echo '</tr>';

						foreach( $mbr['plans'] as $key => $plan ) {
							echo '<tr>';
								echo '<td style="padding: 0px 10px; text-align: right;">';
									echo '<h4 style="margin: 0px">' . esc_html( $plan['name'] ) . '</h4>';
								echo '</td>';
							echo '</tr>';


							echo '<tr>';
								echo '<td style="padding: 0px 10px; text-align: right;">';
									echo 'Plan Pace:';
								echo '</td>';
								echo '<td style="padding: 0px 10px">';
									echo '<textarea name="mbr_plan_pace_' . $key . '" cols="80">'
										. esc_textarea( $plan['pace'] ) . '</textarea>';
								echo '</td>';
							echo '</tr>';

							echo '<tr>';
								echo '<td style="padding: 0px 10px; text-align: right;">';
									echo 'Plan Description:';
								echo '</td>';
								echo '<td style="padding: 0px 10px 20px">';
									echo '<textarea name="mbr_plan_description_' . $key . '" cols="80">'
										. esc_textarea( $plan['description'] ) . '</textarea>';
								echo '</td>';
							echo '</tr>';

						} // END foreach $mbr['plans']

			echo '</table>';
			submit_button('Save Settings');
		echo '</form>';
	echo '</div>';

} // END jswj_display_toolpage()


/**
 * Save Tool Page
 **/
function jswj_save_toolpage( $post_data ) {
	$mbr = get_option('jswj-my-bible-reading');
	$safe_data = jswj_sanitize_validate_mbr_data( $post_data );

	// Save Reminder Frequency
	$mbr['reminder_frequency'] = sanitize_key( $post_data['mbr_reminder_frequency'] );


	// Save Reminder Message
	$mbr['mbr_reminder_message'] = sanitize_textarea_field($post_data['mbr_reminder_message']);

	// Save Default Version
	$mbr['default_version'] = sanitize_key( $post_data['mbr_bible_version'] );

	// Save Default Plan
	if( isset( $post_data['mbr_plan'] ) ) {
		$mbr['default_plan'] = sanitize_key( $post_data['mbr_plan'] );
	}

	// Save Custom Descriptions
	foreach( $mbr['plans'] as $key => $plan ) {
		$mbr['plans'][$key]['pace'] = sanitize_textarea_field( $post_data['mbr_plan_pace_' . $key] );
		$mbr['plans'][$key]['description'] = sanitize_textarea_field( $post_data['mbr_plan_description_' . $key] );
	}

	// Save Start Date
	if( isset( $post_data['mbr_start_date'] ) ) {
		$test_date = sanitize_text_field( $post_data['mbr_start_date'] );
		if( false !== strtotime( $test_date ) ) {
			$mbr['mbr_start_date'] = sanitize_text_field( $post_data['mbr_start_date'] );
		}
	}

	update_option( 'jswj-my-bible-reading', $mbr );

	// Reset Cron Job
	$timestamp = wp_next_scheduled( 'mbr_check_all_reminders_event' );
	wp_unschedule_event( $timestamp, 'mbr_check_all_reminders_event', array() );

} // END jswj_save_toolpage()
