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
 * @copyright   2009-2017 PHPOffice Common contributors
 * @license     http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 */

namespace PhpOffice\Common\Tests;

use Exception;
use PhpOffice\Common\XMLReader;
use PHPUnit\Framework\TestCase;

/**
 * Test class for XMLReader
 *
 * @coversDefaultClass \PhpOffice\Common\XMLReader
 */
class XMLReaderTest extends TestCase
{
    /**
     * Test reading XML from string
     */
    public function testDomFromString(): void
    {
        $reader = new XMLReader();
        $reader->getDomFromString('<element attr="test"><child attr="subtest">AAA</child></element>');

        $this->assertTrue($reader->elementExists('/element/child'));
        $this->assertEquals('AAA', $reader->getElement('/element/child')->textContent);
        $this->assertEquals('AAA', $reader->getValue('/element/child'));
        $this->assertEquals('test', $reader->getAttribute('attr', $reader->getElement('/element')));
        $this->assertEquals('subtest', $reader->getAttribute('attr', $reader->getElement('/element'), 'child'));
    }

    /**
     * Test reading XML from zip
     */
    public function testDomFromZip(): void
    {
        $pathResources = PHPOFFICE_COMMON_TESTS_BASE_DIR . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR;

        $reader = new XMLReader();
        $this->assertInstanceOf(\DOMDocument::class, $reader->getDomFromZip($pathResources . 'reader.zip', 'test.xml'));

        $this->assertTrue($reader->elementExists('/element/child'));

        $this->assertFalse($reader->getDomFromZip($pathResources . 'reader.zip', 'non_existing_xml_file.xml'));
    }

    /**
     * Test reading XML from zip
     */
    public function testDomFromZipWithSharepointPath(): void
    {
        $pathResources = PHPOFFICE_COMMON_TESTS_BASE_DIR . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR;

        $reader = new XMLReader();
        $this->assertInstanceOf(\DOMDocument::class, $reader->getDomFromZip($pathResources . 'reader.zip', '/test.xml'));
    }

    /**
     * Test that read from non existing archive throws exception
     */
    public function testThrowsExceptionOnNonExistingArchive(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot find archive file.');

        $pathResources = PHPOFFICE_COMMON_TESTS_BASE_DIR . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR;

        $reader = new XMLReader();
        $reader->getDomFromZip($pathResources . 'readers.zip', 'test.xml');
    }

    /**
     * Test elements count
     */
    public function testCountElements(): void
    {
        $reader = new XMLReader();
        $reader->getDomFromString('<element attr="test"><child>AAA</child><child>BBB</child></element>');

        $this->assertEquals(2, $reader->countElements('/element/child'));
    }

    /**
     * Test read non existing elements
     */
    public function testReturnNullOnNonExistingNode(): void
    {
        $reader = new XMLReader();
        $this->assertCount(0, $reader->getElements('/element/children'));
        $reader->getDomFromString('<element><child>AAA</child></element>');

        $this->assertNull($reader->getElement('/element/children'));
        $this->assertNull($reader->getValue('/element/children'));
    }

    /**
     * Test that xpath fails if custom namespace is not registered
     */
    public function testShouldThrowExceptionIfNamespaceIsNotKnown(): void
    {
        $reader = new XMLReader();
        $reader->getDomFromString('<element><test:child xmlns:test="http://phpword.com/my/custom/namespace">AAA</test:child></element>');
        $reader->registerNamespace('test', 'http://phpword.com/my/custom/namespace');

        $this->assertTrue($reader->elementExists('/element/test:child'));
        $this->assertEquals('AAA', $reader->getElement('/element/test:child')->textContent);
    }

    /**
     * Test reading XML with manually registered namespace
     */
    public function testShouldParseXmlWithCustomNamespace(): void
    {
        $reader = new XMLReader();
        $reader->getDomFromString('<element><test:child xmlns:test="http://phpword.com/my/custom/namespace">AAA</test:child></element>');
        $reader->registerNamespace('test', 'http://phpword.com/my/custom/namespace');

        $this->assertTrue($reader->elementExists('/element/test:child'));
        $this->assertEquals('AAA', $reader->getElement('/element/test:child')->textContent);
    }

    /**
     * Test that xpath fails if custom namespace is not registered
     */
    public function testShouldThowExceptionIfTryingToRegisterNamespaceBeforeReadingDoc(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Dom needs to be loaded before registering a namespace');

        $reader = new XMLReader();
        $reader->registerNamespace('test', 'http://phpword.com/my/custom/namespace');
    }
}
