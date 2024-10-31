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
 * Sanitize & Validate Data For My Bible Reading Fields
 *
 * @return array with validated data
 **/
function jswj_sanitize_validate_mbr_data( $post_data ) {

	$return_data = array();
	$return_data['error'] = array();
	$checkboxes = array();

	// Process Checkboxes
	$checkboxes[] = 'mbr_reminder_checkbox';
	$checkboxes[] = 'mbr_reminder_blb_checkbox';
	$checkboxes[] = 'mbr_reminder_livingwater_checkbox';
	foreach( $checkboxes as $checkbox ) {
		if( isset( $post_data[$checkbox] ) ) {
			$return_data[$checkbox] = '1';
		} else {
			$return_data[$checkbox] = '0';
		}
	}

	// Process Fields
	foreach( $post_data as $key => $data_value ) {
		switch( $key ) {
			case 'mbr_reminder_frequency':
				$return_data[$key] = sanitize_key( $post_data[$key] ); break;
			case 'mbr_bible_version':
				$return_data[$key] = sanitize_key( $post_data[$key] ); break;
			case 'mbr_plan':
				$return_data[$key] = sanitize_key( $post_data[$key] ); break;
			case 'mbr_start_date':
				$test_date = sanitize_text_field( $post_data[$key] );
				if( false !== strtotime( $test_date ) ) {
					$return_data[$key] = $test_date;
				} else {
					$return_data['error']['mbr_start_date'] = 'Invalid Date: ' . $test_date;
				}
				break;
			case 'mbr_reminder_checkbox':
				if( '1' == $post_data[$key] ) {
					$return_data[$key] = '1';
				} else {
					$return_data[$key] = '0';
				}
				break;
			case 'mbr_reminder_blb_checkbox':
				if( '1' == $post_data[$key] ) {
					$return_data[$key] = '1';
				} else {
					$return_data[$key] = '0';
				}
				break;
			case 'mbr_reminder_livingwater_checkbox':
				if( '1' == $post_data[$key] ) {
					$return_data[$key] = '1';
				} else {
					$return_data[$key] = '0';
				}
				break;
			case 'mbr_reminder_email':
				$return_data[$key] = sanitize_email( $post_data[$key] ); break;
			case 'mbr_reminder_hour':
				$return_data[$key] = sanitize_text_field( $post_data[$key] ); break;
			case 'mbr_reminder_minute':
				$return_data[$key] = sanitize_text_field( $post_data[$key] ); break;
			case 'mbr_timezone':
				$test_timezone = sanitize_text_field( $post_data[$key] );
				if( jswj_valid_timezone( $test_timezone ) ) {
					$return_data[$key] = $test_timezone;
				} else if( empty( $test_timezone ) ) {
					$return_data[$key] = '';
				} else {
					$return_data['error']['mbr_timezone'] = 'Invalid Timezone: '. $test_timezone;
				}
				break;
		} // END switch $key

	} // END foreach $post_data

	$return_data = apply_filters( 'jswj_sanitize_validate_more_mbr_data', $return_data, $post_data );

	return $return_data;
} // END jswj_sanitize_validate_mbr_data()


/**
 * Test If Timezone Is Valid
 *
 * @return boolean
 **/
function jswj_valid_timezone( $test_timezone ) {

	if( in_array( $test_timezone, timezone_identifiers_list() ) ) {
		return true;
	} else {
		return false;
	}
} //END jswj_valid_timezone()


/**
 * Load Reading Schedule From File
 **/
function jswj_mbr_load_reading_schedule() {

	// Skip Loading File If Schedule Exists
	if( !empty( get_option( 'mbr_reading_schedule' ) ) ) { return; }

	ini_set("auto_detect_line_endings", true);
	$plugin_dir = plugin_dir_path(__FILE__);
	$reading_schedule = file( $plugin_dir  . '/mbr_schedule.csv' );

	foreach( $reading_schedule as $key => $year_line ) {
		$reading_schedule[$key] = str_getcsv($year_line);

		// Delete Empty Entries From CSV Import
		foreach( $reading_schedule[$key] as $empty_key => $empty_test ) {
			if( empty( $empty_test ) ) {
				unset( $reading_schedule[$key][$empty_key] );
			}
		}
	}

	update_option( 'mbr_reading_schedule', $reading_schedule );
} // END jswj_mbr_load_reading_schedule()



/**
 * Get Today's Reading Record
 *
 * @return array
 **/
function jswj_mbr_get_todays_reading_record( $reading_plan, $start_date ) {
	$start_date = date_create( $start_date, timezone_open('America/Los_Angeles') );
	$todays_date = date_create( 'now', timezone_open('America/Los_Angeles') );
	$num_days = intval(date_diff($start_date, $todays_date)->format('%R%a'));
	$todays_reading = array();

	// Future Start Date - Return Random Reading From Selected Plan
	if( 0 > $num_days ) {
		$random_reading = jswj_mbr_get_random_reading_record( $reading_plan );
		$random_reading[1]['overview'] = 'Your reading plan is scheduled to start in ' . $num_days * -1
			.' days. <br>Until then, enjoy this random selection:<br>' . $random_reading[1]['overview'];
		return $random_reading[1];
	}

	$reading_schedule = get_option('mbr_reading_schedule');

	if( 'bi3y' == $reading_plan ) {

		if( $num_days > count($reading_schedule) ) {
			$times_completed = intval( $num_days / count($reading_schedule) );
			$num_days = $num_days - ( $times_completed * count($reading_schedule) );
		}

		$todays_reading = jswj_mbr_get_reading_array( $reading_schedule[$num_days] );

	}
	if( 'nt1y' == $reading_plan ) {
		$num_days = jswj_mbr_get_oneyear_days( $num_days );
		$todays_reading = jswj_mbr_get_reading_array( $reading_schedule[$num_days + (365 * 2)] );
	}
	if( 'bi1y-1track' == $reading_plan ) {
		$num_days = 3 * jswj_mbr_get_oneyear_days( $num_days );

		$todays_reading = jswj_mbr_get_reading_array( $reading_schedule[$num_days] );
		$todays_reading = jswj_mbr_append_reading_array( $todays_reading, $reading_schedule[$num_days + 1], true );
		$todays_reading = jswj_mbr_append_reading_array( $todays_reading, $reading_schedule[$num_days + 2], true );

	}
	if( 'bi1y-3tracks' == $reading_plan ) {
		$num_days = jswj_mbr_get_oneyear_days( $num_days );

		$todays_reading = jswj_mbr_get_reading_array( $reading_schedule[$num_days] );
		$todays_reading = jswj_mbr_append_reading_array( $todays_reading, $reading_schedule[$num_days + 365], false );
		$todays_reading = jswj_mbr_append_reading_array( $todays_reading, $reading_schedule[$num_days + (365 * 2)], false );
	}
	if( 'bi6m' == $reading_plan ) {

		while( $num_days >= 183 ) {
			$num_days = $num_days - 183;
		}
		$num_days = $num_days * 6;

		$todays_reading = jswj_mbr_get_reading_array( $reading_schedule[$num_days] );
		$todays_reading = jswj_mbr_append_reading_array( $todays_reading, $reading_schedule[$num_days + 1], true );
		$todays_reading = jswj_mbr_append_reading_array( $todays_reading, $reading_schedule[$num_days + 2], true );
		$todays_reading = jswj_mbr_append_reading_array( $todays_reading, $reading_schedule[$num_days + 3], true );
		$todays_reading = jswj_mbr_append_reading_array( $todays_reading, $reading_schedule[$num_days + 4], true );
		$todays_reading = jswj_mbr_append_reading_array( $todays_reading, $reading_schedule[$num_days + 5], true );
	}

	$todays_reading['random'] = false;

	return $todays_reading;
} // END jswj_mbr_get_todays_reading_record()


/**
 * Get Random Reading Record
 **/
function jswj_mbr_get_random_reading_record( $reading_plan ) {
	$random_reading = array();

	$reading_schedule = get_option('mbr_reading_schedule');

	if( 'bi3y' == $reading_plan ) {
		$random_day = rand( 0, count( $reading_schedule ) );
		$random_reading = jswj_mbr_get_reading_array( $reading_schedule[$random_day] );
	}
	if( 'nt1y' == $reading_plan ) {
		$random_day = rand( 0, 365 );
		$random_reading = jswj_mbr_get_reading_array( $reading_schedule[$random_day + (365 * 2)] );
	}
	if( 'bi1y-1track' == $reading_plan ) {
		$random_day = 3 * rand( 0, 365 );

		$random_reading = jswj_mbr_get_reading_array( $reading_schedule[$random_day] );
		$random_reading = jswj_mbr_append_reading_array( $random_reading, $reading_schedule[$random_day + 1], true );
		$random_reading = jswj_mbr_append_reading_array( $random_reading, $reading_schedule[$random_day + 2], true );
		$random_day = $random_day / 3;
	}
	if( 'bi1y-3tracks' == $reading_plan ) {
		$random_day = rand( 0, 365 );

		$random_reading = jswj_mbr_get_reading_array( $reading_schedule[$random_day] );
		$random_reading = jswj_mbr_append_reading_array( $random_reading,
			$reading_schedule[$random_day + 365], false );
		$random_reading = jswj_mbr_append_reading_array( $random_reading,
			$reading_schedule[$random_day + (365 * 2)], false );
	}
	if( 'bi6m' == $reading_plan ) {
		$random_day = 6 * rand( 0, 183 );

		while( $random_day >= 183 ) {
			$random_day = $random_day - 183;
		}
		$random_day = $random_day * 6;

		$random_reading = jswj_mbr_get_reading_array( $reading_schedule[$random_day] );
		$random_reading = jswj_mbr_append_reading_array( $random_reading, $reading_schedule[$random_day + 1], true );
		$random_reading = jswj_mbr_append_reading_array( $random_reading, $reading_schedule[$random_day + 2], true );
		$random_reading = jswj_mbr_append_reading_array( $random_reading, $reading_schedule[$random_day + 3], true );
		$random_reading = jswj_mbr_append_reading_array( $random_reading, $reading_schedule[$random_day + 4], true );
		$random_reading = jswj_mbr_append_reading_array( $random_reading, $reading_schedule[$random_day + 5], true );
		$random_day = $random_day / 6;
	}


	$random_reading['random'] = true;

	return array( $random_day, $random_reading );
} // END jswj_mbr_get_random_reading_record()


/**
 * Get Full Reading Schedule
 **/
function jswj_mbr_get_full_reading_schedule( $reading_plan, $start_date ) {
	$full_reading_schedule = array();

	$start_date = date_create( $start_date, timezone_open('America/Los_Angeles') );
	$todays_date = date_create( 'now', timezone_open('America/Los_Angeles') );
	$num_days = intval(date_diff($start_date, $todays_date)->format('%R%a'));
	$todays_reading = array();

	// Future Start Date - Return Random Reading From Selected Plan
	if( 0 > $num_days ) {
		$random_reading = jswj_mbr_get_random_reading_record( $reading_plan );
		$random_reading[1]['overview'] = 'Your reading plan is scheduled to start in ' . $num_days * -1
			.' days. <br>Until then, enjoy this random selection:<br>' . $random_reading[1]['overview'];
		return $random_reading[1];
	}

	$reading_schedule = get_option('mbr_reading_schedule');
	$day = 0;
	$current_date = $start_date;

	if( 'bi3y' == $reading_plan ) {
		foreach( $reading_schedule as $daily_reading ) {
			$reading_record = jswj_mbr_get_reading_array( $reading_schedule[$day] );
			$full_reading_schedule[$day] = array(
				'date'			=> date_format( $current_date, 'm-d-Y' ) ,
				'overview'		=> $reading_record['overview'],
				'chapter_array'	=> $reading_record['chapter_array'],
			);
			$day++;
			$current_date = date_add( $current_date, date_interval_create_from_date_string("1 day") );
		}
	}
	if( 'bi1y-3tracks' == $reading_plan ) {
		foreach( $reading_schedule as $daily_reading ) {
			$reading_record = jswj_mbr_get_reading_array( $reading_schedule[$day] );
			$reading_record = jswj_mbr_append_reading_array( $reading_record,
				$reading_schedule[$day + 365], false );
			$reading_record = jswj_mbr_append_reading_array( $reading_record,
				$reading_schedule[$day + (365 * 2)], false );

			$full_reading_schedule[$day] = array(
				'date'			=> date_format( $current_date, 'm-d-Y' ) ,
				'overview'		=> $reading_record['overview'],
				'chapter_array'	=> $reading_record['chapter_array'],
			);
			$day++;
			$current_date = date_add( $current_date, date_interval_create_from_date_string("1 day") );

			if( $day >= 365 ) { break; }
		}
	}
	if( 'nt1y' == $reading_plan ) {
		foreach( $reading_schedule as $daily_reading ) {
			$reading_record = jswj_mbr_get_reading_array( $reading_schedule[$day + (365 * 2)] );

			$full_reading_schedule[$day] = array(
				'date'			=> date_format( $current_date, 'm-d-Y' ) ,
				'overview'		=> $reading_record['overview'],
				'chapter_array'	=> $reading_record['chapter_array'],
			);
			$day++;
			$current_date = date_add( $current_date, date_interval_create_from_date_string("1 day") );

			if( $day >= 365 ) { break; }
		}
	}
	if( 'bi1y-1track' == $reading_plan ) {
		foreach( $reading_schedule as $daily_reading ) {
			if( isset( $reading_schedule[$day] ) ) {
				$reading_record = jswj_mbr_get_reading_array( $reading_schedule[$day] );
			} else { continue; }
			if( isset( $reading_schedule[$day + 1] ) ) {
				$reading_record = jswj_mbr_append_reading_array(
					$reading_record, $reading_schedule[$day + 1], true );
			}
			if( isset( $reading_schedule[$day + 2] ) ) {
				$reading_record = jswj_mbr_append_reading_array(
					$reading_record, $reading_schedule[$day + 2], true );
			}

			$full_reading_schedule[$day] = array(
				'date'			=> date_format( $current_date, 'm-d-Y' ) ,
				'overview'		=> $reading_record['overview'],
				'chapter_array'	=> $reading_record['chapter_array'],
			);
			$day += 3;
			$current_date = date_add( $current_date, date_interval_create_from_date_string("1 day") );
		}
	}
	if( 'bi6m' == $reading_plan ) {
		foreach( $reading_schedule as $daily_reading ) {
			if( isset( $reading_schedule[$day] ) ) {
				$reading_record = jswj_mbr_get_reading_array( $reading_schedule[$day] );
			} else { continue; }
			if( isset( $reading_schedule[$day + 1] ) ) {
				$reading_record = jswj_mbr_append_reading_array(
					$reading_record, $reading_schedule[$day + 1], true );
			}
			if( isset( $reading_schedule[$day + 2] ) ) {
				$reading_record = jswj_mbr_append_reading_array(
					$reading_record, $reading_schedule[$day + 2], true );
			}
			if( isset( $reading_schedule[$day + 3] ) ) {
				$reading_record = jswj_mbr_append_reading_array(
					$reading_record, $reading_schedule[$day + 3], true );
			}
			if( isset( $reading_schedule[$day + 4] ) ) {
				$reading_record = jswj_mbr_append_reading_array(
					$reading_record, $reading_schedule[$day + 4], true );
			}
			if( isset( $reading_schedule[$day + 5] ) ) {
				$reading_record = jswj_mbr_append_reading_array(
					$reading_record, $reading_schedule[$day + 5], true );
			}

			$full_reading_schedule[$day] = array(
				'date'			=> date_format( $current_date, 'm-d-Y' ) ,
				'overview'		=> $reading_record['overview'],
				'chapter_array'	=> $reading_record['chapter_array'],
			);
			$day += 6;
			$current_date = date_add( $current_date, date_interval_create_from_date_string("1 day") );
		}


	}


	$random_reading['random'] = true;

	$random_day = rand( 0, count( $reading_schedule ) );
	$random_reading = jswj_mbr_get_reading_array( $reading_schedule[$random_day] );



	return $full_reading_schedule;
} // END jswj_mbr_get_full_reading_schedule()


/**
 * Get One Year Days
 **/
function jswj_mbr_get_oneyear_days( $num_days ) {

	while( $num_days > 365 ) {
		$num_days -= 365;
	}

	return $num_days;
}


/**
 * Get Reading Array
 *
 * @param $reading string
 *
 * @return array
 **/
function jswj_mbr_get_reading_array( $reading ) {

	$reading_array = array(
		'overview'		=> '',
		'chapter_array'	=> array(),
	);

	if( 1 == count( $reading ) ) {
		$reading_array['overview'] = $reading[0];
		$reading_array['chapter_array'] = $reading;
	} else {
		$reading_array['overview'] = $reading[0];
		$reading_chapters = $reading;
		unset($reading_chapters[0]);
		$reading_array['chapter_array'] = $reading_chapters;
	}

	return $reading_array;
} // END jswj_mbr_get_reading_array()


/**
 * Combines Two Reading Arrays
 *
 * @param $reading_array = Starting Array
 * @param $append_reading = Array To Append To $reading_array
 * @param $overview_range - Format 'overview' element. True = Range, False = Comma Separated
 *
 * @return Combined Reading Array
 **/
function jswj_mbr_append_reading_array( $reading_array, $append_reading, $overview_range = true ) {

	$append_reading = jswj_mbr_get_reading_array( $append_reading );

	foreach( $append_reading['chapter_array'] as $chapter ) {
		$reading_array['chapter_array'][] = $chapter;
	}

	if( $overview_range ) {
		$first_chapter = reset( $reading_array['chapter_array'] );
		$last_chapter = $reading_array['chapter_array'][count($reading_array['chapter_array'])-1];
		$reading_array['overview'] = $first_chapter . ' - ' . $last_chapter;
	} else {
		$reading_array['overview'] .= ', ' . $append_reading['overview'];
	}

	return $reading_array;
} // END jswj_mbr_append_reading_array()


/**
 * Get Blue Letter Bible Link
 *
 * @return url
 **/
function jswj_mbr_get_blb_link( $reading, $bible_version ) {

	$book_divider = strpos( $reading, ' ', 3 );
	$book = str_replace( ' ', '', substr( $reading, 0, $book_divider ) ) . '/';

	$chapter_divider = strpos( $reading, ':', 3 );
	if( false !== $chapter_divider ) {
		$chapter = substr( $reading, $book_divider, $chapter_divider - $book_divider );
	} else {
		$chapter = substr( $reading, $book_divider, strlen($reading) - $book_divider );
	}
	$chapter = trim( $chapter ) . '/';

	$verse_divider = strpos( $reading, '-', $chapter_divider );
	if( false !== $verse_divider ) {
		$verse = trim( substr( $reading, $chapter_divider + 1, $verse_divider - $chapter_divider - 1 ) ) . '/p1/';
	} else {
		$verse = '1/p1/';
	}

	$link = 'https://www.blueletterbible.org/' . $bible_version . '/' . $book . $chapter . $verse;

	return strtolower($link);
} // END jswj_mbr_get_blb_link()


/**
 * Get Link To Resource On Living Water
 **/
function jswj_mbr_get_cclw_link( $reading ) {

	$book_divider = strpos( $reading, ' ', 3 );
	$book = str_replace( ' ', '', substr( $reading, 0, $book_divider ) );
	$link = 'http://www.livingwatercorona.com/' . $book;

	return array(strtolower($link), $book);
}
