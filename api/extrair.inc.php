<?php
// Extrai as informações do site da prefeitura
// Recebe o número da página (padrão: 1)
// Retorna uma array com os índices: prato, guarnicao, pts, salada, sobremesa, suco, data, vegetariano
// O índice "data" é um objeto do tipo Data
// Em caso de erro, retorna false
function extrair($pag=1) {
	// Pega lista de datas publicadas
	static $datas = null;
	if (!$datas) {
		$requisicao = curl_init('http://legiao.prefeitura.unicamp.br/cardapio.php');
		curl_setopt($requisicao, CURLOPT_HTTPHEADER, array('User-Agent:Mozilla/5.0 (Windows NT 6.1; WOW64; rv:12.0) Gecko/20100101 Firefox/12.0'));
		curl_setopt($requisicao, CURLOPT_RETURNTRANSFER, true);
		$pagina = curl_exec($requisicao);
		$datas = array();
		$n = preg_match_all('@<a href="cardapio.php\?d=(.*?)">@', $pagina, $datas);
		$datas = $datas[1];
		if (!$n) return false;
	}
	
	$pag2 = ceil($pag/2)-1;
	if ($pag2 >= count($datas)) return false;
	
	// Pega o conteúdo da página
	
	// Calcula a data correta para o pedido recebido	

	$dataRequisitada = $datas[$pag2];
	
	$requisicao = curl_init('http://engenheiros.prefeitura.unicamp.br/cardapio.php?d=' . $dataRequisitada);
	curl_setopt($requisicao, CURLOPT_HTTPHEADER, array('User-Agent:Mozilla/5.0 (Windows NT 6.1; WOW64; rv:12.0) Gecko/20100101 Firefox/12.0'));
	curl_setopt($requisicao, CURLOPT_RETURNTRANSFER, true);
	$pagina = curl_exec($requisicao);
	if (!$pagina)
		return false;
	// Extrai as partes interessantes
	$matches = array();

	$n = preg_match_all('@<table width="80%" class="fundo_cardapio">(.*?)</table>@s', $pagina, $matches);
	if ($n != 3) return false;
	
	preg_match_all('@<td>(.*?)</td>@s', $matches[1][$pag%2 ? 0 : 2], $matches);

	$partes = array_map(function ($v) {
		return utf8_encode(strip_tags($v));
	}, $matches[1]);

	// Interpreta a data
	$dia = (int)substr($dataRequisitada, 8, 2);
	$mes = (int)substr($dataRequisitada, 5, 2);
	$ano = (int)substr($dataRequisitada, 0, 4);
	
	$almoco = !($pag%2 ? false : true);
	$data = new Data($dia, $mes, $ano, $almoco);
	
	// Interpreta o cardápio em si
	$resposta = array('prato' => '', 'guarnicao' => array(), 'pts' => '', 'salada' => '', 'sobremesa' => '', 'suco' => '');
	$resposta['data'] = $data;
	$resposta['vegetariano'] = false;
	
	$tags = array('prato principal:' => 'prato', 'salada:' => 'salada', 'sobremesa:' => 'sobremesa', 'suco:' => 'suco');
	for ($i=0; $i<count($partes); $i++) {
		$parte = mb_strtolower($partes[$i], 'UTF-8');
		$parte = trim($parte);
		if ($parte == "") continue;
		// Busca pelas tags
		foreach ($tags as $tag=>$var) {
			if (substr($parte, 0, strlen($tag)) == $tag) {
				$resposta[$var] = trim(substr($parte, strlen($tag)));
				
				// Termina no suco
				if ($var == 'suco') break 2;
				
				continue 2;
			}
		}
		
		// Busca por pts
		if (strpos($parte, 'pts') !== false)
			$resposta['pts'] = trim(str_replace('pts', '', $parte));
		else
			// Informações adicionais
			$resposta['guarnicao'][] = $parte;
	}
	$resposta['guarnicao'] = implode(', ', $resposta['guarnicao']);
	
	return $resposta;
}
