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
 * Display MyBibleReading Fields in User Profile
 **/
function jswj_mbr_user_profile_fields( $user ) {

	if( is_object($user) ) {
		$user_id = $user->ID;
	} else {
		$user_id = $user;
	}

	$mbr = get_option('jswj-my-bible-reading');
	$frequency = $mbr['reminder_frequency'];
	$versions = $mbr['versions'];
	$plans = $mbr['plans'];
	$reminder_times = $mbr['reminder_times'];

	// Get User's Selected Bible Version
	$bible_version = get_the_author_meta( 'mbr_bible_version', $user_id );
	if( empty( $bible_version ) ) { $bible_version = $mbr['default_version']; }
	foreach( $versions as $key => $version ) { $versions[$key]['is_selected'] = ''; }
	$versions[$bible_version]['is_selected'] = ' selected';

	// Get User's Selected Reading Plan
	$reading_plan = get_the_author_meta( 'mbr_plan', $user_id );
	if( empty( $reading_plan ) ) { $reading_plan = $mbr['default_plan']; }
	foreach( $plans as $key => $plan ) { $plans[$key]['is_checked'] = ''; }
	$plans[$reading_plan]['is_checked'] = ' checked';

	// Get User's Selected Start Date
	$start_date = get_the_author_meta( 'mbr_start_date', $user_id );
	if( empty( $start_date ) ) { $start_date = $mbr['mbr_start_date']; }

	// Get User's Reminder Info
	$mbr_reminder_checkbox = get_the_author_meta( 'mbr_reminder_checkbox', $user_id );
	if( is_null( $mbr_reminder_checkbox ) ) { $mbr_reminder_checkbox = '1'; }

	$mbr_reminder_blb_checkbox = get_the_author_meta( 'mbr_reminder_blb_checkbox', $user_id );
	if( is_null( $mbr_reminder_blb_checkbox ) ) { $mbr_reminder_blb_checkbox = '1'; }

	$mbr_reminder_livingwater_checkbox = get_the_author_meta( 'mbr_reminder_livingwater_checkbox', $user_id );
	if( is_null( $mbr_reminder_livingwater_checkbox ) ) { $mbr_reminder_livingwater_checkbox = '1'; }

	$reminder_email = get_the_author_meta( 'mbr_reminder_email', $user_id );
	if( empty( $reminder_email ) ) { $reminder_email = get_the_author_meta( 'user_email', $user_id ); }

	$reminder_hour = get_the_author_meta( 'mbr_reminder_hour', $user_id );
	if( !empty( $reminder_hour ) ) {
		foreach( $reminder_times['hours'] as $key => $hour ) { $hour[$key]['is_checked'] = ''; }
		$reminder_times['hours'][$reminder_hour]['is_checked'] = ' checked';
		$reminder_times['hours'][$reminder_hour]['is_selected'] = ' selected';
	} else {
		$reminder_times['hours']['08']['is_checked'] = ' checked';
		$reminder_times['hours']['08']['is_selected'] = ' selected';
	}

	$reminder_minute = get_the_author_meta( 'mbr_reminder_minute', $user_id );
	if( !empty( $reminder_minute ) ) {
		foreach( $reminder_times['minutes'] as $key => $minute ) { $minute[$key]['is_checked'] = ''; }
		$reminder_times['minutes'][$reminder_minute]['is_checked'] = ' checked';
		$reminder_times['minutes'][$reminder_minute]['is_selected'] = ' selected';
	} else {
		$reminder_times['minutes']['00']['is_checked'] = ' checked';
		$reminder_times['minutes']['00']['is_selected'] = ' selected';
	}

	$timezone = get_the_author_meta( 'mbr_timezone', $user_id );
	if( empty( $timezone ) ) { $timezone = 'America/Los_Angeles'; }

	/**
	 * Display My Bible Reading Options Form
	 **/
	echo '<h3>My Bible Reading</h3>';
	echo '<span class="description">Customize Your Bible Reading Plan</span>';
	echo '<table class="form-table">';

		// BIBLE VERSION ROW
		echo '<tr>';
			echo '<th><label for="mbr_bible_version">Bible Version</label></th>';
			echo '<td>';

				echo '<select name="mbr_bible_version" id="mbr_bible_version">';

				foreach( $versions as $key => $version ) {
					echo '<option value="'.$key.'"' . $version['is_selected'] . '>'
						. $version['name'] . '</option>';
				}
				echo '</select>';

			echo '</td>';
		echo '</tr>';

		// READING PLAN ROW
		echo '<tr>';
			echo '<th><label for="mbr_plan">Reading Plan</label></th>';
			echo '<td>';

				foreach( $plans as $key => $plan ) {
					echo '<input type="radio" name="mbr_plan" value="'
						. $key . '"' . $plan['is_checked'] . '> <span class="mbr_plan_name">'
						. $plan['name'] . '</span>';
					echo '<span class="mbr_plan_pace"> ( ' . $plan['pace'] . ' )</span><br>';
					echo '<span class="mbr_plan_description">' . $plan['description'] . '</span><br><br>';

				}

			echo '</td>';
		echo '</tr>';

		// READING PLAN START ROW
		echo '<tr>';
			echo '<th><label for="mbr_start_date">Reading Plan Start Date</label></th>';
			echo '<td>';

				echo '<input type="date" name="mbr_start_date" value="'
					. $start_date . '"> <br>';

			echo '</td>';
		echo '</tr>';

		// RECEIVE REMINDER ROW
		if( 'never' !== $frequency ) {
			echo '<tr>';
				echo '<th><label for="mbr_reminder_checkbox">Receive Daily Reminders</label></th>';
				echo '<td>';
					echo "<input type='checkbox' name='mbr_reminder_checkbox' value='1' "
						.checked(1, $mbr_reminder_checkbox, false)." onchange='jswj_mbr_reminder_checkbox(this)' />";
				echo '</td>';
			echo '</tr>';

			// Add Class If Reminder Checkbox Is Checked
			$mbr_reminder_rows_class = 'mbr_reminder_row';
			if( '1' == $mbr_reminder_checkbox ) {
				$mbr_reminder_rows_class .= ' mbr_show_reminder_fields';
			}

			// REMINDER OPTIONS ROW
			echo '<tr class="' . $mbr_reminder_rows_class . '">';
				echo '<th><label for="mbr_reminder_blb_checkbox">Reminder Options</label></th>';
				echo '<td>';
					echo "<p><input type='checkbox' name='mbr_reminder_blb_checkbox' value='1' "
						.checked(1, $mbr_reminder_blb_checkbox, false)." /> Include Links To Blue Letter Bible</p>";
					echo "<p><input type='checkbox' name='mbr_reminder_livingwater_checkbox' value='1' "
						.checked(1, $mbr_reminder_livingwater_checkbox, false)." /> Include Resource Links From Living Water</p>";
				echo '</td>';
			echo '</tr>';


			// REMINDER EMAIL ROW
			echo '<tr class="' . $mbr_reminder_rows_class . '">';
				echo '<th><label for="mbr_reminder_email">Send Reminder Emails To</label></th>';
				echo '<td>';
					echo '<input type="email" name="mbr_reminder_email" value="'
						. $reminder_email . '" size="50"> <br>';
				echo '</td>';
			echo '</tr>';

			do_action( 'jswj_mbr_more_backend_user_reminder_fields', $user_id, $mbr_reminder_rows_class );

			// REMINDER TIME ROW
			echo '<tr class="' . $mbr_reminder_rows_class . '">';
				echo '<th><label for="mbr_reminder_hour">Reminder Time</label></th>';
				echo '<td>';

					echo '<select name="mbr_reminder_hour" id="mbr_reminder_hour">';
					foreach( $reminder_times['hours'] as $key => $hour ) {
						echo '<option value="'.strval($key).'"' . $hour['is_selected'] . '>'
							. $hour['hour'] . '</option>';
					}
					echo '</select>';

					echo '<select name="mbr_reminder_minute" id="mbr_reminder_minute">';
					foreach( $reminder_times['minutes'] as $key => $minute ) {
						echo '<option value="'.strval($key).'"' . $minute['is_selected'] . '>'
							. $minute['minute'] . '</option>';
					}
					echo '</select><br>';

					echo '<input list="mbr_timezones" name="mbr_timezone" value="'
						. $timezone . '" size="50"> <br>';

					echo '<datalist id="mbr_timezones">';
						$php_timezones = DateTimeZone::listIdentifiers();
						foreach( $php_timezones as $php_timezone ) {
							echo '<option value="' . $php_timezone . '">';
						}
					echo '</datalist>';

					echo '<br>';
					if( jswj_valid_timezone( $timezone ) ) {
						$last_reminded = intval( get_the_author_meta( 'mbr_last_reminded', $user_id ) );
						$next_reminder = intval( get_the_author_meta( 'mbr_next_reminder', $user_id ) );
						echo 'Last Reminded: ' . date("l jS \of F Y h:i:s A",
							$last_reminded + timezone_offset_get( timezone_open( $timezone ), new DateTime() ) ) . '<br>';
						echo 'Next Reminder: ' . date("l jS \of F Y h:i:s A",
							$next_reminder + timezone_offset_get( timezone_open( $timezone ), new DateTime() ) ) . '<br>';
					}

				echo '</td>';
			echo '</tr>';
		} // END If Reminder Schedule

	echo '</table>';

} // END jswj_mbr_user_profile_fields()
add_action( 'show_user_profile', 'jswj_mbr_user_profile_fields', 999 );
add_action( 'edit_user_profile', 'jswj_mbr_user_profile_fields', 999 );


/**
 * Update user profile with submitted data
 **/
function jswj_mbr_save_user_profile_fields( $user_id ) {
	if ( !current_user_can( 'edit_user', $user_id ) ) { return false; }

	$post_data = jswj_sanitize_validate_mbr_data( $_POST );

	do_action( 'jswj_mbr_save_user_profile_fields_before', $user_id, $post_data );

	foreach( $post_data as $key => $data_value ) {
		update_user_meta( $user_id, $key, $data_value );
	}

	jswj_mbr_set_next_reminder( $user_id );

} // END jswj_mbr_save_user_profile_fields()
add_action( 'personal_options_update', 'jswj_mbr_save_user_profile_fields' );
add_action( 'edit_user_profile_update', 'jswj_mbr_save_user_profile_fields' );

