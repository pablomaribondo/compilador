<?php

namespace compiler;

class Symbol
{
    public $code;
    public $lexeme;

    public function __construct($code, $lexeme, $scope)
    {
        $this->code   = $code;
        $this->lexeme = $lexeme;
        $this->scope  = $scope;
    }
}
