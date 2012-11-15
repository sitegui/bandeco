<?php
// Representa um aviso
// {id: int, tipo: int, refeicao: Refeicao, data: Data}
class Aviso {
	public $id;
	public $tipo; // -1 = ruim, 0 = semana, 1 = bom
	public $refeicao;
	public $data;
	
	// Inicializa o aviso
	public function __construct($base) {
		$this->id = $base['id'];
		$this->tipo = $base['tipo'];
		$this->refeicao = new Refeicao(Query::query(true, NULL, 'SELECT * FROM refeicoes WHERE id=? LIMIT 1', $base['refeicao']));
		$this->data = new Data($base['data']);
	}
}
