<?php

namespace App\Entity;

class Search
{
    protected $id;
    protected $pending;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId($id): void
    {
        $this->id = $id;
    }

    public function getPending(): bool
    {
        return $this->pending;
    }

    public function setPending($pending): void
    {
        $this->pending = $pending;
    }
}
