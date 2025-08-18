<?php

namespace PhpOffice\Common\Adapter\Zip;

class ZipArchiveAdapter implements ZipInterface
{
    /**
     * @var \ZipArchive
     */
    protected $oZipArchive;

    /**
     * @var string
     */
    protected $filename;

    public function open($filename)
    {
        $this->filename = $filename;
        $this->oZipArchive = new \ZipArchive();

        if ($this->oZipArchive->open($this->filename, \ZipArchive::OVERWRITE) === true) {
            return $this;
        }
        if ($this->oZipArchive->open($this->filename, \ZipArchive::CREATE) === true) {
            return $this;
        }
        throw new \Exception("Could not open $this->filename for writing.");
    }

    public function close()
    {
        if ($this->oZipArchive->close() === false) {
            throw new \Exception("Could not close zip file $this->filename.");
        }

        return $this;
    }

    public function addFromString(string $localname, string $contents, bool $withCompression = true)
    {
        if ($this->oZipArchive->addFromString($localname, $contents) === false) {
            throw new \Exception('Error zipping files : ' . $localname);
        }
        if (!$withCompression) {
            $this->oZipArchive->setCompressionName($localname, \ZipArchive::CM_STORE);
        }

        return $this;
    }
}
