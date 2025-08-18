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

use PhpOffice\Common\XMLWriter;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test class for XMLWriter
 *
 * @coversDefaultClass \PhpOffice\Common\XMLWriter
 */
class XMLWriterTest extends \PHPUnit\Framework\TestCase
{
    public function testConstruct(): void
    {
        // Memory
        $object = new XMLWriter();
        $object->startElement('element');
        $object->text('AAA');
        $object->endElement();
        $this->assertEquals('<element>AAA</element>' . chr(10), $object->getData());

        // Disk
        $object = new XMLWriter(XMLWriter::STORAGE_DISK);
        $object->startElement('element');
        $object->text('BBB');
        $object->endElement();
        $this->assertEquals('<element>BBB</element>' . chr(10), $object->getData());
    }

    public function testConstructCompatibility(): void
    {
        $object = new XMLWriter(XMLWriter::STORAGE_MEMORY, null, false);
        $object->startElement('element');
        $object->startElement('sub');
        $object->text('CCC');
        $object->endElement();
        $object->endElement();
        $this->assertEquals(
            '<element>' . PHP_EOL . '  <sub>CCC</sub>' . PHP_EOL . '</element>' . PHP_EOL,
            $object->getData()
        );
        $object = new XMLWriter(XMLWriter::STORAGE_MEMORY, null, true);
        $object->startElement('element');
        $object->startElement('sub');
        $object->text('CCC');
        $object->endElement();
        $object->endElement();
        $this->assertEquals(
            '<element><sub>CCC</sub></element>',
            $object->getData()
        );
    }

    public function testWriteAttribute(): void
    {
        $xmlWriter = new XMLWriter();
        $xmlWriter->startElement('element');
        $xmlWriter->writeAttribute('name', 'value');
        $xmlWriter->endElement();

        $this->assertSame('<element name="value"/>' . chr(10), $xmlWriter->getData());
    }

    public function testWriteAttributeIf(): void
    {
        $xmlWriter = new XMLWriter();
        $xmlWriter->startElement('element');
        $xmlWriter->writeAttributeIf(true, 'name', 'value');
        $xmlWriter->endElement();

        $this->assertSame('<element name="value"/>' . chr(10), $xmlWriter->getData());

        $xmlWriter = new XMLWriter();
        $xmlWriter->startElement('element');
        $xmlWriter->writeAttributeIf(false, 'name', 'value');
        $xmlWriter->endElement();

        $this->assertSame('<element/>' . chr(10), $xmlWriter->getData());
    }

    public function testWriteAttributeShouldWriteFloatValueLocaleIndependent(): void
    {
        $value = 1.2;

        $xmlWriter = new XMLWriter();
        $xmlWriter->startElement('element');
        $xmlWriter->writeAttribute('name', $value);
        $xmlWriter->endElement();

        // https://www.php.net/manual/en/language.types.string.php#language.types.string.casting
        // As of PHP 8.0.0, the decimal point character is always ..
        // Prior to PHP 8.0.0, the decimal point character is defined in the script's locale (category LC_NUMERIC).
        setlocale(LC_NUMERIC, 'de_DE.UTF-8', 'de');
        if (PHP_VERSION_ID > 80000) {
            $this->assertSame('1.2', (string) $value);
        } else {
            $this->assertSame('1,2', (string) $value);
        }
        $this->assertSame('<element name="1.2"/>' . chr(10), $xmlWriter->getData());
    }

    public function testWriteElementBlock(): void
    {
        $xmlWriter = new XMLWriter();
        $xmlWriter->writeElementBlock('element', 'name');

        $this->assertSame('<element name=""/>' . chr(10), $xmlWriter->getData());

        $xmlWriter = new XMLWriter();
        $xmlWriter->writeElementBlock('element', 'name', 'value');

        $this->assertSame('<element name="value"/>' . chr(10), $xmlWriter->getData());

        $xmlWriter = new XMLWriter();
        $xmlWriter->writeElementBlock('element', ['name' => 'value']);

        $this->assertSame('<element name="value"/>' . chr(10), $xmlWriter->getData());

        $xmlWriter = new XMLWriter();
        $xmlWriter->writeElementBlock('element', ['name' => 'value'], 'value2');

        $this->assertSame('<element name="value"/>' . chr(10), $xmlWriter->getData());
    }

    /**
     * @dataProvider dataProviderWriteElementIf
     */
    #[DataProvider('dataProviderWriteElementIf')]
    public function testWriteElementIf(bool $condition, ?string $attribute, ?string $value, string $expected): void
    {
        $xmlWriter = new XMLWriter();
        $xmlWriter->writeElementIf($condition, 'element', $attribute, $value);

        $this->assertSame($expected, $xmlWriter->getData());
    }

    /**
     * @return array<array<bool|string|null>>
     */
    public static function dataProviderWriteElementIf(): array
    {
        return [
            [
                false,
                null,
                null,
                '',
            ],
            [
                true,
                null,
                null,
                '<element/>' . chr(10),
            ],
            [
                true,
                'attribute',
                null,
                '<element attribute=""/>' . chr(10),
            ],
            [
                true,
                null,
                'value',
                '<element>value</element>' . chr(10),
            ],
            [
                true,
                'attribute',
                'value',
                '<element attribute="value"/>' . chr(10),
            ],
        ];
    }
}
