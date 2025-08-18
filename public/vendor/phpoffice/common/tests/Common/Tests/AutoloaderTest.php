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

use PhpOffice\Common\Autoloader;

/**
 * Test class for Autoloader
 */
class AutoloaderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Register
     */
    public function testRegister(): void
    {
        Autoloader::register();
        $this->assertContains(
            ['PhpOffice\\Common\\Autoloader', 'autoload'],
            spl_autoload_functions()
        );
    }

    /**
     * Autoload
     */
    public function testAutoload(): void
    {
        $declared = get_declared_classes();
        $declaredCount = count($declared);
        Autoloader::autoload('Foo');
        $this->assertEquals(
            $declaredCount,
            count(get_declared_classes()),
            'PhpOffice\\Common\\Autoloader::autoload() is trying to load ' .
            'classes outside of the PhpOffice\\Common namespace'
        );
    }
}
