<?php

namespace compiler;

function scan($file)
{
    static $char   = ' ';
    static $line   = 1;
    static $column = 0;

    while (true) {
        while (ctype_space($char)) {
            if ($char === PHP_EOL) {
                $line++;
                $column = 0;
            }
           
            $char = fgetc($file);
        }

        if (ctype_alpha($char) || $char === '_') {
            $buffer = $char;
            $char = fgetc($file);
            $column++;

            while (ctype_alpha($char) || $char === '_' || ctype_digit($char)) {
                $buffer .= $char;
                $char = fgetc($file);
                $column++;
            }

            switch ($buffer) {
                case 'main':
                    return new Token(TokenCodes::MAIN_RESERVED_WORD);
                case 'if':
                    return new Token(TokenCodes::IF_RESERVED_WORD);
                case 'else':
                    return new Token(TokenCodes::ELSE_RESERVED_WORD);
                case 'while':
                    return new Token(TokenCodes::WHILE_RESERVED_WORD);
                case 'do':
                    return new Token(TokenCodes::DO_RESERVED_WORD);
                case 'for':
                    return new Token(TokenCodes::FOR_RESERVED_WORD);
                case 'int':
                    return new Token(TokenCodes::INT_RESERVED_WORD);
                case 'float':
                    return new Token(TokenCodes::FLOAT_RESERVED_WORD);
                case 'char':
                    return new Token(TokenCodes::CHAR_RESERVED_WORD);
                default:
                    return new Token(TokenCodes::IDENTIFIER, $buffer);
            }
        }

        if (ctype_digit($char)) {
            $buffer = $char;
            $char = fgetc($file);
            $column++;
            
            while (ctype_digit($char)) {
                $buffer .= $char;
                $char = fgetc($file);
                $column++;
            }

            if ($char === '.') {
                return validateFloat($file, $char, $line, $column, $buffer);
            } else {
                return new Token(TokenCodes::INT_VALUE, $buffer);
            }
        }

        switch ($char) {
            case '.':
                return validateFloat($file, $char, $line, $column);
            case '<':
                $char = fgetc($file);
                $column++;

                if ($char === '=') {
                    $char = fgetc($file);
                    $column++;
        
                    return new Token(TokenCodes::LE_RELATIONAL_OPERATOR);
                } else {
                    return new Token(TokenCodes::LT_RELATIONAL_OPERATOR);
                }
            case '>':
                $char = fgetc($file);
                $column++;

                if ($char === '=') {
                    $char = fgetc($file);
                    $column++;
        
                    return new Token(TokenCodes::GE_RELATIONAL_OPERATOR);
                } else {
                    return new Token(TokenCodes::GT_RELATIONAL_OPERATOR);
                }
            case '=':
                $char = fgetc($file);
                $column++;

                if ($char === '=') {
                    $char = fgetc($file);
                    $column++;
        
                    return new Token(TokenCodes::EQUALITY_OPERATOR);
                } else {
                    return new Token(TokenCodes::ASSIGNMENT_OPERATOR);
                }
            case '!':
                $char = fgetc($file);
                $column++;

                if ($char === '=') {
                    $char = fgetc($file);
                    $column++;
        
                    return new Token(TokenCodes::INEQUALITY_OPERATOR);
                } else {
                    exit("ERRO na linha {$line}, coluna {$column}: Exclamação não seguida de um igual");
                }
            case '+':
                $char = fgetc($file);
                $column++;

                return new Token(TokenCodes::ADD_ARITHMETIC_OPERATOR);
            case '-':
                $char = fgetc($file);
                $column++;

                return new Token(TokenCodes::SUB_ARITHMETIC_OPERATOR);
            case '*':
                $char = fgetc($file);
                $column++;

                return new Token(TokenCodes::MULT_ARITHMETIC_OPERATOR);
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
                    $column++;

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
        
                        !feof($file) ?: exit("ERRO na linha {$line}, coluna {$column}:
                        Fim de arquivo dentro de comentário multilinha");
                        
                        $char = fgetc($file);
                        $column++;
                        
                        if ($char === '/') {
                            $char = fgetc($file);
                            $column++;
                            continue 3;
                        }
                    }
                } else {
                    return new Token(TokenCodes::DIV_ARITHMETIC_OPERATOR);
                }
                // no break
            case '(':
                $char = fgetc($file);
                $column++;
  
                return new Token(TokenCodes::OPEN_PARENTHESIS);
            case ')':
                $char = fgetc($file);
                $column++;
  
                return new Token(TokenCodes::CLOSE_PARENTHESIS);
            case '{':
                $char = fgetc($file);
                $column++;
  
                return new Token(TokenCodes::OPEN_CURLY_BRACKET);
            case '}':
                $char = fgetc($file);
                $column++;
  
                return new Token(TokenCodes::CLOSE_CURLY_BRACKET);
            case ',':
                $char = fgetc($file);
                $column++;
  
                return new Token(TokenCodes::COMMA);
            case ';':
                $char = fgetc($file);
                $column++;
  
                return new Token(TokenCodes::SEMICOLON);
            case "'":
                $buffer = $char;
                $char = fgetc($file);
                $column++;

                if (ctype_alnum($char)) {
                    $buffer .= $char;
                    $char = fgetc($file);
                    $column++;
                    
                    if ($char === "'") {
                        $buffer .= $char;
                        $char = fgetc($file);
                        $column++;

                        return new Token(TokenCodes::CHAR_VALUE, $buffer);
                    } else {
                        exit("ERRO na linha {$line}, coluna {$column}: CHAR mal formado");
                    }
                } else {
                    exit("ERRO na linha {$line}, coluna {$column}: CHAR mal formado");
                }
                // no break
            default:
                if (feof($file)) {
                    return new Token(TokenCodes::EOF);
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
    $column++;

    if (ctype_digit($char)) {
        while (ctype_digit($char)) {
            $buffer .= $char;
            $char = fgetc($file);
            $column++;
        }

        return new Token(TokenCodes::FLOAT_VALUE, $intBuffer.$buffer);
    } else {
        exit("ERRO na linha {$line}, coluna {$column}: FLOAT mal formatado");
    }
}
