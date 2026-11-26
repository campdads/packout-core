<?php
/**
 * The capability list is well formed.
 *
 * @package PackOut_Core
 */

require __DIR__ . '/bootstrap.php';

use PackOut\Core\Capability_Map;
use PackOut\Core\Parity;

$rows = Capability_Map::rows();
check( count( $rows ) >= 107, 'the list has at least the 107 rows the build began with' );

$problems = Parity::malformed( $rows );
foreach ( $problems as $problem ) {
	fwrite( STDERR, '  ' . $problem['id'] . ': ' . $problem['message'] . "\n" );
}
check( array() === $problems, 'every row is well formed' );

$counts = Parity::counts( $rows );
check( $counts['both'] + $counts['hub'] + $counts['partner'] === $counts['total'], 'every row is for both plugins, the hub, or the partner plugin' );
check( $counts['both'] > $counts['hub'] + $counts['partner'], 'most acts are in both plugins' );

$ids = array_keys( $rows );
check( count( $ids ) === count( array_unique( $ids ) ), 'no id is used twice' );

foreach ( $rows as $id => $row ) {
	if ( 'both' !== $row['surface'] ) {
		check( '' !== trim( $row['reason'] ), $id . ' says why only one plugin has it' );
	}
}

check( null === Capability_Map::row( 'no.such.act' ), 'an id that is not in the list has no row' );
check( 'both' === ( Capability_Map::row( 'mission.complete' )['surface'] ?? '' ), 'a row can be read by its id' );

// The page people read is written from the list. It must be current.
$output = array();
$status = 1;
exec( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( dirname( __DIR__ ) . '/bin/capabilities-doc.php' ) . ' --check 2>&1', $output, $status );
check( 0 === $status, 'docs/capabilities.md matches capabilities.php' );

finish();
