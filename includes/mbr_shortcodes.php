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
 * Displays Today's Reading using plugin defaults, or User Settings
 *
 *
 * @return HTML div with today's reading and links
 **/
add_shortcode( 'mbr_display_todays_reading' , 'jswj_mbr_display_todays_reading' );
function jswj_mbr_display_todays_reading( $shortcode_options ) {

	$user_id = get_current_user_id();

	$shortcode_defaults = array(
		'show_date'		=> 'true',
		'heading_tag'	=> 'h4',
		'show_resource'	=> 'true'
	);

	$shortcode_options = shortcode_atts( $shortcode_defaults, $shortcode_options );
	$show_date = sanitize_text_field( $shortcode_options['show_date'] );
	$heading_tag = sanitize_text_field( $shortcode_options['heading_tag'] );

	$show_resource = sanitize_text_field( $shortcode_options['show_resource'] );
	$mbr_reminder_livingwater_checkbox = get_the_author_meta( 'mbr_reminder_livingwater_checkbox', $user_id );
	if( '1' !== $mbr_reminder_livingwater_checkbox ) { $show_resource = 'false'; }

	$mbr = get_option('jswj-my-bible-reading');
	$versions = $mbr['versions'];
	$plans = $mbr['plans'];

	// Get User's Selected Bible Version
	$bible_version = get_user_meta( $user_id, 'mbr_bible_version', true );
	if( empty( $bible_version ) ) { $bible_version = $mbr['default_version']; }

	// Get User's Selected Reading Plan
	$reading_plan = get_user_meta( $user_id, 'mbr_plan', true );
	if( empty( $reading_plan ) ) { $reading_plan = $mbr['default_plan']; }

	// Get User's Selected Start Date
	$start_date = get_user_meta( $user_id, 'mbr_start_date', true );
	if( empty( $start_date ) ) { $start_date = $mbr['mbr_start_date']; }

	$timezone = get_the_author_meta( 'mbr_timezone', $user_id );
	if( empty( $timezone ) ) { $timezone = 'America/Los_Angeles'; }
	$todays_date = date_create( 'now', timezone_open($timezone) );

	$todays_reading = jswj_mbr_get_todays_reading_record( $reading_plan, $start_date );

	/**
	 * Prepare Text To Return
	 **/
	$reading_text =  '<div class="jswj_mbr_display_todays_reading">';
		if( 'true' == $show_date ) {
			$reading_text .= '<p class="mbr_todays_date">' . date_format( $todays_date, 'l, F jS, Y' ) . '</p>';
		}
		$reading_text .= '<'.$heading_tag.'>Today\'s Reading:<br>' . $todays_reading['overview'] . '</'.$heading_tag.'>';

		foreach( $todays_reading['chapter_array'] as $key => $chapter ) {
			$link = jswj_mbr_get_blb_link( $todays_reading['chapter_array'][$key], $bible_version );
			$reading_text .= '<p><a href="' . esc_url( $link ) . '" target="_blank" class="mbr_todays_reading_link">'
				.$todays_reading['chapter_array'][$key] . '</a></p>';

			if( 'true' == $show_resource ) {
				$resources = jswj_mbr_get_cclw_link( $todays_reading['chapter_array'][$key] );
				$reading_text .= '<p class="mbr_resource_link"><a href="' . esc_url( $resources[0] ) . '" target="_blank">'
					.'Learn about the book of ' . sanitize_text_field( $resources[1] ) . '</a></p>';
			}

		}

	$reading_text .= '</div>';

	return $reading_text;
} // END jswj_mbr_display_todays_reading()


/**
 * Gets Today's Reading For A Specific User For Sending Reminder Messages
 *
 * @return Plain Text reading information
 **/
function jswj_mbr_display_todays_reading_user( $user_id ) {

	$mbr = get_option('jswj-my-bible-reading');
	$versions = $mbr['versions'];
	$plans = $mbr['plans'];

	// Get User's Selected Bible Version
	$bible_version = get_user_meta( $user_id, 'mbr_bible_version', true );
	if( 0 == $user_id ) { $bible_version = $mbr['default_version']; }

	// Get User's Selected Reading Plan
	$reading_plan = get_user_meta( $user_id, 'mbr_plan', true );
	if( 0 == $user_id ) { $reading_plan = $mbr['default_plan']; }

	// Get User's Selected Start Date
	$start_date = get_user_meta( $user_id, 'mbr_start_date', true );
	if( 0 == $user_id ) { $start_date = $mbr['mbr_start_date']; }

	$mbr_reminder_blb_checkbox = get_the_author_meta( 'mbr_reminder_blb_checkbox', $user_id );
	$mbr_reminder_livingwater_checkbox = get_the_author_meta( 'mbr_reminder_livingwater_checkbox', $user_id );

	$timezone = get_the_author_meta( 'mbr_timezone', $user_id );
	if( empty( $timezone ) ) { $timezone = 'America/Los_Angeles'; }
	$todays_date = date_create( 'now', timezone_open($timezone) );

	$todays_reading = jswj_mbr_get_todays_reading_record( $reading_plan, $start_date );

	if( !empty($todays_reading) ) {

		if( false == $todays_reading['random'] ) {
			$reading_text = 'Reading for ' . date_format( $todays_date, 'F jS' ) . '</p>';
			$reading_text .= PHP_EOL . PHP_EOL;
		} else {
			$reading_text = 'Your reading plan has not started yet so here is a random reading for you:';
			$reading_text .= PHP_EOL . PHP_EOL;
		}

		foreach( $todays_reading['chapter_array'] as $key => $chapter ) {
			if( '1' == $mbr_reminder_blb_checkbox ) {
				$link = jswj_mbr_get_blb_link( $todays_reading['chapter_array'][$key], $bible_version );
				$reading_text .= $todays_reading['chapter_array'][$key] . ': ' . esc_url( $link ) . PHP_EOL;
			} else {
				$reading_text .= $todays_reading['chapter_array'][$key] . PHP_EOL;
			}

			// Add Living Water Resource Link If Selected
			if( '1' == $mbr_reminder_livingwater_checkbox ) {
				$resources = jswj_mbr_get_cclw_link( $todays_reading['chapter_array'][$key] );
				$reading_text .= 'Learn about the book of ' . sanitize_text_field( $resources[1] ) . ': '
					.esc_url( $resources[0] ) . PHP_EOL;
			}
			$reading_text .= PHP_EOL;

		}
	} else {
		$reading_text .= PHP_EOL . PHP_EOL;
		$reading_text .= 'No reading plan selected. Please choose a reading plan.';
		$reading_text .= PHP_EOL . PHP_EOL;
	}

	// Add Reminder Message, If Not Sent Previously
	$check_message = get_user_meta( $user_id, 'mbr_reminder_message', true );
	if( $check_message != $mbr['mbr_reminder_message'] ) {
		$reading_text .= PHP_EOL . $mbr['mbr_reminder_message'];
		update_user_meta( $user_id, 'mbr_reminder_message', $mbr['mbr_reminder_message'] );
	}

	$reading_text .= PHP_EOL . 'https://MyBibleReading.com';

	return $reading_text;
} // END jswj_mbr_display_todays_reading_user()


/**
 * Displays Random Reading using plugin defaults, or User Settings
 *
 *
 * @return HTML div with random reading and links
 **/
add_shortcode( 'mbr_display_random_reading' , 'jswj_mbr_display_random_reading' );
function jswj_mbr_display_random_reading( $reading_plan = '' ) {

	$user_id = get_current_user_id();

	$mbr = get_option('jswj-my-bible-reading');
	$versions = $mbr['versions'];
	$plans = $mbr['plans'];

	// Get User's Selected Bible Version
	$bible_version = get_user_meta( $user_id, 'mbr_bible_version', true );
	if( 0 == $user_id ) { $bible_version = $mbr['default_version']; }

	// Get User's Selected Reading Plan
	$param_reading_plan = $reading_plan;
	if( empty( $reading_plan ) ) {
		$reading_plan = get_user_meta( $user_id, 'mbr_plan', true );
		if( 0 == $user_id ) { $reading_plan = $mbr['default_plan']; }
	}

	// Get User's Selected Start Date
	$start_date = get_user_meta( $user_id, 'mbr_start_date', true );
	if( 0 == $user_id ) { $start_date = $mbr['mbr_start_date']; }

	$random_record  = jswj_mbr_get_random_reading_record( $reading_plan );
	$random_day = $random_record[0];
	$random_reading = $random_record[1];

	$reading_text = '<p>Day ' . $random_day . ' Reading:<br>' . $random_reading['overview'] . '</p>';

	foreach( $random_reading['chapter_array'] as $key => $chapter ) {
		$link = jswj_mbr_get_blb_link( $random_reading['chapter_array'][$key], $bible_version );
		$reading_text .= '<p><a href="' . esc_url( $link ) . '" target="_blank">'
			.$random_reading['chapter_array'][$key] .'</a></p>';
	}

	return $reading_text;
} // END jswj_mbr_display_random_reading()


/**
 * Display User Form For Customzing The Reading Plan And Reminders
 *
 * @return HTML div
 **/
add_shortcode( 'mbr_customize_reading_form' , 'jswj_mbr_customize_reading_form' );
function jswj_mbr_customize_reading_form() {

	// Return Reading Plan Information Only If User Is Not Logged In
	if( ! is_user_logged_in() ) {
		return jswj_mbr_display_reading_plans();
	}

	$user_id = get_current_user_id();
	$mbr = get_option('jswj-my-bible-reading');
	$frequency = $mbr['reminder_frequency'];
	$versions = $mbr['versions'];
	$plans = $mbr['plans'];
	$reminder_times = $mbr['reminder_times'];
	$settings_saved = '';

	if( isset( $_POST['mbr_customize'] ) && $_POST['mbr_customize'] == 'customize' ) {

		// Sanitize & Save POST Data
		// Function Located in mbr_user_fields.php
		jswj_mbr_save_user_profile_fields( $user_id );

		$settings_saved = '<div class="settings_saved_message">';
			$settings_saved .= '<h2>Your Settings Have Been Saved!</h2>';
		$settings_saved .= '</div>';
	}

	// Get User's Selected Bible Version
	$bible_version = get_the_author_meta( 'mbr_bible_version', $user_id );
	if( empty( $bible_version ) ) { $bible_version = $mbr['default_version']; }
	$versions[$bible_version]['is_checked'] = ' checked';

	// Get User's Selected Reading Plan
	$reading_plan = get_the_author_meta( 'mbr_plan', $user_id );
	if( empty( $reading_plan ) ) { $reading_plan = $mbr['default_plan']; }
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
	$reminder_mobile = get_the_author_meta( 'mbr_reminder_mobile', $user_id );

	$reminder_hour = get_the_author_meta( 'mbr_reminder_hour', $user_id );
	if( !empty( $reminder_hour ) ) {
		foreach( $reminder_times['hours'] as $key => $hour ) { $hour[$key]['is_selected'] = ''; }
		$reminder_times['hours'][$reminder_hour]['is_selected'] = ' selected';
	} else {
		$reminder_times['hours']['08']['is_selected'] = ' selected';
	}

	$reminder_minute = get_the_author_meta( 'mbr_reminder_minute', $user_id );
	if( !empty( $reminder_minute ) ) {
		foreach( $reminder_times['minutes'] as $key => $minute ) { $minute[$key]['is_selected'] = ''; }
		$reminder_times['minutes'][$reminder_minute]['is_selected'] = ' selected';
	} else {
		$reminder_times['minutes']['00']['is_selected'] = ' selected';
	}

	$timezone = get_the_author_meta( 'mbr_timezone', $user_id );
	if( empty( $timezone ) ) { $timezone = 'America/Los_Angeles'; }


	/**
	 * Display My Bible Reading Options Form
	 **/
	$form_text = '<div id="mbr_customize_form">';
		$form_text .= $settings_saved;

		// Add Form HTML Element If Shown On Front End (via shortcode)
		$form_text .= '<form method="post" action="">';

			$form_text .= '<div class="mbr_select_version">';
				$form_text .= '<h2>Bible Version</h2>';
				foreach( $versions as $key => $version ) {
					$key = esc_html( $key );
					$form_text .= '<div class="mbr_version_wrapper">';
						$form_text .= '<input type="radio" name="mbr_bible_version" value="'
							. $key . '"' . esc_html( $version['is_checked'] ) . '>'
							.' <span class="mbr_version_name">'
							. esc_html( $version['name'] ) . '</span>';
					$form_text .= '</div>';
				}
			$form_text .= '</div>';

			// SECTION DIVIDER
			$form_text .= '<div class="mbr_customize_form_divider"></div>';

			// READING PLAN SECTION
			$form_text .= '<div class="mbr_select_plan">';
				$form_text .= '<h2>Reading Plan</h2>';

				foreach( $plans as $key => $plan ) {
					$key = esc_html( $key );
					$form_text .= '<input type="radio" name="mbr_plan" value="'
						. $key . '" class="' . $key . '" ' . esc_html( $plan['is_checked'] ) . '>'
						.'<span class="mbr_plan_name">'
						. esc_html( $plan['name'] ) . '</span>';
					$form_text .= '<span class="mbr_plan_pace"> ( '
						. esc_textarea( $plan['pace'] ) . ' )</span><br>';
				}

				$form_text .= '<div class="mbr_plan_details_wrapper">';
					foreach( $plans as $key => $plan ) {
						$key = esc_html( $key );
						$form_text .= '<div class="mbr_plan_details_column1 '.$key.'">';
							$form_text .= '<h4>Selected Plan Details</h4>';
							$form_text .= '<p class="mbr_plan_description">'
								. esc_textarea( $plan['description'] ) . '</p>';
						$form_text .= '</div>';
						$form_text .= '<div class="mbr_plan_details_column2 '.$key.'">';
							$form_text .= '<h4>Sample Reading</h4>';
							$form_text .= jswj_mbr_display_random_reading($key);
						$form_text .= '</div>';
					}
				$form_text .= '</div>';

				$form_text .= '<div class="mbr_select_start">';
					$form_text .= '<h3>Reading Plan Start Date</h3>';
					$form_text .= '<input type="date" name="mbr_start_date" value="'
						. esc_html( $start_date ) . '"> <br>';
				$form_text .= '</div>';

			$form_text .= '</div>';

			// SECTION DIVIDER
			$form_text .= '<div class="mbr_customize_form_divider"></div>';

			// REMINDER SECTION
			if( 'never' !== $frequency ) {
				$form_text .= '<div class="mbr_select_reminders">';
					$form_text .= "<h2><input type='checkbox' name='mbr_reminder_checkbox' value='1' "
						.checked(1, $mbr_reminder_checkbox, false)." onchange='jswj_mbr_reminder_checkbox(this)' />";
					$form_text .= 'Get Daily Reminders</h2>';

					// Add Class If Reminder Checkbox Is Checked
					$mbr_reminder_rows_class = 'mbr_reminder_row';
					if( '1' == $mbr_reminder_checkbox ) {
						$mbr_reminder_rows_class .= ' mbr_show_reminder_fields';
					}

					$form_text .= '<div class="' . $mbr_reminder_rows_class . '">';

						$form_text .= '<div class="mbr_reminder_options">';
							$form_text .= '<h3>Blue Letter Bible: ';
							$form_text .= "<input type='checkbox' name='mbr_reminder_blb_checkbox' value='1' "
								.checked(1, $mbr_reminder_blb_checkbox, false)." /></h3>";
							$form_text .= '<h3>Living Water Resources: ';
							$form_text .= "<input type='checkbox' name='mbr_reminder_livingwater_checkbox' value='1' "
								.checked(1, $mbr_reminder_livingwater_checkbox, false)." /></h3>";
						$form_text .= '</div>';

						$form_text .= '<div class="mbr_reminder_email">';
							$form_text .= '<h3>Send Emails To</h3>';
							$form_text .= '<input type="email" name="mbr_reminder_email" value="'
								. esc_html( $reminder_email ) . '"> <br>';
						$form_text .= '</div>';

						if( function_exists( 'jswj_mbr_frontend_user_reminder_field_mobile' ) ) {
							$form_text .= jswj_mbr_frontend_user_reminder_field_mobile( $user_id );
						}

						$form_text .= '<div class="mbr_select_reminder_time">';
							$form_text .= '<h3>Reminder Time</h3>';

							$form_text .= '<select name="mbr_reminder_hour" id="mbr_reminder_hour">';
							foreach( $reminder_times['hours'] as $key => $hour ) {
								$form_text .= '<option value="'.strval($key).'"' . esc_html( $hour['is_selected'] ) . '>'
									. $hour['hour'] . '</option>';
							}
							$form_text .= '</select>';


							$form_text .= '<select name="mbr_reminder_minute" id="mbr_reminder_minute">';
							foreach( $reminder_times['minutes'] as $key => $minute ) {
								$form_text .= '<option value="'.strval($key).'"' . $minute['is_selected'] . '>'
									. esc_html( $minute['minute'] ) . '</option>';
							}
							$form_text .= '</select>';

						$form_text .= '</div>';

						$form_text .= '<div class="mbr_select_timezone">';
							$form_text .= '<h3>Timezone</h3>';
							$form_text .= '<input list="mbr_timezones" name="mbr_timezone" value="'
								. esc_html( $timezone ) . '"> <br>';

							$form_text .= '<datalist id="mbr_timezones">';
								$php_timezones = DateTimeZone::listIdentifiers();
								foreach( $php_timezones as $php_timezone ) {
									$form_text .= '<option value="' . $php_timezone . '">';
								}
							$form_text .= '</datalist>';

						$form_text .= '</div>';
					$form_text .= '</div>';
				$form_text .= '</div>';

				// SECTION DIVIDER
				$form_text .= '<div class="mbr_customize_form_divider"></div>';
			} // END If Reminder Schedule


			$form_text .= '<div class="mb_customize_button_wrapper">';
				$form_text .= '<button type="submit" name="mbr_customize_button" class="button et_pb_button" >SAVE YOUR SETTINGS</button>';
			$form_text .= '</div>';
			$form_text .= '<input type="hidden" name="mbr_customize" value="customize" />';
		$form_text .= '</form>';
	$form_text .= '</div>';

	return $form_text;
} // END jswj_mbr_customize_reading_form()


/**
 * Shortcode: Display Available Reading Plans
 *
 * @return HTML div
 **/
add_shortcode( 'mbr_display_reading_plans' , 'jswj_mbr_display_reading_plans' );
function jswj_mbr_display_reading_plans() {

	if( is_user_logged_in() ) {
		return;
	}

	$mbr = get_option('jswj-my-bible-reading');
	$versions = $mbr['versions'];
	$plans = $mbr['plans'];
	$bible_version = $mbr['default_version'];
	$reading_plan = $mbr['default_plan'];
	$plans[$reading_plan]['is_checked'] = ' checked';
	$start_date = $mbr['mbr_start_date'];
	$timezone = 'America/Los_Angeles';

	/**
	 * Display My Bible Reading Options Form
	 **/
	$form_text = '<div id="mbr_customize_form">';
		$form_text .= '<form method="post" action="">';

			// READING PLAN SECTION
			$form_text .= '<div class="mbr_select_plan">';
				$form_text .= '<h2>Available Reading Plans</h2>';

				foreach( $plans as $key => $plan ) {
					$form_text .= '<input type="radio" name="mbr_plan" value="'
						. $key . '" class="' . $key . '" ' . $plan['is_checked'] . '>'
						.'<span class="mbr_plan_name">'
						. esc_html( $plan['name'] ) . '</span>';
					$form_text .= '<span class="mbr_plan_pace"> ( '
						. esc_textarea( $plan['pace'] ) . ' )</span><br>';
				}

				$form_text .= '<div class="mbr_plan_details_wrapper">';
					foreach( $plans as $key => $plan ) {
						$form_text .= '<div class="mbr_plan_details_column1 '.$key.'">';
							$form_text .= '<h4>Selected Plan Details</h4>';
							$form_text .= '<p class="mbr_plan_description">'
								. esc_textarea( $plan['description'] ) . '</p>';
						$form_text .= '</div>';
						$form_text .= '<div class="mbr_plan_details_column2 '.$key.'">';
							$form_text .= '<h4>Sample Reading</h4>';
							$form_text .= jswj_mbr_display_random_reading($key);
						$form_text .= '</div>';
					}
				$form_text .= '</div>';

			$form_text .= '</div>';

		$form_text .= '</form>';
	$form_text .= '</div>';

	return $form_text;

} // END jswj_mbr_display_reading_plans()



add_shortcode( 'mbr_display_reading_schedule' , 'jswj_mbr_display_reading_schedule' );
function jswj_mbr_display_reading_schedule() {

	$user_id = get_current_user_id();

	$mbr = get_option('jswj-my-bible-reading');
	$versions = $mbr['versions'];
	$plans = $mbr['plans'];

	// Get User's Selected Bible Version
	$bible_version = get_user_meta( $user_id, 'mbr_bible_version', true );
	if( empty( $bible_version ) ) { $bible_version = $mbr['default_version']; }

	// Get User's Selected Reading Plan
	$reading_plan = get_user_meta( $user_id, 'mbr_plan', true );
	if( empty( $reading_plan ) ) { $reading_plan = $mbr['default_plan']; }

	// Get User's Selected Start Date
	$start_date = get_user_meta( $user_id, 'mbr_start_date', true );
	if( empty( $start_date ) ) { $start_date = $mbr['mbr_start_date']; }

	$jswj_mbr_get_full_reading_schedule = jswj_mbr_get_full_reading_schedule( $reading_plan, $start_date );

	$reading_table = '<table class="full_reading_schedule_table">';

	foreach( $jswj_mbr_get_full_reading_schedule as $day ) {
		$reading_table .= '<tr><td>' . $day['date'] . '</td>';

		$reading_table .= '<td>' . $day['overview'] . '</td>';

		$chapter_array = $day['chapter_array'];

		$reading_table .= '<td>';
		foreach( $chapter_array as $key => $chapter ) {
			$link = jswj_mbr_get_blb_link( $chapter, $bible_version );
			$reading_table .= '<a href="' . esc_url( $link ) . '" target="_blank" class="mbr_todays_reading_link">'
				.$chapter . '</a>';
			if( count( $chapter_array ) != 1 & count( $chapter_array ) > $key ) {
				$reading_table .=  ', ';
			}
		}
		$reading_table .= '</td>';

		$reading_table .= '</tr>';
	}
	$reading_table .= '</table>';

	return $reading_table;

	return( serialize( $jswj_mbr_get_full_reading_schedule ) );

} // END jswj_mbr_display_reading_schedule()
