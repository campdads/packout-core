<?php
/**
 * What a plugin carries of the library, and how that copy is fingerprinted.
 *
 * @package PackOut_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}
require_once dirname( __DIR__ ) . '/src/class-build.php';

/**
 * The files and directories copied into a plugin's `lib/packout-core/`.
 *
 * Tests, tools, and documents stay in this repository.
 *
 * @return array<int, string>
 */
function packout_core_dist_paths(): array {
	return array( 'LICENSE', 'load.php', 'capabilities.php', 'src', 'contract', 'templates', 'assets', 'languages' );
}

/**
 * Every file under a directory, relative to it, sorted.
 *
 * @param string $root Directory.
 * @return array<int, string>
 */
function packout_core_files( string $root ): array {
	return PackOut\Core\Build::files( $root );
}

/**
 * A fingerprint of a copy of the library. Each plugin's `packout-core.lock`
 * records it, and each plugin's checks compute it again.
 *
 * @param string $root The copy's directory.
 */
function packout_core_tree_hash( string $root ): string {
	return PackOut\Core\Build::fingerprint( $root );
}
