<?php

namespace App\Entity;

class CsvImport
{
    protected $file;
    protected $username;
    protected $key;
    protected $imageType;
    protected $extraColumns;
    protected $imageCount;
    protected $extraInfo;

    public function getFile()
    {
        return $this->file;
    }

    public function setFile($file): void
    {
        $this->file = $file;
    }
    public function getUsername() : string
    {
        return $this->username;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }

    public function getKey() : string
    {
        return $this->key;
    }

    public function setKey($key)
    {
        $this->key = $key;
    }

    public function getImageType()
    {
        return $this->imageType;
    }

    public function setImageType($imageType): void
    {
        $this->imageType = $imageType;
    }

    public function getExtraColumns()
    {
        return $this->extraColumns;
    }

    public function setExtraColumns($extraColumns): void
    {
        $this->extraColumns = $extraColumns;
    }

    public function getImageCount()
    {
        return $this->imageCount;
    }

    public function setImageCount($imageCount): void
    {
        $this->imageCount = $imageCount;
    }

    public function getExtraInfo()
    {
        return $this->extraInfo;
    }

    public function setExtraInfo($extraInfo): void
    {
        $this->extraInfo = $extraInfo;
    }
}
