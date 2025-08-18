<?php

/**
 * This file is part of PHPOffice Common
 *
 * PHPOffice Common is free software distributed under the terms of the GNU Lesser
 * General Public License version 3 as published by the Free Software Foundation.
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code. For the full list of
 * contributors, visit https://github.com/PHPOffice/Common/contributors.
 *
 * @see        https://github.com/PHPOffice/Common
 *
 * @copyright   2009-2016 PHPOffice Common contributors
 * @license     http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 */

namespace PhpOffice\Common;

/**
 * Text
 */
class Text
{
    /**
     * Control characters array
     *
     * @var string[]
     */
    private static $controlCharacters = [];

    /**
     * Build control characters array
     */
    private static function buildControlCharacters(): void
    {
        for ($i = 0; $i <= 19; ++$i) {
            if ($i != 9 && $i != 10 && $i != 13) {
                $find = '_x' . sprintf('%04s', strtoupper(dechex($i))) . '_';
                $replace = chr($i);
                self::$controlCharacters[$find] = $replace;
            }
        }
    }

    /**
     * Convert from PHP control character to OpenXML escaped control character
     *
     * Excel 2007 team:
     * ----------------
     * That's correct, control characters are stored directly in the shared-strings table.
     * We do encode characters that cannot be represented in XML using the following escape sequence:
     * _xHHHH_ where H represents a hexadecimal character in the character's value...
     * So you could end up with something like _x0008_ in a string (either in a cell value (<v>)
     * element or in the shared string <t> element.
     *
     * @param string $value Value to escape
     *
     * @return string
     */
    public static function controlCharacterPHP2OOXML(string $value = ''): string
    {
        if (empty(self::$controlCharacters)) {
            self::buildControlCharacters();
        }

        return str_replace(array_values(self::$controlCharacters), array_keys(self::$controlCharacters), $value);
    }

    /**
     * Return a number formatted for being integrated in xml files
     *
     * @param float $number
     * @param int $decimals
     *
     * @return string
     */
    public static function numberFormat(float $number, int $decimals): string
    {
        return number_format($number, $decimals, '.', '');
    }

    /**
     * @param int $dec
     *
     * @see http://stackoverflow.com/a/7153133/2235790
     *
     * @author velcrow
     *
     * @return string
     */
    public static function chr(int $dec): string
    {
        if ($dec <= 0x7F) {
            return chr($dec);
        }
        if ($dec <= 0x7FF) {
            return chr(($dec >> 6) + 192) . chr(($dec & 63) + 128);
        }
        if ($dec <= 0xFFFF) {
            return chr(($dec >> 12) + 224) . chr((($dec >> 6) & 63) + 128) . chr(($dec & 63) + 128);
        }
        if ($dec <= 0x1FFFFF) {
            return chr(($dec >> 18) + 240) . chr((($dec >> 12) & 63) + 128) . chr((($dec >> 6) & 63) + 128) . chr(($dec & 63) + 128);
        }

        return '';
    }

    /**
     * Convert from OpenXML escaped control character to PHP control character
     *
     * @param string $value Value to unescape
     *
     * @return string
     */
    public static function controlCharacterOOXML2PHP(string $value = ''): string
    {
        if (empty(self::$controlCharacters)) {
            self::buildControlCharacters();
        }

        return str_replace(array_keys(self::$controlCharacters), array_values(self::$controlCharacters), $value);
    }

    /**
     * Check if a string contains UTF-8 data
     *
     * @param string $value
     *
     * @return bool
     */
    public static function isUTF8(string $value = ''): bool
    {
        return is_string($value) && ($value === '' || preg_match('/^./su', $value) == 1);
    }

    /**
     * Return UTF8 encoded value
     *
     * @param string|null $value
     *
     * @return string|null
     */
    public static function toUTF8(?string $value = ''): ?string
    {
        if (!is_null($value) && !self::isUTF8($value)) {
            $value = utf8_encode($value);
        }

        return $value;
    }

    /**
     * Returns unicode from UTF8 text
     *
     * The function is splitted to reduce cyclomatic complexity
     *
     * @param string $text UTF8 text
     *
     * @return string Unicode text
     *
     * @since 0.11.0
     */
    public static function toUnicode(string $text): string
    {
        return self::unicodeToEntities(self::utf8ToUnicode($text));
    }

    /**
     * Returns unicode array from UTF8 text
     *
     * @param string $text UTF8 text
     *
     * @return array<int, int>
     *
     * @since 0.11.0
     * @see http://www.randomchaos.com/documents/?source=php_and_unicode
     */
    public static function utf8ToUnicode(string $text): array
    {
        $unicode = [];
        $values = [];
        $lookingFor = 1;

        // Gets unicode for each character
        for ($i = 0; $i < strlen($text); ++$i) {
            $thisValue = ord($text[$i]);
            if ($thisValue < 128) {
                $unicode[] = $thisValue;
            } else {
                if (count($values) == 0) {
                    $lookingFor = $thisValue < 224 ? 2 : 3;
                }
                $values[] = $thisValue;
                if (count($values) == $lookingFor) {
                    if ($lookingFor == 3) {
                        $number = (($values[0] % 16) * 4096) + (($values[1] % 64) * 64) + ($values[2] % 64);
                    } else {
                        $number = (($values[0] % 32) * 64) + ($values[1] % 64);
                    }
                    $unicode[] = $number;
                    $values = [];
                    $lookingFor = 1;
                }
            }
        }

        return $unicode;
    }

    /**
     * Returns entites from unicode array
     *
     * @param array<int, int> $unicode
     *
     * @return string
     *
     * @since 0.11.0
     * @see http://www.randomchaos.com/documents/?source=php_and_unicode
     */
    private static function unicodeToEntities(array $unicode): string
    {
        $entities = '';

        foreach ($unicode as $value) {
            if ($value != 65279) {
                $entities .= $value > 127 ? '\uc0{\u' . $value . '}' : chr($value);
            }
        }

        return $entities;
    }

    /**
     * Return name without underscore for < 0.10.0 variable name compatibility
     *
     * @param string|null $value
     *
     * @return string
     */
    public static function removeUnderscorePrefix(?string $value): string
    {
        if (!is_null($value)) {
            if (substr($value, 0, 1) == '_') {
                $value = substr($value, 1);
            }
        }

        return $value;
    }
}
