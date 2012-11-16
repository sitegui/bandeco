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
require_once 'Email.class.php';

// Permite ser acessado por qualquer domínio
header('Access-Control-Allow-Origin: *');

$op = @$_GET['op'];

// Salva o RA (se enviado) para uso posterior
$_ra = ler('GET', 'ra', 'int', NULL);

if ($op == '')
	// Mostra a página de documentação
	readfile('ajuda.html');
else if ($op == 'busca') {
	// Busca por pratos com o nome dado. Retorna uma array ordenando-os pela nota
	$busca = ler('GET', 'busca', 'string');
	$max = min(ler('GET', 'max', 'int', 20), 50);
	$pratos = array();
	foreach (Query::query(false, NULL, 'SELECT * FROM pratos WHERE nome LIKE ? ORDER BY nota DESC LIMIT ?', "%$busca%", $max) as $cada)
		$pratos[] = Prato::instanciar($cada);
	retornar($pratos);
} else if ($op == 'cardapio') {
	// Retorna informações sobre o cardápio numa data (ou na data mais próxima posterior). Se não houver dados, retorna null.
	$dia = ler('GET', 'dia', 'int', (int)date('d'));
	$mes = ler('GET', 'mes', 'int', (int)date('m'));
	$ano = ler('GET', 'ano', 'int', (int)date('Y'));
	$almoco = ler('GET', 'almoco', 'bool', date('H')<15);
	$data = new Data($dia, $mes, $ano, $almoco);
	
	try {
		// Tenta pegar a refeição exata
		$refeicao = Query::query(true, NULL, 'SELECT * FROM refeicoes WHERE data=? LIMIT 1', $data);
	} catch (Exception $e) {
		try {
			// Busca a mais próxima
			$refeicao = Query::query(true, NULL, 'SELECT * FROM refeicoes WHERE data>? ORDER BY data LIMIT 1', $data);
		} catch (Exception $e) {
			// Erro
			retornar();
		}
	}
	
	retornar(new Refeicao($refeicao));
} else if ($op == 'avisos') {
	// Retorna os últimos avisos disparados (máximo de 10)
	$desde = ler('GET', 'desde', 'int', 0);
	$avisos = array();
	foreach (Query::query(false, NULL, 'SELECT * FROM avisos WHERE id>? ORDER BY id DESC LIMIT 10', $desde) as $cada)
		$avisos[] = new Aviso($cada);
	retornar($avisos);
} else if ($op == 'infoFamilia') {
	// Retorna informações sobre a família e as últimas refeições com essa família. Caso a família não seja encontrada, retorna null
	$familia = ler('GET', 'familia', 'int');
	$max = min(ler('GET', 'refeicoes', 'int', 10), 50);
	
	// Pega a família
	try {
		$familia = Familia::instanciar($familia);
	} catch (Exception $e) {
		retornar();
	}
	
	// Pega os pratos associados à família
	$pratos = array();
	foreach (Query::query(false, NULL, 'SELECT * FROM pratos WHERE familia=?', $familia->id) as $cada)
		$pratos[] = Prato::instanciar($cada);
	
	// Pega as últimas refeições com pratos da família
	$historico = array();
	foreach (Query::query(false, NULL,
		'SELECT r.* FROM refeicoes AS r JOIN pratos AS p ON p.id=r.prato WHERE p.familia=? ORDER BY r.data DESC LIMIT ?', $familia->id, $max) as $cada)
		$historico[] = new Refeicao($cada);
	
	// Calcula o rank
	$ids = Query::query(false, 0, 'SELECT id FROM familias WHERE nota IS NOT NULL ORDER BY nota DESC, id');
	$rank = new Rank(count($ids), array_search($familia->id, $ids));
	
	// Retorna a resposta
	retornar(array('familia' => $familia, 'pratos' => $pratos, 'historico' => $historico, 'rank' => $rank));
} else if ($op == 'infoPrato') {
	// Retorna informações sobre o prato e as últimas refeições com esse prato. Caso o prato não seja encontrado, retorna null
	$prato = ler('GET', 'prato', 'int');
	$max = min(ler('GET', 'refeicoes', 'int', 10), 50);
	
	// Pega o prato
	try {
		$prato = Prato::instanciar($prato);
	} catch (Exception $e) {
		retornar();
	}
	
	// Pega as últimas refeições com o prato
	$historico = array();
	foreach (Query::query(false, NULL, 'SELECT * FROM refeicoes WHERE prato=? ORDER BY data DESC LIMIT ?', $prato->id, $max) as $cada)
		$historico[] = new Refeicao($cada);
	
	// Calcula o rank
	$ids = Query::query(false, 0, 'SELECT id FROM pratos WHERE nota IS NOT NULL ORDER BY nota DESC, id');
	$rank = new Rank(count($ids), array_search($prato->id, $ids));
	
	// Retorna a resposta
	retornar(array('prato' => $prato, 'historico' => $historico, 'rank' => $rank));
} else if ($op == 'infoRefeicao') {
	// Retorna informações sobre a refeição. Caso a refeição não seja encontrada, retorna null
	$refeicao = ler('GET', 'refeicao', 'int');
	
	// Pega a refeição
	try {
		$refeicao = new Refeicao(Query::query(true, NULL, 'SELECT * FROM refeicoes WHERE id=? LIMIT 1', $refeicao));
	} catch (Exception $e) {
		retornar();
	}
	
	// Retorna a resposta
	retornar($refeicao);
} else if ($op == 'ranking') {
	// Retorna o ranking dos pratos, iniciando na posição desejada e retornando, no máximo, a quantidade de posições indicadas
	$inicio = ler('GET', 'inicio', 'int', 0);
	$max = min(ler('GET', 'quantidade', 'int', 10), 50);
	
	$pratos = array();
	foreach (Query::query(false, NULL, 'SELECT * FROM pratos WHERE nota IS NOT NULL ORDER BY nota DESC, id LIMIT ? OFFSET ?', $max, $inicio) as $cada)
		$pratos[] = Prato::instanciar($cada);
	
	retornar($pratos);
} else if ($op == 'semana') {
	// Retorna informações sobre os cardápios de uma semana
	$semana = ler('GET', 'semana', 'int', Data::getSemana());
	
	$inicio = date('Y-m-d H:i:s', Data::getInicioSemana($semana));
	$fim = date('Y-m-d H:i:s', Data::getInicioSemana($semana+1));
	
	$refeicoes = array();
	foreach (Query::query(false, NULL, 'SELECT * FROM refeicoes WHERE data>? AND data<? ORDER BY data', $inicio, $fim) as $cada)
		$refeicoes[] = new Refeicao($cada);
	retornar($refeicoes);
} else if ($op == 'votar') {
	// Salva o voto de uma pessoa. Cada RA só pode ter um voto para cada refeição e um novo voto irá sobreescrever o antigo.
	// Só será possível votar numa refeição até uma semana após ela ter começado a ser servida
	$refeicao = ler('POST', 'refeicao', 'int');
	$ra = ler('POST', 'ra', 'int');
	$voto = ler('POST', 'voto', 'int', NULL);
	
	// Executa o voto
	try {
		// Valida o voto
		if ($voto !== NULL && ($voto<-2 || $voto>2) || !preg_match('@^[0-9]{4,6}$@', $ra))
			throw new Exception('Voto inválido');
		
		// Verifica se a refeição está aberta a votação
		$data = new Data(Query::query(true, 0, 'SELECT data FROM refeicoes WHERE id=? LIMIT 1', $refeicao));
		$dif = time()-$data->getInicio();
		if ($dif<0 || $dif>7*24*60*60)
			throw new Exception('Fora do prazo');
		
		// Executa o voto
		if ($voto === NULL)
			new Query('DELETE FROM votos WHERE refeicao=? AND ra=? LIMIT 1', $refeicao, $ra);
		else
			new Query('REPLACE INTO votos (refeicao, ra, data, voto) VALUES (?, ?, NOW(), ?)', $refeicao, $ra, $voto);
		retornar(true);
	} catch (Exception $e) {
		retornar(false);
	}
} else if ($op == 'pedirChave') {
	// Envia a chave por e-mail (retorna false caso não precise)
	$ra = ler('POST', 'ra', 'int');
	
	try {
		$dados = Query::query(true, NULL, 'SELECT email, nome, chave FROM ouvintes WHERE ra=? LIMIT 1', $ra);
		Email::enviar($dados['email'], Email::CHAVE, $dados);
		retornar(true);
	} catch (Exception $e) {
		retornar(false);
	}
} else if ($op == 'getOuvinte') {
	// Retorna as informações registradas de ouvinte (nulo em caso de erro)
	$chave = ler('GET', 'chave', 'string');
	
	try {
		retornar(new Ouvinte(Query::query(true, NULL, 'SELECT * FROM ouvintes WHERE chave=? LIMIT 1', $chave)));
	} catch (Exception $e) {
		retornar();
	}
} else if ($op == 'removerOuvinte') {
	// Retorna as informações registradas de ouvinte (nulo em caso de erro)
	$chave = ler('POST', 'chave', 'string');
	

	if (Query::existe('SELECT 1 FROM ouvintes WHERE chave=? LIMIT 1', $chave)) {
		new Query('DELETE FROM ouvintes WHERE chave=? LIMIT 1', $chave);
		retornar(true);
	} else
		retornar(false);
} else if ($op == 'setOuvinte') {
	// Retorna as informações registradas de ouvinte (nulo em caso de erro)
	$ra = ler('POST', 'ra', 'int');
	$email = ler('POST', 'email', 'string', NULL);
	$nome = ler('POST', 'nome', 'string', NULL);
	$avisos = ler('POST', 'avisos', 'int', NULL);
	$chave = ler('POST', 'chave', 'string', NULL);
	
	// Valida a chave
	$chaveCerta = Query::getValor('SELECT chave FROM ouvintes WHERE ra=? LIMIT 1', $ra);
	if ($chaveCerta === NULL || $chave == $chaveCerta) {
		// Junta os dados
		$dados = array();
		if ($email !== NULL) $dados['email'] = $email;
		if ($nome !== NULL) $dados['nome'] = $nome;
		if ($avisos !== NULL) $dados['avisos'] = $avisos;
		
		try {
			if ($chaveCerta === NULL) {
				// Insere
				if ($email === NULL || !preg_match('#^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$#i', $email))
					retornar(false);
				
				$dados['ra'] = $ra;
				$dados['chave'] = '';
				$base64 = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_';
				for ($i=0; $i<20; $i++)
					$dados['chave'] .= $base64[mt_rand(0, 63)];
				new Query('INSERT INTO ouvintes SET ?', $dados);
			} else if (count($dados)) {
				// Atualiza
				new Query('UPDATE ouvintes SET ? WHERE ra=? LIMIT 1', $dados, $ra);
			}
			retornar(true);
		} catch (Exception $e) {
			retornar(false);
		}
	} else
		retornar(false);
} else
	// Erro
	falharHttp();

// Retorna um código de erro HTTP devido à má formatação da requisição
function falharHttp() {
	header('HTTP/1.1 400 Bad Request');
	exit;
}

// Retorna o valor
function retornar($valor=NULL) {
	exit(json_encode($valor));
}

// Lê um valor de entrada
// $origem é 'GET' ou 'POST'
// $nome é o nome do valor a ser lido
// $tipo é 'string', 'int' ou 'bool'
// $padrao indica o valor assumido caso o valor não esteja presente
// Se o $padrao não for enviado e o valor não estiver presente, encerra o programa com erro 400
function ler($origem, $nome, $tipo, $padrao=NULL) {
	$base = $origem=='GET' ? $_GET : $_POST;
	$valor = isset($base[$nome]) ? $base[$nome] : '';
	if ($valor == '')
		if (func_num_args()>3)
			return $padrao;
		else
			falharHttp();
	switch ($tipo) {
		case 'int': return (int)$valor; break;
		case 'bool': return $valor=='true' || $valor=='1'; break;
		case 'usuario':	return getIdUsuario($valor); break;
		default: return $valor;
	}
}
