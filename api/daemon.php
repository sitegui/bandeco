<?php
// Esse script roda a cada duas horas para sincronizar o banco de dados com os dados da página da prefeitura

require_once 'extrair.inc.php';
require_once 'query.2.1.php';
require_once 'mysql.inc.php';
require_once 'Data.class.php';
require_once 'Refeicao.class.php';
require_once 'Prato.class.php';
require_once 'Familia.class.php';
require_once 'Email.class.php';
require_once 'Ouvinte.class.php';

set_time_limit(90);

// Atualiza os pratos
$pag = 1;
while ($dados = extrair($pag++)) {
	// Pega o id do prato
	try {
		$dados['prato'] = Query::query(true, 0, 'SELECT id FROM pratos WHERE nome=? LIMIT 1', $dados['prato']);
	} catch (Exception $e) {
		// Novo prato
		new Query('INSERTO INTO pratos (nome) VALUES (?)', $dados['prato']);
		$dados['prato'] = Query::$conexao->insert_id;
	}
	
	// Salva a refeicao
	try {
		// Tenta atualizar a refeição existente
		$idRefeicao = Query::query(true, 0, 'SELECT id FROM refeicoes WHERE data=? LIMIT 1', (string)$dados['data']);
		$pratoAntes = Prato::instanciar(Query::getValor('SELECT prato FROM refeicoes WHERE id=? LIMIT 1', $idRefeicao));
		$pratoDepois = Prato::instanciar($dados['prato']);
		$dadosUpdate = $dados;
		unset($dadosUpdate['data']);
		new Query('UPDATE refeicoes SET ? WHERE id=? LIMIT 1', $dadosUpdate, $idRefeicao);
		
		// Dispara o evento de mudança (pra bom ou pra ruim)
		$notaAntes = $pratoAntes->getNotaMedia();
		$notaDepois = $pratoDepois->getNotaMedia();
		$dados = array('antes' => json_encode($pratoAntes), 'depois' => json_encode($pratoDepois));
		if ($notaDepois < -.5 && $notaDepois < $notaAntes) {
			// Prato ficou ruim
			Email::enviar(Ouvinte::RUIM, $dados);
		} else if ($notaDepois > .5 && $notaDepois > $notaAntes) {
			// Prato ficou bom
			Email::enviar(Ouvinte::BOM, $dados);
		}
		
	} catch (Exception $e) {
		// Insere a nova refeição
		$dadosInsert = $dados;
		$dadosInsert['data'] = (string)$dadosInsert['data'];
		new Query('INSERT INTO refeicoes SET ?', $dadosInsert);
	}
}

// Verifica se mudou a semana para avisar do cardápio da semana
$ultimaSemana = (int)(@file_get_contents('ultimaSemana.txt'));
$semanaAtual = Data::getSemana();
if ($semanaAtual > $ultimaSemana || true) {
	// Pega todas as refeições
	$refeicoes = array();
	$tempoInicio = Data::getInicioSemana();
	$tempoFim = $tempoInicio+Data::SEMANA;
	$inicio = date('Y-m-d H:i:s', $tempoInicio);
	$fim = date('Y-m-d H:i:s', $tempoFim);
	foreach (Query::query(false, NULL, 'SELECT * FROM refeicoes WHERE data>? AND data<? ORDER BY data', $inicio, $fim) as $cada)
		$refeicoes[] = new Refeicao($cada);
	
	// Processa as informações
	$dados = array('semana' => date('d/m', $tempoInicio+2*Data::SEMANA/7) . ' a ' . date('d/m', $tempoFim-Data::SEMANA/7));
	$dias = array('domingo', 'segunda', 'terça', 'quarta', 'quinta', 'sexta', 'sábado');
	$melhor = array(-2, NULL);
	foreach ($refeicoes as $refeicao) {
		$nota = $refeicao->prato->getNotaMedia();
		if ($nota !== NULL && $nota > $melhor[0])
			$melhor = array($nota, $refeicao);
		$idData = (getDiaSemana($refeicao)+1) . ($refeicao->data->almoco ? 'a' : 'j');
		$dados['prato' . $idData] = ucwords($refeicao->prato->nome);
		if ($nota === NULL)
			$dados['nota' . $idData] = 'Histórico desconhecido';
		else {
			$dados['nota' . $idData] = str_replace('.', ',', round($nota, 1));
			$dados['nota' . $idData] .= ' <img style="vertical-align:middle" src="http://sitegui.com.br/bandeco/' . round($nota) . '.png">';
		}
	}
	
	foreach (array('2a', '2j', '3a', '3j', '4a', '4j', '5a', '5j', '6a', '6j') as $cada)
		if (!isset($dados['prato' . $cada]))
			$dados['prato' . $cada] = $dados['nota' . $cada] = '-';
	
	if ($melhor[1] !== NULL) {
		$dados['diaShow'] = $dias[getDiaSemana($melhor[1])];
		$dados['pratoShow'] = $melhor[1]->prato->nome;
	} else {
		$dados['diaShow'] = $dados['pratoShow'] = '-';
	}
	
	// Envia para os emails cadastrados
	Email::enviar(Ouvinte::SEMANA, $dados);
	
	// Salva
	$ultimaSemana = $semanaAtual;
	file_put_contents('ultimaSemana.txt', $ultimaSemana);
}

// Função de apoio: recebe uma Data e retorna o dia da semana
function getDiaSemana($refeicao) {
	return (int)date('w', $refeicao->data->getInicio());
}
