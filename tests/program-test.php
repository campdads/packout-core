<?php
/**
 * A program's owner, who takes part, and what may be filed under it.
 *
 * @package PackOut_Core
 */

require __DIR__ . '/bootstrap.php';

use PackOut\Core\Program;

check( 'pos_program' === Program::POST_TYPE && '_pos_program' === Program::FILED_META, 'the record and the field on a campaign or mission have their names' );
check( 'open' === Program::state( 'open' ) && 'closed' === Program::state( 'closed' ), 'a program is open or closed' );
check( 'open' === Program::state( '' ) && 'open' === Program::state( null ) && 'open' === Program::state( 'finished' ), 'anything else reads as open' );

// Owner.
$partner = Program::owner( 7, 0 );
$team    = Program::owner( 0, 12 );
check(
	array(
		'kind' => 'partner',
		'id'   => 7,
	) === $partner,
	'a program that names a partner is the partner’s'
);
check(
	array(
		'kind' => 'team',
		'id'   => 12,
	) === $team,
	'a program that names an organization is the organization’s'
);
check( Program::owner( 7, 12 ) === $partner, 'a program a partner took over is the partner’s' );
check( '' === Program::owner( 0, 0 )['kind'] && 0 === Program::owner( 0, 0 )['id'], 'a program that names neither has no owner' );

// Taking part.
check( array( 12 ) === Program::taking_part( $team, array( 12, 13, 14 ) ), 'an organization’s own program has only itself' );
check( array( 13, 14 ) === Program::taking_part( $partner, array( 13, '14', 13, 0, 'x' ), array( 13, 14, 15 ) ), 'a partner’s program takes the organizations it names, each once' );
check( array( 13 ) === Program::taking_part( $partner, array( 13, 20 ), array( 13, 14 ) ), 'an organization that is not the partner’s does not take part' );
check( array( 13 ) === Program::taking_part( $partner, array( 13, 14 ), array( 13 ) ), 'an organization that left the partner no longer takes part' );
check( array() === Program::taking_part( $partner, array(), array( 13 ) ), 'a partner’s program takes nobody until it names them' );
check( array() === Program::taking_part( Program::owner( 0, 0 ), array( 13 ), array( 13 ) ), 'a program with no owner takes nobody' );

// Filing.
check( '' === Program::refusal( 'open', array( 13, 14 ), 13 ), 'an organization taking part files its work under an open program' );
check( 'outside' === Program::refusal( 'open', array( 13, 14 ), 20 ), 'an organization that does not take part is refused' );
check( 'outside' === Program::refusal( 'open', array( 13 ), 0 ), 'work with no organization is refused' );
check( 'closed' === Program::refusal( 'closed', array( 13 ), 13 ), 'a closed program takes nothing new' );
check( 'outside' === Program::refusal( 'closed', array( 13 ), 20 ), 'an outsider is told it is outside before it is told the program is closed' );

// A mission follows its campaign.
check( 5 === Program::of_mission( 0, 30, 5 ), 'a mission in a campaign with a program has that program' );
check( 5 === Program::of_mission( 9, 30, 5 ), 'its own field does not count while it is in a campaign' );
check( 0 === Program::of_mission( 9, 30, 0 ), 'a mission in a campaign with no program has none' );
check( 9 === Program::of_mission( 9, 0, 0 ), 'a mission in no campaign has the program of its own field' );
check( 0 === Program::of_mission( 0, 0, 5 ), 'a campaign’s program means nothing to a mission that is not in it' );
check( 0 === Program::of_mission( -3, 0, 0 ) && 0 === Program::of_mission( 0, 30, -3 ), 'a program is never a negative number' );
check( Program::offers_field( 0 ) && ! Program::offers_field( 30 ), 'the mission form offers a Program field only outside a campaign' );

// Window.
check(
	array(
		'start' => '2027-03-01',
		'end'   => '2027-10-31',
	) === Program::window( '2027-03-01', '2027-10-31' ),
	'a window keeps its two days'
);
check(
	array(
		'start' => '2027-03-01',
		'end'   => '',
	) === Program::window( '2027-03-01', '' ),
	'a program may have a start and no end'
);
check(
	array(
		'start' => '',
		'end'   => '',
	) === Program::window( '', '' ),
	'a program may have no window'
);
check(
	array(
		'start' => '2027-03-01',
		'end'   => '',
	) === Program::window( '2027-03-01', '2027-02-01' ),
	'an end before the start is dropped'
);
check(
	array(
		'start' => '',
		'end'   => '',
	) === Program::window( '', '2027-10-31' ),
	'an end with no start is dropped'
);
check(
	array(
		'start' => '2027-03-01',
		'end'   => '2027-03-01',
	) === Program::window( '2027-03-01', '2027-03-01' ),
	'a program may last one day'
);
check(
	array(
		'start' => '',
		'end'   => '',
	) === Program::window( '2027-02-30', '03/01/2027' ),
	'a day that is not a day is dropped'
);
check(
	array(
		'start' => '',
		'end'   => '',
	) === Program::window( null, array() ),
	'a window that is not text is dropped'
);

// Public.
check( Program::shown( true, true ), 'a public program of a listed owner has a page' );
check( ! Program::shown( false, true ) && ! Program::shown( true, false ), 'a private program, or one whose owner is not listed, has none' );

finish();
