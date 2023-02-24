<?php

declare(strict_types=1);

namespace Crutch\CaptchaGenerator;

use Crutch\CaptchaGenerator\Exception\UnsupportedCharacters;

final class CaptchaGenerator
{
    private const TYPE_PNG = 'png';
    private const TYPE_JPG = 'jpg';
    private const TYPE_GIF = 'gif';
    private const TYPE_WEBP = 'webp';

    private array $alphabet;
    private int $fluctuationAmplitude = 8;
    private ?string $credits = null;
    /** @var array<string, string> */
    private array $fonts = [];
    /** @var null|array{0:int, 1:int, 2:int} */
    private ?array $backgroundColor = null;
    /** @var null|array{0:int, 1:int, 2:int} */
    private ?array $foregroundColor = null;
    private float $whiteNoiseDensity = 1 / 6;
    private float $blackNoiseDensity = 1 / 30;
    private bool $isUseSpaces = false;
    private int $width = 160;
    private int $height = 80;
    private string $type = self::TYPE_PNG;
    private int $quality = 1;

    public function __construct()
    {
        $this->alphabet = mb_str_split('0123456789abcdefghijklmnopqrstuvwxyz');
        $fontsDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'fonts' . DIRECTORY_SEPARATOR;
        $this->addFont($fontsDir . 'palatino_linotype_bold.png');
        $this->addFont($fontsDir . 'perpetua_bold.png');
        $this->addFont($fontsDir . 'times_bold.png');
    }

    public function withSize(int $width, int $height): self
    {
        $that = clone $this;
        $that->width = $width;
        $that->height = $height;
        return $that;
    }

    public function withBackgroundColor(int $red, int $green, int $blue): self
    {
        $that = clone $this;
        $that->backgroundColor = [
            min(max($red, 0), 255),
            min(max($green, 0), 255),
            min(max($blue, 0), 255),
        ];
        return $that;
    }

    public function withForegroundColor(int $red, int $green, int $blue): self
    {
        $that = clone $this;
        $that->foregroundColor = [
            min(max($red, 0), 255),
            min(max($green, 0), 255),
            min(max($blue, 0), 255),
        ];
        return $that;
    }

    public function withCredits(?string $credits): self
    {
        $that = clone $this;
        $that->credits = $credits;
        return $that;
    }

    public function withSpaces(bool $isUseSpaces = true): self
    {
        $that = clone $this;
        $that->isUseSpaces = $isUseSpaces;
        return $that;
    }

    public function withFluctuationAmplitude(int $fluctuationAmplitude): self
    {
        $that = clone $this;
        $that->fluctuationAmplitude = max(0, $fluctuationAmplitude);
        return $that;
    }

    public function withWhiteNoiseDensity(float $whiteNoiseDensity): self
    {
        $that = clone $this;
        $that->whiteNoiseDensity = min(1, max(0, $whiteNoiseDensity));
        return $that;
    }

    public function withBlackNoiseDensity(float $blackNoiseDensity): self
    {
        $that = clone $this;
        $that->blackNoiseDensity = min(1, max(0, $blackNoiseDensity));
        return $that;
    }

    public function withFont(string $font, bool $unsetOther = false): self
    {
        $that = clone $this;
        $that->addFont($font, $unsetOther);
        return $that;
    }

    public function withoutFont(string $font): self
    {
        $that = clone $this;
        $that->removeFont($font);
        return $that;
    }

    public function asPng(int $quality = 9): self
    {
        $that = clone $this;
        $that->type = self::TYPE_PNG;
        $that->quality = min(9, max(1, $quality));
        return $that;
    }

    public function asJpeg(int $quality = 100): self
    {
        $that = clone $this;
        $that->type = self::TYPE_JPG;
        $that->quality = min(100, max(1, $quality));
        return $that;
    }

    public function asGif(): self
    {
        $that = clone $this;
        $that->type = self::TYPE_GIF;
        $that->quality = 0;
        return $that;
    }

    public function asWebp(int $quality = 100): self
    {
        $that = clone $this;
        $that->type = self::TYPE_WEBP;
        $that->quality = min(100, max(1, $quality));
        return $that;
    }

    public function generate(string $text): string
    {
        $text = mb_strtolower($text);
        $alphabet = implode('', $this->alphabet);
        if (!preg_match('/^[' . $alphabet . ']+$/i', $text)) {
            throw new UnsupportedCharacters($alphabet);
        }
        $textImage = $this->drawText($text);

        $foregroundColor = $this->getForegroundColor();
        $backgroundColor = $this->getBackgroundColor();

        $captchaImage = imagecreatetruecolor($this->width, $this->height);
        $foreground = $this->getColor($captchaImage, $foregroundColor);
        $background = $this->getColor($captchaImage, $backgroundColor);
        imagefilledrectangle($captchaImage, 0, 0, $this->width, $this->height, $background);

        $this->apply($textImage, $captchaImage, $foregroundColor, $backgroundColor);
        $this->drawCredits($captchaImage, $foreground, $background);

        ob_start();
        switch ($this->type) {
            case self::TYPE_GIF:
                imagegif($captchaImage);
                break;
            case self::TYPE_WEBP:
                imagewebp($captchaImage, null, $this->quality);
                break;
            case self::TYPE_JPG:
                imagejpeg($captchaImage, null, $this->quality);
                break;
            default:
                imagepng($captchaImage, null, 9 - $this->quality);
        }
        $blob = ob_get_clean();
        imagedestroy($textImage);
        imagedestroy($captchaImage);
        return $blob;
    }

    /**
     * @param resource $img
     * @param array{0:int, 1:int, 2:int} $array
     * @return int
     */
    private function getColor($img, array $array): int
    {
        [$red, $green, $blue] = $array;
        return imagecolorallocate($img, $red, $green, $blue);
    }

    /**
     * @param resource $img
     * @param int $foreground
     * @param int $background
     * @return void
     */
    private function drawCredits($img, int $foreground, int $background): void
    {
        if (is_null($this->credits)) {
            return;
        }
        imagefilledrectangle($img, 0, $this->height - 12, $this->width, $this->height, $foreground);
        $font = 2;
        $x = (int)($this->width / 2 - imagefontwidth($font) * strlen($this->credits) / 2);
        $y = $this->height - 14;
        imagestring($img, $font, $x, $y, $this->credits, $background);
    }

    /**
     * @param resource $img
     * @return void
     */
    private function drawNoise($img): void
    {
        $x = imagesx($img);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);

        for ($i = 0; $i < (($this->height - 30) * $x) * $this->whiteNoiseDensity; $i++) {
            imagesetpixel($img, mt_rand(0, $x - 1), mt_rand(10, $this->height - 15), $white);
        }
        for ($i = 0; $i < (($this->height - 30) * $x) * $this->blackNoiseDensity; $i++) {
            imagesetpixel($img, mt_rand(0, $x - 1), mt_rand(10, $this->height - 15), $black);
        }
    }

    private function addFont(string $font, bool $unsetOther = false): void
    {
        if ($unsetOther) {
            $this->fonts = [];
        }
        $this->fonts[$font] = $font;
    }

    private function removeFont(string $font): void
    {
        if (!array_key_exists($font, $this->fonts)) {
            return;
        }
        unset($this->fonts[$font]);
    }

    private function getBackgroundColor(): array
    {
        if (!is_null($this->backgroundColor)) {
            return  $this->backgroundColor;
        }
        return [
            mt_rand(220, 255),
            mt_rand(220, 255),
            mt_rand(220, 255),
        ];
    }

    private function getForegroundColor(): array
    {
        if (!is_null($this->foregroundColor)) {
            return  $this->foregroundColor;
        }
        return [
            mt_rand(0, 80),
            mt_rand(0, 80),
            mt_rand(0, 80),
        ];
    }

    /**
     * @return array{letters:array<string, array{start:int,end:int}>,height:int,resource:resource}
     */
    private function getFont(): array
    {
        $alphabetLength = count($this->alphabet);
        $fontFile = $this->fonts[array_rand($this->fonts)];
        $font = imagecreatefrompng($fontFile);
        imagealphablending($font, true);

        $fontFileWidth = imagesx($font);

        $metrics = [
            'letters' => [],
            'height' => imagesy($font) - 1,
            'resource' => $font,
        ];
        $symbol = 0;
        $readingSymbol = false;

        for ($i = 0; $i < $fontFileWidth && $symbol < $alphabetLength; $i++) {
            $transparent = (imagecolorat($font, $i, 0) >> 24) == 127;

            if (!$readingSymbol && !$transparent) {
                $metrics['letters'][$this->alphabet[$symbol]] = ['start' => $i];
                $readingSymbol = true;
                continue;
            }

            if ($readingSymbol && $transparent) {
                $metrics['letters'][$this->alphabet[$symbol]]['end'] = $i;
                $readingSymbol = false;
                $symbol++;
            }
        }
        return $metrics;
    }

    /**
     * @param string $text
     * @return resource
     */
    private function drawText(string $text)
    {
        $length = mb_strlen($text);
        $font = $this->getFont();

        $width = 1;
        $odd = mt_rand(0, 1);
        if ($odd == 0) {
            $odd = -1;
        }

        $steps = [];
        for ($i = 0; $i < $length; $i++) {
            $char = substr($text, $i, 1);
            $metric = $font['letters'][$char];

            $jump = (($i % 2) * $this->fluctuationAmplitude - $this->fluctuationAmplitude / 2) * $odd;
            $amplitude = (int)round($this->fluctuationAmplitude / 3);
            $offset = mt_rand(-$amplitude, $amplitude);
            $y = (int)($jump + $offset + ($this->height - $font['height']) / 2);

            $shift = $this->isUseSpaces ? mt_rand(-10, -3) : 1;

            $steps[] = [
                'dstX' => $width - $shift,
                'dstY' => $y,
                'srcX' => $metric['start'],
                'srcY' => 1,
                'srcW' => $metric['end'] - $metric['start'],
            ];

            $width += $metric['end'] - $metric['start'] - $shift;
        }

        $image = imagecreatetruecolor($width + 1, $this->height + 1);
        imagealphablending($image, true);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefilledrectangle($image, 0, 0, $width + 1, $this->height + 1, $white);

        foreach ($steps as $step) {
            imagecopy(
                $image,
                $font['resource'],
                $step['dstX'],
                $step['dstY'],
                $step['srcX'],
                $step['srcY'],
                $step['srcW'],
                $font['height']
            );
        }

        $resized = imagecreatetruecolor($this->width + 1, $this->height + 1);
        imagealphablending($resized, true);
        $white = imagecolorallocate($resized, 255, 255, 255);
        imagefilledrectangle($resized, 0, 0, $this->width + 1, $this->height + 1, $white);

        if ($width <= $this->width) {
            $this->drawNoise($image);
            $dstX = (int)(($this->width - $width) / 2);
            imagecopyresampled(
                $resized,
                $image,
                $dstX,
                0,
                0,
                0,
                $width,
                $this->height,
                $width,
                $this->height
            );
        } else {
            imagecopyresampled(
                $resized,
                $image,
                0,
                0,
                0,
                0,
                $this->width,
                $this->height,
                $width,
                $this->height
            );
            $this->drawNoise($resized);
        }

        imagedestroy($image);
        return $resized;
    }

    /**
     * @param resource $mask
     * @param resource $captcha
     * @param array $foregroundColor
     * @param array $backgroundColor
     * @return void
     */
    private function apply($mask, $captcha, array $foregroundColor, array $backgroundColor): void
    {
        // periods
        $rand01 = mt_rand(750000, 1200000) / 10000000;
        $rand02 = mt_rand(750000, 1200000) / 10000000;
        $rand03 = mt_rand(750000, 1200000) / 10000000;
        $rand04 = mt_rand(750000, 1200000) / 10000000;
        // phases
        $rand05 = mt_rand(0, 31415926) / 10000000;
        $rand06 = mt_rand(0, 31415926) / 10000000;
        $rand07 = mt_rand(0, 31415926) / 10000000;
        $rand08 = mt_rand(0, 31415926) / 10000000;
        // amplitudes
        $rand09 = mt_rand(330, 420) / 110;
        $rand10 = mt_rand(330, 450) / 100;

        //wave distortion
        for ($x = 0; $x < $this->width; $x++) {
            for ($y = 0; $y < $this->height; $y++) {
                $sx = intval($x + (sin($x * $rand01 + $rand05) + sin($y * $rand03 + $rand07)) * $rand09 + 1);
                $sy = intval($y + (sin($x * $rand02 + $rand06) + sin($y * $rand04 + $rand08)) * $rand10);

                if ($sx < 0 || $sy < 0 || $sx >= $this->width - 1 || $sy >= $this->height - 1) {
                    continue;
                }

                $color = $this->defineColor($mask, $sx, $sy, $foregroundColor, $backgroundColor);
                if (is_null($color)) {
                    continue;
                }
                [$red, $green, $blue] = $color;
                imagesetpixel($captcha, $x, $y, imagecolorallocate($captcha, $red, $green, $blue));
            }
        }
    }

    private function defineColor($mask, $sx, $sy, $foregroundColor, $backgroundColor): ?array
    {
        $color = imagecolorat($mask, $sx, $sy) & 0xFF;
        $color_x = imagecolorat($mask, $sx + 1, $sy) & 0xFF;
        $color_y = imagecolorat($mask, $sx, $sy + 1) & 0xFF;
        $color_xy = imagecolorat($mask, $sx + 1, $sy + 1) & 0xFF;

        if ($color === 255 && $color_x === 255 && $color_y === 255 && $color_xy === 255) {
            return null;
        }

        if ($color === 0 && $color_x === 0 && $color_y === 0 && $color_xy === 0) {
            return $foregroundColor;
        }

        $tmpX0 = $sx - floor($sx);
        $tmpY0 = $sy - floor($sy);
        $tmpX1 = 1 - $tmpX0;
        $tmpY1 = 1 - $tmpY0;

        $newColor0 = (
            $color * $tmpX1 * $tmpY1 +
            $color_x * $tmpX0 * $tmpY1 +
            $color_y * $tmpX1 * $tmpY0 +
            $color_xy * $tmpX0 * $tmpY0
        );

        $newColor0 = min($newColor0, 255) / 255;
        $newColor1 = 1 - $newColor0;

        return [
            intval($newColor1 * $foregroundColor[0] + $newColor0 * $backgroundColor[0]),
            intval($newColor1 * $foregroundColor[1] + $newColor0 * $backgroundColor[1]),
            intval($newColor1 * $foregroundColor[2] + $newColor0 * $backgroundColor[2]),
        ];
    }
}
