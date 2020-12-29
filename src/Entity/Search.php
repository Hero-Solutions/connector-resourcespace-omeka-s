<?php

namespace App\Entity;

class Search
{
    protected $input;
    protected $field;

    public function getInput() : string
    {
        return $this->input;
    }

    public function setInput($input)
    {
        $this->input = $input;
    }

    public function getField() : string
    {
        return $this->field;
    }

    public function setField($field)
    {
        $this->field = $field;
    }
}
