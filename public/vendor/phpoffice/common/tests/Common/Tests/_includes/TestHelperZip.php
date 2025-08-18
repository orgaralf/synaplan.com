<?php

namespace PhpOffice\Common\Tests;

class TestHelperZip
{
    public static function assertFileExists(string $fileZip, string $path): bool
    {
        $oZip = new \ZipArchive();
        if ($oZip->open($fileZip) !== true) {
            return false;
        }
        if ($oZip->statName($path) === false) {
            return false;
        }

        return true;
    }

    public static function assertFileContent(string $fileZip, string $path, string $content): bool
    {
        $oZip = new \ZipArchive();
        if ($oZip->open($fileZip) !== true) {
            return false;
        }
        $zipFileContent = $oZip->getFromName($path);
        if ($zipFileContent === false) {
            return false;
        }
        if ($zipFileContent != $content) {
            return false;
        }

        return true;
    }

    public static function assertFileIsCompressed(string $fileZip, string $path): bool
    {
        $oZip = new \ZipArchive();
        $oZip->open($fileZip);
        $stat = $oZip->statName($path);

        // size: uncompressed
        // comp_size: compressed

        return $stat['size'] > $stat['comp_size'];
    }
}
