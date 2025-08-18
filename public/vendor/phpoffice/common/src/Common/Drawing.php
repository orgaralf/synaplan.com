<?php

/**
 * This file is part of PHPOffice Common
 *
 * PHPOffice Common is free software distributed under the terms of the GNU Lesser
 * General Public License version 3 as published by the Free Software Foundation.
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code. For the full list of
 * contributors, visit https://github.com/PHPOffice/PHPWord/contributors.
 *
 * @see        https://github.com/PHPOffice/Common
 *
 * @copyright   2009-2016 PHPOffice Common contributors
 * @license     http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 */

namespace PhpOffice\Common;

class Drawing
{
    public const DPI_96 = 96;

    // Source : angle
    /**
     * Convert angle to degrees
     *
     * @param int $pValue Angle
     *
     * @return float
     */
    public static function angleToDegrees(int $pValue = 0): float
    {
        if ($pValue == 0) {
            return 0;
        }

        return round($pValue / 60000);
    }

    // Source : cms
    /**
     * Convert centimeters width to pixels
     *
     * @param float $pValue Value in centimeters
     *
     * @return int
     */
    public static function centimetersToPixels(float $pValue = 0): int
    {
        if ($pValue == 0) {
            return 0;
        }

        return (int) round(($pValue / 2.54) * self::DPI_96);
    }

    /**
     * Convert centimeters width to points
     *
     * @param float $pValue Value in centimeters
     *
     * @return float
     */
    public static function centimetersToPoints(float $pValue = 0): float
    {
        if ($pValue == 0) {
            return 0;
        }

        return ($pValue / 2.54) * self::DPI_96 * 0.75;
    }

    /**
     * Convert centimeters width to twips
     *
     * @param int $pValue
     *
     * @return float
     */
    public static function centimetersToTwips(int $pValue = 0): float
    {
        if ($pValue == 0) {
            return 0;
        }

        return $pValue * 566.928;
    }

    // Source : Degrees
    /**
     * Convert degrees to angle
     *
     * @param int $pValue Degrees
     *
     * @return int
     */
    public static function degreesToAngle(int $pValue = 0): int
    {
        return (int) round($pValue * 60000);
    }

    // Source : EMU
    /**
     * Convert EMU to pixels
     *
     * @param int $pValue Value in EMU
     *
     * @return float
     */
    public static function emuToPixels(int $pValue = 0): float
    {
        if ($pValue == 0) {
            return 0;
        }

        return $pValue / 9525;
    }

    // Source : HTML Color
    /**
     * Convert HTML hexadecimal to RGB
     *
     * @param string $pValue HTML Color in hexadecimal
     *
     * @return array<int, int>|null Value in RGB
     */
    public static function htmlToRGB(string $pValue): ?array
    {
        if ($pValue[0] == '#') {
            $pValue = substr($pValue, 1);
        }

        if (strlen($pValue) == 6) {
            list($colorR, $colorG, $colorB) = [$pValue[0] . $pValue[1], $pValue[2] . $pValue[3], $pValue[4] . $pValue[5]];
        } elseif (strlen($pValue) == 3) {
            list($colorR, $colorG, $colorB) = [$pValue[0] . $pValue[0], $pValue[1] . $pValue[1], $pValue[2] . $pValue[2]];
        } else {
            return null;
        }

        $colorR = hexdec($colorR);
        $colorG = hexdec($colorG);
        $colorB = hexdec($colorB);

        return [$colorR, $colorG, $colorB];
    }

    // Source : Inches
    /**
     * Convert inches to points
     *
     * @param float $pValue
     *
     * @return float
     */
    public static function inchesToPoints(float $pValue): float
    {
        return $pValue * 72;
    }

    /**
     * Convert inches width to twips
     *
     * @param int $pValue
     *
     * @return int
     */
    public static function inchesToTwips(int $pValue = 0): int
    {
        if ($pValue == 0) {
            return 0;
        }

        return $pValue * 1440;
    }

    // Source : Picas
    /**
     * Convert picas to points
     *
     * @param float $pValue
     *
     * @return float
     */
    public static function picasToPoints(float $pValue): float
    {
        return $pValue * 12;
    }

    // Source : Pixels
    /**
     * Convert pixels to centimeters
     *
     * @param int $pValue Value in pixels
     *
     * @return float
     */
    public static function pixelsToCentimeters(int $pValue = 0): float
    {
        // return $pValue * 0.028;
        return ($pValue / self::DPI_96) * 2.54;
    }

    /**
     * Convert pixels to EMU
     *
     * @param float $pValue Value in pixels
     *
     * @return float
     */
    public static function pixelsToEmu(float $pValue = 0): float
    {
        return $pValue * 9525;
    }

    /**
     * Convert pixels to points
     *
     * @param int $pValue Value in pixels
     *
     * @return float
     */
    public static function pixelsToPoints(int $pValue = 0): float
    {
        return $pValue * 0.75;
    }

    // Source : Points
    /**
     * Convert points width to centimeters
     *
     * @param float $pValue Value in points
     *
     * @return float
     */
    public static function pointsToCentimeters(float $pValue = 0): float
    {
        if ($pValue == 0) {
            return 0;
        }

        return (($pValue / 0.75) / self::DPI_96) * 2.54;
    }

    /**
     * Convert points to emu
     *
     * @param float $pValue
     *
     * @return int
     */
    public static function pointsToEmu(float $pValue = 0): int
    {
        if ($pValue == 0) {
            return 0;
        }

        return (int) round(($pValue / 0.75) * 9525);
    }

    /**
     * Convert points width to pixels
     *
     * @param float $pValue Value in points
     *
     * @return float
     */
    public static function pointsToPixels(float $pValue = 0): float
    {
        if ($pValue == 0) {
            return 0;
        }

        return $pValue / 0.75;
    }

    // Source : Twips
    /**
     * Convert twips width to centimeters
     *
     * @param int $pValue
     *
     * @return float
     */
    public static function twipsToCentimeters(int $pValue = 0): float
    {
        if ($pValue == 0) {
            return 0;
        }

        return $pValue / 566.928;
    }

    /**
     * Convert twips width to inches
     *
     * @param int $pValue
     *
     * @return float
     */
    public static function twipsToInches(int $pValue = 0): float
    {
        if ($pValue == 0) {
            return 0;
        }

        return $pValue / 1440;
    }

    /**
     * Convert twips width to pixels
     *
     * @param int $pValue
     *
     * @return float
     */
    public static function twipsToPixels(int $pValue = 0): float
    {
        if ($pValue == 0) {
            return 0;
        }

        return round($pValue / 15);
    }
}
