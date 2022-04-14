<?php
/*
 * This file is part of Aplus Framework Routing Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tests\Routing;

use PHPUnit\Framework\TestCase;

/**
 * Class LanguagesTest.
 */
final class LanguagesTest extends TestCase
{
    protected string $langDir = __DIR__ . '/../src/Languages/';

    /**
     * @return array<int,string>
     */
    protected function getCodes() : array
    {
        // @phpstan-ignore-next-line
        $codes = \array_filter((array) \glob($this->langDir . '*'), 'is_dir');
        $length = \strlen($this->langDir);
        $result = [];
        foreach ($codes as $dir) {
            if ($dir === false) {
                continue;
            }
            $result[] = \substr($dir, $length);
        }
        return $result;
    }

    public function testKeys() : void
    {
        $rules = require $this->langDir . 'en/routing.php';
        $rules = \array_keys($rules);
        foreach ($this->getCodes() as $code) {
            $lines = require $this->langDir . $code . '/routing.php';
            $lines = \array_keys($lines);
            \sort($lines);
            self::assertSame($rules, $lines, 'Language: ' . $code);
        }
    }
}
