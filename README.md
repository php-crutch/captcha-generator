# crutch/captcha-generator

Captcha Generator.

This code based on [KCAPTCHA v2.0](http://www.captcha.ru/kcaptcha/)

![example](data:image/png;base64,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)

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
;

//
$symbols = '23456789abcdegikpqsvxyz'; // alphabet without similar symbols (o=0, 1=l, i=j, t=f)
$length = 6;
do {
    $text = '';
    for ($i = 0; $i < $length; $i++) {
        $text .= substr($symbols, mt_rand(0, strlen($symbols) - 1), 1);
    }
} while(!preg_match('/cp|cb|ck|c6|c9|rn|rm|mm|co|do|cl|db|qp|qb|dp|ww/', $symbols));

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
