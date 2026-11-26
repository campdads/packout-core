#!/usr/bin/env php
<?php
/**
 * Keep the contract as this version carries it, for the compatibility test.
 *
 * Usage:
 *
 *     php bin/freeze-contract.php
 *
 * Copies every schema and fixture of `contract/v1/` to
 * `tests/compat/<version>/`, where the version is the one `load.php` names.
 * Run it in the change that alters a schema or a fixture, and declare what
 * the change adds in `tests/compat-test.php`. The copies kept under earlier
 * versions are what those versions released, and are never written again:
 * a version that has a tag is refused. Raise the version in `load.php` first.
 *
 * @package PackOut_Core
 */

$packout_root = dirname( __DIR__ );
if ( 1 !== preg_match( "/define\( 'PACKOUT_CORE_VERSION', '([^']+)' \)/", (string) file_get_contents( $packout_root . '/load.php' ), $packout_found ) ) {
	fwrite( STDERR, "load.php names no version\n" );
	exit( 1 );
}
$packout_version = $packout_found[1];
$packout_from    = $packout_root . '/contract/v1';
$packout_to      = $packout_root . '/tests/compat/' . $packout_version;

// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec -- A developer's tool, asking git about a tag.
$packout_tag = trim( (string) exec( 'git -C ' . escapeshellarg( $packout_root ) . ' tag --list ' . escapeshellarg( 'v' . $packout_version ) . ' 2>/dev/null' ) );
if ( '' !== $packout_tag ) {
	fwrite( STDERR, $packout_version . " is released. Raise the version in load.php, then keep the contract under the new one.\n" );
	exit( 1 );
}

$packout_later = array();
foreach ( (array) glob( $packout_root . '/tests/compat/*', GLOB_ONLYDIR ) as $packout_dir ) {
	if ( version_compare( basename( $packout_dir ), $packout_version, '>' ) ) {
		$packout_later[] = basename( $packout_dir );
	}
}
if ( array() !== $packout_later ) {
	fwrite( STDERR, 'a later version is already kept: ' . implode( ', ', $packout_later ) . "\n" );
	exit( 1 );
}

foreach ( array( $packout_to . '/fixtures', $packout_to ) as $packout_dir ) {
	foreach ( (array) glob( $packout_dir . '/*.json' ) as $packout_file ) {
		unlink( $packout_file );
	}
}
if ( ! is_dir( $packout_to . '/fixtures' ) && ! mkdir( $packout_to . '/fixtures', 0755, true ) ) {
	fwrite( STDERR, 'could not make ' . $packout_to . "\n" );
	exit( 1 );
}

$packout_count = 0;
foreach ( array(
	''          => '/*.json',
	'/fixtures' => '/fixtures/*.json',
) as $packout_under => $packout_pattern ) {
	foreach ( (array) glob( $packout_from . $packout_pattern ) as $packout_file ) {
		if ( ! copy( $packout_file, $packout_to . $packout_under . '/' . basename( $packout_file ) ) ) {
			fwrite( STDERR, 'could not copy ' . $packout_file . "\n" );
			exit( 1 );
		}
		++$packout_count;
	}
}
fwrite( STDOUT, $packout_count . ' files kept under tests/compat/' . $packout_version . "\n" );
