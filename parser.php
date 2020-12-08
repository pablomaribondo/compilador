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
        $exists = searchSymbol($token->lexeme, SymbolTable::$scope, true);

        is_null($exists)
            ? array_push(SymbolTable::$table, (new Symbol($code, $token->lexeme, SymbolTable::$scope)))
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
            $exists = searchSymbol($token->lexeme, SymbolTable::$scope, true);

            is_null($exists)
                ? array_push(SymbolTable::$table, (new Symbol($code, $token->lexeme, SymbolTable::$scope)))
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

    SymbolTable::$scope++;

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

    foreach (array_reverse(SymbolTable::$table) as $symbol) {
        if ($symbol->scope === SymbolTable::$scope) {
            array_pop(SymbolTable::$table);
        }
    }

    SymbolTable::$scope--;
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

            echo 'if '.GCI::getNewTemp().' == 0 goto '.GCI::getNewLabel()."\n";

            command($file, $token);

            if ($token->code === TokenCodes::ELSE_RESERVED_WORD) {
                echo 'goto '.GCI::getNewLabel()."\n";
                echo GCI::getRollbackLabel(2).":\n";

                $token = scan($file);
                command($file, $token);

                echo GCI::getRollbackLabel(1).":\n";
            } else {
                echo GCI::getRollbackLabel(1).":\n";
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
            echo GCI::getNewLabel().":\n";
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

            echo 'if '.GCI::getNewTemp().' == 0 goto '.GCI::getNewLabel()."\n";

            command($file, $token);

            echo 'goto '.GCI::getRollbackLabel(2)."\n";
            echo GCI::getRollbackLabel(1).":\n";

            break;
        case TokenCodes::DO_RESERVED_WORD:
            echo GCI::getNewLabel().":\n";
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

            echo 'if '.GCI::getNewTemp().' != 0 goto '.GCI::getRollbackLabel(1)."\n";

            break;
        default:
            exit("ERRO na linha {$token->line}, coluna {$token->column}: Iteração mal formada");
    }
}

function assignment($file, &$token)
{
    $first = null;
    $identifier = null;

    if ($token->code === TokenCodes::IDENTIFIER) {
        $exists = searchSymbol($token->lexeme, SymbolTable::$scope, false);

        if (is_null($exists)) {
            exit("ERRO na linha {$token->line}, coluna {$token->column}: " .
                'Variáveis devem ser declaradas antes de serem usadas');
        }

        $first = $token->lexeme;
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

    $second = $token->lexeme;
    $expression = addSubArithmeticExpression($file, $token);

    $result = checkCompatibility($identifier, $expression, TokenCodes::ASSIGNMENT_OPERATOR, $token->line, $token->column);
    intToFloatCast($result, $first, $second);

    echo $first.' = '.$second."\n";

    $token->code === TokenCodes::SEMICOLON
        ? $token = scan($file)
        : exit("ERRO na linha {$token->line}, coluna {$token->column}: " .
                    'Atribuição mal formada, ";" esperado');
}

function relationalExpression($file, &$token)
{
    $operation = null;
    $first = $token->lexeme;
    $firstExpression = addSubArithmeticExpression($file, $token);
    $operation = $token->code;

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

    $second = $token->lexeme;
    $secondExpression = addSubArithmeticExpression($file, $token);

    $result = checkCompatibility($firstExpression, $secondExpression, $operation, $token->line, $token->column);

    intToFloatCast($result, $first, $second);

    switch ($operation) {
        case TokenCodes::EQUALITY_OPERATOR:
            echo GCI::getNewTemp().' = '.$first.' == '.$second."\n";
            break;
        case TokenCodes::INEQUALITY_OPERATOR:
            echo GCI::getNewTemp().' = '.$first.' != '.$second."\n";
            break;
        case TokenCodes::LT_RELATIONAL_OPERATOR:
            echo GCI::getNewTemp().' = '.$first.' < '.$second."\n";
            break;
        case TokenCodes::GT_RELATIONAL_OPERATOR:
            echo GCI::getNewTemp().' = '.$first.' > '.$second."\n";
            break;
        case TokenCodes::LE_RELATIONAL_OPERATOR:
            echo GCI::getNewTemp().' = '.$first.' <= '.$second."\n";
            break;
        case TokenCodes::GE_RELATIONAL_OPERATOR:
            echo GCI::getNewTemp().' = '.$first.' >= '.$second."\n";
            break;
    }

    return $result;
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
    $first = $token->lexeme;
    $firstExpression = multDivArithmeticExpression($file, $token);
    return nonTerminalExpression($file, $token, $firstExpression, $first);
}

function nonTerminalExpression($file, &$token, $first=null, $firstLexeme=null)
{
    $result = $first;
    if (is_null($firstLexeme)) {
        $firstLexeme = $token->lexeme;
    }

    if (
        $token->code === TokenCodes::ADD_ARITHMETIC_OPERATOR ||
        $token->code === TokenCodes::SUB_ARITHMETIC_OPERATOR
    ) {
        $operation = $token->code;

        $token = scan($file);

        $firstExpression = null;
        if ($token->code === TokenCodes::IDENTIFIER) {
            $exists = searchSymbol($token->lexeme, SymbolTable::$scope, false);

            if (is_null($exists)) {
                exit("ERRO na linha {$token->line}, coluna {$token->column}: " .
                    'Variáveis devem ser declaradas antes de ser usadas');
            }

            $firstExpression = $exists;
        } elseif ($token->code !== TokenCodes::OPEN_PARENTHESIS) {
            $firstExpression = $token->code;
        }
        $second = $token->lexeme;
        $secondExpression = multDivArithmeticExpression($file, $token);

        $result = checkCompatibility($firstExpression, $secondExpression, $operation, $token->line, $token->column);

        intToFloatCast($result, $firstLexeme, $second);

        nonTerminalExpression($file, $token, $result);
    }

    return $result;
}

function multDivArithmeticExpression($file, &$token)
{
    $first = $token->lexeme;
    $firstExpression = factor($file, $token);

    $operation = $token->code;
    while (
        $token->code === TokenCodes::MULT_ARITHMETIC_OPERATOR ||
        $token->code === TokenCodes::DIV_ARITHMETIC_OPERATOR
    ) {
        $token = scan($file);

        $second = $token->lexeme;
        $secondExpression = factor($file, $token);

        $firstExpression = checkCompatibility($firstExpression, $secondExpression, $operation, $token->line, $token->column);

        intToFloatCast($firstExpression, $first, $second);

        if ($operation === TokenCodes::MULT_ARITHMETIC_OPERATOR) {
            echo GCI::getNewTemp().' = '.$first.' * '.$second."\n";
        } else if ($operation === TokenCodes::DIV_ARITHMETIC_OPERATOR) {
            echo GCI::getNewTemp().' = '.$first.' / '.$second."\n";
        }
    }
    return $firstExpression;
}

function factor($file, &$token)
{
    switch ($token->code) {
        case TokenCodes::OPEN_PARENTHESIS:
            $token = scan($file);

            $expression = addSubArithmeticExpression($file, $token);

            $token->code === TokenCodes::CLOSE_PARENTHESIS
                ? $token = scan($file)
                : exit("ERRO na linha {$token->line}, coluna {$token->column}: " .
                    'Fator mal formado, ")" esperado');

            return $expression;
        case TokenCodes::IDENTIFIER:
            $exists = searchSymbol($token->lexeme, SymbolTable::$scope, false);

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
    if ((is_null($firstValue) || is_null($secondValue)) && $operation !== TokenCodes::ASSIGNMENT_OPERATOR) {
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

    if ($firstValue === TokenCodes::INT_VALUE && $secondValue === TokenCodes::FLOAT_VALUE) {
        return 'convertFirst';
    }

    if ($secondValue === TokenCodes::INT_VALUE && $firstValue === TokenCodes::FLOAT_VALUE) {
        return 'convertSecond';
    }

    if ($firstValue === TokenCodes::FLOAT_VALUE && $secondValue === TokenCodes::FLOAT_VALUE) {
        return TokenCodes::FLOAT_VALUE;
    }

    if ($operation === TokenCodes::ASSIGNMENT_OPERATOR) {
        return 'convertSecond';
    }


    return TokenCodes::FLOAT_VALUE;
}

function searchSymbol($lexeme, $scope, $new)
{
    $code = null;

    foreach (SymbolTable::$table as $symbol) {
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

function intToFloatCast($result, &$first, &$second)
{
    if ($result === 'convertFirst') {
        echo GCI::getNewTemp().' = int_to_float '.$first."\n";
        $first = GCI::getRollbackTemp(1);
        return true;
    } elseif ($result === 'convertSecond') {
        echo GCI::getNewTemp().' = int_to_float '.$second."\n";
        $second = GCI::getRollbackTemp(1);
        return true;
    }

    return false;
}
