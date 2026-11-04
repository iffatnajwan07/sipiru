<?php

namespace App\Support;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode as Generator;
use chillerlan\QRCode\QROptions;

final class QrCode
{
    /** SVG inline untuk ditampilkan di halaman web. */
    public static function svg(string $data): string
    {
        return (new Generator(new QROptions([
            'outputInterface' => QRMarkupSVG::class,
            'outputBase64' => false,
            'eccLevel' => EccLevel::M,
            'addQuietzone' => true,
            'svgAddXmlHeader' => false,
            'drawLightModules' => false,
            'connectPaths' => true,
        ])))->render($data);
    }

    /** PNG data URI — dipakai di PDF (dompdf) dan unduhan. */
    public static function pngDataUri(string $data, int $scale = 8): string
    {
        return (new Generator(new QROptions([
            'outputInterface' => QRGdImagePNG::class,
            'outputBase64' => true,
            'eccLevel' => EccLevel::M,
            'scale' => $scale,
            'addQuietzone' => true,
        ])))->render($data);
    }

    public static function png(string $data, int $scale = 10): string
    {
        $uri = self::pngDataUri($data, $scale);

        return base64_decode(substr($uri, strpos($uri, ',') + 1));
    }
}
