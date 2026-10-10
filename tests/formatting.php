<?php

declare(strict_types=1);

use NhanAZ\Uncensor\Main;

$serverSource = $argv[1] ?? null;
if (!is_string($serverSource) || !is_file($serverSource . '/vendor/autoload.php')) {
    fwrite(STDERR, "Usage: php tests/formatting.php /path/to/Axolotl-PM [plugin PHAR]\n");
    exit(2);
}
require $serverSource . '/vendor/autoload.php';
$phar = $argv[2] ?? null;
if (is_string($phar)) {
    require 'phar://' . realpath($phar) . '/src/NhanAZ/Uncensor/Main.php';
} else {
    require dirname(__DIR__) . '/src/NhanAZ/Uncensor/Main.php';
}

function check(bool $condition, string $case): void {
    if (!$condition) {
        throw new RuntimeException("Failed: $case");
    }
}

$plugin = (new ReflectionClass(Main::class))->newInstanceWithoutConstructor();
$regex = new ReflectionProperty(Main::class, 'regex');
$unfilter = new ReflectionMethod(Main::class, 'unfilter');
$strip = static fn (string $value): string => preg_replace('/§[0-9a-fk-or]/iu', '', $value) ?? '';

check($unfilter->invoke($plugin, 'Clean text') === 'Clean text', 'idle without word list');
$regex->setValue($plugin, '/(bad|cấm)/iu');

$plain = 'bad and BAD';
$processed = $unfilter->invoke($plugin, $plain);
if (!is_string($processed)) {
    throw new RuntimeException('Formatter did not return text');
}
check($strip($processed) === $plain, 'visible text preserved for repeated words');
check(substr_count($processed, '§r') === 2, 'both word occurrences processed');
check($unfilter->invoke($plugin, 'Clean text') === 'Clean text', 'unlisted text unchanged');

$colored = '§aBad §lbad';
$processed = $unfilter->invoke($plugin, $colored);
if (!is_string($processed)) {
    throw new RuntimeException('Formatter did not return colored text');
}
check($strip($processed) === $strip($colored), 'visible colored text preserved');
check(str_contains($processed, 'B§r§aad'), 'active color restored');
check(str_contains($processed, 'b§r§a§lad'), 'active color and style restored');

$unicode = 'cấm';
$processed = $unfilter->invoke($plugin, $unicode);
if (!is_string($processed)) {
    throw new RuntimeException('Formatter did not return Unicode text');
}
check($strip($processed) === $unicode, 'Unicode word preserved');
check(str_contains($processed, 'c§rấm'), 'Unicode character boundary preserved');

echo "PASS: 8 formatting cases\n";
