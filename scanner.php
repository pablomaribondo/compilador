<?php

namespace compiler;

function scan($file)
{
    static $char   = ' ';
    static $line   = 1;
    static $column = -1;

    while (true) {
        while (ctype_space($char)) {
            if ($char === PHP_EOL) {
                $line++;
                $column = 0;
            } else {
                $column++;
            }

            $char = fgetc($file);
        }

        if (ctype_alpha($char) || $char === '_') {
            $buffer = $char;
            $char = fgetc($file);
            $column++;
            $beginningColumn = $column;


            while (ctype_alpha($char) || $char === '_' || ctype_digit($char)) {
                $buffer .= $char;
                $char = fgetc($file);
                $column++;
            }

            switch ($buffer) {
                case 'main':
                    return new Token(TokenCodes::MAIN_RESERVED_WORD, null, $line, $beginningColumn);
                case 'if':
                    return new Token(TokenCodes::IF_RESERVED_WORD, null, $line, $beginningColumn);
                case 'else':
                    return new Token(TokenCodes::ELSE_RESERVED_WORD, null, $line, $beginningColumn);
                case 'while':
                    return new Token(TokenCodes::WHILE_RESERVED_WORD, null, $line, $beginningColumn);
                case 'do':
                    return new Token(TokenCodes::DO_RESERVED_WORD, null, $line, $beginningColumn);
                case 'for':
                    return new Token(TokenCodes::FOR_RESERVED_WORD, null, $line, $beginningColumn);
                case 'int':
                    return new Token(TokenCodes::INT_RESERVED_WORD, null, $line, $beginningColumn);
                case 'float':
                    return new Token(TokenCodes::FLOAT_RESERVED_WORD, null, $line, $beginningColumn);
                case 'char':
                    return new Token(TokenCodes::CHAR_RESERVED_WORD, null, $line, $beginningColumn);
                default:
                    return new Token(TokenCodes::IDENTIFIER, $buffer, $line, $beginningColumn);
            }
        }

        if (ctype_digit($char)) {
            $buffer = $char;
            $char = fgetc($file);

            while (ctype_digit($char)) {
                $buffer .= $char;
                $char = fgetc($file);
            }


            if ($char === '.') {
                return validateFloat($file, $char, $line, $column, $buffer);
            } else {
                $beginningColumn = $column + 1;
                $column += strlen($buffer);

                return new Token(TokenCodes::INT_VALUE, $buffer, $line, $beginningColumn);
            }
        }

        switch ($char) {
            case '.':
                return validateFloat($file, $char, $line, $column);
            case '<':
                $char = fgetc($file);
                $column++;
                $beginningColumn = $column;

                if ($char === '=') {
                    $char = fgetc($file);
                    $column++;

                    return new Token(TokenCodes::LE_RELATIONAL_OPERATOR, null, $line, $beginningColumn);
                } else {
                    return new Token(TokenCodes::LT_RELATIONAL_OPERATOR, null, $line, $beginningColumn);
                }
                // no break
            case '>':
                $char = fgetc($file);
                $column++;
                $beginningColumn = $column;

                if ($char === '=') {
                    $char = fgetc($file);
                    $column++;

                    return new Token(TokenCodes::GE_RELATIONAL_OPERATOR, null, $line, $beginningColumn);
                } else {
                    return new Token(TokenCodes::GT_RELATIONAL_OPERATOR, null, $line, $beginningColumn);
                }
                // no break
            case '=':
                $char = fgetc($file);
                $column++;
                $beginningColumn = $column;

                if ($char === '=') {
                    $char = fgetc($file);
                    $column++;

                    return new Token(TokenCodes::EQUALITY_OPERATOR, null, $line, $beginningColumn);
                } else {
                    return new Token(TokenCodes::ASSIGNMENT_OPERATOR, null, $line, $beginningColumn);
                }
                // no break
            case '!':
                $char = fgetc($file);
                $column++;
                $beginningColumn = $column;

                if ($char === '=') {
                    $char = fgetc($file);
                    $column++;

                    return new Token(TokenCodes::INEQUALITY_OPERATOR, null, $line, $beginningColumn);
                } else {
                    exit("ERRO na linha {$line}, coluna {$beginningColumn}: Exclamação não seguida de um igual");
                }
                // no break
            case '+':
                $char = fgetc($file);
                $column++;

                return new Token(TokenCodes::ADD_ARITHMETIC_OPERATOR, null, $line, $column);
            case '-':
                $char = fgetc($file);
                $column++;

                return new Token(TokenCodes::SUB_ARITHMETIC_OPERATOR, null, $line, $column);
            case '*':
                $char = fgetc($file);
                $column++;

                return new Token(TokenCodes::MULT_ARITHMETIC_OPERATOR, null, $line, $column);
            case '/':
                $char = fgetc($file);
                $column++;

                if ($char === '/') {
                    while ($char !== PHP_EOL && !feof($file)) {
                        $char = fgetc($file);
                        $column++;
                    }

                    if ($char === PHP_EOL) {
                        $line++;
                        $column = 0;
                    }

                    $char = fgetc($file);

                    continue 2;
                } elseif ($char === '*') {
                    while (true) {
                        $char = fgetc($file);
                        $column++;

                        if ($char === PHP_EOL) {
                            $line++;
                            $column = 0;
                        }

                        while ($char !== '*' && !feof($file)) {
                            $char = fgetc($file);
                            $column++;

                            if ($char === PHP_EOL) {
                                $line++;
                                $column = 0;
                            }
                        }

                        while ($char === '*') {
                            $char = fgetc($file);
                            $column++;
                        }


                        if ($char === '/') {
                            $char = fgetc($file);
                            $column++;
                            continue 3;
                        }

                        !feof($file) ?: exit("ERRO na linha {$line}, coluna {$column}: Fim de arquivo dentro de comentário multilinha");
                    }
                } else {
                    return new Token(TokenCodes::DIV_ARITHMETIC_OPERATOR, null, $line, $column);
                }
                // no break
            case '(':
                $char = fgetc($file);
                $column++;

                return new Token(TokenCodes::OPEN_PARENTHESIS, null, $line, $column);
            case ')':
                $char = fgetc($file);
                $column++;

                return new Token(TokenCodes::CLOSE_PARENTHESIS, null, $line, $column);
            case '{':
                $char = fgetc($file);
                $column++;

                return new Token(TokenCodes::OPEN_CURLY_BRACKET, null, $line, $column);
            case '}':
                $char = fgetc($file);
                $column++;

                return new Token(TokenCodes::CLOSE_CURLY_BRACKET, null, $line, $column);
            case ',':
                $char = fgetc($file);
                $column++;

                return new Token(TokenCodes::COMMA, null, $line, $column);
            case ';':
                $char = fgetc($file);
                $column++;

                return new Token(TokenCodes::SEMICOLON, null, $line, $column);
            case "'":
                $buffer = $char;
                $char = fgetc($file);
                $column++;
                $beginningColumn = $column;

                if (ctype_alnum($char)) {
                    $buffer .= $char;
                    $char = fgetc($file);
                    $column++;

                    if ($char === "'") {
                        $buffer .= $char;
                        $char = fgetc($file);
                        $column++;

                        return new Token(TokenCodes::CHAR_VALUE, $buffer, $line, $beginningColumn);
                    } else {
                        exit("ERRO na linha {$line}, coluna {$beginningColumn}: CHAR mal formado");
                    }
                } else {
                    exit("ERRO na linha {$line}, coluna {$beginningColumn}: CHAR mal formado");
                }
                // no break
            default:
                if (feof($file)) {
                    return new Token(TokenCodes::EOF, null, $line, $column);
                } else {
                    exit("ERRO na linha {$line}, coluna {$column}: Caracter inválido");
                }
        }
    }
}

function validateFloat($file, &$char, &$line, &$column, $intBuffer = '')
{
    $buffer = $char;
    $char = fgetc($file);

    $beginningColumn = $column + 1;

    if (ctype_digit($char)) {
        while (ctype_digit($char)) {
            $buffer .= $char;
            $char = fgetc($file);
        }

        $column += strlen($intBuffer) + strlen($buffer);

        return new Token(TokenCodes::FLOAT_VALUE, $intBuffer.$buffer, $line, $beginningColumn);
    } else {
        exit("ERRO na linha {$line}, coluna {$beginningColumn}: FLOAT mal formatado");
    }
}
