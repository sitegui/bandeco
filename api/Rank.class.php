<?php
// Representa um rank
// {total: int, posicao*: int}
class Rank {
	public $total;
	public $posicao;
	
	// Inicializa o rank
	public function __construct($total, $posicao) {
		$this->total = (int)$total;
		$this->posicao = $posicao===false ? NULL : (int)$posicao+1;
	}
}
