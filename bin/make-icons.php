<?php
/**
 * Draws the field app icons: a rust map pin on charcoal.
 *
 * Run from the library's directory, with PHP's GD extension:
 * php bin/make-icons.php
 *
 * @package PackOut_Core
 */

if ( 'cli' !== PHP_SAPI ) {
	exit;
}

$target = dirname( __DIR__ ) . '/assets/img';
if ( ! is_dir( $target ) ) {
	mkdir( $target, 0755, true );
}

foreach ( array( 192, 512 ) as $size ) {
	// Draw at four times the size and scale down for smooth edges.
	$scale  = 4;
	$full   = $size * $scale;
	$canvas = imagecreatetruecolor( $full, $full );
	$ground = imagecolorallocate( $canvas, 47, 50, 50 );
	$rust   = imagecolorallocate( $canvas, 187, 94, 40 );
	$white  = imagecolorallocate( $canvas, 255, 255, 255 );
	imagefilledrectangle( $canvas, 0, 0, $full, $full, $ground );

	// The pin stays inside the middle 60 percent, which is safe for masked icons.
	$center = $full / 2;
	$head_y = $full * 0.42;
	$radius = $full * 0.17;
	imagefilledellipse( $canvas, (int) $center, (int) $head_y, (int) ( $radius * 2 ), (int) ( $radius * 2 ), $rust );
	imagefilledpolygon(
		$canvas,
		array(
			(int) ( $center - $radius * 0.86 ),
			(int) ( $head_y + $radius * 0.5 ),
			(int) ( $center + $radius * 0.86 ),
			(int) ( $head_y + $radius * 0.5 ),
			(int) $center,
			(int) ( $full * 0.76 ),
		),
		$rust
	);
	imagefilledellipse( $canvas, (int) $center, (int) $head_y, (int) ( $radius * 0.8 ), (int) ( $radius * 0.8 ), $white );

	$icon = imagecreatetruecolor( $size, $size );
	imagecopyresampled( $icon, $canvas, 0, 0, 0, 0, $size, $size, $full, $full );
	imagepng( $icon, $target . '/icon-' . $size . '.png', 9 );
	echo 'icon-' . $size . ".png\n";
}//end foreach
