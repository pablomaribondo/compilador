<?php

namespace compiler;

function parse($file)
{
    static $scope;
    static $symbolTable;
    static $token;

    $scope = 0;
    $symbolTable = array();
    $token = scan($file);

    program($file, $token, $scope, $symbolTable);
}

function declaration($file, &$token, $scope, &$symbolTable)
{
    $code = null;

    if (
        $token->code === TokenCodes::INT_RESERVED_WORD ||
        $token->code === TokenCodes::FLOAT_RESERVED_WORD ||
        $token->code === TokenCodes::CHAR_RESERVED_WORD
    ) {
        $code = $token->code - 9;
        $token = scan($file);
    } else {
        exit("ERRO na linha {$token->line}, coluna {$token->column}: " .
            'Declaração mal formada, tipo esperado');
    }

    if ($token->code === TokenCodes::IDENTIFIER) {
        $exists = searchSymbol($symbolTable, $token->lexeme, $scope, true);

        is_null($exists)
            ? array_push($symbolTable, (new Symbol($code, $token->lexeme, $scope)))
            : exit("ERRO na linha {$token->line}, coluna {$token->column}: " .
                'Não podem haver variáveis com o mesmo nome no mesmo escopo');

        $token = scan($file);
    } else {
        exit("ERRO na linha {$token->line}, coluna {$token->column}: " .
            'Declaração mal formada, identificador esperado');
    }

    while ($token->code === TokenCodes::COMMA) {
        $token = scan($file);

        if ($token->code === TokenCodes::IDENTIFIER) {
            $exists = searchSymbol($symbolTable, $token->lexeme, $scope, true);

            is_null($exists)
                ? array_push($symbolTable, (new Symbol($code, $token->lexeme, $scope)))
                : exit("ERRO na linha {$token->line}, coluna {$token->column}: " .
                    'Não podem haver variáveis com o mesmo nome no mesmo escopo');

            $token = scan($file);
        } else {
            exit("ERRO na linha {$token->line}, coluna {$token->column}: " .
                'Declaração mal formada, identificador esperado');
        }
    }

    $token->code === TokenCodes::SEMICOLON
        ? $token = scan($file)
        : exit("ERRO na linha {$token->line}, coluna {$token->column}: " .
            'Declaração mal formada, ";" esperado');
}

function program($file, &$token, &$scope, &$symbolTable)
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

    codeBlock($file, $token, $scope, $symbolTable);

    $token->code === TokenCodes::EOF
        ? $token = scan($file)
        : exit("ERRO na linha {$token->line}, coluna {$token->column}: " .
            'Programa mal formado, fim de arquivo esperado');
}

function codeBlock($file, &$token, &$scope, &$symbolTable)
{
    $token->code === TokenCodes::OPEN_CURLY_BRACKET
        ? $token = scan($file)
        : exit("ERRO na linha {$token->line}, coluna {$token->column}: " .
            'Bloco mal formado, "{" esperado');

    $scope++;

    while (
        $token->code === TokenCodes::INT_RESERVED_WORD ||
        $token->code === TokenCodes::FLOAT_RESERVED_WORD ||
        $token->code === TokenCodes::CHAR_RESERVED_WORD
    ) {
        declaration($file, $token, $scope, $symbolTable);
    }

    while (
        $token->code === TokenCodes::IDENTIFIER ||
        $token->code === TokenCodes::OPEN_CURLY_BRACKET ||
        $token->code === TokenCodes::WHILE_RESERVED_WORD ||
        $token->code === TokenCodes::DO_RESERVED_WORD ||
        $token->code === TokenCodes::IF_RESERVED_WORD
    ) {
        command($file, $token, $scope, $symbolTable);
    }

    $token->code === TokenCodes::CLOSE_CURLY_BRACKET
        ? $token = scan($file)
        : exit("ERRO na linha {$token->line}, coluna {$token->column}: " .
            'Bloco mal formado, "}" esperado');

    foreach (array_reverse($symbolTable) as $symbol) {
        if ($symbol->scope === $scope) {
            array_pop($symbolTable);
        }
    }

    $scope--;
}

function command($file, &$token, $scope, $symbolTable)
{
    switch ($token->code) {
        case TokenCodes::IDENTIFIER:
        case TokenCodes::OPEN_CURLY_BRACKET:
            basicCommand($file, $token, $scope, $symbolTable);
            break;
        case TokenCodes::WHILE_RESERVED_WORD:
        case TokenCodes::DO_RESERVED_WORD:
            iteration($file, $token, $scope, $symbolTable);
            break;
        case TokenCodes::IF_RESERVED_WORD:
            $token = scan($file);

            $token->code === TokenCodes::OPEN_PARENTHESIS
                ? $token = scan($file)
                : exit("ERRO na linha {$token->line}, coluna {$token->column}: " .
                    'Comando mal formado, "(" esperado');

            relationalExpression($file, $token, $scope, $symbolTable);

            $token->code === TokenCodes::CLOSE_PARENTHESIS
                ? $token = scan($file)
                : exit("ERRO na linha {$token->line}, coluna {$token->column}: " .
                    'Comando mal formado, ")" esperado');

            command($file, $token, $scope, $symbolTable);

            if ($token->code === TokenCodes::ELSE_RESERVED_WORD) {
                $token = scan($file);
                command($file, $token, $scope, $symbolTable);
            }

            break;
        default:
            exit("ERRO na linha {$token->line}, coluna {$token->column}: Comando mal formado");
    }
}

function basicCommand($file, &$token, $scope, $symbolTable)
{
    switch ($token->code) {
        case TokenCodes::IDENTIFIER:
            assignment($file, $token, $scope, $symbolTable);
            break;
        case TokenCodes::OPEN_CURLY_BRACKET:
            codeBlock($file, $token, $scope, $symbolTable);
            break;
        default:
            exit("ERRO na linha {$token->line}, coluna {$token->column}: Comando mal formado");
    }
}

function iteration($file, &$token, $scope, $symbolTable)
{
    switch ($token->code) {
        case TokenCodes::WHILE_RESERVED_WORD:
            $token = scan($file);

            $token->code === TokenCodes::OPEN_PARENTHESIS
                ? $token = scan($file)
                : exit("ERRO na linha {$token->line}, coluna {$token->column}: " .
                    'Iteração mal formada, "(" esperado');

            relationalExpression($file, $token, $scope, $symbolTable);

            $token->code === TokenCodes::CLOSE_PARENTHESIS
                ? $token = scan($file)
                : exit("ERRO na linha {$token->line}, coluna {$token->column}: " .
                    'Iteração mal formada, ")" esperado');

            command($file, $token, $scope, $symbolTable);
            break;
        case TokenCodes::DO_RESERVED_WORD:
            $token = scan($file);

            command($file, $token, $scope, $symbolTable);

            $token->code === TokenCodes::WHILE_RESERVED_WORD
                ? $token = scan($file)
                : exit("ERRO na linha {$token->line}, coluna {$token->column}: " .
                    'Iteração mal formada, "while" esperado');

            $token->code === TokenCodes::OPEN_PARENTHESIS
                ? $token = scan($file)
                : exit("ERRO na linha {$token->line}, coluna {$token->column}: " .
                    'Iteração mal formada, "(" esperado');

            relationalExpression($file, $token, $scope, $symbolTable);

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

function assignment($file, &$token, $scope, &$symbolTable)
{
    $identifier = null;

    if ($token->code === TokenCodes::IDENTIFIER) {
        $exists = searchSymbol($symbolTable, $token->lexeme, $scope, false);

        if (is_null($exists)) {
            exit("ERRO na linha {$token->line}, coluna {$token->column}: " .
                'Variáveis devem ser declaradas antes de serem usadas');
        }

        $identifier = $exists;
        $token = scan($file);
    } else {
        exit("ERRO na linha {$token->line}, coluna {$token->column}: " .
            'Atribuição mal formada, identificador esperado');
    }

    $token->code === TokenCodes::ASSIGNMENT_OPERATOR
        ? $token = scan($file)
        : exit("ERRO na linha {$token->line}, coluna {$token->column}: " .
                    'Atribuição mal formada, "=" esperado');

    $expression = addSubArithmeticExpression($file, $token, $scope, $symbolTable);

    checkCompatibility($identifier, $expression, TokenCodes::ASSIGNMENT_OPERATOR, $token->line, $token->column);

    $token->code === TokenCodes::SEMICOLON
        ? $token = scan($file)
        : exit("ERRO na linha {$token->line}, coluna {$token->column}: " .
                    'Atribuição mal formada, ";" esperado');
}

function relationalExpression($file, &$token, $scope, $symbolTable)
{
    $operation = null;

    $firstExpression = addSubArithmeticExpression($file, $token, $scope, $symbolTable);

    if (
        $token->code === TokenCodes::EQUALITY_OPERATOR ||
        $token->code === TokenCodes::INEQUALITY_OPERATOR ||
        $token->code === TokenCodes::LT_RELATIONAL_OPERATOR ||
        $token->code === TokenCodes::GT_RELATIONAL_OPERATOR ||
        $token->code === TokenCodes::LE_RELATIONAL_OPERATOR ||
        $token->code === TokenCodes::GE_RELATIONAL_OPERATOR
    ) {
        $operation = $token->code;

        $token = scan($file);
    } else {
        exit("ERRO na linha {$token->line}, coluna {$token->column}: Expressão relacional mal formada");
    }

    $secondExpression = addSubArithmeticExpression($file, $token, $scope, $symbolTable);

    return checkCompatibility($firstExpression, $secondExpression, $operation, $token->line, $token->column);
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
function addSubArithmeticExpression($file, &$token, $scope, $symbolTable)
{
    $firstExpression = multDivArithmeticExpression($file, $token, $scope, $symbolTable);
    return nonTerminalExpression($file, $token, $scope, $symbolTable, $firstExpression);
}

function nonTerminalExpression($file, &$token, $scope, $symbolTable, $first=null)
{
    $result = $first;

    if (
        $token->code === TokenCodes::ADD_ARITHMETIC_OPERATOR ||
        $token->code === TokenCodes::SUB_ARITHMETIC_OPERATOR
    ) {
        $operation = $token->code;

        $token = scan($file);

        $firstExpression = null;
        if ($token->code === TokenCodes::IDENTIFIER) {
            $exists = searchSymbol($symbolTable, $token->lexeme, $scope, false);

            if (is_null($exists)) {
                exit("ERRO na linha {$token->line}, coluna {$token->column}: " .
                    'Variáveis devem ser declaradas antes de ser usadas');
            }

            $firstExpression = $exists;
        } elseif ($token->code !== TokenCodes::OPEN_PARENTHESIS) {
            $firstExpression = $token->code;
        }


        $secondExpression = multDivArithmeticExpression($file, $token, $scope, $symbolTable);

        $result = checkCompatibility($firstExpression, $secondExpression, $operation, $token->line, $token->column);
        nonTerminalExpression($file, $token, $scope, $symbolTable);
    }

    return $result;
}

function multDivArithmeticExpression($file, &$token, $scope, $symbolTable)
{
    $firstExpression = factor($file, $token, $scope, $symbolTable);

    while (
        $token->code === TokenCodes::MULT_ARITHMETIC_OPERATOR ||
        $token->code === TokenCodes::DIV_ARITHMETIC_OPERATOR
    ) {
        $operation = $token->code;
        $token = scan($file);

        $secondExpression = factor($file, $token, $scope, $symbolTable);

        $firstExpression = checkCompatibility($firstExpression, $secondExpression, $operation, $token->line, $token->column);
    }
    return $firstExpression;
}

function factor($file, &$token, $scope, $symbolTable)
{
    switch ($token->code) {
        case TokenCodes::OPEN_PARENTHESIS:
            $token = scan($file);

            $expression = addSubArithmeticExpression($file, $token, $scope, $symbolTable);

            $token->code === TokenCodes::CLOSE_PARENTHESIS
                ? $token = scan($file)
                : exit("ERRO na linha {$token->line}, coluna {$token->column}: " .
                    'Fator mal formado, ")" esperado');

            return $expression;
        case TokenCodes::IDENTIFIER:
            $exists = searchSymbol($symbolTable, $token->lexeme, $scope, false);

            if (is_null($exists)) {
                exit("ERRO na linha {$token->line}, coluna {$token->column}: " .
                    'Variáveis devem ser declaradas antes de ser usadas');
            }

            $token = scan($file);
            break;
        case TokenCodes::FLOAT_VALUE:
        case TokenCodes::INT_VALUE:
        case TokenCodes::CHAR_VALUE:
            $code = $token->code;
            $token = scan($file);
            return $code;
        default:
            exit("ERRO na linha {$token->line}, coluna {$token->column}: Fator mal formado");
    }
}

function checkCompatibility($firstValue, $secondValue, $operation, $line, $column)
{
    if (is_null($firstValue) || is_null($secondValue)) {
        return $firstValue ?? $secondValue;
    }

    if ($firstValue === TokenCodes::CHAR_VALUE || $secondValue === TokenCodes::CHAR_VALUE) {
        if ($firstValue === TokenCodes::CHAR_VALUE && $secondValue === TokenCodes::CHAR_VALUE) {
            return TokenCodes::CHAR_VALUE;
        }

        exit("ERRO na linha {$line}, coluna {$column}: " .
            'Erro na compatibilidade de tipos, valores do tipo "char" são apenas compatíveis entre si');
    }

    if ($firstValue === TokenCodes::INT_VALUE && $secondValue === TokenCodes::INT_VALUE) {
        if ($operation === TokenCodes::DIV_ARITHMETIC_OPERATOR) {
            return TokenCodes::FLOAT_VALUE;
        }

        return TokenCodes::INT_VALUE;
    }

    if ($firstValue === TokenCodes::INT_VALUE && $operation === TokenCodes::ASSIGNMENT_OPERATOR) {
        exit("ERRO na linha {$line}, coluna {$column}: " .
            'Erro na compatibilidade de tipos, valores do tipo "float" não podem ser atribuídos À um valor do tipo "int"');
    }

    return TokenCodes::FLOAT_VALUE;
}

function searchSymbol($symbolTable, $lexeme, $scope, $new)
{
    $code = null;

    foreach ($symbolTable as $symbol) {
        if ($new && $scope !== $symbol->scope) {
            break;
        }

        if (
            $symbol->lexeme === $lexeme
        ) {
            $code = $symbol->code;
            break;
        }
    }

    return $code;
}
