<?php
/**
 * The days the copies of a repeated mission fall on.
 *
 * @package PackOut_Core
 */

require __DIR__ . '/bootstrap.php';

use PackOut\Core\Repeat;

check( '2' === Repeat::every( '2' ) && '12' === Repeat::every( 12 ) && 'month' === Repeat::every( 'month' ), 'an interval is one to twelve weeks, or a month' );
check( '' === Repeat::every( '0' ) && '' === Repeat::every( '13' ) && '' === Repeat::every( 'year' ) && '' === Repeat::every( null ) && '' === Repeat::every( '2.5' ), 'anything else is not' );
check( 3 === Repeat::copies( '3' ) && 12 === Repeat::copies( 12 ) && 0 === Repeat::copies( '0' ) && 0 === Repeat::copies( '13' ) && 0 === Repeat::copies( 'many' ) && 0 === Repeat::copies( null ), 'copies are one to twelve' );
check( array( 'checklist', 'teams' ) === Repeat::carry( array( 'teams', 'checklist', 'spots' ) ) && array() === Repeat::carry( null ) && Repeat::CARRY === Repeat::carry( Repeat::CARRY ), 'a copy carries over what was ticked, of what can be' );
check( ! in_array( 'spots', Repeat::CARRY, true ), 'Spots are never carried: a Spot belongs to the visit that cleans it' );

check( '' === Repeat::refusal( '2026-03-14', '2', 3 ), 'a dated mission can be repeated' );
check( 'undated' === Repeat::refusal( '', '2', 3 ) && 'every' === Repeat::refusal( '2026-03-14', '', 3 ) && 'copies' === Repeat::refusal( '2026-03-14', '2', 0 ), 'one with no day, no interval, or no copies cannot' );
check( array() === Repeat::dates( '', '', '2', 3 ) && array() === Repeat::dates( '2026-03-14', '', '', 3 ), 'and has no dates' );

$weeks = Repeat::dates( '2026-03-14', '', '2', 3 );
check( array( '2026-03-28', '2026-04-11', '2026-04-25' ) === array_column( $weeks, 'start' ), 'every two weeks, three copies, are fourteen days apart' );
check( array( '', '', '' ) === array_column( $weeks, 'end' ), 'a mission with no last day makes copies with none' );
$long = Repeat::dates( '2026-03-13', '2026-03-15', '1', 2 );
check( array( '2026-03-20', '2026-03-27' ) === array_column( $long, 'start' ) && array( '2026-03-22', '2026-03-29' ) === array_column( $long, 'end' ), 'a copy lasts as many days as the mission' );
$same = Repeat::dates( '2026-03-14', '2026-03-14', '1', 1 );
check( '2026-03-21' === $same[0]['start'] && '2026-03-21' === $same[0]['end'], 'a one-day mission makes one-day copies' );
check( 12 === count( Repeat::dates( '2026-03-14', '', '12', 12 ) ) && '2028-12-16' === Repeat::dates( '2026-03-14', '', '12', 12 )[11]['start'], 'twelve copies twelve weeks apart reach into later years' );

$months = Repeat::dates( '2026-01-31', '', 'month', 4 );
check( array( '2026-02-28', '2026-03-31', '2026-04-30', '2026-05-31' ) === array_column( $months, 'start' ), 'monthly copies fall on the same day, or the last day of a shorter month' );
$turn = Repeat::dates( '2026-11-15', '2026-11-16', 'month', 3 );
check( array( '2026-12-15', '2027-01-15', '2027-02-15' ) === array_column( $turn, 'start' ) && '2027-01-16' === $turn[1]['end'], 'and cross the year' );
check( '2028-02-29' === Repeat::dates( '2028-01-31', '', 'month', 1 )[0]['start'], 'a leap year’s February has its 29th' );

finish();
