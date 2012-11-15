<?php
// Representa uma família:
// {id: number, nome: string(100), nota*: number, numVotos: int}
class Familia {
	// Guarda cache de instâncias já criadas
	private static $familias = array();
	
	public $id;
	public $nome;
	public $nota;
	public $numVotos;
	
	// Retorna uma instância da família pelo id
	// Verifica se já não existe uma criada para evitar retrabalho
	public static function instanciar($id) {
		if (isset(Familia::$familias[$id]))
			return Familia::$familias[$id];
		
		$novo = new Familia(Query::query(true, NULL, 'SELECT * FROM familias WHERE id=? LIMIT 1', $id));
		Familia::$familias[$id] = $novo;
		return $novo;
	}
	
	// Inicializa o prato com as informações da linha do banco de dados
	private function __construct($base) {
		$this->id = $base['id'];
		$this->nome = $base['nome'];
		$this->nota = $base['nota'];
		$this->numVotos = $base['votos'];
	}
}
