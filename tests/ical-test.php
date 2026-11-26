<?php
/**
 * A calendar file: its text, its lines, its zones, and its events.
 *
 * @package PackOut_Core
 */

require __DIR__ . '/bootstrap.php';

use PackOut\Core\Ical;
use PackOut\Core\Schedule;

/**
 * Read a calendar file back: unfold it, and return its components.
 *
 * @param string $file The file.
 * @return array{lines: array<int, string>, events: array<int, array<string, string>>, zones: array<int, array<string, mixed>>, problems: array<int, string>}
 */
function packout_test_parse( string $file ): array {
	$problems = array();
	if ( "\r\n" !== substr( $file, -2 ) ) {
		$problems[] = 'the file does not end with a line break';
	}
	foreach ( explode( "\r\n", $file ) as $raw ) {
		if ( strlen( $raw ) > 75 ) {
			$problems[] = 'a line is ' . strlen( $raw ) . ' octets';
		}
		if ( false !== strpos( $raw, "\n" ) || false !== strpos( $raw, "\r" ) ) {
			$problems[] = 'a line holds a bare line break';
		}
		if ( 1 !== preg_match( '//u', $raw ) ) {
			$problems[] = 'a line is not UTF-8, so a character was split';
		}
	}
	$lines  = explode( "\r\n", rtrim( str_replace( "\r\n ", '', $file ), "\r\n" ) );
	$events = array();
	$zones  = array();
	$stack  = array();
	$now    = array();
	foreach ( $lines as $line ) {
		if ( 1 !== preg_match( '/^([A-Z-]+)((?:;[^:]+)?):(.*)$/s', $line, $part ) ) {
			$problems[] = 'not a content line: ' . $line;
			continue;
		}
		if ( 'BEGIN' === $part[1] ) {
			$stack[] = $part[3];
			if ( 'VEVENT' === $part[3] || 'VTIMEZONE' === $part[3] ) {
				$now = array( 'parts' => array() );
			} elseif ( in_array( 'VTIMEZONE', $stack, true ) && count( $stack ) > 2 ) {
				$now['parts'][] = array( 'kind' => $part[3] );
			}
			continue;
		}
		if ( 'END' === $part[1] ) {
			if ( array_pop( $stack ) !== $part[3] ) {
				$problems[] = 'END:' . $part[3] . ' closes something else';
			}
			if ( 'VEVENT' === $part[3] ) {
				unset( $now['parts'] );
				$events[] = $now;
			} elseif ( 'VTIMEZONE' === $part[3] ) {
				$zones[] = $now;
			}
			continue;
		}
		if ( in_array( 'VTIMEZONE', $stack, true ) && count( $stack ) > 2 ) {
			$now['parts'][ count( $now['parts'] ) - 1 ][ $part[1] ] = $part[3];
		} elseif ( $stack && 'VCALENDAR' !== end( $stack ) ) {
			$now[ $part[1] . $part[2] ] = $part[3];
		}
	}
	if ( $stack ) {
		$problems[] = 'something was left open';
	}
	return array(
		'lines'    => $lines,
		'events'   => $events,
		'zones'    => $zones,
		'problems' => $problems,
	);
}

// Text.
check( 'Trail day\\, bring gloves\\; meet at 8' === Ical::escape( 'Trail day, bring gloves; meet at 8' ), 'a comma and a semicolon are escaped' );
check( 'one\\ntwo\\nthree' === Ical::escape( "one\r\ntwo\rthree" ), 'a line break is written as \\n, whatever kind it was' );
check( 'a\\\\b' === Ical::escape( 'a\\b' ), 'a backslash is escaped' );
check( 'ab' === Ical::escape( "a\x00\x07b" ), 'a control character is dropped' );

// Lines.
check( 'SUMMARY:short' === Ical::fold( 'SUMMARY:short' ), 'a short line is left alone' );
$exact = str_repeat( 'x', 75 );
check( Ical::fold( $exact ) === $exact, 'a line of 75 octets is left alone' );
$folded = Ical::fold( 'DESCRIPTION:' . str_repeat( 'abcdefghij', 20 ) );
$rows   = explode( "\r\n", $folded );
check( 3 === count( $rows ) && 75 === strlen( $rows[0] ) && 75 === strlen( $rows[1] ) && ' ' === $rows[1][0], 'a long line is folded at 75 octets, each further line starting with a space' );
check( 'DESCRIPTION:' . str_repeat( 'abcdefghij', 20 ) === str_replace( "\r\n ", '', $folded ), 'and unfolds to what it was' );
$wide = Ical::fold( 'SUMMARY:' . str_repeat( 'é', 60 ) . str_repeat( '山', 30 ) );
check( ! array_filter( explode( "\r\n", $wide ), static fn( $row ) => strlen( $row ) > 75 || 1 !== preg_match( '//u', $row ) ), 'a character of several octets is never split' );

check( '-0600' === Ical::offset( -21600 ) && '+0530' === Ical::offset( 19800 ) && '+0000' === Ical::offset( 0 ), 'an offset is written as hours and minutes' );

// A file.
$denver  = Schedule::of(
	array(
		'start'      => '2026-03-07',
		'start_time' => '08:00',
		'end_time'   => '14:00',
		'timezone'   => 'America/Denver',
	)
);
$summer  = Schedule::of(
	array(
		'start'      => '2026-06-20',
		'start_time' => '09:00',
		'end_time'   => '11:30',
		'timezone'   => 'America/Denver',
	)
);
$chicago = Schedule::of(
	array(
		'start'      => '2026-04-11',
		'start_time' => '10:00',
		'timezone'   => 'America/Chicago',
	)
);
$all_day = Schedule::of(
	array(
		'start' => '2026-05-01',
		'end'   => '2026-05-03',
	),
	'America/Chicago'
);
$fixed   = Schedule::of(
	array(
		'start'      => '2026-05-09',
		'start_time' => '07:00',
		'end_time'   => '09:00',
		'timezone'   => '+05:30',
	)
);
$event   = static function ( int $id, array $schedule, string $summary, array $more = array() ): array {
	return $more + array(
		'uid'     => 'mission-' . $id . '@packout.test',
		'stamp'   => 1770000000,
		'start'   => $schedule['start'],
		'end'     => $schedule['end'],
		'all_day' => $schedule['all_day'],
		'zone'    => $schedule['zone'],
		'summary' => $summary,
	);
};
$file    = Ical::document(
	array(
		$event(
			1,
			$denver,
			'Forest Road 12, north end',
			array(
				'description' => "Bring gloves; we have bags.\nMeet at the gate, 8 am sharp. " . str_repeat( 'A long description that has to be folded. ', 4 ),
				'url'         => 'https://packout.test/mission/forest-road-12/',
				'location'    => 'Gate 4, Forest Road 12',
			)
		),
		$event( 2, $summer, 'Summer sweep' ),
		$event( 3, $chicago, 'Called off: river bank', array( 'cancelled' => true ) ),
		$event( 4, $all_day, 'Trail weekend' ),
		$event( 5, $fixed, 'Offset zone' ),
	),
	array(
		'product' => '-//Pack-Out Spot//Calendar 0.3.0//EN',
		'name'    => 'Pack-Out Spot, missions',
	)
);
$read    = packout_test_parse( $file );

check( array() === $read['problems'], 'the file reads back with no problem' . ( $read['problems'] ? ': ' . implode( '; ', $read['problems'] ) : '' ) );
check( 'BEGIN:VCALENDAR' === $read['lines'][0] && 'VERSION:2.0' === $read['lines'][1] && 'END:VCALENDAR' === end( $read['lines'] ), 'it is one calendar, version 2.0' );
check( in_array( 'PRODID:-//Pack-Out Spot//Calendar 0.3.0//EN', $read['lines'], true ) && in_array( 'X-WR-CALNAME:Pack-Out Spot\\, missions', $read['lines'], true ), 'it names what wrote it and what it is' );
check( 5 === count( $read['events'] ), 'it has one event for each event given' );
check( 5 === count( array_unique( array_column( $read['events'], 'UID' ) ) ) && 'mission-1@packout.test' === $read['events'][0]['UID'], 'each with its own identifier' );
check( ! array_filter( $read['events'], static fn( $row ) => '20260202T024000Z' !== $row['DTSTAMP'] ), 'and the time it was last changed, in UTC' );

check( '20260307T080000' === $read['events'][0]['DTSTART;TZID=America/Denver'] && '20260307T140000' === $read['events'][0]['DTEND;TZID=America/Denver'], 'a timed event is given on its own clock, with its zone named' );
check( 'Forest Road 12\\, north end' === $read['events'][0]['SUMMARY'] && 0 === strpos( $read['events'][0]['DESCRIPTION'], 'Bring gloves\\; we have bags.\\nMeet at the gate\\, 8 am sharp.' ), 'its text is escaped' );
check( 'https://packout.test/mission/forest-road-12/' === $read['events'][0]['URL'] && 'Gate 4\\, Forest Road 12' === $read['events'][0]['LOCATION'], 'it carries its address and its meeting place' );
check( 'CONFIRMED' === $read['events'][0]['STATUS'] && 'CANCELLED' === $read['events'][2]['STATUS'], 'a called-off mission is cancelled, and the others are not' );
check( ! isset( $read['events'][1]['DESCRIPTION'] ) && ! isset( $read['events'][1]['URL'] ) && ! isset( $read['events'][1]['LOCATION'] ), 'what an event does not have is left out' );
check( '20260501' === $read['events'][3]['DTSTART;VALUE=DATE'] && '20260504' === $read['events'][3]['DTEND;VALUE=DATE'], 'an all-day event is given as dates, and its end is the day after its last day' );
check( '20260509T013000Z' === $read['events'][4]['DTSTART'] && '20260509T033000Z' === $read['events'][4]['DTEND'], 'an event on a fixed offset is given in UTC' );
check( '20260411T110000' === $read['events'][2]['DTEND;TZID=America/Chicago'], 'an event with no stated end is an hour long' );

// Zones.
$named = array_column( $read['zones'], 'TZID' );
check( array( 'America/Chicago', 'America/Denver' ) === $named, 'the file describes each zone its timed events use, once, and no other' );
$mountain = $read['zones'][1]['parts'];
check( 2 === count( $mountain ) && 'STANDARD' === $mountain[0]['kind'] && 'DAYLIGHT' === $mountain[1]['kind'], 'a zone whose clocks change between its events says so' );
check( '-0700' === $mountain[0]['TZOFFSETFROM'] && '-0700' === $mountain[0]['TZOFFSETTO'] && 'MST' === $mountain[0]['TZNAME'], 'it starts in the state the first event is in' );
check( '20260308T020000' === $mountain[1]['DTSTART'] && '-0700' === $mountain[1]['TZOFFSETFROM'] && '-0600' === $mountain[1]['TZOFFSETTO'] && 'MDT' === $mountain[1]['TZNAME'], 'and gives the change in the time on the clock before it' );
$central = $read['zones'][0]['parts'];
check( 1 === count( $central ) && 'DAYLIGHT' === $central[0]['kind'] && '-0500' === $central[0]['TZOFFSETTO'], 'a zone whose clocks do not change across its events has one state' );

// Nothing.
$empty = packout_test_parse( Ical::document( array(), array() ) );
check( array() === $empty['problems'] && array() === $empty['events'] && array() === $empty['zones'] && 'END:VCALENDAR' === end( $empty['lines'] ), 'a file with no event is still a calendar' );
$days = packout_test_parse( Ical::document( array( $event( 4, $all_day, 'Trail weekend' ) ), array() ) );
check( array() === $days['zones'], 'all-day events need no zone described' );
check( 'text/calendar; charset=utf-8' === Ical::TYPE, 'a calendar file has its media type' );

finish();
