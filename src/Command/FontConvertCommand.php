<?php

declare(strict_types=1);

namespace Crutch\CaptchaGenerator\Command;

use InvalidArgumentException;

final class FontConvertCommand extends AbstractCommand
{
    public static function getName(): string
    {
        return 'font:convert';
    }

    public static function getDescription(): string
    {
        return 'Convert TTF font to internal PNG font';
    }

    public static function getArguments(): array
    {
        return [
            'ttf' => 'path to source ttf file',
            'png' => 'path to output png file',
        ];
    }

    public static function getOptions(): array
    {
        return [];
    }

    public static function getFlags(): array
    {
        return [
            'outline' => 'draw outline of letters',
        ];
    }

    public function execute(array $arguments, array $options, array $flags): int
    {
        $ttf = $arguments['ttf'] ?? null;
        $png = $arguments['png'] ?? null;
        $outline = $flags['outline'] ?? false;
        if (empty($ttf)) {
            throw new InvalidArgumentException('Argument ttf required', 1);
        }
        if (empty($png)) {
            throw new InvalidArgumentException('Argument png required', 1);
        }

        if (!file_exists($ttf)) {
            throw new InvalidArgumentException(sprintf('File %s not found', $ttf), 1);
        }

        if (!is_readable($ttf)) {
            throw new InvalidArgumentException(sprintf('File %s not readable', $ttf), 1);
        }

        $dir = dirname($png);
        if (!is_dir($dir)) {
            throw new InvalidArgumentException(sprintf('Directory %s not found', $dir), 1);
        }

        if (!is_writable($dir)) {
            throw new InvalidArgumentException(sprintf('Directory %s not writable', $dir), 1);
        }
        $size = 0;
        do {
            $size++;
            $box = @imagettfbbox($size, 0, $ttf, 'a');
            if (empty($box)) {
                throw new InvalidArgumentException(sprintf('Invalid ttf font %s', $ttf), 1);
            }
            $h = max($box[3], $box[1]) - min($box[5], $box[7]);
            if ($h < 0) {
                $h = -$h;
            }
        } while ($h <= 35);

        $alphabet = mb_str_split('0123456789abcdefghijklmnopqrstuvwxyz');

        if ($outline) {
            $img = $this->createOutlineFont($alphabet, $ttf, $size);
        } else {
            $img = $this->createSimpleFont($alphabet, $ttf, $size);
        }

        imagepng($img, $png, 9);
        imagedestroy($img);

        return 0;
    }

    private function createSimpleFont(array $alphabet, string $ttf, int $size)
    {
        [$metrics, $width, $height, $bottom] = $this->getMetrics($alphabet, $ttf, $size, false);

        $img = imagecreatetruecolor($width, $height);
        imagealphablending($img, false);
        imagesavealpha($img, true);

        $black = imagecolorallocate($img, 0, 0, 0);
        $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
        imagefilledrectangle($img, 0, 0, $width, $height, $transparent);

        $x = 5;
        imagesetthickness($img, 1);
        foreach ($metrics as $letter => $metric) {
            $w = $metric['w'];
            imageline($img, $x, 0, $x + $w, 0, $black);
            imagettftext($img, $size, 0, $x, $height - $bottom - 5, $black, $ttf, (string)$letter);
            $x += $w + 10;
        }

        return $img;
    }

    private function createOutlineFont(array $alphabet, string $ttf, int $size)
    {
        [$metrics, $width, $height, $bottom] = $this->getMetrics($alphabet, $ttf, $size, true);

        $img = imagecreatetruecolor($width, $height);
        imagealphablending($img, false);
        imagesavealpha($img, true);

        $black = imagecolorallocate($img, 0, 0, 0);
        $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
        $semiTransparent = imagecolorallocatealpha($img, 0, 0, 0, 64);
        imagefilledrectangle($img, 0, 0, $width, $height, $transparent);

        $x = 5;
        $y = $height - $bottom - 5;
        imagesetthickness($img, 1);
        foreach ($metrics as $letter => $metric) {
            $w = $metric['w'];

            $letterImg = imagecreatetruecolor($w, $height);
            imagealphablending($letterImg, false);
            imagesavealpha($letterImg, true);

            $letterBlack = imagecolorallocate($letterImg, 0, 0, 0);
            $letterWhite = imagecolorallocate($letterImg, 255, 255, 255);
            imagefilledrectangle($letterImg, 0, 0, $w, $height, $letterWhite);
            imagettftext($letterImg, $size, 0, 0, $y, $letterBlack, $ttf, (string)$letter);

            for ($sy = 0; $sy < $height; $sy++) {
                for ($sx = 0; $sx < $w; $sx++) {
                    $color = imagecolorat($letterImg, $sx, $sy) & 0xFF;
                    if ($color === 0) {
                        imagesetpixel($img, $x + $sx - 2, $sy, $semiTransparent);
                        imagesetpixel($img, $x + $sx + 2, $sy, $semiTransparent);
                        for ($x1 = -1; $x1 <= 1; $x1++) {
                            for ($y1 = -1; $y1 <= 1; $y1++) {
                                imagesetpixel($img, $x + $sx + $x1, $sy + $y1, $black);
                            }
                        }
                    }
                }
            }
            imagettftext($img, $size, 0, $x, $y, $transparent, $ttf, (string)$letter);
            imageline($img, $x, 0, $x + $w, 0, $black);
            imagedestroy($letterImg);
            $x += $w + 10;
        }

        return $img;
    }

    private function getMetrics(array $alphabet, string $ttf, int $size, bool $outline): array
    {
        $metrics = [];
        $height = 0;
        $width = 0;
        $bottom = 0;

        foreach ($alphabet as $letter) {
            $box = imagettfbbox($size, 0, $ttf, $letter);
            $w = max($box[4], $box[2]) - min($box[6], $box[0]);
            $h = max($box[3], $box[1]) - min($box[5], $box[7]);
            $add = $outline ? 4 : 0;
            $metrics[$letter] = [
                'w' => $w + $add,
                'h' => $h,
            ];
            if ($h > $height) {
                $height = $h;
            }
            $b = max($box[3], $box[1]);
            if ($b > $bottom) {
                $bottom = $b;
            }
            $width += $w + 10;
        }
        $height += $bottom + 5;
        return [$metrics, $width, $height, $bottom];
    }
}
