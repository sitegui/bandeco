<?php
error_reporting(E_ALL);

// Inclui alguns arquivos
require_once 'query.2.1.php';
require_once 'mysql.inc.php';

// Inclui as classes
require_once 'Data.class.php';
require_once 'Familia.class.php';
require_once 'Prato.class.php';
require_once 'Rank.class.php';
require_once 'Refeicao.class.php';
require_once 'Aviso.class.php';
require_once 'Ouvinte.class.php';

// Permite ser acessado por qualquer domínio
header('Access-Control-Allow-Origin: *');

$op = @$_GET['op'];

// TODO: salvar o ra em $_ra

if ($op == '')
	readfile('ajuda.html');
else if ($op == 'busca') {
	
} else if ($op == 'cardapio') {
} else if ($op == 'avisos') {
} else if ($op == 'infoFamilia') {
} else if ($op == 'infoPrato') {
} else if ($op == 'infoRefeicao') {
} else if ($op == 'ranking') {
} else if ($op == 'semana') {
} else if ($op == 'votar') {
} else if ($op == 'temOuvinte') {
} else if ($op == 'pedirChave') {
} else if ($op == 'getOuvinte') {
} else if ($op == 'removerOuvinte') {
} else if ($op == 'setOuvinte') {
} else
	falharHttp();

// Retorna um código de erro HTTP devido à má formatação da requisição
function falharHttp() {
	header('HTTP/1.1 400 Bad Request');
	exit;
}
