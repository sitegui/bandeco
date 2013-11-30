<?php
// Extrai as informações do site da prefeitura
// Recebe o número da página (padrão: 1)
// Retorna uma array com os índices: prato, guarnicao, pts, salada, sobremesa, suco, data, vegetariano
// O índice "data" é um objeto do tipo Data
// Em caso de erro, retorna false
function extrair($pag=1) {
	// Pega o conteúdo da página
	$requisicao = curl_init('http://www.prefeitura.unicamp.br/cardapio_pref.php?pagina=' . $pag);
	curl_setopt($requisicao, CURLOPT_HTTPHEADER, array('User-Agent:Mozilla/5.0 (Windows NT 6.1; WOW64; rv:12.0) Gecko/20100101 Firefox/12.0'));
	curl_setopt($requisicao, CURLOPT_RETURNTRANSFER, true);
	$pagina = curl_exec($requisicao);
	if (!$pagina)
		return false;
	
	// Extrai as partes interessantes
	$matches = array();
	preg_match_all('@<th>(.*?)</th>@', $pagina, $matches);
	$partes = array_map(function ($v) {
		return utf8_encode(strip_tags($v));
	}, $matches[1]);
	
	// Interpreta a data
	$dia = (int)substr($partes[1], 0, 2);
	if (!$dia)
		// Cardápio indisponível
		return false;
	$mes = (int)substr($partes[1], 3, 2);
	$ano = (int)substr($partes[1], 6, 4);
	$almoco = strpos($partes[0], 'JANTAR')===false;
	$data = new Data($dia, $mes, $ano, $almoco);
	
	// Interpreta o cardápio em si
	$resposta = array('prato' => '', 'guarnicao' => array(), 'pts' => '', 'salada' => '', 'sobremesa' => '', 'suco' => '');
	$resposta['data'] = $data;
	$resposta['vegetariano'] = strpos($partes[0], 'VEGETARIANO')!==false;
	
	$tags = array('prato principal:  ' => 'prato', 'salada: ' => 'salada', 'sobremesa: ' => 'sobremesa', 'suco: ' => 'suco');
	for ($i=2; $i<count($partes); $i++) {
		$parte = mb_strtolower($partes[$i], 'UTF-8');
		
		// Busca pelas tags
		foreach ($tags as $tag=>$var)
			if (substr($parte, 0, strlen($tag)) == $tag) {
				$resposta[$var] = substr($parte, strlen($tag));
				
				// Termina no suco
				if ($var == 'suco') break 2;
				
				continue 2;
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
