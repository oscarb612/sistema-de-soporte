<?php

declare(strict_types=1);

if (! extension_loaded('gd')) {
    fwrite(STDERR, "La extensión GD es necesaria.\n");
    exit(1);
}

$outputDirectory = dirname(__DIR__).'/public/images';

$roundedRectangle = static function ($image, int $x1, int $y1, int $x2, int $y2, int $radius, int $color): void {
    imagefilledrectangle($image, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
    imagefilledrectangle($image, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);
    imagefilledellipse($image, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
    imagefilledellipse($image, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
    imagefilledellipse($image, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
    imagefilledellipse($image, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
};

$render = static function (int $size, bool $maskable) use ($outputDirectory, $roundedRectangle): void {
    $scale = $size / 512;
    $image = imagecreatetruecolor($size, $size);
    imageantialias($image, true);
    $blue = imagecolorallocate($image, 23, 79, 117);
    $light = imagecolorallocate($image, 219, 234, 254);
    $gold = imagecolorallocate($image, 217, 180, 74);
    $white = imagecolorallocate($image, 255, 255, 255);
    imagefill($image, 0, 0, $blue);

    $factor = $maskable ? .80 : 1;
    $offset = (512 - (512 * $factor)) / 2;
    $point = static fn (float $value): int => (int) round(($offset + ($value * $factor)) * $scale);

    if (! $maskable) {
        $roundedRectangle($image, 0, 0, $size - 1, $size - 1, (int) round(104 * $scale), $blue);
    }

    imagefilledpolygon($image, [
        $point(88), $point(232), $point(256), $point(92), $point(424), $point(232),
        $point(424), $point(420), $point(400), $point(444), $point(112), $point(444),
        $point(88), $point(420),
    ], $light);
    imagefilledrectangle($image, $point(198), $point(298), $point(314), $point(444), $blue);
    imagefilledrectangle($image, $point(322), $point(174), $point(412), $point(262), $gold);
    imagefilledrectangle($image, $point(345), $point(195), $point(389), $point(241), $white);

    $suffix = $maskable ? '-maskable' : '';
    imagepng($image, $outputDirectory.'/home-service-icon'.$suffix.'-'.$size.'.png', 9);
    imagedestroy($image);
};

foreach ([192, 512] as $size) {
    $render($size, false);
    $render($size, true);
}

$render(180, false);

echo "Iconos PWA generados correctamente.\n";
