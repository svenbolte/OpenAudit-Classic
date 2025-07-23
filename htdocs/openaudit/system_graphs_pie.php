<?php
// Open Audit percentage pie chart image, used for disk usage graphs.
// 
include("include_lang.php");

function disk_percent_pie( $image,$percent_free, $width, $height  ) {
// Dont like the font? 
// Replace number with any of 0 -9 or whatever is installed on your system .
$font = 3;
// create image
$image = imagecreatetruecolor($width, $height);

// allocate some colors
$white    = imagecolorallocate($image, 0xFF, 0xFF, 0xFF);
$black    = imagecolorallocate($image, 0x00, 0x00, 0x00);
// Orange #FF8407
$orange = imagecolorallocate($image,0xff, 0x84, 0x07);


//Set "Empty colours
$empty_dark = imagecolorallocate( $image, 210, 210, 210 );
$empty_light = imagecolorallocate($image, 220, 220, 220 );

// Set "Full" Colour
$full_dark = imagecolorallocate( $image, 156, 190, 222 );
$full_light = imagecolorallocate( $image, 166, 200, 232 );

// Set som other colours we migh need
$gray    = imagecolorallocate($image, 0xC0, 0xC0, 0xC0);
$grey = $gray;
$darkgray = imagecolorallocate($image, 0x8d, 0x8d, 0x8d);
$navy    = imagecolorallocate($image, 0x00, 0x00, 0x80);
$darknavy = imagecolorallocate($image, 0x00, 0x00, 0x50);
$red      = imagecolorallocate($image, 0xFF, 0x00, 0x00);
$darkred  = imagecolorallocate($image, 0x90, 0x00, 0x00);

// Fill the canvas
imagefill( $image, 0,0,$white);

// find out the angle of the free space
$angle = 360*($percent_free/100);


// Percentage distortion factor (skew from circular)
$width_distortion = 25;
$height_distortion = 70;

// Thickness of pie (as a factor of image height)
$slice_thickness = $height/10;

// Wedge offset 
$wedge_offset = $width/50;

// Make the 3D pie effect
// Larger slice at the left of the image, otherwise it looks odd.

if ($percent_free <= 50) {

// Thin wedge is percent free

for ($i = $height/2; $i > $height/2-$slice_thickness; $i--)  {
     if ($percent_free !=0){ 
       imagefilledarc($image, $width/2, $i+$wedge_offset, $width-($width*$width_distortion/100),$height-($height*$height_distortion/100), 0, $angle, $empty_dark, IMG_ARC_PIE);
       } else {} 
       imagefilledarc($image, $width/2-$wedge_offset, $i, $width-($width*$width_distortion/100), $height-($height*$height_distortion/100), $angle, 360 , $full_dark, IMG_ARC_PIE);

}

  if ($percent_free !=0){
    imagefilledarc($image, $width/2, $i+$wedge_offset, $width-($width*$width_distortion/100), $height-($height*$height_distortion/100), 0, $angle, $empty_light, IMG_ARC_PIE);
    } else {}
    imagefilledarc($image, $width/2-$wedge_offset, $i, $width-($width*$width_distortion/100), $height-($height*$height_distortion/100), $angle, 360 , $full_light, IMG_ARC_PIE);

}
else 
{

// Thin wedge is percent used... well actually, we just fake it, reverse angle and colours. 

$angle = 360- $angle ;

$centerY = (int)($height / 2);
$slice = (int)$slice_thickness;

for ($i = $centerY; $i > $centerY - $slice; $i--) {
  imagefilledarc($image, $width / 2, $i + $wedge_offset, 
                 $width - ($width * $width_distortion / 100),
                 $height - ($height * $height_distortion / 100), 
                 0, $angle, $full_dark, IMG_ARC_PIE);
                 
  imagefilledarc($image, $width / 2 - $wedge_offset, $i, 
                 $width - ($width * $width_distortion / 100), 
                 $height - ($height * $height_distortion / 100), 
                 $angle, 360, $empty_dark, IMG_ARC_PIE);
}

// Letzter Slice für Licht (hier auch auf $i achten)
imagefilledarc($image, $width / 2, $i + $wedge_offset, ..., $full_light, IMG_ARC_PIE);
imagefilledarc($image, $width / 2 - $wedge_offset, $i, ..., $empty_light, IMG_ARC_PIE);

}

// The text to draw
$this_text = $percent_free;

imagestring($image,$font,17,109,"".__("Current Usage").":%",$darkgray);
// Uncomment this for a shadow effect, 
//imagestring($image,$font,16,108,"".__("Current Usage").":%",$empty_dark);

// Show Free space %
imagestring($image,$font,16,0,"".__("Free").":".$percent_free."%",$darkgray);
imagefilledellipse($image,6,8,12,12,$empty_dark);
imagefilledellipse($image,6,8,6,6,$empty_light);
imagefilledellipse($image,6,8,5,5,$red);

// Show used space %
imagestring($image,$font,16,16,"".__("Used").":".(100-$percent_free)."%",$darkgray);
imagefilledellipse($image,6,22,12,12,$full_dark);
imagefilledellipse($image,6,22,6,6,$full_light);
imagefilledellipse($image,6,22,5,5,$red);


return($image);

}

if (isset($_REQUEST["disk_percent"]) and ($_REQUEST["disk_percent"]!="") and isset($_REQUEST["width"]) and ($_REQUEST["width"]!="") and isset($_REQUEST["height"]) and ($_REQUEST["height"]!="") )
{
$percentage = $_REQUEST["disk_percent"] ;
$width = $_REQUEST["width"] ;
$height = $_REQUEST["height"] ;

$image = '';
$image = disk_percent_pie($image,$percentage,$width,$height);
// flush image
header('Content-type: image/png');
imagepng($image);
imagedestroy($image);
}
?> 
