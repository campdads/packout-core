<?php
/**
 * A plugin's copy of the library is the build its lock names.
 *
 * @package PackOut_Core
 */

require __DIR__ . '/bootstrap.php';

use PackOut\Core\Build;

$work = sys_get_temp_dir() . '/packout-core-build-' . bin2hex( random_bytes( 4 ) );
$copy = $work . '/lib/packout-core';
mkdir( $copy . '/src', 0755, true );
file_put_contents( $copy . '/load.php', "<?php\ndefine( 'PACKOUT_CORE_VERSION', '1.2.3' );\n" );
file_put_contents( $copy . '/src/class-a.php', "<?php\n// a\n" );
file_put_contents( $copy . '/src/class-b.php', "<?php\n// b\n" );
file_put_contents( $copy . '/.DS_Store', 'noise' );

$lock_file = $work . '/packout-core.lock';
$write     = static function ( array $lock ) use ( $lock_file ): void {
	file_put_contents( $lock_file, json_encode( $lock ) );
};

check( array( 'load.php', 'src/class-a.php', 'src/class-b.php' ) === Build::files( $copy ), 'the files of a copy are listed in path order, without Finder noise' );
check( array() === Build::files( $work . '/nowhere' ), 'a directory that does not exist has no files' );

$print = Build::fingerprint( $copy );
check( 1 === preg_match( '/^[a-f0-9]{64}$/', $print ), 'a fingerprint is a SHA-256' );
check( Build::fingerprint( $copy . '/' ) === $print, 'a trailing slash does not change the fingerprint' );

$expected = hash(
	'sha256',
	"load.php\0" . hash( 'sha256', "<?php\ndefine( 'PACKOUT_CORE_VERSION', '1.2.3' );\n" ) . "\n" .
	"src/class-a.php\0" . hash( 'sha256', "<?php\n// a\n" ) . "\n" .
	"src/class-b.php\0" . hash( 'sha256', "<?php\n// b\n" ) . "\n"
);
check( $expected === $print, 'the fingerprint is the hash of each path and file hash, in path order' );

$good = array(
	'version' => '1.2.3',
	'tag'     => 'v1.2.3',
	'commit'  => str_repeat( 'a', 40 ),
	'tree'    => $print,
);

check( str_contains( Build::problem( $lock_file, $copy ), 'no packout-core.lock' ), 'a copy with no lock is a problem, and the message says which' );
$write( $good );
check( '' === Build::problem( $lock_file, $copy ), 'a copy that matches a tagged lock is no problem' );
check( Build::lock( $lock_file ) === $good, 'the lock is read back as it was written' );

$write(
	array_merge(
		$good,
		array(
			'version' => '1.2.4',
			'tag'     => 'v1.2.4',
		)
	)
);
check( str_contains( Build::problem( $lock_file, $copy ), '1.2.4' ), 'a lock for another version is a problem' );

$write(
	array_merge(
		$good,
		array(
			'tag'    => '',
			'commit' => '',
		)
	)
);
check( '' !== Build::problem( $lock_file, $copy ), 'a copy that was not exported from a tag is a problem for a release' );
check( '' === Build::problem( $lock_file, $copy, false ), 'and is no problem while working' );

$write( array_merge( $good, array( 'tag' => 'v1.2.2' ) ) );
check( '' !== Build::problem( $lock_file, $copy ), 'a tag that is not the version is a problem' );
$write( array_merge( $good, array( 'commit' => 'abc' ) ) );
check( '' !== Build::problem( $lock_file, $copy ), 'a lock with no full commit is a problem' );

$write( $good );
file_put_contents( $copy . '/src/class-a.php', "<?php\n// a, edited in place\n" );
check( str_contains( Build::problem( $lock_file, $copy, false ), 'not the build' ), 'a file edited in place is a problem' );
file_put_contents( $copy . '/src/class-a.php', "<?php\n// a\n" );
check( '' === Build::problem( $lock_file, $copy ), 'and putting it back clears the problem' );

file_put_contents( $copy . '/src/class-c.php', "<?php\n// c\n" );
check( '' !== Build::problem( $lock_file, $copy, false ), 'an added file is a problem' );
unlink( $copy . '/src/class-c.php' );
rename( $copy . '/src/class-b.php', $copy . '/src/class-z.php' );
check( '' !== Build::problem( $lock_file, $copy, false ), 'a renamed file is a problem' );
rename( $copy . '/src/class-z.php', $copy . '/src/class-b.php' );
unlink( $copy . '/src/class-b.php' );
check( '' !== Build::problem( $lock_file, $copy, false ), 'a removed file is a problem' );
file_put_contents( $copy . '/src/class-b.php', "<?php\n// b\n" );

unlink( $copy . '/load.php' );
check( str_contains( Build::problem( $lock_file, $copy ), 'no copy' ), 'a lock with no library beside it is a problem' );

file_put_contents( $lock_file, 'not json' );
check( '' === Build::lock( $lock_file )['version'] && '' !== Build::problem( $lock_file, $copy ), 'a lock that is not JSON is a problem' );

// This repository's own export writes a lock the check accepts.
$plugin = $work . '/plugin';
mkdir( $plugin, 0755, true );
$output = array();
$status = 1;
exec( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( dirname( __DIR__ ) . '/bin/export.php' ) . ' --allow-dirty ' . escapeshellarg( $plugin ) . ' 2>&1', $output, $status );
check( 0 === $status, 'the export tool copies the library into a plugin' );
check( '' === Build::problem( $plugin . '/packout-core.lock', $plugin . '/lib/packout-core', false ), 'and the copy matches the lock it wrote' );
check( ! is_dir( $plugin . '/lib/packout-core/tests' ) && ! is_dir( $plugin . '/lib/packout-core/bin' ) && is_file( $plugin . '/lib/packout-core/src/class-build.php' ), 'the copy has the library and not its tests or tools' );
check( PACKOUT_CORE_VERSION === Build::lock( $plugin . '/packout-core.lock' )['version'], 'the lock names the library version' );

exec( 'rm -rf ' . escapeshellarg( $work ) );
finish();
