<?php
/**
 * A package is accepted only when a release key signed it.
 *
 * @package PackOut_Core
 */

require __DIR__ . '/bootstrap.php';

use PackOut\Core\Release;

$pair   = sodium_crypto_sign_keypair();
$secret = sodium_crypto_sign_secretkey( $pair );
$public = sodium_bin2hex( sodium_crypto_sign_publickey( $pair ) );
$bytes  = 'PK' . random_bytes( 64 );
$signed = sodium_bin2hex( sodium_crypto_sign_detached( $bytes, $secret ) );

check( 'packout.spot' === Release::HOST && 'https://packout.spot' === Release::ORIGIN, 'releases come from packout.spot over HTTPS' );
check( count( Release::PUBLIC_KEYS ) >= 1, 'there is a release key' );
foreach ( Release::PUBLIC_KEYS as $key ) {
	check( 1 === preg_match( '/^[0-9a-f]{64}$/', $key ), 'a release key is 64 hexadecimal characters' );
}
check( Release::PUBLIC_KEYS === Release::public_keys(), 'a site accepts exactly the listed keys' );

// A key added by a filter counts only on a developer's own site.
add_filter(
	'packout_release_public_keys',
	static function ( $keys ) use ( $public ) {
		$keys[] = $public;
		$keys[] = 'not a key';
		return $keys;
	}
);
check( Release::PUBLIC_KEYS === Release::public_keys(), 'on a production site a filter cannot add a key' );
check( ! Release::verify( $bytes, $signed ), 'on a production site a package signed by another key is refused' );

$GLOBALS['packout_test']['environment'] = 'staging';
check( ! Release::verify( $bytes, $signed ), 'on a staging site too' );

$GLOBALS['packout_test']['environment'] = 'local';
check( in_array( $public, Release::public_keys(), true ), 'on a local site a filter can add a key' );
check( ! in_array( 'not a key', Release::public_keys(), true ), 'and something that is not a key is dropped' );
check( Release::verify( $bytes, $signed ), 'a package signed by an accepted key verifies' );
check( Release::verify( $bytes, strtoupper( $signed ) . "\n" ), 'the signature may be uppercase with space around it' );
check( ! Release::verify( $bytes . 'x', $signed ), 'a package with one byte added does not verify' );
$flipped    = $bytes;
$flipped[5] = chr( ord( $flipped[5] ) ^ 1 );
check( ! Release::verify( $flipped, $signed ), 'a package with one bit changed does not verify' );
check( ! Release::verify( $bytes, '' ), 'a package with no signature does not verify' );
check( ! Release::verify( $bytes, substr( $signed, 0, 126 ) ), 'a short signature does not verify' );
check( ! Release::verify( $bytes, str_repeat( 'zz', 64 ) ), 'a signature that is not hexadecimal does not verify' );
check( ! Release::verify( '', $signed ), 'an empty package does not verify' );
check( ! Release::verify( $bytes, sodium_bin2hex( sodium_crypto_sign_detached( $bytes, sodium_crypto_sign_secretkey( sodium_crypto_sign_keypair() ) ) ) ), 'a signature by a key that is not listed does not verify' );

check( Release::checksum_matches( $bytes, hash( 'sha256', $bytes ) ), 'the SHA-256 of a package matches itself' );
check( Release::checksum_matches( $bytes, strtoupper( hash( 'sha256', $bytes ) ) ), 'whatever its case' );
check( ! Release::checksum_matches( $bytes . 'x', hash( 'sha256', $bytes ) ), 'a changed package does not match' );
check( ! Release::checksum_matches( $bytes, '' ), 'no checksum does not match' );
check( ! Release::checksum_matches( $bytes, hash( 'md5', $bytes ) ), 'a checksum of another kind does not match' );

finish();
