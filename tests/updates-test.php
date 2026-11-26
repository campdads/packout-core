<?php
/**
 * The update client offers what the catalog offers and refuses a package
 * that is not the one the catalog described.
 *
 * @package PackOut_Core
 */

require __DIR__ . '/bootstrap.php';

use PackOut\Core\Release;
use PackOut\Core\Updates;

const FILE = 'packout-partner/packout-partner.php';

$pair                                   = sodium_crypto_sign_keypair();
$secret                                 = sodium_crypto_sign_secretkey( $pair );
$public                                 = sodium_bin2hex( sodium_crypto_sign_publickey( $pair ) );
$package                                = 'PK' . random_bytes( 128 );
$GLOBALS['packout_test']['environment'] = 'local';
add_filter(
	'packout_release_public_keys',
	static function ( $keys ) use ( $public ) {
		$keys[] = $public;
		return $keys;
	}
);

/**
 * What the catalog answers, and every request it was sent.
 */
$catalog = array(
	'requests' => array(),
	'status'   => 200,
	'modules'  => array(),
	'plugin'   => array(),
);

/**
 * A module as the catalog lists it.
 *
 * @param string $version   Version.
 * @param string $bytes     Package contents.
 * @param string $secret    Signing key, or an empty string for no signature.
 * @param string $origin    Catalog address.
 */
function module_for( string $version, string $bytes, string $secret, string $origin = 'https://packout.spot' ): array {
	return array(
		'slug'        => 'packout-partner',
		'name'        => 'Pack-Out Spot Partner',
		'version'     => $version,
		'package_url' => $origin . '/wp-json/vectra/v1/packages/packout-partner/' . $version,
		'checksum'    => hash( 'sha256', $bytes ),
		'signature'   => '' === $secret ? '' : sodium_bin2hex( sodium_crypto_sign_detached( $bytes, $secret ) ),
		'requires'    => array(
			'php'              => '8.2',
			'wordpress'        => '6.8',
			'tested_wordpress' => '7.1',
		),
	);
}

$http = static function ( $url, $args ) use ( &$catalog ) {
	$catalog['requests'][] = array(
		'url'  => $url,
		'args' => $args,
	);
	if ( 200 !== $catalog['status'] ) {
		return array(
			'response' => array( 'code' => $catalog['status'] ),
			'body'     => '',
		);
	}
	$body = false !== strpos( $url, '/plugins/' ) ? $catalog['plugin'] : array(
		'manifest' => 1,
		'modules'  => array_merge(
			array(
				array(
					'slug'    => 'vectra-ui',
					'version' => '9.9.9',
				),
			),
			$catalog['modules']
		),
	);
	return array(
		'response' => array( 'code' => 200 ),
		'body'     => json_encode( $body ),
	);
};

$downloads = array();
$written   = array();
$download  = static function ( $url ) use ( &$downloads, &$written, &$package ) {
	$downloads[] = $url;
	$path        = tempnam( sys_get_temp_dir(), 'pos' );
	file_put_contents( $path, $package );
	$written[] = $path;
	return $path;
};

$channel = 'stable';
$client  = new Updates(
	array(
		'file'     => FILE,
		'slug'     => 'packout-partner',
		'version'  => '1.0.0',
		'http'     => $http,
		'download' => $download,
		'channel'  => static function () use ( &$channel ) {
			return $channel;
		},
	)
);

// What the client is built from.
foreach ( array(
	array(),
	array(
		'file'    => FILE,
		'slug'    => 'Packout Partner',
		'version' => '1.0.0',
	),
	array(
		'file'    => 'other/other.php',
		'slug'    => 'packout-partner',
		'version' => '1.0.0',
	),
	array(
		'file' => FILE,
		'slug' => 'packout-partner',
	),
) as $config ) {
	$refused = false;
	try {
		new Updates( $config );
	} catch ( InvalidArgumentException $error ) {
		$refused = true;
	}
	check( $refused, 'a client without a plugin’s file, slug, and version is refused' );
}

// Registration.
$client->register();
check( has_filter( 'update_plugins_packout.spot' ), 'the client answers WordPress’s check for packout.spot' );
check( has_filter( 'plugins_api' ) && has_filter( 'upgrader_pre_download' ), 'and the details window and the download' );

// The check.
$catalog['modules'] = array( module_for( '1.1.0', $package, $secret ) );
$offer              = $client->check( false, array( 'Version' => '1.0.0' ), FILE );
check( is_array( $offer ) && '1.1.0' === $offer['version'], 'a newer version in the catalog is offered' );
check( 'https://packout.spot/wp-json/vectra/v1/packages/packout-partner/1.1.0' === $offer['package'], 'with the catalog’s package address' );
check( 'https://packout.spot/plugins/packout-partner' === $offer['id'] && 'packout-partner' === $offer['slug'], 'under the plugin’s Update URI and slug' );
check( '6.8' === $offer['requires'] && '8.2' === $offer['requires_php'] && '7.1' === $offer['tested'], 'with what the version requires' );

$sent = $catalog['requests'][0];
check( 'https://packout.spot/wp-json/vectra/v1/manifest' === $sent['url'] && 'POST' === $sent['args']['method'], 'the check is a POST to the catalog’s manifest' );
check( array( FILE => array( 'Version' => '1.0.0' ) ) === json_decode( $sent['args']['body']['plugins'], true ), 'it sends the plugin’s file and version, and nothing else about the site' );
check( 'stable' === $sent['args']['body']['channel'], 'it asks for the stable channel' );
check( ! isset( $sent['args']['user-agent'] ) && ! isset( $sent['args']['headers']['User-Agent'] ), 'it leaves WordPress’s own User-Agent in place' );

$client->check( false, array(), FILE );
check( 1 === count( $catalog['requests'] ), 'a second check within twelve hours does not ask again' );
check( 43200 === reset( $GLOBALS['packout_test']['transients'] )['ttl'], 'the answer is kept for twelve hours' );

check( 'kept' === $client->check( 'kept', array(), 'another/another.php' ), 'another plugin’s check is left alone' );
check( 1 === count( $catalog['requests'] ), 'and costs no request' );

$status = $client->status();
check( '1.1.0' === $status['version'] && ! $status['failed'] && $status['checked'] > 0, 'the client reports its last check' );

// The channel.
$channel = 'beta';
$client->check( false, array(), FILE );
check( 2 === count( $catalog['requests'] ) && 'beta' === $catalog['requests'][1]['args']['body']['channel'], 'a site that chose beta versions asks for the beta channel, at once' );
$channel = 'stable';
$client->forget();

// Nothing offered, and no answer.
$catalog['modules'] = array();
check( false === $client->check( false, array(), FILE ), 'when the catalog lists no such plugin, nothing is offered' );
$client->forget();

$catalog['status'] = 503;
check( false === $client->check( false, array(), FILE ), 'when the catalog does not answer, nothing is offered' );
check( 3600 === reset( $GLOBALS['packout_test']['transients'] )['ttl'], 'and the next try waits an hour' );
check( $client->status()['failed'], 'and the client says the check failed' );
$before = count( $catalog['requests'] );
$client->check( false, array(), FILE );
check( count( $catalog['requests'] ) === $before, 'a check within that hour does not ask again' );
$catalog['status'] = 200;
$client->forget();

// A catalog answer that points somewhere else is not an offer.
$elsewhere                = module_for( '1.1.0', $package, $secret );
$elsewhere['package_url'] = 'https://example.org/wp-json/vectra/v1/packages/packout-partner/1.1.0';
$catalog['modules']       = array( $elsewhere );
check( false === $client->check( false, array(), FILE ), 'a package on another site is not offered' );
$client->forget();

$odd                = module_for( '1.1.0', $package, $secret );
$odd['version']     = '1.1';
$catalog['modules'] = array( $odd );
check( false === $client->check( false, array(), FILE ), 'a version that is not three numbers is not offered' );
$client->forget();

// Updates turned off.
$catalog['modules']                   = array( module_for( '1.1.0', $package, $secret ) );
$GLOBALS['packout_test']['file_mods'] = false;
$before                               = count( $catalog['requests'] );
check( false === $client->check( false, array(), FILE ) && count( $catalog['requests'] ) === $before, 'a site that turned plugin changes off is not asked about' );
$GLOBALS['packout_test']['file_mods'] = true;

// The download.
$url  = 'https://packout.spot/wp-json/vectra/v1/packages/packout-partner/1.1.0';
$path = $client->pre_download( false, $url, null, array( 'plugin' => FILE ) );
check( is_string( $path ) && is_file( $path ) && file_get_contents( $path ) === $package, 'a signed package with the right checksum is handed to WordPress' );
check( array( $url ) === $downloads, 'the client downloads it itself' );
unlink( $path );

check( 'theirs' === $client->pre_download( 'theirs', $url, null, array( 'plugin' => FILE ) ), 'a download someone else already answered is left alone' );
check( false === $client->pre_download( false, 'https://downloads.wordpress.org/plugin/akismet.zip', null, array( 'plugin' => 'akismet/akismet.php' ) ), 'another plugin’s package is left alone' );
check( false === $client->pre_download( false, 'https://packout.spot/wp-json/vectra/v1/packages/packout-spot/1.0.0', null, array() ), 'another Pack-Out Spot plugin’s package is left alone' );
check( false === $client->pre_download( false, $url, null, array( 'plugin' => 'other/other.php' ) ), 'this plugin’s package offered for another plugin is left alone' );

/**
 * The code of what the client answers for a download.
 *
 * @param Updates $client The client.
 * @param string  $url    Package address.
 */
function refusal( Updates $client, string $url ): string {
	$answer = $client->pre_download( false, $url, null, array( 'plugin' => FILE ) );
	return $answer instanceof WP_Error ? $answer->get_error_code() : 'accepted';
}

$good    = $package;
$package = $good . 'x';
check( 'packout_package_checksum' === refusal( $client, $url ), 'a package with one byte added is refused: it is not the file described' );
$package = $good;

$client->forget();
$wrong              = module_for( '1.1.0', $good, $secret );
$wrong['signature'] = sodium_bin2hex( sodium_crypto_sign_detached( $good, sodium_crypto_sign_secretkey( sodium_crypto_sign_keypair() ) ) );
$catalog['modules'] = array( $wrong );
check( 'packout_package_signature' === refusal( $client, $url ), 'a package signed by a key that is not a release key is refused' );

$client->forget();
$catalog['modules'] = array( module_for( '1.1.0', $good, '' ) );
$before             = count( $downloads );
check( 'packout_package_unsigned' === refusal( $client, $url ), 'a package with no signature is refused' );
check( count( $downloads ) === $before, 'and is not downloaded' );

$client->forget();
$catalog['modules'] = array( module_for( '1.2.0', $good, $secret ) );
check( 'packout_package_unknown' === refusal( $client, $url ), 'a package the catalog no longer offers is refused' );

$client->forget();
$catalog['modules']                     = array( module_for( '1.1.0', $good, $secret ) );
$GLOBALS['packout_test']['environment'] = 'production';
check( 'packout_package_signature' === refusal( $client, $url ), 'on a production site a test key signs nothing' );
$GLOBALS['packout_test']['environment'] = 'local';
check( count( $written ) >= 4 && array() === array_filter( $written, 'is_file' ), 'a refused download is removed from the disk' );

// The details window.
$catalog['plugin'] = array(
	'slug'         => 'packout-partner',
	'name'         => 'Pack-Out Spot Partner',
	'description'  => 'Run stewardship work on your own site.',
	'version'      => '1.1.0',
	'package_url'  => $url,
	'release_date' => '2026-12-01',
	'changelog'    => 'First version.',
	'requires'     => array(
		'php'              => '8.2',
		'wordpress'        => '6.8',
		'tested_wordpress' => '7.1',
	),
);
$details           = $client->details( false, 'plugin_information', (object) array( 'slug' => 'packout-partner' ) );
check( is_object( $details ) && 'Pack-Out Spot Partner' === $details->name && '1.1.0' === $details->version, 'the details window is filled from the catalog' );
check( '6.8' === $details->requires && '8.2' === $details->requires_php && '7.1' === $details->tested, 'with what the version requires' );
check( false !== strpos( $details->sections['description'], 'stewardship' ) && false !== strpos( $details->sections['changelog'], 'First version' ), 'and its description and changes' );
check( 'x' === $client->details( 'x', 'plugin_information', (object) array( 'slug' => 'akismet' ) ), 'another plugin’s details are left alone' );
check( 'x' === $client->details( 'x', 'query_plugins', (object) array( 'slug' => 'packout-partner' ) ), 'a search of the directory is left alone' );

// Where the catalog is.
check( 'https://packout.spot' === Updates::origin(), 'the catalog is packout.spot' );
define( 'PACKOUT_CATALOG_ORIGIN', 'http://packout.local:8090/' );
check( 'http://packout.local:8090' === Updates::origin(), 'a local site may name another catalog' );
check( $client->is_package( 'http://packout.local:8090/wp-json/vectra/v1/packages/packout-partner/1.1.0' ) && ! $client->is_package( $url ), 'and then that catalog’s packages are the plugin’s' );
$GLOBALS['packout_test']['environment'] = 'production';
check( 'https://packout.spot' === Updates::origin(), 'a production site ignores that setting' );
check( 'https://packout.spot/plugins/packout-partner' === $offer['id'], 'the Update URI never changes' );

finish();
