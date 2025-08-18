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

namespace PhpOffice\Common\Tests;

use PhpOffice\Common\File;

/**
 * Test class for File
 *
 * @coversDefaultClass \PhpOffice\Common\File
 */
class FileTest extends \PHPUnit\Framework\TestCase
{
    public function testFileExists(): void
    {
        $pathResources = PHPOFFICE_COMMON_TESTS_BASE_DIR . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR;
        $this->assertTrue(File::fileExists($pathResources . 'images' . DIRECTORY_SEPARATOR . 'PHPPowerPointLogo.png'));
        $this->assertFalse(File::fileExists($pathResources . 'images' . DIRECTORY_SEPARATOR . 'PHPPowerPointLogo_404.png'));
        $this->assertTrue(File::fileExists('zip://' . $pathResources . 'files' . DIRECTORY_SEPARATOR . 'Sample_01_Simple.pptx#[Content_Types].xml'));
        $this->assertFalse(File::fileExists('zip://' . $pathResources . 'files' . DIRECTORY_SEPARATOR . 'Sample_01_Simple.pptx#404.xml'));
        $this->assertFalse(File::fileExists('zip://' . $pathResources . 'files' . DIRECTORY_SEPARATOR . '404.pptx#404.xml'));

        // Set a ZIP en ReadOnly Mode
        $zipTest = tempnam(sys_get_temp_dir(), 'PhpOfficeCommon');
        copy($pathResources . 'files' . DIRECTORY_SEPARATOR . 'Sample_01_Simple.pptx', $zipTest);
        chmod($zipTest, 333);
        $this->assertFalse(File::fileExists('zip://' . $zipTest));
        unlink($zipTest);
    }

    public function testGetFileContents(): void
    {
        $pathResources = PHPOFFICE_COMMON_TESTS_BASE_DIR . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR;
        $this->assertIsString(File::fileGetContents($pathResources . 'images' . DIRECTORY_SEPARATOR . 'PHPPowerPointLogo.png'));
        $this->assertNull(File::fileGetContents($pathResources . 'images' . DIRECTORY_SEPARATOR . 'PHPPowerPointLogo_404.png'));
        $this->assertIsString(File::fileGetContents('zip://' . $pathResources . 'files' . DIRECTORY_SEPARATOR . 'Sample_01_Simple.pptx#[Content_Types].xml'));
        $this->assertNull(File::fileGetContents('zip://' . $pathResources . 'files' . DIRECTORY_SEPARATOR . 'Sample_01_Simple.pptx#404.xml'));
        $this->assertNull(File::fileGetContents('zip://' . $pathResources . 'files' . DIRECTORY_SEPARATOR . '404.pptx#404.xml'));
    }

    public function testRealPath(): void
    {
        $pathFiles = PHPOFFICE_COMMON_TESTS_BASE_DIR . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR;
        $this->assertEquals($pathFiles . 'Sample_01_Simple.pptx', File::realpath($pathFiles . 'Sample_01_Simple.pptx'));
        $this->assertEquals(
            'zip://' . $pathFiles . 'Sample_01_Simple.pptx#[Content_Types].xml',
            File::realpath('zip://' . $pathFiles . 'Sample_01_Simple.pptx#[Content_Types].xml')
        );
        $this->assertEquals('zip://' . $pathFiles . 'Sample_01_Simple.pptx#/[Content_Types].xml', File::realpath('zip://' . $pathFiles . 'Sample_01_Simple.pptx#/rels/../[Content_Types].xml'));
    }
}
