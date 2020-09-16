<?php

namespace compiler;

require_once('Token.php');
require_once('TokenCodes.php');
require_once('scanner.php');


$argc !== 1 ?: exit('ERRO: Parâmetro com o nome do arquivo não encontrado!');

$file = fopen($argv[1], 'r') or die('ERRO: Arquivo não encontrado!');

$token = scan($file);
while ($token->code !== TokenCodes::EOF) {
    $token = scan($file);
}

fclose($file);
