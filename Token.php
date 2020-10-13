<?php

namespace compiler;

class Token
{
    public $code;
    public $lexeme;
    public $line;
    public $column;

    public function __construct($code, $lexeme, $line, $column)
    {
        $this->code   = $code;
        $this->lexeme = $lexeme;
        $this->line   = $line;
        $this->column = $column;
    }
}
