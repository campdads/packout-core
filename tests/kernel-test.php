<?php
/**
 * The kernel takes eleven adapters and refuses anything less.
 *
 * @package PackOut_Core
 */

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/adapters.php';

use PackOut\Core\Kernel;

check( '0.1.0' === Kernel::version() || 1 === preg_match( '/^\d+\.\d+\.\d+/', Kernel::version() ), 'the library reports a version' );
check( 11 === count( Kernel::ADAPTERS ), 'there are eleven adapters' );
check( null === Kernel::instance(), 'no kernel is registered before a plugin registers one' );

$adapters = test_adapters();
$kernel   = new Kernel( $adapters );
check( $kernel->site() === $adapters['site'], 'the kernel hands back the site adapter it was given' );
check( $kernel->settings() === $adapters['settings'], 'the kernel hands back the settings adapter it was given' );
check( $kernel->adapter( 'mailer' ) === $adapters['mailer'], 'an adapter can be asked for by name' );
foreach ( array_keys( Kernel::ADAPTERS ) as $name ) {
	check( is_callable( array( $kernel, $name ) ) && $kernel->$name() === $adapters[ $name ], 'the kernel has a method for the ' . $name . ' adapter' );
}

$heard = null;
add_action(
	'packout_core_loaded',
	static function ( $given ) use ( &$heard ) {
		$heard = $given;
	}
);
$kernel->register();
check( Kernel::instance() === $kernel, 'registering makes the kernel the one the library uses' );
check( $heard === $kernel, 'registering announces the kernel' );
Kernel::reset();
check( null === Kernel::instance(), 'the kernel can be forgotten' );

foreach ( array_keys( Kernel::ADAPTERS ) as $name ) {
	$short = $adapters;
	unset( $short[ $name ] );
	$refused = false;
	try {
		new Kernel( $short );
	} catch ( InvalidArgumentException $error ) {
		$refused = false !== strpos( $error->getMessage(), $name );
	}
	check( $refused, 'a kernel without the ' . $name . ' adapter is refused, and the message names it' );
}

$wrong            = $adapters;
$wrong['palette'] = new Test_Icons();
$refused          = false;
try {
	new Kernel( $wrong );
} catch ( InvalidArgumentException $error ) {
	$refused = true;
}
check( $refused, 'an adapter of the wrong kind is refused' );

$odd                  = test_adapters();
$odd['site']->surface = 'both';
$refused              = false;
try {
	new Kernel( $odd );
} catch ( InvalidArgumentException $error ) {
	$refused = true;
}
check( $refused, 'a site that is neither the hub nor a partner is refused' );

$refused = false;
try {
	$kernel->adapter( 'payments' );
} catch ( InvalidArgumentException $error ) {
	$refused = true;
}
check( $refused, 'asking for an adapter that does not exist is refused' );

finish();
