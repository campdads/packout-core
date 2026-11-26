#!/usr/bin/env php
<?php
/**
 * Copy the library into a plugin and write that plugin's lock file.
 *
 * Usage:
 *
 *     php bin/export.php <plugin directory>
 *
 * Replaces `<plugin directory>/lib/packout-core/` with this working copy's
 * files and writes `<plugin directory>/packout-core.lock`: the version, the
 * commit, and a fingerprint of the copy. Run it from a clean checkout of a
 * tagged version. With `--allow-dirty` it copies a working copy that has
 * changes, and the lock says so.
 *
 * It also replaces `<plugin directory>/tests/core/` with `tests/site/`: the
 * checks of the application that run inside WordPress, on the hub and on a
 * partner site alike. They are not part of the copy a site runs, and the
 * fingerprint does not cover them.
 *
 * @package PackOut_Core
 */

$root = dirname( __DIR__ );
require __DIR__ . '/dist.php';

$args  = array_slice( $argv, 1 );
$dirty = in_array( '--allow-dirty', $args, true );
$args  = array_values( array_diff( $args, array( '--allow-dirty' ) ) );
$dest  = isset( $args[0] ) ? rtrim( $args[0], '/' ) : '';
if ( '' === $dest || ! is_dir( $dest ) ) {
	fwrite( STDERR, "usage: php bin/export.php [--allow-dirty] <plugin directory>\n" );
	exit( 2 );
}

if ( 1 !== preg_match( "/define\( 'PACKOUT_CORE_VERSION', '([^']+)' \)/", (string) file_get_contents( $root . '/load.php' ), $found ) ) {
	fwrite( STDERR, "cannot read the version from load.php\n" );
	exit( 1 );
}
$version = $found[1];

$git     = static function ( string $command ) use ( $root ): string {
	$output = array();
	$status = 1;
	exec( 'git -C ' . escapeshellarg( $root ) . ' ' . $command . ' 2>/dev/null', $output, $status );
	return 0 === $status ? trim( implode( "\n", $output ) ) : '';
};
$commit  = $git( 'rev-parse HEAD' );
$changed = '' !== $git( 'status --porcelain' );
$tagged  = $git( 'tag --points-at HEAD' );
$is_tag  = in_array( 'v' . $version, explode( "\n", $tagged ), true );
if ( ( $changed || ! $is_tag ) && ! $dirty ) {
	fwrite( STDERR, "this is not a clean checkout of v{$version}. Tag the version first, or pass --allow-dirty.\n" );
	exit( 1 );
}

$target = $dest . '/lib/packout-core';

// What the copy should hold.
$wanted = array();
foreach ( packout_core_dist_paths() as $path ) {
	$from = $root . '/' . $path;
	if ( is_file( $from ) ) {
		$wanted[] = $path;
	} elseif ( is_dir( $from ) ) {
		foreach ( packout_core_files( $from ) as $file ) {
			$wanted[] = $path . '/' . $file;
		}
	}
}

/*
 * A file is written only when it is new or changed, and removed only when the
 * library no longer has it. Emptying the copy and writing it again made a
 * synced folder keep a second copy of every file ("load 2.php"), which then
 * sat in the plugin and changed its fingerprint.
 */
$copied  = 0;
$removed = 0;
if ( is_dir( $target ) ) {
	foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $target, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST ) as $entry ) {
		$relative = str_replace( '\\', '/', substr( $entry->getPathname(), strlen( $target ) + 1 ) );
		if ( $entry->isDir() ) {
			// Only an empty directory goes: one that still holds a wanted file stays.
			if ( 2 === count( (array) scandir( $entry->getPathname() ) ) ) {
				rmdir( $entry->getPathname() );
			}
		} elseif ( ! in_array( $relative, $wanted, true ) ) {
			unlink( $entry->getPathname() );
			++$removed;
		}
	}
}
foreach ( $wanted as $file ) {
	$to = $target . '/' . $file;
	if ( is_file( $to ) && hash_file( 'sha256', $to ) === hash_file( 'sha256', $root . '/' . $file ) ) {
		continue;
	}
	if ( ! is_dir( dirname( $to ) ) ) {
		mkdir( dirname( $to ), 0755, true );
	}
	copy( $root . '/' . $file, $to );
	++$copied;
}

// The copy is now exactly the library's files, or this stops.
$held = packout_core_files( $target );
sort( $wanted, SORT_STRING );
if ( $held !== $wanted ) {
	fwrite( STDERR, "the copy does not hold exactly the library's files:\n" );
	foreach ( array_merge( array_diff( $held, $wanted ), array_diff( $wanted, $held ) ) as $odd ) {
		fwrite( STDERR, '  ' . $odd . "\n" );
	}
	exit( 1 );
}

// The application's own checks, for the plugin to run on its kind of site.
$checks       = is_dir( $root . '/tests/site' ) ? packout_core_files( $root . '/tests/site' ) : array();
$checks_dir   = $dest . '/tests/core';
$checks_wrote = 0;
if ( $checks ) {
	if ( ! is_dir( $checks_dir ) ) {
		mkdir( $checks_dir, 0755, true );
	}
	foreach ( packout_core_files( $checks_dir ) as $old ) {
		if ( ! in_array( $old, $checks, true ) ) {
			unlink( $checks_dir . '/' . $old );
		}
	}
	foreach ( $checks as $file ) {
		$to = $checks_dir . '/' . $file;
		if ( is_file( $to ) && hash_file( 'sha256', $to ) === hash_file( 'sha256', $root . '/tests/site/' . $file ) ) {
			continue;
		}
		if ( ! is_dir( dirname( $to ) ) ) {
			mkdir( dirname( $to ), 0755, true );
		}
		copy( $root . '/tests/site/' . $file, $to );
		++$checks_wrote;
	}
}//end if

$lock = array(
	'version' => $version,
	'tag'     => $is_tag && ! $changed ? 'v' . $version : '',
	'commit'  => $changed ? '' : $commit,
	'tree'    => packout_core_tree_hash( $target ),
);
file_put_contents( $dest . '/packout-core.lock', json_encode( $lock, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n" );

fwrite( STDOUT, "packout-core {$version}: " . count( $wanted ) . " files in {$target}, {$copied} written, {$removed} removed\n" );
fwrite( STDOUT, 'tree ' . $lock['tree'] . "\n" );
if ( $checks ) {
	fwrite( STDOUT, count( $checks ) . " checks in {$checks_dir}, {$checks_wrote} written\n" );
}
if ( '' === $lock['tag'] ) {
	fwrite( STDOUT, "not a tagged version: the lock has no tag, and the plugin's checks will refuse it\n" );
}
