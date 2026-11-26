<?php
/**
 * The two ends are seldom of one version: a site updates when its owner
 * chooses, and the hub when it is released. `tests/compat/` holds the
 * contract as each released version carried it, its schemas and its
 * fixtures, and this test asks both questions of every one of them.
 *
 * Forward: what a site running that version sends is accepted by this hub,
 * and what that hub refused is still refused. This must always hold while the
 * contract's number stays the same.
 *
 * Reverse: what a site running this version sends, said to a hub of that
 * version. Whatever that hub would refuse is something a later version
 * added, and every addition is declared in the table below. A schema or a
 * field nobody declared fails here, so nothing is added to the contract by
 * accident, and a version that adds something is released to the hub before
 * any site is offered it.
 *
 * `php bin/freeze-contract.php` keeps the contract as it stands under the
 * version `load.php` names.
 *
 * @package PackOut_Core
 */

require __DIR__ . '/bootstrap.php';

use PackOut\Core\Contract;

/**
 * What each version added to the contract. A whole schema is named alone. A
 * field is named with its schema and its place, with `*` for any item.
 */
$added = array(
	'0.7.0'  => array( 'register', 'signed', 'key', 'profile', 'listings', 'stats' ),
	'0.8.0'  => array( 'spots', 'adoptions', 'place-requests' ),
	'0.9.0'  => array( 'profile: images' ),
	'0.11.0' => array( 'campaigns', 'listings: items/*/joint' ),
	'0.12.0' => array( 'claims', 'move', 'recover' ),
);

/**
 * Read a JSON file as arrays.
 *
 * @param string $file The file.
 * @return mixed
 */
function packout_compat_read( string $file ) {
	return json_decode( (string) file_get_contents( $file ), true );
}

/**
 * The schemas and fixtures a directory holds, as `name => bytes`.
 *
 * @param string $dir The directory.
 * @return array<string, string>
 */
function packout_compat_files( string $dir ): array {
	$files = array();
	foreach ( array_merge( (array) glob( $dir . '/*.json' ), (array) glob( $dir . '/fixtures/*.json' ) ) as $file ) {
		$files[ substr( (string) $file, strlen( $dir ) + 1 ) ] = (string) file_get_contents( (string) $file );
	}
	ksort( $files );
	return $files;
}

/**
 * The payloads a directory's fixtures hold, each with the schema it is for.
 * `invalid.json` is left out: those are payloads to refuse.
 *
 * @param string $dir The directory that holds `fixtures/`.
 * @return array<int, array{0: string, 1: string, 2: mixed}> Schema, label, payload.
 */
function packout_compat_payloads( string $dir ): array {
	$payloads = array();
	foreach ( (array) glob( $dir . '/fixtures/*.json' ) as $file ) {
		$name = basename( (string) $file, '.json' );
		if ( 'invalid' === $name ) {
			continue;
		}
		if ( 'adoptions-more' === $name ) {
			foreach ( (array) packout_compat_read( (string) $file ) as $act => $payload ) {
				$payloads[] = array( 'adoptions', 'adoptions, ' . $act, $payload );
			}
			continue;
		}
		$payloads[] = array( $name, $name, packout_compat_read( (string) $file ) );
	}
	return $payloads;
}

/**
 * Every fault a schema finds in a payload, and not the first alone: each
 * faulty field is taken out and the payload is asked about again.
 *
 * @param array<string, mixed> $schema  The schema.
 * @param mixed                $payload The payload.
 * @return string[] The places, with `*` for an item's position.
 */
function packout_compat_faults( array $schema, $payload ): array {
	$faults = array();
	for ( $i = 0; $i < 200; $i++ ) {
		$fault = Contract::fault_in( $schema, $payload );
		if ( '' === $fault ) {
			break;
		}
		$faults[] = (string) preg_replace( '~(^|/)[0-9]+(?=/|$)~', '$1*', $fault );
		$steps    = explode( '/', $fault );
		$last     = array_pop( $steps );
		$node     = &$payload;
		foreach ( $steps as $step ) {
			if ( ! is_array( $node ) || ! array_key_exists( $step, $node ) ) {
				unset( $node );
				break 2;
			}
			$node = &$node[ $step ];
		}
		if ( ! is_array( $node ) || ! array_key_exists( $last, $node ) ) {
			unset( $node );
			break;
		}
		unset( $node[ $last ], $node );
	}
	return array_values( array_unique( $faults ) );
}

$root     = __DIR__ . '/compat';
$versions = array_map( 'basename', (array) glob( $root . '/*', GLOB_ONLYDIR ) );
usort( $versions, 'version_compare' );
$newest = (string) end( $versions );

check( count( $versions ) >= 5 && '0.7.0' === $versions[0], 'the contract of every version that changed it is kept, from the first that had one' );
check( array_keys( $added ) === array_values( array_intersect( array_keys( $added ), $versions ) ) && array() === array_diff( $versions, array_keys( $added ) ), 'and each of them says what it added' );
check( version_compare( $newest, PACKOUT_CORE_VERSION, '<=' ), 'none is of a version later than this one' );

$now = packout_compat_files( Contract::directory() );
check( packout_compat_files( $root . '/' . $newest ) === $now, 'the newest copy kept is the contract as it stands: run `php bin/freeze-contract.php` after changing a schema or a fixture' );

$schemas = array();
foreach ( array_keys( $now ) as $file ) {
	if ( false === strpos( $file, '/' ) ) {
		$schemas[] = basename( $file, '.json' );
	}
}
$declared = array();
foreach ( $added as $list ) {
	foreach ( $list as $one ) {
		if ( false === strpos( $one, ':' ) ) {
			$declared[] = $one;
		}
	}
}
sort( $declared );
sort( $schemas );
check( $schemas === $declared, 'every schema of the contract is declared by the version that added it, and by one version only' );

$mine = packout_compat_payloads( Contract::directory() );
check( count( $mine ) >= count( $schemas ), 'this version has a fixture of every payload it sends' );

foreach ( $versions as $version ) {
	$dir = $root . '/' . $version;

	// Forward: a site of that version, this hub.
	$theirs = packout_compat_payloads( $dir );
	$faults = array();
	foreach ( $theirs as list( $name, $label, $payload ) ) {
		$fault = Contract::fault( $name, $payload );
		if ( '' !== $fault ) {
			$faults[] = $label . ': ' . $fault;
		}
	}
	check( array() !== $theirs && array() === $faults, $version . ': what a site of that version sends, this hub accepts ' . implode( '; ', $faults ) );

	$invalid = packout_compat_read( $dir . '/fixtures/invalid.json' );
	$let_in  = array();
	foreach ( (array) $invalid as $label => $case ) {
		if ( '' === Contract::fault( (string) $case['schema'], $case['payload'] ) ) {
			$let_in[] = $label;
		}
	}
	check( is_array( $invalid ) && array() !== $invalid && array() === $let_in, $version . ': what that hub refused, this hub refuses ' . implode( '; ', $let_in ) );

	// Reverse: a site of this version, that hub.
	$expected = array();
	foreach ( $added as $at => $list ) {
		if ( version_compare( $at, $version, '>' ) ) {
			$expected = array_merge( $expected, $list );
		}
	}
	$found = array();
	foreach ( $mine as list( $name, $label, $payload ) ) {
		$file = $dir . '/' . $name . '.json';
		if ( ! is_file( $file ) ) {
			$found[] = $name;
			continue;
		}
		foreach ( packout_compat_faults( (array) packout_compat_read( $file ), $payload ) as $fault ) {
			$found[] = $name . ': ' . $fault;
		}
	}
	$found = array_values( array_unique( $found ) );
	sort( $found );
	sort( $expected );
	check( $found === $expected, $version . ': a hub of that version would refuse of this site only what later versions added (' . implode( ', ', $found ) . ')' );
}

check( array() === packout_compat_faults( (array) Contract::schema( 'listings' ), packout_compat_read( Contract::directory() . '/fixtures/listings.json' ) ), 'the listings fixture has no fault to find' );
$with                       = (array) packout_compat_read( Contract::directory() . '/fixtures/listings.json' );
$with['someone']            = 'a@example.org';
$with['items'][0]['secret'] = 'x';
$with['items'][2]['secret'] = 'y';
check( array( 'items/*/secret', 'someone' ) === packout_compat_faults( (array) Contract::schema( 'listings' ), $with ), 'and every field a schema does not list is found, wherever it is' );

finish();
