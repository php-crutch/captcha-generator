# crutch/captcha-generator

Captcha Generator.

This code based on [KCAPTCHA v2.0](http://www.captcha.ru/kcaptcha/)

![example](https://raw.githubusercontent.com/php-crutch/captcha-generator/v1.0.0/examples/captcha00.png)

## Install

```bash
composer require crutch/captcha-generator:^1.0
```

## Usage

```php
<?php

use Crutch\CaptchaGenerator\CaptchaGenerator;

require __DIR__ . '/vendor/autoload.php';

$generator = (new CaptchaGenerator())
    ->withSize(250, 100) // Image size. By default, 160x80
    ->withBackgroundColor(20, 20, 20) // Set background color (r, g, b). By default, random light color
    ->withForegroundColor(200, 200, 200) // Set foreground color (r, g, b). By default, random dark color
    ->withCredits('crutch captcha') // Added text to image bottom. By default, NULL
    ->withSpaces(true) // Adds spaces between characters. By default, FALSE
    ->withFluctuationAmplitude(0) // Vertical fluctuation amplitude. By default, 8
    ->withWhiteNoiseDensity(.1) // White noise density. By default, 1 / 6
    ->withBlackNoiseDensity(.05) // White noise density. By default, 1 / 30
    ->asJpeg(90) // Use JPEG format with quality from 1 to 100
    ->asWebp(80) // Use WEBP format with quality from 1 to 100
    ->asGif() // Use GIF format
    ->asPng(8) // Use PNG format with quality from 1 to 9
;

//
$symbols = '23456789abcdegkpqsvxyz'; // alphabet without similar symbols (o=0, 1=l, i=j, t=f)

$text = '';
for ($i = 0; $i < 6; $i++) {
    $text .= substr($symbols, mt_rand(0, strlen($symbols) - 1), 1);
}

$image = $generator->generate($text); // generate PNG image

file_put_contents('/tmp/captcha.png', $image); // save to file

echo sprintf('<img src="data:image/png;base64,%s" alt="captcha"/>', base64_encode($image)); // out inline
```

## Fonts

You may generate fonts from ttl files

```bash
./vendor/bin/crutch-captcha-generator font:convert /path/to/input-font.ttf /path/to/output-font-simple.png
```

or outlined font

```bash
./vendor/bin/crutch-captcha-generator font:convert /path/to/input-font.ttf /path/to/output-font-outline.png --outline
```

and set it to captcha

```php
<?php

use Crutch\CaptchaGenerator\CaptchaGenerator;

require __DIR__ . '/vendor/autoload.php';

$generator = (new CaptchaGenerator())
    ->withFont('/path/to/output-font-simple.png', true) // add custom font and unset others
    ->withFont('/path/to/output-font-outline.png') // add other custom font
;

$image = $generator->generate('abc'); // generate PNG image
```
