<?php

namespace compiler;

function parse($file)
{
    static $token;
    $token = scan($file);

    program($file, $token);
}

function declaration($file, &$token)
{
    if (
        $token->code === TokenCodes::INT_RESERVED_WORD ||
        $token->code === TokenCodes::FLOAT_RESERVED_WORD ||
        $token->code === TokenCodes::CHAR_RESERVED_WORD
    ) {
        $token = scan($file);
    } else {
        exit("ERRO na linha {$token->line}, coluna {$token->column}: " .
            'Declaração mal formada, tipo esperado');
    }

    $token->code === TokenCodes::IDENTIFIER
        ? $token = scan($file)
        : exit("ERRO na linha {$token->line}, coluna {$token->column}: " .
            'Declaração mal formada, identificador esperado');

    while ($token->code === TokenCodes::COMMA) {
        $token = scan($file);

        $token->code === TokenCodes::IDENTIFIER
            ? $token = scan($file)
            : exit("ERRO na linha {$token->line}, coluna {$token->column}: " .
                'Declaração mal formada, identificador esperado');
    }

    $token->code === TokenCodes::SEMICOLON
        ? $token = scan($file)
        : exit("ERRO na linha {$token->line}, coluna {$token->column}: " .
            'Declaração mal formada, ";" esperado');
}

function program($file, &$token)
{
    $token->code === TokenCodes::INT_RESERVED_WORD
        ? $token = scan($file)
        : exit("ERRO na linha {$token->line}, coluna {$token->column}: " .
            'Programa mal formado, "int" esperado');

    $token->code === TokenCodes::MAIN_RESERVED_WORD
        ? $token = scan($file)
        : exit("ERRO na linha {$token->line}, coluna {$token->column}: " .
            'Programa mal formado, "main" esperado');

    $token->code === TokenCodes::OPEN_PARENTHESIS
        ? $token = scan($file)
        : exit("ERRO na linha {$token->line}, coluna {$token->column}: " .
            'Programa mal formado, "(" esperado');

    $token->code === TokenCodes::CLOSE_PARENTHESIS
        ? $token = scan($file)
        : exit("ERRO na linha {$token->line}, coluna {$token->column}: " .
            'Programa mal formado, ")" esperado');

    codeBlock($file, $token);

    $token->code === TokenCodes::EOF
        ? $token = scan($file)
        : exit("ERRO na linha {$token->line}, coluna {$token->column}: " .
            'Programa mal formado, fim de arquivo esperado');
}

function codeBlock($file, &$token)
{
    $token->code === TokenCodes::OPEN_CURLY_BRACKET
        ? $token = scan($file)
        : exit("ERRO na linha {$token->line}, coluna {$token->column}: " .
            'Bloco mal formado, "{" esperado');

    while (
        $token->code === TokenCodes::INT_RESERVED_WORD ||
        $token->code === TokenCodes::FLOAT_RESERVED_WORD ||
        $token->code === TokenCodes::CHAR_RESERVED_WORD
    ) {
        declaration($file, $token);
    }

    while (
        $token->code === TokenCodes::IDENTIFIER ||
        $token->code === TokenCodes::OPEN_CURLY_BRACKET ||
        $token->code === TokenCodes::WHILE_RESERVED_WORD ||
        $token->code === TokenCodes::DO_RESERVED_WORD ||
        $token->code === TokenCodes::IF_RESERVED_WORD
    ) {
        command($file, $token);
    }

    $token->code === TokenCodes::CLOSE_CURLY_BRACKET
        ? $token = scan($file)
        : exit("ERRO na linha {$token->line}, coluna {$token->column}: " .
            'Bloco mal formado, "}" esperado');
}

function command($file, &$token)
{
    switch ($token->code) {
        case TokenCodes::IDENTIFIER:
        case TokenCodes::OPEN_CURLY_BRACKET:
            basicCommand($file, $token);
            break;
        case TokenCodes::WHILE_RESERVED_WORD:
        case TokenCodes::DO_RESERVED_WORD:
            iteration($file, $token);
            break;
        case TokenCodes::IF_RESERVED_WORD:
            $token = scan($file);

            $token->code === TokenCodes::OPEN_PARENTHESIS
                ? $token = scan($file)
                : exit("ERRO na linha {$token->line}, coluna {$token->column}: " .
                    'Comando mal formado, "(" esperado');

            relationalExpression($file, $token);

            $token->code === TokenCodes::CLOSE_PARENTHESIS
                ? $token = scan($file)
                : exit("ERRO na linha {$token->line}, coluna {$token->column}: " .
                    'Comando mal formado, ")" esperado');

            command($file, $token);

            if ($token->code === TokenCodes::ELSE_RESERVED_WORD) {
                $token = scan($file);
                command($file, $token);
            }

            break;
        default:
            exit("ERRO na linha {$token->line}, coluna {$token->column}: Comando mal formado");
    }
}

function basicCommand($file, &$token)
{
    switch ($token->code) {
        case TokenCodes::IDENTIFIER:
            assignment($file, $token);
            break;
        case TokenCodes::OPEN_CURLY_BRACKET:
            codeBlock($file, $token);
            break;
        default:
            exit("ERRO na linha {$token->line}, coluna {$token->column}: Comando mal formado");
    }
}

function iteration($file, &$token)
{
    switch ($token->code) {
        case TokenCodes::WHILE_RESERVED_WORD:
            $token = scan($file);

            $token->code === TokenCodes::OPEN_PARENTHESIS
                ? $token = scan($file)
                : exit("ERRO na linha {$token->line}, coluna {$token->column}: " .
                    'Iteração mal formada, "(" esperado');

            relationalExpression($file, $token);

            $token->code === TokenCodes::CLOSE_PARENTHESIS
                ? $token = scan($file)
                : exit("ERRO na linha {$token->line}, coluna {$token->column}: " .
                    'Iteração mal formada, ")" esperado');

            command($file, $token);
            break;
        case TokenCodes::DO_RESERVED_WORD:
            $token = scan($file);

            command($file, $token);

            $token->code === TokenCodes::WHILE_RESERVED_WORD
                ? $token = scan($file)
                : exit("ERRO na linha {$token->line}, coluna {$token->column}: " .
                    'Iteração mal formada, "while" esperado');

            $token->code === TokenCodes::OPEN_PARENTHESIS
                ? $token = scan($file)
                : exit("ERRO na linha {$token->line}, coluna {$token->column}: " .
                    'Iteração mal formada, "(" esperado');

            relationalExpression($file, $token);

            $token->code === TokenCodes::CLOSE_PARENTHESIS
                ? $token = scan($file)
                : exit("ERRO na linha {$token->line}, coluna {$token->column}: " .
                    'Iteração mal formada, ")" esperado');

            $token->code === TokenCodes::SEMICOLON
                ? $token = scan($file)
                : exit("ERRO na linha {$token->line}, coluna {$token->column}: " .
                    'Iteração mal formada, ";" esperado');

            break;
        default:
            exit("ERRO na linha {$token->line}, coluna {$token->column}: Iteração mal formada");
    }
}

function assignment($file, &$token)
{
    $token->code === TokenCodes::IDENTIFIER
        ? $token = scan($file)
        : exit("ERRO na linha {$token->line}, coluna {$token->column}: " .
                    'Atribuição mal formada, identificador esperado');

    $token->code === TokenCodes::ASSIGNMENT_OPERATOR
        ? $token = scan($file)
        : exit("ERRO na linha {$token->line}, coluna {$token->column}: " .
                    'Atribuição mal formada, "=" esperado');

    addSubArithmeticExpression($file, $token);

    $token->code === TokenCodes::SEMICOLON
        ? $token = scan($file)
        : exit("ERRO na linha {$token->line}, coluna {$token->column}: " .
                    'Atribuição mal formada, ";" esperado');
}

function relationalExpression($file, &$token)
{
    addSubArithmeticExpression($file, $token);

    if (
        $token->code === TokenCodes::EQUALITY_OPERATOR ||
        $token->code === TokenCodes::INEQUALITY_OPERATOR ||
        $token->code === TokenCodes::LT_RELATIONAL_OPERATOR ||
        $token->code === TokenCodes::GT_RELATIONAL_OPERATOR ||
        $token->code === TokenCodes::LE_RELATIONAL_OPERATOR ||
        $token->code === TokenCodes::GE_RELATIONAL_OPERATOR
    ) {
        $token = scan($file);
    } else {
        exit("ERRO na linha {$token->line}, coluna {$token->column}: Expressão relacional mal formada");
    }

    addSubArithmeticExpression($file, $token);
}

/*
* addSubArithmeticExpression -> addSubArithmeticExpression + multDivArithmeticExpression (E -> E + M)
* addSubArithmeticExpression -> addSubArithmeticExpression - multDivArithmeticExpression (E -> E - M)
* addSubArithmeticExpression -> multDivArithmeticExpression (E -> M)
*
* addSubArithmeticExpression -> multDivArithmeticExpression nonTerminalExpression (E -> M E')
* nonTerminalExpression -> + multDivArithmeticExpression nonTerminalExpression (E' -> + M E')
* nonTerminalExpression -> - multDivArithmeticExpression nonTerminalExpression (E' -> - M E')
* nonTerminalExpression -> ε (E' -> ε)
*/
function addSubArithmeticExpression($file, &$token)
{
    multDivArithmeticExpression($file, $token);
    nonTerminalExpression($file, $token);
}

function nonTerminalExpression($file, &$token)
{
    if (
        $token->code === TokenCodes::ADD_ARITHMETIC_OPERATOR ||
        $token->code === TokenCodes::SUB_ARITHMETIC_OPERATOR
    ) {
        $token = scan($file);
        multDivArithmeticExpression($file, $token);
        nonTerminalExpression($file, $token);
    }
}

function multDivArithmeticExpression($file, &$token)
{
    factor($file, $token);

    while (
        $token->code === TokenCodes::MULT_ARITHMETIC_OPERATOR ||
        $token->code === TokenCodes::DIV_ARITHMETIC_OPERATOR
    ) {
        $token = scan($file);
        factor($file, $token);
    }
}

function factor($file, &$token)
{
    switch ($token->code) {
        case TokenCodes::OPEN_PARENTHESIS:
            $token = scan($file);

            addSubArithmeticExpression($file, $token);

            $token->code === TokenCodes::CLOSE_PARENTHESIS
                ? $token = scan($file)
                : exit("ERRO na linha {$token->line}, coluna {$token->column}: " .
                    'Fator mal formado, ")" esperado');

            break;
        case TokenCodes::IDENTIFIER:
        case TokenCodes::FLOAT_VALUE:
        case TokenCodes::INT_VALUE:
        case TokenCodes::CHAR_VALUE:
            $token = scan($file);
            break;
        default:
            exit("ERRO na linha {$token->line}, coluna {$token->column}: Fator mal formado");
    }
}
