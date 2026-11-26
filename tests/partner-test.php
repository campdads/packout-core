<?php
/**
 * A partner's states, what each allows, and who leads it.
 *
 * @package PackOut_Core
 */

require __DIR__ . '/bootstrap.php';

use PackOut\Core\Partner;

check( 'pos_partner' === Partner::POST_TYPE && '_pos_partner' === Partner::ORGANIZATION_META, 'the record and the organization field have their names' );
check( array( 'pending', 'active', 'suspended', 'dormant', 'disconnected' ) === Partner::STATES, 'there are five states' );

check( 'active' === Partner::state( 'active' ), 'a known state is kept' );
check( 'pending' === Partner::state( 'approved' ) && 'pending' === Partner::state( '' ) && 'pending' === Partner::state( null ) && 'pending' === Partner::state( array( 'active' ) ), 'anything unknown reads as pending' );
check( 'remote' === Partner::origin( 'remote' ) && '' === Partner::origin( 'hub' ) && '' === Partner::origin( null ), 'an origin is remote or nothing' );

// A pending partner.
check( Partner::allows( 'pending', 'profile' ), 'a pending partner may change its profile and invite co-leads' );
check( ! Partner::allows( 'pending', 'organization' ), 'no organization can be filed under a pending partner' );
check( ! Partner::allows( 'pending', 'program' ), 'a pending partner cannot open a program' );
check( ! Partner::allows( 'pending', 'images' ), 'a pending partner has no logo or cover' );
check( ! Partner::shown( 'pending', true, 'page' ) && ! Partner::shown( 'pending', true, 'directory' ), 'a pending partner is not shown, whatever it chose' );

// An active partner.
foreach ( array( 'profile', 'images', 'organization', 'program', 'message', 'page', 'directory', 'totals' ) as $act ) {
	check( Partner::allows( 'active', $act ), 'an active partner is allowed: ' . $act );
}
check( Partner::shown( 'active', true, 'page' ) && Partner::shown( 'active', true, 'directory' ) && Partner::shown( 'active', true, 'totals' ), 'a listed, active partner is shown' );
check( ! Partner::shown( 'active', false, 'page' ) && ! Partner::shown( 'active', false, 'directory' ), 'an active partner that is not listed is not shown' );
check( ! Partner::shown( 'active', true, 'profile' ), 'only a page, a card, and figures are things the public sees' );
check( ! Partner::allows( 'active', 'anything else' ), 'an act that is not on the list is not allowed' );

// A suspended partner.
check( ! Partner::shown( 'suspended', true, 'page' ) && ! Partner::shown( 'suspended', true, 'directory' ) && ! Partner::shown( 'suspended', true, 'totals' ), 'a suspended partner leaves public view' );
check( ! Partner::allows( 'suspended', 'program' ) && ! Partner::allows( 'suspended', 'organization' ) && ! Partner::allows( 'suspended', 'message' ), 'a suspended partner opens nothing and writes to nobody' );
check( Partner::allows( 'suspended', 'profile' ), 'a suspended partner keeps its profile' );

// A dormant partner.
check( Partner::shown( 'dormant', true, 'page' ) && Partner::shown( 'dormant', true, 'totals' ), 'a dormant partner keeps its page and its totals' );
check( ! Partner::shown( 'dormant', true, 'directory' ), 'a dormant partner is off the directory and the filter' );
check( ! Partner::allows( 'dormant', 'program' ), 'a dormant partner opens no program' );

// A disconnected partner.
foreach ( array( 'profile', 'images', 'organization', 'program', 'message', 'page', 'directory', 'totals' ) as $act ) {
	check( ! Partner::allows( 'disconnected', $act ), 'a disconnected partner is not allowed: ' . $act );
}

// Steps.
check( 'active' === Partner::step( 'pending', 'approve' ), 'approving a pending partner makes it active' );
check( '' === Partner::step( 'pending', 'decline' ), 'declining a pending partner leads to no state' );
check( null === Partner::step( 'active', 'approve' ) && null === Partner::step( 'active', 'decline' ), 'an active partner cannot be approved or declined again' );
check( 'suspended' === Partner::step( 'active', 'suspend' ) && 'active' === Partner::step( 'suspended', 'lift' ), 'a coordinator suspends an active partner and lifts it' );
check( null === Partner::step( 'pending', 'suspend' ) && null === Partner::step( 'active', 'lift' ), 'a pending partner is not suspended, and nothing is lifted that was not suspended' );
check( null === Partner::step( 'active', 'quiet' ) && null === Partner::step( 'active', 'disconnect' ), 'a partner kept on this site never goes dormant or disconnects' );
check( 'dormant' === Partner::step( 'active', 'quiet', 'remote' ) && 'active' === Partner::step( 'dormant', 'heard', 'remote' ), 'a partner site goes dormant and comes back' );
check( 'suspended' === Partner::step( 'dormant', 'suspend', 'remote' ), 'a dormant partner can be suspended' );
check( 'disconnected' === Partner::step( 'active', 'disconnect', 'remote' ) && null === Partner::step( 'disconnected', 'disconnect', 'remote' ), 'a partner site disconnects once' );
check( null === Partner::step( 'disconnected', 'approve', 'remote' ) && null === Partner::step( 'disconnected', 'lift', 'remote' ), 'nothing brings a disconnected partner back but a new registration' );
check( null === Partner::step( 'active', 'promote' ), 'a step that does not exist cannot be taken' );
check( 'active' === Partner::step( 'no such state', 'approve' ), 'an unknown state is treated as pending' );
check( array( 'approve', 'decline' ) === Partner::steps( 'pending' ), 'a pending partner can be approved or declined' );
check( array( 'suspend' ) === Partner::steps( 'active' ), 'an active partner kept here can only be suspended' );
check( array( 'suspend', 'quiet', 'disconnect' ) === Partner::steps( 'active', 'remote' ), 'an active partner site can also go quiet or disconnect' );
check( array() === Partner::steps( 'disconnected', 'remote' ), 'a disconnected partner has no step left' );

// Leads.
check( array( 4, 5 ) === Partner::coleads( array( 4, '5', 4, 0, -2, 'x', 3.5, null ), 9 ), 'co-leads are whole user ids, each once' );
check( array( 4 ) === Partner::coleads( array( 9, 4 ), 9 ), 'the lead is not also a co-lead' );
check( 10 === count( Partner::coleads( range( 1, 14 ) ) ) && range( 1, 10 ) === Partner::coleads( range( 1, 14 ) ), 'no more than ten co-leads, and the first ten stay' );
check( Partner::has_room( range( 1, 9 ) ) && ! Partner::has_room( range( 1, 10 ) ), 'the tenth co-lead is the last' );
check( Partner::has_room( array( 7, 7, 7, 7, 7, 7, 7, 7, 7, 7 ) ), 'the same person ten times is one co-lead' );
check( Partner::is_led_by( 9, 9, array() ) && Partner::is_led_by( 4, 9, array( 4 ) ), 'the lead and a co-lead lead it' );
check( ! Partner::is_led_by( 5, 9, array( 4 ) ) && ! Partner::is_led_by( 0, 0, array( 0 ) ), 'anyone else does not, and nobody is not a lead of a partner with none' );
// With 9 the lead, the ten co-leads kept from 1 to 14 are 1 to 8, 10, and 11.
check( Partner::is_led_by( 11, 9, range( 1, 14 ) ) && ! Partner::is_led_by( 12, 9, range( 1, 14 ) ), 'a co-lead past the limit does not count' );

// What the leads may do for an organization under the partner.
check( Partner::reaches( 'members' ) && Partner::reaches( 'missions' ) && Partner::reaches( 'profile' ), 'a partner’s leads do what an organization’s co-lead may' );
check( ! Partner::reaches( 'remove_lead' ) && ! Partner::reaches( 'hand_over' ) && ! Partner::reaches( 'remove' ), 'they do not remove its lead, hand it over, or remove it' );

finish();
