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
 * Register Cron Frequency Schedules
 **/
function jswj_mbr_cron_schedule( $schedules ) {

	$schedules['mbr_cron_fifteen'] = array(
		'interval' => 900, // Every 15 minutes
		'display'  => __( 'Every 15 minutes' ),
	);
	$schedules['mbr_cron_ten'] = array(
		'interval' => 600, // Every 10 minutes
		'display'  => __( 'Every 10 minutes' ),
	);
	$schedules['mbr_cron_five'] = array(
		'interval' => 300, // Every 5 minutes
		'display'  => __( 'Every 5 minutes' ),
	);
	$schedules['mbr_cron_two'] = array(
		'interval' => 120, // Every 2 minutes
		'display'  => __( 'Every 2 minutes' ),
	);
	return $schedules;
}
add_filter( 'cron_schedules', 'jswj_mbr_cron_schedule' );


/**
 * Schedule Reminder Hooks
 **/
function jswj_mbr_setup_reminder_hooks() {

	$mbr = get_option('jswj-my-bible-reading');
	$frequency = $mbr['reminder_frequency'];
	if( 'never' == $frequency ) { return; }

	if ( !wp_next_scheduled( 'mbr_check_all_reminders_event' ) ) {
		wp_schedule_event( time(), $frequency, 'mbr_check_all_reminders_event' );
	}
	add_action('mbr_check_all_reminders_event', 'jswj_mbr_check_all_reminders');
}
add_action ( 'init', 'jswj_mbr_setup_reminder_hooks' );


/**
 * Loop Through Users And Send Reminders
 **/
function jswj_mbr_check_all_reminders() {

	$mbr = get_option('jswj-my-bible-reading');
	$frequency = $mbr['reminder_frequency'];
	if( 'never' == $frequency ) { return; }

	$current_utc = intval(date('U'));

	$users = get_users();
	foreach( $users as $user ) {

		// Skip User If Reminder Option Not Selected
		$mbr_reminder_checkbox = get_the_author_meta( 'mbr_reminder_checkbox', $user->ID );
		if( '1' !== $mbr_reminder_checkbox ) { return; }

		$next_reminder = intval( get_the_author_meta( 'mbr_next_reminder', $user->ID ) );
		if( empty( $next_reminder ) ) { continue; }

		if( $current_utc > $next_reminder ) {
			jswj_mbr_remind_user_function( $user->ID );
		}
	}
} // END jswj_mbr_check_all_reminders


/**
 * Build And Send Reading Reminder
 **/
function jswj_mbr_remind_user_function( $user_id, $reminder_email='', $reminder_mobile='' ) {

	$mbr = get_option('jswj-my-bible-reading');
	$frequency = $mbr['reminder_frequency'];
	if( 'never' == $frequency ) { return; }

	if( false !== strpos( get_home_url(), '.dev' ) ) {
		error_log('Dev Environment - Skipping Reminder Functions');
		return;
	}

	$mbr_reminder_checkbox = get_the_author_meta( 'mbr_reminder_checkbox', $user_id );
	if( '1' !== $mbr_reminder_checkbox ) { return; }

	if( empty( $reminder_email ) ) {
		$reminder_email = get_the_author_meta( 'mbr_reminder_email', $user_id );
	}

	$todays_reading = jswj_mbr_display_todays_reading_user( $user_id );
	$todays_reading_email = str_replace( PHP_EOL, '<br>', $todays_reading);

	$timezone = get_the_author_meta( 'mbr_timezone', $user_id );
	if( empty( $timezone ) ) { $timezone = 'America/Los_Angeles'; }
	$todays_date = date_create( 'now', timezone_open($timezone) );

	if( !empty( $reminder_email ) ) {
		$headers = array('Content-Type: text/html; charset=UTF-8');
		$subject = 'Reading for ' . date_format( $todays_date, 'l, F jS, Y' );
		wp_mail( $reminder_email, $subject, $todays_reading_email, $headers );
	}

	do_action( 'jswj_mbr_more_remind_user_functions', $user_id, $todays_reading );

	update_user_meta( $user_id, 'mbr_last_reminded', date('U') );
	jswj_mbr_set_next_reminder( $user_id );

} // END jswj_mbr_remind_user_function()


/**
 * Set Next Reminder For User ID
 **/
function jswj_mbr_set_next_reminder( $user_id ) {

	$reminder_hour = get_the_author_meta( 'mbr_reminder_hour', $user_id );
	$reminder_minute = get_the_author_meta( 'mbr_reminder_minute', $user_id );
	$timezone = get_the_author_meta( 'mbr_timezone', $user_id );
	$mbr_reminder_checkbox = get_the_author_meta( 'mbr_reminder_checkbox', $user_id );

	if( empty($reminder_hour) ) { return; }
	if( empty($reminder_minute) ) { return; }
	if( empty($timezone) ) { return; }
	if( '1' !== $mbr_reminder_checkbox ) {
		update_user_meta( $user_id, 'mbr_next_reminder', '' );
		return;
	}

	// Try to set reminder for today
	$reminder_time = strtotime( 'today ' . $reminder_hour . ':' . $reminder_minute . ':00', date('U') + timezone_offset_get( timezone_open( $timezone ), new DateTime() ) );
	$reminder_time_utc = $reminder_time - timezone_offset_get( timezone_open( $timezone ), new DateTime() );

	// If today's time has passed, set reminder for tomorrow
	if( intval(date('U')) > $reminder_time_utc ) {
		$reminder_time = strtotime( 'tomorrow ' . $reminder_hour . ':' . $reminder_minute . ':00', date('U') + timezone_offset_get( timezone_open( $timezone ), new DateTime() ) );
		$reminder_time_utc = $reminder_time - timezone_offset_get( timezone_open( $timezone ), new DateTime() );
	}

	update_user_meta( $user_id, 'mbr_next_reminder', $reminder_time_utc );

} // END jswj_mbr_set_next_reminder()
