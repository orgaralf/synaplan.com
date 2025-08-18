<?php

namespace PhpOffice\Common\Tests\Adapter\Zip;

use PhpOffice\Common\Adapter\Zip\ZipArchiveAdapter;
use PhpOffice\Common\Adapter\Zip\ZipInterface;

class ZipArchiveAdapterTest extends AbstractZipAdapter
{
    protected function createAdapter(): ZipInterface
    {
        return new ZipArchiveAdapter();
    }
}
