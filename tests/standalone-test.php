<?php
/**
 * The library uses nothing but WordPress.
 *
 * A partner site has no Vectra plugin, no Astra theme, and no particular
 * host. Whatever the application needs from outside WordPress it asks its
 * plugin for, through an adapter. This reads every file a plugin carries and
 * fails on a name that belongs to one of those.
 *
 * The catalog's routes are under `vectra/v1`, which is an address and not a
 * call, and a comment may say what the hub answers with.
 *
 * @package PackOut_Core
 */

require __DIR__ . '/bootstrap.php';

$root  = dirname( __DIR__ );
$names = array(
	'a Vectra class'             => '/\bVectra\\\\/',
	'a Vectra function or hook'  => '/\bvectra_[a-z]/i',
	'a Vectra handle or class'   => '/\bvectra-[a-z]/i',
	'a Vectra script'            => '/\bvectra(Ui|\.[a-z])/',
	'an Astra function or hook'  => '/\bastra_[a-z]/i',
	'an Astra handle or class'   => '/\bastra-[a-z]/i',
	'an Astra color'             => '/--ast-/',
	'a Spectra block or setting' => '/\buagb[\/_-]/i',
	'a host’s cache'             => '/\bWpeCommon\b/',
);

$files = array( $root . '/load.php', $root . '/capabilities.php' );
foreach ( array( 'src', 'assets', 'templates' ) as $directory ) {
	if ( ! is_dir( $root . '/' . $directory ) ) {
		continue;
	}
	$found = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root . '/' . $directory, FilesystemIterator::SKIP_DOTS ) );
	foreach ( $found as $file ) {
		$path = $file->getPathname();
		// Other projects' files, carried unmodified.
		if ( false !== strpos( $path, '/assets/lib/' ) || str_ends_with( $path, '/chart.umd.min.js' ) ) {
			continue;
		}
		if ( 1 === preg_match( '/\.(php|js|css|html|json)$/', $path ) ) {
			$files[] = $path;
		}
	}
}
sort( $files );
check( count( $files ) > 100, 'the application’s files were found' );

$problems = array();
foreach ( $files as $path ) {
	$lines = file( $path, FILE_IGNORE_NEW_LINES );
	foreach ( false === $lines ? array() : $lines as $number => $line ) {
		foreach ( $names as $what => $pattern ) {
			if ( 1 === preg_match( $pattern, $line ) ) {
				$problems[] = substr( $path, strlen( $root ) + 1 ) . ':' . ( $number + 1 ) . ' names ' . $what;
			}
		}
	}
}
check( array() === $problems, 'no file names Vectra, a theme, or a host' . ( $problems ? ': ' . implode( '; ', array_slice( $problems, 0, 8 ) ) : '' ) );

// The check itself: each pattern finds what it is for, and leaves an address and a sentence alone.
$samples = array(
	'\\Vectra\\Admin\\Icons::svg( $name )'       => true,
	"apply_filters( 'vectra_can_read_post' )"    => true,
	"wp_enqueue_script( 'vectra-ui-view' )"      => true,
	'window.vectraUi.runtime'                    => true,
	'window.vectra.dataViews'                    => true,
	"astra_get_option( 'global-color-palette' )" => true,
	'color: var(--ast-global-color-0);'          => true,
	'.uagb-container-inner-blocks-wrap'          => true,
	'\\WpeCommon::purge_varnish_cache()'         => true,
	"'/wp-json/vectra/v1/manifest'"              => false,
	'On the hub that is Vectra Toolkit.'         => false,
	'Spectra containers, core groups'            => false,
);
foreach ( $samples as $line => $expected ) {
	$hit = false;
	foreach ( $names as $pattern ) {
		$hit = $hit || 1 === preg_match( $pattern, $line );
	}
	check( $hit === $expected, ( $expected ? 'the check catches ' : 'the check allows ' ) . $line );
}

finish();
