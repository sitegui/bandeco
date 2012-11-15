<?php
// Esse script roda a cada duas horas para sincronizar o banco de dados com os dados da página da prefeitura

require_once 'extrair.inc.php';
require_once 'mysql.inc.php';
require_once '../query/1.1.php';
require_once 'Data.class.php';

set_time_limit(90);

// Atualiza os pratos
$pag = 1;
while ($dados = extrair($pag++)) {
	// Pega o id do prato
	try {
		$id = queryResult(query("SELECT id FROM pratos WHERE nome='?' LIMIT 1", $dados['prato']), true, 0);
	} catch (Exception $e) {
		mail('sitegui@sitegui.com.br', 'Novo prato', "Novo prato do bandeco: $dados[prato]", "From:sitegui@sitegui.com.br\r\nContent-type: text/plain; charset=UTF-8");
		query("INSERT INTO pratos VALUES(NULL, NULL, '?', NULL, 0)", $dados['prato']);
		$id = mysql_insert_id();
	}
	
	// Atualiza a refeicao
	if (queryReturns("SELECT id FROM refeicoes WHERE data='$dados[data]' LIMIT 1"))
		query("UPDATE refeicoes SET prato=$id, guarnicao='?', pts='?', salada='?', sobremesa='?', suco='?' WHERE data='$dados[data]' LIMIT 1",
		$dados['guarnicao'], $dados['pts'], $dados['salada'], $dados['sobremesa'], $dados['suco']);
	else
		query("INSERT INTO refeicoes VALUES (NULL, '?', ?, '?', '?', '?', '?', '?', NULL, 0)",
			$dados['data'], $id, $dados['guarnicao'], $dados['pts'], $dados['salada'], $dados['sobremesa'], $dados['suco']);
}
