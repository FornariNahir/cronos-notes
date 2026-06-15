<?php
// Script to extract colors from the paletaColores.png image
$imagePath = "C:\\xampp\\htdocs\\Cronos-Notes\\public\\imagenes\\paletaColores.png";

if (!file_exists($imagePath)) {
    echo "Image not found at $imagePath\n";
    exit(1);
}

$im = imagecreatefrompng($imagePath);
if (!$im) {
    echo "Failed to load image.\n";
    exit(1);
}

$width = imagesx($im);
$height = imagesy($im);

$colors = [];
// Sample pixels across the image to find distinct colors
for ($x = 0; $x < $width; $x += max(1, (int)($width / 50))) {
    for ($y = 0; $y < $height; $y += max(1, (int)($height / 50))) {
        $rgb = imagecolorat($im, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        
        $hex = sprintf("#%02x%02x%02x", $r, $g, $b);
        if (!isset($colors[$hex])) {
            $colors[$hex] = 0;
        }
        $colors[$hex]++;
    }
}

// Sort colors by frequency
arsort($colors);

echo "Extracted Colors from paletaColores.png:\n";
$i = 0;
foreach ($colors as $hex => $count) {
    if ($count > 5) { // filter out minor noise/anti-aliased borders
        echo "Color: $hex (Frequency: $count)\n";
        $i++;
        if ($i > 15) break;
    }
}
imagedestroy($im);
