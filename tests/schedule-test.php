<?php
/**
 * A window as days, times, and a zone, and as moments in time.
 *
 * @package PackOut_Core
 */

require __DIR__ . '/bootstrap.php';

use PackOut\Core\Schedule;

check( '_pos_start_time' === Schedule::START_TIME_META && '_pos_end_time' === Schedule::END_TIME_META && '_pos_timezone' === Schedule::ZONE_META, 'the three fields have their names' );

// Days and times.
check( '2026-03-14' === Schedule::day( '2026-03-14' ) && '2026-03-14' === Schedule::day( ' 2026-03-14 ' ), 'a day is kept as it is' );
check( '' === Schedule::day( '2026-02-30' ) && '' === Schedule::day( '14/03/2026' ) && '' === Schedule::day( null ) && '' === Schedule::day( 20260314 ), 'what is not a day is dropped' );
check( '08:00' === Schedule::time( '08:00' ) && '23:59' === Schedule::time( '23:59' ) && '00:00' === Schedule::time( '00:00' ), 'a time is kept as it is' );
check( '08:00' === Schedule::time( '08:00:30' ), 'seconds are dropped' );
check( '' === Schedule::time( '24:00' ) && '' === Schedule::time( '8:00' ) && '' === Schedule::time( '08:60' ) && '' === Schedule::time( '8 am' ) && '' === Schedule::time( null ), 'what is not a time is dropped' );

// Zones.
check( Schedule::is_zone( 'America/Chicago' ) && Schedule::is_zone( 'UTC' ) && Schedule::is_zone( '+05:30' ) && Schedule::is_zone( '-08:00' ), 'a zone is an IANA name, UTC, or a fixed offset' );
check( ! Schedule::is_zone( 'Mountain' ) && ! Schedule::is_zone( '' ) && ! Schedule::is_zone( null ) && ! Schedule::is_zone( '+25:00' ) && ! Schedule::is_zone( 'CDT' ), 'anything else is not' );
check( 'America/Denver' === Schedule::zone( 'America/Denver', 'America/Chicago' ), 'a record keeps the zone it names' );
check( 'America/Chicago' === Schedule::zone( '', 'America/Chicago' ) && 'America/Chicago' === Schedule::zone( 'Nowhere/City', 'America/Chicago' ), 'a record that names none has the site’s' );
check( 'UTC' === Schedule::zone( '', 'Nowhere/City' ), 'and UTC when the site names none either' );
check( Schedule::is_named( 'America/Chicago' ) && ! Schedule::is_named( 'UTC' ) && ! Schedule::is_named( '+05:30' ), 'only an IANA name is a zone a calendar program knows by name' );

// What may be stored.
$window = Schedule::window( '2026-03-14', '2026-03-14', '08:00', '14:00' );
check( '' === $window['refusal'] && '08:00' === $window['start_time'] && '14:00' === $window['end_time'], 'a day with a start and an end time is accepted' );
check( '' === Schedule::window( '2026-03-14', '', '', '' )['refusal'] && '' === Schedule::window( '', '', '', '' )['refusal'], 'days alone, or nothing at all, are accepted' );
check( '' === Schedule::window( '2026-03-14', '', '08:00', '' )['refusal'], 'a start time with no end time is accepted' );
check( 'order' === Schedule::window( '2026-03-14', '2026-03-13', '', '' )['refusal'], 'a last day before the first is refused' );
check( 'order' === Schedule::window( '2026-03-14', '2026-03-14', '14:00', '08:00' )['refusal'], 'an end before a start on the same day is refused' );
check( 'order' === Schedule::window( '2026-03-14', '', '14:00', '08:00' )['refusal'], 'and the same with no last day given' );
check( '' === Schedule::window( '2026-03-14', '2026-03-15', '20:00', '02:00' )['refusal'], 'an end time earlier than the start time is fine on a later day' );
check( '' === Schedule::window( '2026-03-14', '2026-03-14', '08:00', '08:00' )['refusal'], 'an end at the start is not before it' );
check( 'end_time_alone' === Schedule::window( '2026-03-14', '', '', '14:00' )['refusal'], 'an end time needs a start time' );
check( 'time_without_day' === Schedule::window( '', '', '08:00', '' )['refusal'], 'a time needs a day' );

// As moments.
check( null === Schedule::of( array() ) && null === Schedule::of( array( 'start' => 'soon' ) ), 'a record with no day has no schedule' );

$old = Schedule::of( array( 'start' => '2026-03-14' ), 'America/Chicago' );
check( null !== $old && $old['all_day'] && ! $old['open_end'], 'a record with no start time is all-day' );
check( '2026-03-14 00:00' === $old['start']->format( 'Y-m-d H:i' ) && '2026-03-15 00:00' === $old['end']->format( 'Y-m-d H:i' ), 'an all-day window ends at the start of the day after its last day' );
check( '2026-03-14' === $old['first_day'] && '2026-03-14' === $old['last_day'] && 'America/Chicago' === $old['zone'], 'it names its days and the site’s zone' );

$span = Schedule::of(
	array(
		'start' => '2026-03-14',
		'end'   => '2026-03-20',
	),
	'America/Chicago'
);
check( '2026-03-21 00:00' === $span['end']->format( 'Y-m-d H:i' ) && '2026-03-20' === $span['last_day'], 'a window of several days covers its last day' );
$only_end = Schedule::of( array( 'end' => '2026-03-20' ), 'UTC' );
check( '2026-03-20' === $only_end['first_day'] && '2026-03-20' === $only_end['last_day'], 'a window with only a last day is that day' );
$backward = Schedule::of(
	array(
		'start' => '2026-03-14',
		'end'   => '2026-03-01',
	),
	'UTC'
);
check( '2026-03-14' === $backward['last_day'], 'a stored last day before the first is not believed' );

$fields = array(
	'start'      => '2026-03-14',
	'end'        => '2026-03-14',
	'start_time' => '08:00',
	'end_time'   => '14:00',
	'timezone'   => 'America/Denver',
);
$denver = Schedule::of( $fields, 'America/Chicago' );
check( ! $denver['all_day'] && ! $denver['open_end'] && 'America/Denver' === $denver['zone'], 'a record with times is timed, in its own zone' );
check( '08:00' === $denver['start']->format( 'H:i' ) && '14:00' === $denver['end']->format( 'H:i' ), 'it starts and ends at the times on its own clock' );
check( 'MDT' === Schedule::abbreviation( $denver['start'] ), 'and names Mountain time' );
$elsewhere = Schedule::of( $fields, 'Asia/Tokyo' );
check( $denver['start']->getTimestamp() === $elsewhere['start']->getTimestamp() && $denver['end']->getTimestamp() === $elsewhere['end']->getTimestamp(), 'the moment is the same whatever zone the site is set to' );
check( 1773496800 === $denver['start']->getTimestamp(), 'and it is 14:00 UTC' );

$site = Schedule::of( array( 'timezone' => '' ) + $fields, 'America/Chicago' );
check( 'America/Chicago' === $site['zone'] && 'CDT' === Schedule::abbreviation( $site['start'] ) && 3600 === $denver['start']->getTimestamp() - $site['start']->getTimestamp(), 'a timed record that names no zone is on the site’s clock' );

$open = Schedule::of( array( 'end_time' => '' ) + $fields, 'UTC' );
check( $open['open_end'] && 3600 === $open['end']->getTimestamp() - $open['start']->getTimestamp(), 'a start time with no end time is drawn one hour long' );
$long = Schedule::of(
	array(
		'end'      => '2026-03-16',
		'end_time' => '',
	) + $fields,
	'UTC'
);
check( $long['open_end'] && '2026-03-17 00:00' === $long['end']->format( 'Y-m-d H:i' ) && '2026-03-16' === $long['last_day'], 'over several days it runs to the end of its last day' );
$bad = Schedule::of( array( 'end_time' => '07:00' ) + $fields, 'UTC' );
check( $bad['open_end'] && '09:00' === $bad['end']->format( 'H:i' ), 'a stored end before the start is not believed' );
$alone = Schedule::of(
	array(
		'start'    => '2026-03-14',
		'end_time' => '14:00',
	),
	'UTC'
);
check( $alone['all_day'], 'an end time with no start time is all-day' );

// Winter and summer.
$winter = Schedule::of(
	array(
		'start'      => '2026-01-10',
		'start_time' => '08:00',
		'timezone'   => 'America/Denver',
	)
);
check( 'MST' === Schedule::abbreviation( $winter['start'] ), 'the zone’s name follows the season' );
$offset = Schedule::of(
	array(
		'start'      => '2026-01-10',
		'start_time' => '08:00',
		'timezone'   => '+05:30',
	)
);
check( 'UTC+5:30' === Schedule::abbreviation( $offset['start'] ), 'a fixed offset is named by its offset' );
$utc = Schedule::of(
	array(
		'start'      => '2026-01-10',
		'start_time' => '08:00',
		'timezone'   => 'UTC',
	)
);
check( 'UTC' === Schedule::abbreviation( $utc['start'] ), 'UTC is UTC' );

// Over or not.
check( Schedule::is_over( $denver, $denver['end']->getTimestamp() ) && ! Schedule::is_over( $denver, $denver['end']->getTimestamp() - 1 ), 'a window is over from its end' );
check( ! Schedule::is_over( $old, ( new DateTimeImmutable( '2026-03-14 23:59:59', new DateTimeZone( 'America/Chicago' ) ) )->getTimestamp() ), 'an all-day window lasts to the end of its day on its own clock' );

finish();
