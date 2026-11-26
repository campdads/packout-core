#!/usr/bin/env php
<?php
/**
 * Run every unit test. Exits with 1 when any script fails.
 *
 * Usage:
 *
 *     php tests/run.php
 *
 * @package PackOut_Core
 */

$failed = 0;
$files  = glob( __DIR__ . '/*-test.php' );
sort( $files );
foreach ( $files as $file ) {
	passthru( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( $file ), $status );
	if ( 0 !== $status ) {
		++$failed;
	}
}
fwrite( STDOUT, count( $files ) . ' scripts, ' . $failed . " failed\n" );
exit( $failed > 0 ? 1 : 0 );
