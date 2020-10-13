<?php

namespace compiler;

require_once('parser.php');
require_once('scanner.php');
require_once('Token.php');
require_once('TokenCodes.php');

$argc !== 1 ?: exit('ERRO: Parâmetro com o nome do arquivo não encontrado!');

$file = fopen($argv[1], 'r') or die('ERRO: Arquivo não encontrado!');

parse($file);

fclose($file);
