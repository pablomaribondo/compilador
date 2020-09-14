<?php

namespace compiler;

abstract class TokenCodes
{
    const IDENTIFIER               = 0;
    const LT_RELATIONAL_OPERATOR   = 1;
    const LE_RELATIONAL_OPERATOR   = 2;
    const GT_RELATIONAL_OPERATOR   = 3;
    const GE_RELATIONAL_OPERATOR   = 4;
    const EQUALITY_OPERATOR        = 5;
    const INEQUALITY_OPERATOR      = 6;
    const ADD_ARITHMETIC_OPERATOR  = 7;
    const SUB_ARITHMETIC_OPERATOR  = 8;
    const MULT_ARITHMETIC_OPERATOR = 9;
    const DIV_ARITHMETIC_OPERATOR  = 10;
    const ASSIGNMENT_OPERATOR      = 11;
    const OPEN_PARENTHESIS         = 12;
    const CLOSE_PARENTHESIS        = 13;
    const OPEN_CURLY_BRACKET       = 14;
    const CLOSE_CURLY_BRACKET      = 15;
    const COMMA                    = 16;
    const SEMICOLON                = 17;
    const INT_VALUE                = 18;
    const FLOAT_VALUE              = 19;
    const CHAR_VALUE               = 20;
    const MAIN_RESERVED_WORD       = 21;
    const IF_RESERVED_WORD         = 22;
    const ELSE_RESERVED_WORD       = 23;
    const WHILE_RESERVED_WORD      = 24;
    const DO_RESERVED_WORD         = 25;
    const FOR_RESERVED_WORD        = 26;
    const INT_RESERVED_WORD        = 27;
    const FLOAT_RESERVED_WORD      = 28;
    const CHAR_RESERVED_WORD       = 29;
    const EOF                      = 30;
}
