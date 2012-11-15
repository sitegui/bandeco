<?php
// Esse script roda a cada duas horas para sincronizar o banco de dados com os dados da página da prefeitura

require_once 'extrair.inc.php';
require_once 'mysql.inc.php';
require_once 'query.2.1.php';
require_once 'Data.class.php';

set_time_limit(90);

$cacheProximas = getCacheProximas();

// Atualiza os pratos
$pag = 1;
while ($dados = extrair($pag++)) {
	// Pega o id do prato
	try {
		$idPrato = Query::query(true, 0, 'SELECT id FROM pratos WHERE nome=? LIMIT 1', $dados['prato']);
	} catch (Exception $e) {
		// Novo prato
		new Query('INSERTO INTO pratos (nome) VALUES (?)', $dados['prato']);
		$idPrato = Query::$conexao->insert_id;
	}
	
	// Salva a refeicao
	try {
		// Tenta atualizar a refeição existente
		$idRefeicao = Query::query(true, 0, 'SELECT id FROM refeicoes WHERE data=? LIMIT 1', (string)$dados['data']);
		$dadosUpdate = $dados;
		unset($dadosUpdate['data']);
		new Query('UPDATE refeicoes SET ? WHERE id=? LIMIT 1', $dadosUpdate, $idRefeicao);
	} catch (Exception $e) {
		// Insere a nova refeição
		$dadosInsert = $dados;
		$dadosInsert['data'] = (string)$dadosInsert['data'];
		new Query('INSERT INTO refeicoes SET ?', $dadosInsert);
	}
	
	if (queryReturns("SELECT id FROM refeicoes WHERE data='$dados[data]' LIMIT 1"))
		query("UPDATE refeicoes SET prato=$id, guarnicao='?', pts='?', salada='?', sobremesa='?', suco='?' WHERE data='$dados[data]' LIMIT 1",
		$dados['guarnicao'], $dados['pts'], $dados['salada'], $dados['sobremesa'], $dados['suco']);
	else
		query("INSERT INTO refeicoes VALUES (NULL, '?', ?, '?', '?', '?', '?', '?', NULL, 0)",
			$dados['data'], $id, $dados['guarnicao'], $dados['pts'], $dados['salada'], $dados['sobremesa'], $dados['suco']);
}

// Define o campo 'proxima' da array
// Recebe o cache de próximas (gerado por getCacheProximas())
// Recebe o id da refeição a ser atualizada e sua array de dados
function setProxima(&$cache, $id, &$dados) {
	$inicio = $dados['data']->getInicio();
	if ($inicio > $cache['max']) {
		// É a última refeição
		$dados['proxima'] = NULL;
		// TODO: linkar da antiga NULL para essa
	} else if (isset($cache[$dados['id']])) {
		// Já tinha refeição nessa posição
		
	} else {
	}
}

// Retorna um cache da relação de 'próxima' entre as últimas refeições
function getCacheProximas() {
	$cache = Query::query(false, NULL, 'SELECT id, data, proxima FROM refeicoes ORDER BY data DESC LIMIT 10');
	$retorno = array();
	$max = 0;
	foreach ($cache as $cada) {
		$data = new Data($cada['data']);
		$max = max($max, $data->getInicio());
		if ($cada['proxima'])
			$retorno[$cada['proxima']] = $cada['id'];
	}
	$retorno['max'] = $max;
	return $retorno;
}
