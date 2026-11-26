#!/usr/bin/env php
<?php
/**
 * Write docs/capabilities.md from capabilities.php.
 *
 * Usage:
 *
 *     php bin/capabilities-doc.php          writes the page
 *     php bin/capabilities-doc.php --check  fails when the page is out of date
 *
 * @package PackOut_Core
 */

define( 'ABSPATH', __DIR__ . '/' );
$root = dirname( __DIR__ );
$rows = require $root . '/capabilities.php';

$cell = static function ( string $text ): string {
	return str_replace( '|', '\\|', $text );
};

$counts = array(
	'both'    => 0,
	'hub'     => 0,
	'partner' => 0,
);
$groups = array();
foreach ( $rows as $id => $row ) {
	$groups[ $row['group'] ][ $id ] = $row;
	++$counts[ $row['surface'] ];
}

$page  = "# Capabilities\n\n";
$page .= "Every act a person can take in Pack-Out Spot, who may take it, and which plugin has it. This page is written from `capabilities.php` by `bin/capabilities-doc.php`. Change that file, not this one.\n\n";
$page .= "The hub is the plugin that runs packout.spot. A partner site runs the partner plugin. **Surface** says which of them has the act: `both`, `hub`, or `partner`. **Since** is the phase of the partner build that gave each plugin the act; 0 means the hub had it before the build began.\n\n";
$page .= 'The parity test in each plugin fails when a row that plugin should have has no handler, when a plugin has a handler for an act that belongs to the other alone, when a handler names an id that is not here, or when a row only one plugin has gives no reason.' . "\n";

foreach ( $groups as $group => $members ) {
	$page .= "\n## {$group}\n\n";
	$page .= "| Id | Act | Who | Surface | Since: hub | Since: partner | Reason or note |\n";
	$page .= "| --- | --- | --- | --- | --- | --- | --- |\n";
	foreach ( $members as $id => $row ) {
		$page .= sprintf(
			"| `%s` | %s | %s | %s | %s | %s | %s |\n",
			$id,
			$cell( $row['act'] ),
			$cell( $row['who'] ),
			$row['surface'],
			isset( $row['since']['hub'] ) ? (string) $row['since']['hub'] : '',
			isset( $row['since']['partner'] ) ? (string) $row['since']['partner'] : '',
			$cell( $row['reason'] )
		);
	}
}

$page .= "\n## Counts\n\n";
$page .= sprintf(
	"%d rows. %d are for both plugins. %d are the hub’s alone, and %d are the partner plugin’s alone.\n",
	count( $rows ),
	$counts['both'],
	$counts['hub'],
	$counts['partner']
);

$path = $root . '/docs/capabilities.md';
if ( in_array( '--check', $argv, true ) ) {
	if ( ! is_file( $path ) || file_get_contents( $path ) !== $page ) {
		fwrite( STDERR, "docs/capabilities.md is out of date. Run php bin/capabilities-doc.php\n" );
		exit( 1 );
	}
	fwrite( STDOUT, "docs/capabilities.md is current\n" );
	exit( 0 );
}
file_put_contents( $path, $page );
fwrite( STDOUT, 'wrote docs/capabilities.md, ' . count( $rows ) . " rows\n" );
