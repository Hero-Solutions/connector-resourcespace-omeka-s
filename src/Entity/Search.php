<?php

namespace App\Entity;

class Search
{
    protected $username;
    protected $key;
    protected $input;
    protected $field;

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
