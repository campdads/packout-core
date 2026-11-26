<?php
/**
 * The parity check finds each way a plugin can fall short of the list.
 *
 * @package PackOut_Core
 */

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/adapters.php';

use PackOut\Core\Capability_Map;
use PackOut\Core\Parity;

/**
 * The codes of a list of problems, with the id each is about.
 *
 * @param array<int, array<string, string>> $problems Problems.
 * @return array<int, string>
 */
function codes( array $problems ): array {
	$codes = array();
	foreach ( $problems as $problem ) {
		$codes[] = $problem['code'] . ':' . $problem['id'];
	}
	sort( $codes );
	return $codes;
}

$rows = array(
	'spot.report' => array(
		'group'   => 'Spots',
		'act'     => 'Report a Spot',
		'who'     => 'Visitor',
		'surface' => 'both',
		'reason'  => '',
		'since'   => array(
			'hub'     => 0,
			'partner' => 5,
		),
	),
	'place.draw'  => array(
		'group'   => 'Map',
		'act'     => 'Outline a Place',
		'who'     => 'Coordinator',
		'surface' => 'hub',
		'reason'  => 'Places are the hub’s',
		'since'   => array( 'hub' => 0 ),
	),
	'site.wizard' => array(
		'group'   => 'Admin',
		'act'     => 'Run the setup wizard',
		'who'     => 'Administrator',
		'surface' => 'partner',
		'reason'  => 'The hub is set up by a script',
		'since'   => array( 'partner' => 8 ),
	),
	'event.plan'  => array(
		'group'   => 'Missions',
		'act'     => 'Repeat a mission',
		'who'     => 'Lead',
		'surface' => 'both',
		'reason'  => '',
		'since'   => array(
			'hub'     => 4,
			'partner' => 5,
		),
	),
);
check( array() === Parity::malformed( $rows ), 'the test rows are well formed' );

$done = 'strlen';

// The hub.
check(
	array() === Parity::check(
		$rows,
		array(
			'spot.report' => $done,
			'place.draw'  => $done,
		),
		'hub',
		2
	),
	'a hub with a handler for every act it has reached keeps the list'
);
check( array( 'missing:spot.report' ) === codes( Parity::check( $rows, array( 'place.draw' => $done ), 'hub', 2 ) ), 'a hub without a handler for an act of both plugins fails' );
check( array( 'missing:place.draw' ) === codes( Parity::check( $rows, array( 'spot.report' => $done ), 'hub', 2 ) ), 'a hub without a handler for an act of its own fails' );
check(
	array( 'missing:event.plan' ) === codes(
		Parity::check(
			$rows,
			array(
				'spot.report' => $done,
				'place.draw'  => $done,
			),
			'hub',
			4
		)
	),
	'a row is expected once its phase is reached'
);
check(
	array() === Parity::check(
		$rows,
		array(
			'spot.report' => $done,
			'place.draw'  => $done,
			'event.plan'  => $done,
		),
		'hub',
		2
	),
	'a handler may arrive before its phase'
);
check(
	array( 'foreign:site.wizard' ) === codes(
		Parity::check(
			$rows,
			array(
				'spot.report' => $done,
				'place.draw'  => $done,
				'site.wizard' => $done,
			),
			'hub',
			2
		)
	),
	'a hub with a handler for a partner-only act fails'
);

// The partner plugin.
check( array() === Parity::check( $rows, array(), 'partner', 2 ), 'a partner plugin that has reached no row keeps the list with no handlers' );
check( array( 'missing:event.plan', 'missing:spot.report' ) === codes( Parity::check( $rows, array(), 'partner', 5 ) ), 'at phase 5 the partner plugin owes every act of both plugins built by then' );
check(
	array( 'missing:site.wizard' ) === codes(
		Parity::check(
			$rows,
			array(
				'spot.report' => $done,
				'event.plan'  => $done,
			),
			'partner',
			8
		)
	),
	'at phase 8 it owes its own acts too'
);
check( array( 'foreign:place.draw' ) === codes( Parity::check( $rows, array( 'place.draw' => $done ), 'partner', 2 ) ), 'a partner plugin with a handler for a hub-only act fails' );
check(
	array() === Parity::check(
		$rows,
		array(
			'spot.report' => $done,
			'event.plan'  => $done,
			'site.wizard' => $done,
		),
		'partner',
		12
	),
	'a partner plugin with every handler keeps the list'
);

// Handlers.
check(
	array( 'unknown:spot.teleport' ) === codes(
		Parity::check(
			$rows,
			array(
				'spot.report'   => $done,
				'place.draw'    => $done,
				'spot.teleport' => $done,
			),
			'hub',
			2
		)
	),
	'a handler for an act that is not in the list fails'
);
check(
	array( 'uncallable:spot.report' ) === codes(
		Parity::check(
			$rows,
			array(
				'spot.report' => array( 'No_Such_Class', 'method' ),
				'place.draw'  => $done,
			),
			'hub',
			2
		)
	),
	'a handler that cannot be called fails'
);
check( array( 'surface:' ) === codes( Parity::check( $rows, array(), 'both', 2 ) ), 'a plugin must be the hub or a partner site' );

// Rows.
$bad                           = $rows;
$bad['place.draw']['reason']   = '';
$bad['spot.report']['surface'] = 'everywhere';
$bad['Site Wizard']            = $rows['site.wizard'];
$bad['event.plan']['since']    = array( 'hub' => 4 );
$bad['site.wizard']['since']   = array(
	'hub'     => 1,
	'partner' => 8,
);
$bad['spot.blank']             = array(
	'group'   => 'Spots',
	'act'     => '',
	'who'     => 'Visitor',
	'surface' => 'both',
	'reason'  => '',
	'since'   => array(
		'hub'     => 0,
		'partner' => 5,
	),
);
$found                         = codes( Parity::malformed( $bad ) );
check( in_array( 'reason:place.draw', $found, true ), 'a row only one plugin has needs a reason' );
check( in_array( 'surface:spot.report', $found, true ), 'a surface must be both, hub, or partner' );
check( in_array( 'id:Site Wizard', $found, true ), 'an id is lowercase words joined by dots' );
check( in_array( 'since:event.plan', $found, true ), 'a row of both plugins gives a phase for each' );
check( in_array( 'since:site.wizard', $found, true ), 'a partner-only row gives no phase for the hub' );
check( in_array( 'field:spot.blank', $found, true ), 'a row says what the act is' );
check( in_array( 'reason:place.draw', codes( Parity::check( $bad, array(), 'hub', 0 ) ), true ), 'the full check reports malformed rows too' );

// The map itself.
Capability_Map::reset();
check( array() === Capability_Map::handlers(), 'the map starts with no handlers' );
Capability_Map::handle( 'mission.complete', $done );
Capability_Map::handle_all(
	array(
		'mission.start'   => $done,
		'mission.calloff' => $done,
	)
);
check( 3 === count( Capability_Map::handlers() ), 'handlers are kept by id' );
check( Capability_Map::handler( 'mission.start' ) === $done, 'a handler can be read back' );
check( null === Capability_Map::handler( 'mission.repeat' ), 'an act with no handler reads as null' );
Capability_Map::forget( 'mission.start' );
check( null === Capability_Map::handler( 'mission.start' ), 'a handler can be removed' );

// The real list, for a plugin at the very start.
Capability_Map::reset();
$site = new Test_Site();
check( array() === Parity::check_site( $site ), 'the partner plugin owes nothing at phase 2' );
$site->phase = 5;
$owed        = Parity::check_site( $site );
check( count( $owed ) > 50 && array( 'missing' ) === array_values( array_unique( array_column( $owed, 'code' ) ) ), 'at phase 5 it owes the acts both plugins have, and nothing else is wrong' );
$site->surface = 'hub';
$site->phase   = 0;
$owed          = Parity::check_site( $site );
check( count( $owed ) > 50, 'a hub with no handlers owes what it had before the build began' );
check( ! in_array( 'partner.approve', array_column( $owed, 'id' ), true ), 'and not what a later phase builds' );

finish();
