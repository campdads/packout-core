#!/usr/bin/env php
<?php
/**
 * Check that the version is the same everywhere it is written.
 *
 * Usage:
 *
 *     php bin/check-versions.php [tag]
 *
 * `load.php` and the first entry of CHANGELOG.md must agree. Given a tag,
 * such as v0.1.0, they must agree with it too.
 *
 * @package PackOut_Core
 */

$root = dirname( __DIR__ );
$seen = array();

if ( 1 === preg_match( "/define\( 'PACKOUT_CORE_VERSION', '([^']+)' \)/", (string) file_get_contents( $root . '/load.php' ), $found ) ) {
	$seen['load.php'] = $found[1];
}
if ( 1 === preg_match( '/^## \[?([0-9][^\]\s]*)\]?/m', (string) file_get_contents( $root . '/CHANGELOG.md' ), $found ) ) {
	$seen['CHANGELOG.md'] = $found[1];
}
if ( isset( $argv[1] ) && '' !== $argv[1] ) {
	$seen['tag'] = ltrim( $argv[1], 'v' );
}

$failed = ! isset( $seen['load.php'], $seen['CHANGELOG.md'] ) || count( array_unique( $seen ) ) > 1;
foreach ( $seen as $where => $version ) {
	fwrite( STDOUT, str_pad( $where, 14 ) . $version . "\n" );
}
if ( $failed ) {
	fwrite( STDERR, "the versions do not agree\n" );
	exit( 1 );
}
if ( 1 !== preg_match( '/^(?:0|[1-9][0-9]*)\.(?:0|[1-9][0-9]*)\.(?:0|[1-9][0-9]*)(?:-[0-9A-Za-z.-]+)?$/', $seen['load.php'] ) ) {
	fwrite( STDERR, "the version is not of the form 1.2.3 or 1.2.3-beta.1\n" );
	exit( 1 );
}
fwrite( STDOUT, "the versions agree\n" );
