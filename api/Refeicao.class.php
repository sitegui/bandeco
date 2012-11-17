<?php
// Representa uma refeição:
// {id: int, data: Data, prato: Prato, guarnicao: string(100), pts: string(100), salada: string(100),
//  sobremesa: string(100), suco: string(100), nota*: number, notaPessoal*: int, podeVotar: boolean, numVotos: int}
class Refeicao {
	public $id;
	public $data;
	public $prato;
	public $guarnicao;
	public $pts;
	public $salada;
	public $sobremesa;
	public $suco;
	public $nota;
	public $notaPessoal = NULL;
	public $podeVotar;
	public $numVotos;
	
	// Inicializa a refeição com as informações da linha do banco de dados
	public function __construct($base) {
		global $_ra;
		
		// Salva as propriedades imediatas
		$this->id = $base['id'];
		$this->prato = Prato::instanciar($base['prato']);
		$this->guarnicao = $base['guarnicao'];
		$this->pts = $base['pts'];
		$this->salada = $base['salada'];
		$this->sobremesa = $base['sobremesa'];
		$this->suco = $base['suco'];
		$this->nota = $base['nota'];
		$this->numVotos = $base['votos'];
		
		// Extrai as informações da data
		$this->data = new Data($base['data']);
		
		// Verifica se o usuário poderia votar nessa refeição (prazo de 3 dias)
		$dif = time()-$this->data->getInicio();
		$this->podeVotar = $dif>0 && $dif<7*24*60*60;
		
		// Calcula a nota pessoal
		if ($_ra)
			try {
				$this->notaPessoal = Query::query(true, 0, 'SELECT voto FROM votos WHERE refeicao=? AND ra=? LIMIT 1', $this->id, $_ra);
			} catch (Exception $e) {
				$this->notaPessoal = NULL;
			}
	}
}
