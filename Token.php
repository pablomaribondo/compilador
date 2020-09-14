<?php

namespace compiler;

class Token
{
    public $code;
    public $lexeme;

    public function __construct($code, $lexeme = null)
    {
        $this->code   = $code;
        $this->lexeme = $lexeme;
    }
}
