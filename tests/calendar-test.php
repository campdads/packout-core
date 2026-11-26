<?php
/**
 * The forms of a calendar, the days each covers, and where an event sits.
 *
 * @package PackOut_Core
 */

require __DIR__ . '/bootstrap.php';

use PackOut\Core\Calendar;

check( array( 'day', 'week', 'month', 'agenda', 'upcoming', 'past' ) === Calendar::VIEWS, 'a calendar has six forms' );
check( array( 'day', 'week', 'month', 'agenda' ) === Calendar::SWITCHABLE, 'a visitor switches among four of them' );
check( 'week' === Calendar::view( 'week' ) && 'month' === Calendar::view( 'year' ) && 'month' === Calendar::view( null ), 'an unknown form is a month' );
check( 10 === Calendar::count( '' ) && 10 === Calendar::count( 0 ) && 3 === Calendar::count( '3' ) && 50 === Calendar::count( 500 ), 'a list shows ten, or what was asked up to fifty' );
check( Calendar::KINDS === Calendar::kinds( '' ) && Calendar::KINDS === Calendar::kinds( array( 'parties' ) ), 'a calendar shows everything unless told' );
check( array( 'missions', 'programs' ) === Calendar::kinds( 'programs,missions' ) && array( 'campaigns' ) === Calendar::kinds( array( 'campaigns' ) ), 'or the kinds it was told, in their own order' );
check( 0 === Calendar::week_start( 'sunday', 1 ) && 1 === Calendar::week_start( 'monday', 0 ) && 6 === Calendar::week_start( 'site', 6 ) && 0 === Calendar::week_start( 'site', 9 ), 'a week starts on Sunday, on Monday, or when the site says' );

// Days.
check( '2026-03-01' === Calendar::shift( '2026-02-28', 1 ) && '2024-02-29' === Calendar::shift( '2024-03-01', -1 ) && '2026-01-01' === Calendar::shift( '2025-12-31', 1 ), 'a day moves across months and years' );
check( '2026-03-09' === Calendar::shift( '2026-03-07', 2 ), 'and across the day the clocks change' );
check( 14 === Calendar::between( '2026-03-01', '2026-03-15' ) && -1 === Calendar::between( '2026-03-02', '2026-03-01' ) && 0 === Calendar::between( '2026-03-01', '2026-03-01' ), 'the days between two days are counted' );
check( 6 === Calendar::weekday( '2026-03-14' ) && 0 === Calendar::weekday( '2026-03-15' ), 'a day knows its day of the week' );
check( '2026-03-08' === Calendar::week_of( '2026-03-14', 0 ) && '2026-03-09' === Calendar::week_of( '2026-03-14', 1 ) && '2026-03-09' === Calendar::week_of( '2026-03-15', 1 ) && '2026-03-15' === Calendar::week_of( '2026-03-15', 0 ), 'a week starts on the day it is told' );

// Ranges.
check(
	array(
		'from' => '2026-03-14',
		'to'   => '2026-03-14',
	) === Calendar::range( 'day', '2026-03-14' ),
	'a day covers itself'
);
check(
	array(
		'from' => '2026-03-08',
		'to'   => '2026-03-14',
	) === Calendar::range( 'week', '2026-03-11', 0 ),
	'a week covers seven days'
);
check(
	array(
		'from' => '2026-03-01',
		'to'   => '2026-04-04',
	) === Calendar::range( 'month', '2026-03-14', 0 ),
	'a month covers whole weeks'
);
check(
	array(
		'from' => '2026-02-23',
		'to'   => '2026-04-05',
	) === Calendar::range( 'month', '2026-03-31', 1 ),
	'and starts them on the day it is told'
);
check(
	array(
		'from' => '2026-02-01',
		'to'   => '2026-02-28',
	) === Calendar::range( 'month', '2026-02-10', 0 ),
	'a February of four whole weeks is four weeks'
);
$agenda = Calendar::range( 'agenda', '2026-03-14' );
check( '2026-03-14' === $agenda['from'] && 61 === Calendar::between( $agenda['from'], $agenda['to'] ), 'an agenda runs forward from its day' );
foreach ( array( '2026-01-15', '2026-05-31', '2026-08-01', '2027-02-01' ) as $day ) {
	foreach ( array( 0, 1, 6 ) as $start ) {
		$range = Calendar::range( 'month', $day, $start );
		$whole = 0 === ( Calendar::between( $range['from'], $range['to'] ) + 1 ) % 7 && Calendar::weekday( $range['from'] ) === $start && $range['from'] <= substr( $day, 0, 8 ) . '01' && '' === Calendar::refusal( $range['from'], $range['to'] );
		check( $whole, 'the month of ' . $day . ' starting on day ' . $start . ' is whole weeks that may be asked for' );
	}
}

// Steps.
check( '2026-03-15' === Calendar::step( 'day', '2026-03-14', 1 ) && '2026-03-13' === Calendar::step( 'day', '2026-03-14', -1 ), 'a day steps by a day' );
check( '2026-03-21' === Calendar::step( 'week', '2026-03-14', 1 ) && '2026-03-07' === Calendar::step( 'week', '2026-03-14', -1 ), 'a week steps by a week' );
check( '2026-02-01' === Calendar::step( 'month', '2026-01-31', 1 ) && '2025-12-01' === Calendar::step( 'month', '2026-01-31', -1 ) && '2027-01-01' === Calendar::step( 'month', '2026-12-15', 1 ), 'a month steps to the first of the next, whatever day it was on' );
check( '2026-05-15' === Calendar::step( 'agenda', '2026-03-14', 1 ), 'an agenda steps past what it covered' );

// What may be asked for.
check( '' === Calendar::refusal( '2026-03-01', '2026-05-02' ), 'a range of 62 days may be asked for' );
check( 'long' === Calendar::refusal( '2026-03-01', '2026-05-03' ), 'a longer one is refused' );
check( 'order' === Calendar::refusal( '2026-03-02', '2026-03-01' ), 'a range that ends before it starts is refused' );
check( 'invalid' === Calendar::refusal( '', '2026-03-01' ) && 'invalid' === Calendar::refusal( '2026-03-01', 'later' ) && 'invalid' === Calendar::refusal( null, null ), 'and so is one that is not two days' );

// Days and weeks.
check( array( '2026-02-27', '2026-02-28', '2026-03-01' ) === Calendar::days( '2026-02-27', '2026-03-01' ) && array() === Calendar::days( '2026-03-02', '2026-03-01' ), 'a range lists its days' );
$weeks = Calendar::weeks( '2026-03-01', '2026-04-04' );
check( 5 === count( $weeks ) && 7 === count( $weeks[4] ) && '2026-03-08' === $weeks[1][0] && '2026-04-04' === $weeks[4][6], 'and its weeks' );
check( Calendar::overlaps( '2026-03-10', '2026-03-20', '2026-03-20', '2026-03-25' ) && Calendar::overlaps( '2026-03-10', '2026-03-20', '2026-03-01', '2026-03-10' ) && Calendar::overlaps( '2026-01-01', '2026-12-31', '2026-03-01', '2026-03-31' ), 'an event is in a range when any of its days is' );
check( ! Calendar::overlaps( '2026-03-10', '2026-03-20', '2026-03-21', '2026-03-25' ) && ! Calendar::overlaps( '2026-03-10', '2026-03-20', '2026-03-01', '2026-03-09' ), 'and not when none is' );

// Where a timed event sits.
$zone  = new DateTimeZone( 'America/Denver' );
$start = new DateTimeImmutable( '2026-03-14 08:00', $zone );
$end   = new DateTimeImmutable( '2026-03-14 14:00', $zone );
check( array( 480, 840 ) === Calendar::segment( $start, $end, '2026-03-14' ), 'an event sits from its start to its end, in minutes' );
$late = new DateTimeImmutable( '2026-03-15 02:00', $zone );
check( array( 480, 1440 ) === Calendar::segment( $start, $late, '2026-03-14' ) && array( 0, 120 ) === Calendar::segment( $start, $late, '2026-03-15' ), 'an event that crosses midnight is cut at it' );
check( array( 480, 510 ) === Calendar::segment( $start, $start, '2026-03-14' ), 'an event with no length is still tall enough to read' );
check( array( 1410, 1440 ) === Calendar::segment( new DateTimeImmutable( '2026-03-14 23:50', $zone ), new DateTimeImmutable( '2026-03-14 23:55', $zone ), '2026-03-14' ), 'and one at the very end of the day stays on the day' );

check( array( 8, 18 ) === Calendar::hours( array() ) && array( 8, 18 ) === Calendar::hours( array( array( 540, 600 ) ) ), 'a day’s scale is never less than 8 am to 6 pm' );
check( array( 6, 21 ) === Calendar::hours( array( array( 390, 600 ), array( 1000, 1230 ) ) ), 'it grows to the earliest and the latest event' );
check( array( 0, 24 ) === Calendar::hours( array( array( 0, 1440 ) ) ), 'and to the whole day at most' );

$columns = Calendar::columns(
	array(
		'a' => array( 480, 600 ),
		'b' => array( 540, 660 ),
		'c' => array( 600, 700 ),
		'd' => array( 900, 960 ),
	)
);
check( 0 === $columns['a']['column'] && 1 === $columns['b']['column'] && 0 === $columns['c']['column'], 'events that overlap sit side by side, and a later one takes a freed place' );
check( 2 === $columns['a']['of'] && 2 === $columns['b']['of'] && 2 === $columns['c']['of'], 'each knows how many share its row' );
check( 0 === $columns['d']['column'] && 1 === $columns['d']['of'], 'an event on its own has the whole width' );
check( array() === Calendar::columns( array() ), 'no events, no columns' );

// Order.
$sorted = Calendar::sorted(
	array(
		array(
			'id'    => 4,
			'kind'  => 'mission',
			'title' => 'B',
			'start' => 200,
		),
		array(
			'id'    => 3,
			'kind'  => 'mission',
			'title' => 'A',
			'start' => 200,
		),
		array(
			'id'    => 2,
			'kind'  => 'campaign',
			'title' => 'Z',
			'start' => 200,
		),
		array(
			'id'    => 1,
			'kind'  => 'mission',
			'title' => 'Early',
			'start' => 100,
		),
		array(
			'id'    => 9,
			'kind'  => 'program',
			'title' => 'Z',
			'start' => 200,
		),
		array(
			'id'    => 5,
			'kind'  => 'mission',
			'title' => 'A',
			'start' => 200,
		),
	)
);
check( array( 1, 9, 2, 3, 5, 4 ) === array_column( $sorted, 'id' ), 'events are listed by start, spans first, then by title, then by id' );

finish();
