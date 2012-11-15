<?php
// Representa um ouvinte
// {ra: int, email: string(100), nome: string(100), avisos: int}
class Ouvinte {
	// Constantes de aviso
	const SEMANA = 0x1;
	const RUIM = 0x2;
	const BOM = 0x4;
	
	public $ra;
	public $email;
	public $nome;
	public $avisos;
	
	// Inicializa o ouvinte
	public function __construct($base) {
		$this->ra = $base['ra'];
		$this->email = $base['email'];
		$this->nome = $base['nome'];
		$this->avisos = $base['avisos'];
	}
}
