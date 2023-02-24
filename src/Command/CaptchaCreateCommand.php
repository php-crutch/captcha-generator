<?php

declare(strict_types=1);

namespace Crutch\CaptchaGenerator\Command;

use Crutch\CaptchaGenerator\CaptchaGenerator;
use InvalidArgumentException;

final class CaptchaCreateCommand extends AbstractCommand
{
    public static function getName(): string
    {
        return 'captcha:create';
    }

    public static function getDescription(): string
    {
        return 'Create captcha image';
    }

    public static function getArguments(): array
    {
        return [
            'text' => 'text of captcha',
            'file' => 'path to output file',
        ];
    }

    public static function getOptions(): array
    {
        return [
            'size' => 'size of image, format {WIDTH}x{HEIGHT}. By default, 160x80',
            'background-color' => 'background color, css format (ffffff of eee). By default, random light color',
            'foreground-color' => 'foreground color, css format (000000 or 111). By default, random dark color',
            'credits' => 'credit text (added to footer of image). By default, NULL',
            'fluctuation-amplitude' => 'vertical fluctuation amplitude. By default, 8',
            'white-noise-density' => 'white noise density, float between 0 and 1. By default, .16',
            'black-noise-density' => 'black noise density, float between 0 and 1. By default, .03',
            'type' => 'type png|jpeg|gif|webp. By defaults, png',
            'quality' => implode('', [
                'image quality, integer between 0 and 9 for png, between 0 and 100 for jpeg and webp. ',
                'By defaults, 6 for png, 70 for jpeg and webp, ignored for gif',
            ]),
        ];
    }

    public static function getFlags(): array
    {
        return [
            'with-spaces' => 'add spaces between symbols',
        ];
    }

    public function execute(array $arguments, array $options, array $flags): int
    {
        $text = $arguments['text'] ?? null;
        $file = $arguments['file'] ?? null;
        if (empty($text)) {
            throw new InvalidArgumentException('Argument text required', 1);
        }
        if (empty($file)) {
            throw new InvalidArgumentException('Argument file required', 1);
        }

        $dir = dirname($file);
        if (!is_dir($dir)) {
            throw new InvalidArgumentException(sprintf('Directory %s not found', $dir), 1);
        }

        if (!is_writable($dir)) {
            throw new InvalidArgumentException(sprintf('Directory %s not writable', $dir), 1);
        }

        $size = $options['size'] ?? '160x80';
        preg_match('#^(?<width>([1-9]\d*))x(?<height>([1-9]\d*))$#ui', $size, $matches);
        $width = (int)($matches['width'] ?? 0);
        $height = (int)($matches['height'] ?? 0);
        if ($width === 0 || $height === 0) {
            throw new InvalidArgumentException(sprintf('Invalid size (%s)', $size), 1);
        }

        preg_match('#^(?<amplitude>([1-9]\d*))$#ui', $options['fluctuation-amplitude'] ?? '8', $matches);
        $amplitude = $matches['amplitude'] ?? null;
        if (is_null($amplitude)) {
            throw new InvalidArgumentException(
                sprintf('Invalid fluctuation-amplitude (%s)', $options['fluctuation-amplitude'] ?? '8'),
                1
            );
        }

        preg_match('#^(?<noise>((0)?\.[0-9]\d*))$#ui', $options['white-noise-density'] ?? '.16', $matches);
        $whiteNoise = $matches['noise'] ?? null;
        if (is_null($whiteNoise)) {
            throw new InvalidArgumentException(
                sprintf('Invalid white-noise-density (%s)', $options['white-noise-density'] ?? '.16'),
                1
            );
        }

        preg_match('#^(?<noise>((0)?\.[0-9]\d*))$#ui', $options['black-noise-density'] ?? '.03', $matches);
        $blackNoise = $matches['noise'] ?? null;
        if (is_null($blackNoise)) {
            throw new InvalidArgumentException(
                sprintf('Invalid black-noise-density (%s)', $options['black-noise-density'] ?? '.03'),
                1
            );
        }

        $backgroundColor = $this->parseColor($options['background-color'] ?? 'random', 'background-color');
        $foregroundColor = $this->parseColor($options['foreground-color'] ?? 'random', 'foreground-color');

        preg_match('#^(?<type>(png|jpg|jpeg|gif|webp))$#ui', $options['type'] ?? 'png', $matches);
        $type = strtolower($matches['type'] ?? 'png');
        preg_match('#^(?<quality>([1-9]\d*))$#ui', $options['quality'] ?? '', $matches);
        $quality = $matches['quality'] ?? null;
        if (!is_null($quality)) {
            $quality = (int)$quality;
        }

        $withSpaces = $flags['with-spaces'] ?? false;

        $generator = (new CaptchaGenerator())
            ->withSize($width, $height)
            ->withCredits($options['credits'] ?? null)
            ->withFluctuationAmplitude((int)$amplitude)
            ->withWhiteNoiseDensity((float)$whiteNoise)
            ->withBlackNoiseDensity((float)$blackNoise)
            ->withSpaces((bool)$withSpaces)
        ;
        switch ($type) {
            case 'gif':
                $generator = $generator->asGif();
                break;
            case 'webp':
                $generator = $generator->asWebp(is_null($quality) ? 70 : $quality);
                break;
            case 'jpg': // no break
            case 'jpeg':
                $generator = $generator->asJpeg(is_null($quality) ? 70 : $quality);
                break;
            default:
                $generator = $generator->asPng(is_null($quality) ? 6 : $quality);
        }
        if (!is_null($backgroundColor)) {
            $generator = $generator->withBackgroundColor(...$backgroundColor);
        }
        if (!is_null($foregroundColor)) {
            $generator = $generator->withForegroundColor(...$foregroundColor);
        }

        $image = $generator->generate($text);
        file_put_contents($file, $image);

        return 0;
    }

    /**
     * @param string $color
     * @param string $parameter
     * @return null|array{0:int, 1:int, 2:int}
     */
    private function parseColor(string $color, string $parameter): ?array
    {
        preg_match('/^(?<code>(random|([0-9a-f]{6})|([0-9a-f]{3})))$/ui', $color, $matches);
        $code = $matches['code'] ?? null;
        if (is_null($code)) {
            throw new InvalidArgumentException(sprintf('Invalid %s (%s)', $parameter, $color), 1);
        }
        if ($code === 'random') {
            return null;
        }
        $short = strlen($code) === 3;
        if ($short) {
            $tmp = str_split($code);
            $hex = [str_repeat($tmp[0], 2), str_repeat($tmp[1], 2), str_repeat($tmp[2], 2)];
        } else {
            $hex = str_split($code, 2);
        }
        return [(int)hexdec($hex[0]), (int)hexdec($hex[1]), (int)hexdec($hex[2])];
    }
}
