<?php

namespace App\Entity;

class CsvImport
{
    protected $file;
    protected $imageType;

    public function getFile()
    {
        return $this->file;
    }

    public function setFile($file): void
    {
        $this->file = $file;
    }

    public function getImageType()
    {
        return $this->imageType;
    }

    public function setImageType($imageType): void
    {
        $this->imageType = $imageType;
    }
}
