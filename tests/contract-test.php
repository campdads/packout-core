<?php
/**
 * A payload is accepted only in the shape its schema gives, and a field the
 * schema does not list is a fault.
 *
 * @package PackOut_Core
 */

require __DIR__ . '/bootstrap.php';

use PackOut\Core\Contract;

check( 1 === Contract::version() && PACKOUT_CORE_CONTRACT === Contract::version(), 'this build speaks contract 1' );
check( Contract::accepts( 1 ), 'the hub accepts its own version' );
check( ! Contract::accepts( 0 ) && ! Contract::accepts( 2 ) && ! Contract::accepts( '1' ) && ! Contract::accepts( null ), 'and not version 0, a later one, or something that is not a number' );
check( Contract::accepts( 1, 2 ) && Contract::accepts( 2, 2 ) && ! Contract::accepts( 0, 2 ) && ! Contract::accepts( 3, 2 ), 'a hub at version 2 accepts 1 and 2' );
check( ! Contract::accepts( 1, 3 ) && Contract::accepts( 2, 3 ), 'a hub at version 3 no longer accepts 1' );

$directory = Contract::directory();
check( is_dir( $directory ) && is_dir( $directory . '/fixtures' ), 'the schemas and their fixtures are in the library' );

$names = array( 'register', 'signed', 'key', 'profile', 'listings', 'stats', 'spots', 'place-requests', 'campaigns', 'claims', 'recover', 'move' );
foreach ( $names as $name ) {
	$schema = Contract::schema( $name );
	check( is_array( $schema ) && 'object' === ( $schema['type'] ?? '' ) && false === ( $schema['additionalProperties'] ?? true ), $name . ': the schema lists every field of an object' );
	$fixture = json_decode( (string) file_get_contents( $directory . '/fixtures/' . $name . '.json' ), true );
	check( is_array( $fixture ) && '' === Contract::fault( $name, $fixture ), $name . ': its fixture fits' . ( is_array( $fixture ) ? ' ' . Contract::fault( $name, $fixture ) : '' ) );
	foreach ( array( 'contract', 'core_version', 'plugin_version' ) as $field ) {
		$without = $fixture;
		unset( $without[ $field ] );
		check( Contract::fault( $name, $without ) === $field, $name . ': a payload that does not say its ' . $field . ' is refused' );
	}
	check( 'someone' === Contract::fault( $name, $fixture + array( 'someone' => 'a@example.org' ) ), $name . ': a field the schema does not list is refused' );
}
// An adoption has four shapes, and `act` says which.
$adopt = json_decode( (string) file_get_contents( $directory . '/fixtures/adoptions.json' ), true );
$more  = json_decode( (string) file_get_contents( $directory . '/fixtures/adoptions-more.json' ), true );
check( is_array( $adopt ) && '' === Contract::fault( 'adoptions', $adopt ), 'adoptions: taking a Spot on fits' );
foreach ( (array) $more as $act => $payload ) {
	check( '' === Contract::fault( 'adoptions', $payload ), 'adoptions: ' . $act . ' fits ' . Contract::fault( 'adoptions', $payload ) );
	check( 'someone' === Contract::fault( 'adoptions', $payload + array( 'someone' => 'a@example.org' ) ), 'adoptions: ' . $act . ' takes no field the schema does not list' );
	$without = $payload;
	unset( $without['contract'] );
	check( 'contract' === Contract::fault( 'adoptions', $without ), 'adoptions: ' . $act . ' says which contract it follows' );
}
check( 'act' === Contract::fault( 'adoptions', array_diff_key( $adopt, array( 'act' => 1 ) ) ), 'adoptions: a request that does not say what it does is refused' );
check( null === Contract::schema( 'nothing' ) && null === Contract::schema( '../load' ) && '/' === Contract::fault( 'nothing', array() ), 'a schema that does not exist fits nothing' );
check( '/' === Contract::fault( 'listings', 'a string' ) && '/' === Contract::fault( 'listings', array( 1, 2 ) ) && '/' === Contract::fault( 'listings', null ), 'a payload that is not an object is refused' );

$invalid = json_decode( (string) file_get_contents( $directory . '/fixtures/invalid.json' ), true );
check( is_array( $invalid ) && count( $invalid ) >= 10, 'there are fixtures of payloads to refuse' );
foreach ( (array) $invalid as $label => $case ) {
	check( Contract::fault( $case['schema'], $case['payload'] ) === $case['fault'], 'refused, naming the fault: ' . $label . ' (' . Contract::fault( $case['schema'], $case['payload'] ) . ')' );
}

$listings = json_decode( (string) file_get_contents( $directory . '/fixtures/listings.json' ), true );
$kinds    = array_values( array_unique( array_column( $listings['items'], 'kind' ) ) );
check( array( 'organization', 'team', 'program', 'campaign', 'mission' ) === $kinds, 'the listings fixture has one of each kind, in the order a batch is applied' );
check( ! empty( $listings['items'][5]['deleted'] ), 'and one to remove' );
$text = (string) wp_json_encode( $listings );
check( false === strpos( $text, '@' ), 'no listing in the fixture holds an email address' );

$item          = $listings['items'][4];
$item['title'] = str_repeat( 'a', 201 );
check( 'items/0/title' === Contract::fault( 'listings', array( 'items' => array( $item ) ) + $listings ), 'a title over its length is refused' );
$item          = $listings['items'][4];
$item['start'] = '17 April';
check( 'items/0/start' === Contract::fault( 'listings', array( 'items' => array( $item ) ) + $listings ), 'a day that is not a day is refused' );
$item          = $listings['items'][4];
$item['spots'] = '3';
check( 'items/0/spots' === Contract::fault( 'listings', array( 'items' => array( $item ) ) + $listings ), 'a count sent as text is refused' );
$item        = $listings['items'][4];
$item['url'] = 'javascript:alert(1)';
check( 'items/0/url' === Contract::fault( 'listings', array( 'items' => array( $item ) ) + $listings ), 'an address that is not a web address is refused' );
check( '' === Contract::fault( 'listings', array( 'items' => array() ) + $listings ), 'an empty batch fits' );
// A joint campaign: the host's listing says so, and a participant's mission names the hub's campaign.
check( 'open' === $listings['items'][3]['joint'], 'the campaign in the fixture is hosted as a joint campaign' );
$item             = $listings['items'][4];
$item['campaign'] = 'hub:9041';
check( '' === Contract::fault( 'listings', array( 'items' => array( $item ) ) + $listings ), 'a mission may name a joint campaign by the hub\'s number' );
$item          = $listings['items'][4];
$item['joint'] = 'open';
check( '' === Contract::fault( 'listings', array( 'items' => array( $item ) ) + $listings ), 'the schema lists every field once, so it cannot tell a mission from a campaign: the hub reads `joint` on a campaign only' );
check( '' === Contract::fault( 'campaigns', array( 'participant' => '77' ) + array_diff_key( json_decode( (string) file_get_contents( $directory . '/fixtures/campaigns.json' ), true ), array( 'organization' => 1 ) ) ), 'a host names a participant by the hub\'s number' );
check( Contract::BATCH === Contract::schema( 'listings' )['properties']['items']['maxItems'] && Contract::BATCH === Contract::schema( 'stats' )['properties']['items']['maxItems'], 'a batch holds at most 200 items' );

$stats = json_decode( (string) file_get_contents( $directory . '/fixtures/stats.json' ), true );
$deep  = array();
$level = &$deep;
for ( $i = 0; $i < 20; $i++ ) {
	$level['a'] = array();
	$level      = &$level['a'];
}
unset( $level );
$snapshot                    = $stats['items'][0];
$snapshot['overview']['now'] = $deep;
check( '' !== Contract::fault( 'stats', array( 'items' => array( $snapshot ) ) + $stats ), 'figures nested too deep are refused' );
$snapshot                    = $stats['items'][0];
$snapshot['overview']['now'] = array( 'Reported By' => 3 );
check( 'items/0/overview/now/Reported By' === Contract::fault( 'stats', array( 'items' => array( $snapshot ) ) + $stats ), 'a key in the figures that is not a short plain word is refused' );
$snapshot                        = $stats['items'][0];
$snapshot['overview']['periods'] = array( '14' => array() );
check( 'items/0/overview/periods/14' === Contract::fault( 'stats', array( 'items' => array( $snapshot ) ) + $stats ), 'a period other than 7, 30, 90, or 365 days is refused' );
$snapshot              = array_diff_key( $stats['items'][1], array( 'overview' => 1 ) );
$snapshot['scope']     = 'campaign';
$snapshot['remote_id'] = 'hub:9041';
check( '' === Contract::fault( 'stats', array( 'items' => array( $snapshot ) ) + $stats ), 'a site\'s part of a joint campaign is a snapshot named by the hub\'s number' );
$snapshot['remote_id'] = 'hub:9041-2';
check( 'items/0/remote_id' === Contract::fault( 'stats', array( 'items' => array( $snapshot ) ) + $stats ), 'and by nothing else after `hub:`' );

finish();
