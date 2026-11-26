<?php
/**
 * A signed request passes only as it was sent, once, and soon.
 *
 * @package PackOut_Core
 */

require __DIR__ . '/bootstrap.php';

use PackOut\Core\Signing;

$pair  = Signing::keypair();
$other = Signing::keypair();
check( Signing::is_public_key( $pair['public'] ) && Signing::is_secret_key( $pair['secret'] ), 'a key pair is a 64-character public key and a 128-character secret key' );
check( ! Signing::is_public_key( $pair['secret'] ) && ! Signing::is_public_key( 'not a key' ) && ! Signing::is_public_key( null ), 'anything else is not a public key' );
check( $pair['public'] !== $other['public'], 'two key pairs differ' );

$nonce = Signing::nonce();
check( Signing::is_nonce( $nonce ) && Signing::nonce() !== $nonce, 'a number used once is 32 hexadecimal characters, new each time' );
check( ! Signing::is_nonce( 'abc' ) && ! Signing::is_nonce( strtoupper( $nonce ) . 'x' ), 'anything else is not one' );

$now  = 1795000000;
$path = '/wp-json/packout/v1/partners/listings';
$body = '{"items":[]}';
$text = Signing::text( 'post', $path, $body, $now, $nonce );
check( "POST\n" . $path . "\n" . hash( 'sha256', $body ) . "\n" . $now . "\n" . $nonce === $text, 'the signed text is the method, the path, the hash of the body, the time, and the number, one to a line' );
check( hash( 'sha256', '' ) === explode( "\n", Signing::text( 'GET', '/x', '', $now, $nonce ) )[2], 'an empty body hashes as the empty string' );

$signature = Signing::sign( $text, $pair['secret'] );
check( '' !== $signature && false !== base64_decode( $signature, true ) && 64 === strlen( base64_decode( $signature, true ) ), 'a signature is 64 bytes, as base64' );
check( Signing::verify( $text, $signature, $pair['public'] ), 'a signature verifies against the key that made it' );
check( ! Signing::verify( $text, $signature, $other['public'] ), 'and not against another key' );
check( ! Signing::verify( Signing::text( 'POST', $path, '{"items":[1]}', $now, $nonce ), $signature, $pair['public'] ), 'a body changed after signing is refused' );
check( ! Signing::verify( Signing::text( 'POST', $path . '?full=1', $body, $now, $nonce ), $signature, $pair['public'] ), 'a path changed after signing is refused' );
check( ! Signing::verify( Signing::text( 'PUT', $path, $body, $now, $nonce ), $signature, $pair['public'] ), 'a method changed after signing is refused' );
check( ! Signing::verify( Signing::text( 'POST', $path, $body, $now + 1, $nonce ), $signature, $pair['public'] ), 'a time changed after signing is refused' );
check( ! Signing::verify( Signing::text( 'POST', $path, $body, $now, Signing::nonce() ), $signature, $pair['public'] ), 'a number changed after signing is refused' );
check( ! Signing::verify( $text, 'not base64 !!', $pair['public'] ) && ! Signing::verify( $text, base64_encode( 'short' ), $pair['public'] ) && ! Signing::verify( $text, '', $pair['public'] ), 'a signature that is not one is refused' );
check( ! Signing::verify( $text, $signature, 'not a key' ), 'a key that is not one is refused' );
check( '' === Signing::sign( $text, 'not a key' ) && '' === Signing::sign( $text, $pair['public'] ), 'nothing is signed with a key that is not a secret key' );

check( Signing::fresh( $now, $now ) && Signing::fresh( $now - 300, $now ) && Signing::fresh( $now + 300, $now ), 'a request within five minutes of the clock is fresh' );
check( ! Signing::fresh( $now - 301, $now ) && ! Signing::fresh( $now + 301, $now ) && ! Signing::fresh( $now - 600, $now ), 'one further off is stale, early or late' );
check( Signing::REMEMBER >= 2 * Signing::WINDOW, 'a number is remembered for longer than a request stays fresh' );

$headers = Signing::headers( 'site123', $pair['secret'], 'GET', '/wp-json/packout/v1/partners/me?nonce=' . $nonce, '', $now, $nonce );
check( array( 'X-PackOut-Key', 'X-PackOut-Timestamp', 'X-PackOut-Nonce', 'X-PackOut-Signature' ) === array_keys( $headers ), 'a signed request carries four headers' );
check( 'site123' === $headers[ Signing::KEY ] && (string) $now === $headers[ Signing::TIMESTAMP ] && $nonce === $headers[ Signing::NONCE ], 'the site, the time, and the number' );
check( Signing::verify( Signing::text( 'GET', '/wp-json/packout/v1/partners/me?nonce=' . $nonce, '', $now, $nonce ), $headers[ Signing::SIGNATURE ], $pair['public'] ), 'and a signature of all of it' );

check( '/wp-json/packout/v1/partners/me?nonce=abc&contract=1' === Signing::path( 'https://packout.spot/wp-json/packout/v1/partners/me?nonce=abc&contract=1' ), 'the signed path is the path and the query of the address' );
check( '/sub/wp-json/packout/v1/partners/stats' === Signing::path( 'https://example.org/sub/wp-json/packout/v1/partners/stats' ), 'with the directory a site is installed in' );
check( '/?rest_route=/packout/v1/partners/me' === Signing::path( 'http://packout.local:8090?rest_route=/packout/v1/partners/me' ), 'and a slash when the address has no path' );

finish();
