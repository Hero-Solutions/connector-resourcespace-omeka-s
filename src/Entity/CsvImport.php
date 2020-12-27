<?php

namespace App\Entity;

class CsvImport
{
    protected $file;

    public function getFile()
    {
        return $this->file;
    }

    public function setFile($file): void
    {
        $this->file = $file;
    }
}
