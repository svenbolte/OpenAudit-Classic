<?php
header( "Expires: Mon, 20 Dec 1998 01:00:00 GMT" );
//header( "Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT" );
header( "Cache-Control: no-cache, must-revalidate" );
header( "Pragma: no-cache" );
set_time_limit(60);

// Get image variables
$height=$_GET["height"];
$width=$_GET["width"];
$top=$_GET["top"];

header("Content-type: image/png");

$border = 1;

// create image
$image = imagecreatetruecolor ($width, $height);
$white = imagecolorallocate($image, 255, 255, 255);
$orange = imagecolorallocate($image, 0,96, 96);
$edge = imagecolorallocate($image, 192,192,192 );

imagefilledrectangle ($image, 0, 0, $width, $height, $white);
imagecolortransparent($image, $white);

if($height-$top>$border) {
	imagefilledrectangle ($image, 0 , $top, $width , $height, $edge);
	DrawColorGradient($image, $border, $top + (2 * $border),$width - (2 * $border) -1,$height - $top - $border -1  ,$orange,$white,"v");
} else {
	imagefilledrectangle ($image, 0 , $height-$border, $width , $height, $edge);
}

imagepng($image);


// ****** DrawColorGradient *****************************************************

function DrawColorGradient(
    GdImage $image,
    int $x,
    int $y,
    int $w,
    int $h,
    int $startColor,
    int $endColor,
    string $direction = 'v'
): void {
    if ($direction === 'v') {
        for ($i = 0; $i < $h; $i++) {
            $ratio = $h > 1 ? $i / ($h - 1) : 0;

            $r = (int)((1 - $ratio) * (($startColor >> 16) & 0xFF) + $ratio * (($endColor >> 16) & 0xFF));
            $g = (int)((1 - $ratio) * (($startColor >> 8) & 0xFF) + $ratio * (($endColor >> 8) & 0xFF));
            $b = (int)((1 - $ratio) * ($startColor & 0xFF) + $ratio * ($endColor & 0xFF));

            $color = imagecolorallocate($image, $r, $g, $b);
            imageline($image, $x, $y + $i, $x + $w, $y + $i, $color);
        }
    } elseif ($direction === 'h') {
        for ($i = 0; $i < $w; $i++) {
            $ratio = $w > 1 ? $i / ($w - 1) : 0;

            $r = (int)((1 - $ratio) * (($startColor >> 16) & 0xFF) + $ratio * (($endColor >> 16) & 0xFF));
            $g = (int)((1 - $ratio) * (($startColor >> 8) & 0xFF) + $ratio * (($endColor >> 8) & 0xFF));
            $b = (int)((1 - $ratio) * ($startColor & 0xFF) + $ratio * ($endColor & 0xFF));

            $color = imagecolorallocate($image, $r, $g, $b);
            imageline($image, $x + $i, $y, $x + $i, $y + $h, $color);
        }
    }
}




// ****** int2rgb *****************************************************
function int2rgbarray($intcolor)
{
  return array(0xFF & ($intcolor >> 0x10), 0xFF & ($intcolor >> 0x8), 0xFF & $intcolor);
}

?>