<?php
// Representa uma data:
// {dia: int, mes: int, ano: int, semana: int, almoco: boolean}
class Data {
	const SEMANA = 604800; // 1 semana, em segundos
	const BASE = 1293926400; // 02/01/2011 00:00:00, domingo
	
	public $dia;
	public $mes;
	public $ano;
	public $semana;
	public $almoco;
	
	// Retorna a semana associada ao tempo enviado (padrão: time())
	public static function getSemana($time=NULL) {
		if ($time === NULL)
			$time = time();
		return floor(($time-Data::BASE)/Data::SEMANA);
	}
	
	// Retorna o tempo de início da semana (padrão: essa semana)
	public static function getInicioSemana($semana=NULL) {
		if ($semana === NULL)
			$semana = Data::getSemana();
		return $semana*Data::SEMANA+Data::BASE;
	}
	
	// Inicializa a data com cada componente, com uma string DATETIME do BD ou com uma array com cada componente:
	// new Data('2012-11-15 12:00:00')
	// new Data(array('dia' => 15, 'mes' => 11, 'ano' => 2012, 'almoco' => true))
	// new Data(15, 11, 2012, true)
	public function __construct($data, $mes=NULL, $ano=NULL, $almoco=NULL) {
		if ($mes === NULL) {
			$this->dia = substr($data, 8, 2);
			$this->mes = substr($data, 5, 2);
			$this->ano = substr($data, 0, 4);
			$this->almoco = substr($data, 11, 2)=='12';
		} else if (is_array($data)) {
			$this->dia = $data['dia'];
			$this->mes = $data['mes'];
			$this->ano = $data['ano'];
			$this->almoco = (boolean)$data['almoco'];
		} else {
			$this->dia = $data;
			$this->mes = $mes;
			$this->ano = $ano;
			$this->almoco = (boolean)$almoco;
		}
		
		// Corrige os intervalos
		$time = mktime(15, 0, 0, $this->mes, $this->dia, $this->ano);
		$this->dia = (int)date('d', $time);
		$this->mes = (int)date('m', $time);
		$this->ano = (int)date('Y', $time);
		
		// Calcula a semana
		$this->semana = Data::getSemana($time);
	}
	
	// Retorna a data no formato DATETIME
	public function __toString() {
		 return "$this->ano-$this->mes-$this->dia " . ($this->almoco ? '12' : '18') . ':00:00';
	}
	
	// Retorna o tempo de início da refeição
	public function getInicio() {
		return mktime($this->almoco ? 10 : 17, 30, 0, $this->mes, $this->dia, $this->ano);
	}
}
