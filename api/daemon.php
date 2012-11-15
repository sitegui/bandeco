<?php
// Esse script roda a cada duas horas para sincronizar o banco de dados com os dados da página da prefeitura

require_once 'extrair.inc.php';
require_once 'mysql.inc.php';
require_once 'query.2.1.php';
require_once 'Data.class.php';

set_time_limit(90);

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
		
		// Atualiza os links de próxima
		$idRefeicao = Query::$conexao->insert_id;
		$proxima = Query::getValor('SELECT id FROM refeicoes WHERE data>? ORDER BY data LIMIT 1', $dadosInsert['data']);
		if ($proxima) {
			new Query('UPDATE refeicoes SET proxima=? WHERE proxima=? LIMIT 1', $idRefeicao, $proxima);
			new Query('UPDATE refeicoes SET proxima=? WHERE id=? LIMIT 1', $proxima, $idRefeicao);
		} else
			new Query('UPDATE refeicoes SET proxima=? WHERE proxima IS NULL LIMIT 1', $idRefeicao);
	}
}
