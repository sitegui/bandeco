<?php
// Representa um prato:
// {id: int, familia*: Familia, nome: string(100), nota*: number, numVotos: int, notaPessoal*: number, numVotosPessoal: int}
class Prato {
	// Guarda cache de instâncias já criadas
	private static $pratos = array();
	
	public $id;
	public $familia;
	public $nome;
	public $nota;
	public $numVotos;
	public $notaPessoal = NULL;
	public $numVotosPessoal = 0;
	
	// Retorna uma instância do prato (a partir do id ou da linha do banco de dados)
	// Verifica se já não existe uma criada para evitar retrabalho
	public static function instanciar($base) {
		$id = is_array($base) ? $base['id'] : $base;
		if (isset(Prato::$pratos[$id]))
			return Prato::$pratos[$id];
		
		$novo = new Prato(is_array($base) ? $base : Query::query(true, NULL, 'SELECT * FROM pratos WHERE id=? LIMIT 1', $id));
		Prato::$pratos[$id] = $novo;
		return $novo;
	}
	
	// Inicializa o prato com as informações da linha do banco de dados
	private function __construct($base) {
		global $_ra;
		
		// Salva as propriedades imediatas
		$this->id = $base['id'];
		$this->familia = $base['familia'];
		$this->nome = $base['nome'];
		$this->nota = $base['nota'];
		$this->numVotos = $base['votos'];
		
		// Calcula a nota pessoal
		if ($_ra) {
			$notas = Query::query(true, NULL, 'SELECT COUNT(*) AS num, SUM(voto)/COUNT(*) AS nota FROM votos AS v
			JOIN refeicoes AS r ON r.id=v.refeicao WHERE r.prato=? AND v.ra=?', $this->id, $_ra);
			$this->notaPessoal = $notas['nota'];
			$this->numVotosPessoal = $notas['num'];
		}
		
		// Instancia a família
		if ($this->familia)
			$this->familia = Familia::instanciar($this->familia);
	}
}
