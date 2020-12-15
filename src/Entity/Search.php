<?php

namespace App\Entity;

class Search
{
    protected $id;
    protected $pending;

    public function getId() : string
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getPending() : string
    {
        return $this->pending;
    }

    public function setPending($pending)
    {
        $this->pending = $pending;
    }
}
