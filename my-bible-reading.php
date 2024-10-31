<?php
/**
 * My Bible Reading
 *
 * @package		my-bible-reading
 * @author		Jerry Simmons <jerry@ferventsolutions.com>
 * @copyright	2017 Jerry Simmons
 * @license		GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name:	My Bible Reading
 * Description:	Encourage Users To Read The Bible With Four Bible Reading Plans And Daily Email Reminders
 * Version:		1.1.1
 * Author:		Jerry Simmons
 * Author URI:	https://ferventsolutions.com
 * Text Domain:	my-bible-reading
 * Requires at least: 4.6
 * Tested up to: 6.1
 * Requires PHP: 5.6
 * License:		GPL-2.0+
 * License URI:	http://www.gnu.org/licenses/gpl-2.0.txt
 **/

if ( ! defined( 'ABSPATH' ) ) { exit; }


/**
 * Load Reading Schedule Functions
 **/
require_once( plugin_dir_path(__FILE__) . 'includes/mbr_functions.php' );

/**
 * Load User Profile Markup Fields
 **/
require_once( plugin_dir_path(__FILE__) . 'admin/mbr_user_fields.php' );

/**
 * Load Tool Page
 **/
require_once( plugin_dir_path(__FILE__) . 'admin/mbr_tool_page.php' );

/**
 * Load Shortcodes
 **/
require_once( plugin_dir_path(__FILE__) . 'includes/mbr_shortcodes.php' );

/**
 * Load Reminder Functions
 **/
require_once( plugin_dir_path(__FILE__) . 'includes/mbr_reminders.php' );


/**
 * Load Front End CSS
 **/
function mbr_shortcode_style() {
	wp_register_style( 'mbr_shortcode_css', plugin_dir_url(__FILE__) . 'includes/mbr_shortcodes.css', false, rand() );
	wp_enqueue_style( 'mbr_shortcode_css' );
}
add_action( 'wp_enqueue_scripts', 'mbr_shortcode_style' );


/**
 * Load Admin CSS
 **/
function mbr_admin_style() {
	wp_register_style( 'mbr_admin_css', plugin_dir_url(__FILE__) . 'admin/mbr_admin.css', false, rand() );
	wp_enqueue_style( 'mbr_admin_css' );
}
add_action( 'admin_enqueue_scripts', 'mbr_admin_style' );


// Enqueue My Bible Reading Javascript
function jswj_mbr_enqueue_js() {
	wp_enqueue_script(
		'mbr_user_fields', // Script  ID
		plugin_dir_url(__FILE__) . '/js/mbr_user_fields.js', // JS file
		array(),
		rand(1, 1000),
		true
	);
}
add_action('wp_enqueue_scripts', 'jswj_mbr_enqueue_js');
add_action( 'admin_enqueue_scripts', 'jswj_mbr_enqueue_js' );


jswj_mbr_defaults_load();
jswj_mbr_load_reading_schedule();


/**
 * Setup Default Values
 **/
function jswj_mbr_defaults_load() {
	if( empty( get_option('jswj-my-bible-reading') ) ) {
		$mbr = array();
		$mbr['mbr_reminder_message'] = '';
		$mbr['versions'] = array(
			'nkjv'	=> array('name' => 'New King James Version', 'is_checked' => '', 'is_selected' => ''),
			'niv'	=> array('name' => 'New International Version', 'is_checked' => '', 'is_selected' => ''),
			'nlt'	=> array('name' => 'New Living Translation', 'is_checked' => '', 'is_selected' => ''),
			'esv'	=> array('name' => 'English Standard Version', 'is_checked' => '', 'is_selected' => ''),
			'kjv'	=> array('name' => 'King James Version', 'is_checked' => '', 'is_selected' => ''),
			'rvr60'	=> array('name' => 'Reina-Valera 1960', 'is_checked' => '', 'is_selected' => '')
		);
		$mbr['default_version'] = 'nkjv';

		$mbr['reminder_frequencies'] = array(
			'never'				=> array( 'name' => 'Never Send Reminders' , 'is_selected' => '' ),
			'daily'				=> array( 'name' => 'Once A Day' , 'is_selected' => '' ),
			'twicedaily'		=> array( 'name' => 'Twice A Day' , 'is_selected' => '' ),
			'hourly'			=> array( 'name' => 'Hourly' , 'is_selected' => '' ),
			'mbr_cron_fifteen'	=> array( 'name' => 'Every 15 Minutes' , 'is_selected' => '' ),
			'mbr_cron_ten'		=> array( 'name' => 'Every 10 Minutes' , 'is_selected' => '' ),
			'mbr_cron_five'		=> array( 'name' => 'Every 5 Minutes' , 'is_selected' => '' ),
			'mbr_cron_two'		=> array( 'name' => 'Every 2 Minutes' , 'is_selected' => '' ),
		);
		$mbr['reminder_frequency'] = 'hourly';

		$mbr['plans'] = array();
		$mbr['plans']['bi3y'] = array(
			'name'	=> 'Bible In 3 Years',
			'pace'	=> 'One Or Two Chapters Each Day',
			'description'	=> 'Reading From Genesis Through Revelation',
			'is_checked'	=> '',
		);
		$mbr['plans']['bi1y-3tracks'] = array(
			'name'	=> 'Bible In 1 Year - 3 Tracks',
			'pace'	=> 'Three Or Four Chapters Each Day',
			'description'	=> 'Follows The 3 Year Plan, Reading 3 Portions Each Day. Track 1: Reading From Genesis Through 2 Kings. Track 2: Reading From 1 Chronicles Through Malachi. Track 3: Reading From Matthew Through Revelation',
			'is_checked'	=> '',
		);
		$mbr['plans']['bi1y-1track'] = array(
			'name'	=> 'Bible In 1 Year',
			'pace'	=> 'Three Or Four Chapters Each Day',
			'description'	=> 'Reading From Genesis Through Revelation',
			'is_checked'	=> '',
		);
		$mbr['plans']['nt1y'] = array(
			'name'	=> 'New Testament In 1 Year',
			'pace'	=> 'One Chapter Or Less Each Day',
			'description'	=> 'Reading From Matthew Through Revelation',
			'is_checked'	=> '',
		);
		$mbr['default_plan'] = 'bi3y';

		$mbr['mbr_start_date'] = '2018-01-01';

		$mbr['reminder_times'] = array();
		$mbr['reminder_times']['hours'] = array(
			'00'	=> array('hour' => '12<span class="mbr_ampm">AM</span>', 'is_selected' => ''),
			'01'	=> array('hour' => '1<span class="mbr_ampm">AM</span>', 'is_selected' => ''),
			'02'	=> array('hour' => '2<span class="mbr_ampm">AM</span>', 'is_selected' => ''),
			'03'	=> array('hour' => '3<span class="mbr_ampm">AM</span>', 'is_selected' => ''),
			'04'	=> array('hour' => '4<span class="mbr_ampm">AM</span>', 'is_selected' => ''),
			'05'	=> array('hour' => '5<span class="mbr_ampm">AM</span>', 'is_selected' => ''),
			'06'	=> array('hour' => '6<span class="mbr_ampm">AM</span>', 'is_selected' => ''),
			'07'	=> array('hour' => '7<span class="mbr_ampm">AM</span>', 'is_selected' => ''),
			'08'	=> array('hour' => '8<span class="mbr_ampm">AM</span>', 'is_selected' => ''),
			'09'	=> array('hour' => '9<span class="mbr_ampm">AM</span>', 'is_selected' => ''),
			'10'	=> array('hour' => '10<span class="mbr_ampm">AM</span>', 'is_selected' => ''),
			'11'	=> array('hour' => '11<span class="mbr_ampm">AM</span>', 'is_selected' => ''),
			'12'	=> array('hour' => '12<span class="mbr_ampm">PM</span>', 'is_selected' => ''),
			'13'	=> array('hour' => '1<span class="mbr_ampm">PM</span>', 'is_selected' => ''),
			'14'	=> array('hour' => '2<span class="mbr_ampm">PM</span>', 'is_selected' => ''),
			'15'	=> array('hour' => '3<span class="mbr_ampm">PM</span>', 'is_selected' => ''),
			'16'	=> array('hour' => '4<span class="mbr_ampm">PM</span>', 'is_selected' => ''),
			'17'	=> array('hour' => '5<span class="mbr_ampm">PM</span>', 'is_selected' => ''),
			'18'	=> array('hour' => '6<span class="mbr_ampm">PM</span>', 'is_selected' => ''),
			'19'	=> array('hour' => '7<span class="mbr_ampm">PM</span>', 'is_selected' => ''),
			'20'	=> array('hour' => '8<span class="mbr_ampm">PM</span>', 'is_selected' => ''),
			'21'	=> array('hour' => '9<span class="mbr_ampm">PM</span>', 'is_selected' => ''),
			'22'	=> array('hour' => '10<span class="mbr_ampm">PM</span>', 'is_selected' => ''),
			'23'	=> array('hour' => '11<span class="mbr_ampm">PM</span>', 'is_selected' => ''),
		);
		$mbr['reminder_times']['minutes'] = array(
			'00'	=> array('minute' => ':00', 'is_selected' => ''),
			'15'	=> array('minute' => ':15', 'is_selected' => ''),
			'30'	=> array('minute' => ':30', 'is_selected' => ''),
			'45'	=> array('minute' => ':45', 'is_selected' => ''),
		);

		update_option( 'jswj-my-bible-reading', $mbr );
	}

	/**
	 * Update Options To Include 6 Month Reading Plan
	 *
	 * v1.0 to v1.1 Update
	 **/
	$mbr = get_option('jswj-my-bible-reading');
	if( !isset( $mbr['plans']['bi6m'] ) ) {
		$mbr['plans']['bi6m'] = array(
			'name'	=> 'Bible In 6 Months',
			'pace'	=> 'Six To Eight Chapters Each Day',
			'description'	=> 'Reading From Genesis Through Revelation',
			'is_checked'	=> '',
		);
	}
	update_option( 'jswj-my-bible-reading', $mbr );

} // END mbr_defaults_load()
