<?php
/**
 * What the unit tests stand on: the few WordPress functions the library
 * calls, a place to count checks, and the library itself.
 *
 * The tests run with PHP alone. No WordPress is loaded.
 *
 * @package PackOut_Core
 */

define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['packout_test'] = array(
	'passed'      => 0,
	'failed'      => 0,
	'filters'     => array(),
	'transients'  => array(),
	'environment' => 'production',
	'file_mods'   => true,
);

/**
 * Record one check.
 *
 * @param bool   $condition What should be true.
 * @param string $label     What was checked.
 */
function check( bool $condition, string $label ): void {
	if ( $condition ) {
		++$GLOBALS['packout_test']['passed'];
		return;
	}
	++$GLOBALS['packout_test']['failed'];
	fwrite( STDERR, 'FAIL ' . $label . "\n" );
}

/**
 * Print the count and exit with the result.
 */
function finish(): void {
	$test = $GLOBALS['packout_test'];
	fwrite( STDOUT, basename( $_SERVER['SCRIPT_NAME'] ) . ': ' . $test['passed'] . ' passed, ' . $test['failed'] . " failed\n" );
	exit( $test['failed'] > 0 ? 1 : 0 );
}

if ( ! class_exists( 'WP_Error' ) ) {
	/**
	 * Stands in for WordPress's error.
	 */
	class WP_Error {
		/**
		 * Code.
		 *
		 * @var string
		 */
		public $code;
		/**
		 * Message.
		 *
		 * @var string
		 */
		public $message;
		/**
		 * Build one.
		 *
		 * @param string $code    Code.
		 * @param string $message Message.
		 */
		public function __construct( $code = '', $message = '' ) {
			$this->code    = $code;
			$this->message = $message;
		}
		/**
		 * The code.
		 */
		public function get_error_code() {
			return $this->code;
		}
	}
}

function is_wp_error( $thing ) {
	return $thing instanceof WP_Error;
}
function __( $text, $domain = 'default' ) {
	return $text;
}
function add_filter( $hook, $callback, $priority = 10, $args = 1 ) {
	$GLOBALS['packout_test']['filters'][ $hook ][] = $callback;
	return true;
}
function add_action( $hook, $callback, $priority = 10, $args = 1 ) {
	return add_filter( $hook, $callback, $priority, $args );
}
function apply_filters( $hook, $value, ...$args ) {
	foreach ( $GLOBALS['packout_test']['filters'][ $hook ] ?? array() as $callback ) {
		$value = $callback( $value, ...$args );
	}
	return $value;
}
function do_action( $hook, ...$args ) {
	foreach ( $GLOBALS['packout_test']['filters'][ $hook ] ?? array() as $callback ) {
		$callback( ...$args );
	}
}
function has_filter( $hook ) {
	return ! empty( $GLOBALS['packout_test']['filters'][ $hook ] );
}
function wp_get_environment_type() {
	return $GLOBALS['packout_test']['environment'];
}
function wp_is_file_mod_allowed( $context ) {
	return $GLOBALS['packout_test']['file_mods'];
}
function get_site_transient( $key ) {
	return $GLOBALS['packout_test']['transients'][ $key ]['value'] ?? false;
}
function set_site_transient( $key, $value, $ttl = 0 ) {
	$GLOBALS['packout_test']['transients'][ $key ] = array(
		'value' => $value,
		'ttl'   => $ttl,
	);
	return true;
}
function delete_site_transient( $key ) {
	unset( $GLOBALS['packout_test']['transients'][ $key ] );
	return true;
}
function wp_json_encode( $value ) {
	return json_encode( $value );
}
function wp_remote_retrieve_response_code( $response ) {
	return is_array( $response ) ? ( $response['response']['code'] ?? 0 ) : 0;
}
function wp_remote_retrieve_body( $response ) {
	return is_array( $response ) ? ( $response['body'] ?? '' ) : '';
}
function wp_remote_request( $url, $args = array() ) {
	return new WP_Error( 'no_network', 'The unit tests do not use the network.' );
}
function wp_delete_file( $path ) {
	if ( is_file( $path ) ) {
		unlink( $path );
	}
}
function wp_kses_post( $text ) {
	return $text;
}
function wpautop( $text ) {
	return '' === $text ? '' : '<p>' . $text . '</p>';
}

require dirname( __DIR__ ) . '/load.php';
