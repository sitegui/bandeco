<?php
class Email {
	// Armazena os dados enviados, usados para preencher o molde
	private static $dados = NULL;
	
	// Não pode instanciar
	private function __construct() {}
	
	// Envia e-mail para destinatarios com base num modelo
	// $destinatarios é uma constante da classe Ouvinte ou uma array com os índices nome, email e chave
	// $dados é uma array associativa usada para substituir os valores no molde
	public static function enviar($destinatarios, $dados) {
		// Carrega os destinatários
		if (is_array($destinatarios)) {
			$formato = 'chave';
			$destinatarios = array($destinatarios);
		} else {
			switch ($destinatarios) {
				case Ouvinte::BOM: $formato = 'bom'; break;
				case Ouvinte::RUIM: $formato = 'ruim'; break;
				case Ouvinte::SEMANA: $formato = 'semana'; break;
				default: return;
			}
			$destinatarios = Query::query(false, NULL, 'SELECT nome, email, chave FROM ouvintes WHERE avisos & ?', $destinatarios);
		}
		
		if (empty($destinatarios))
			// Nada a fazer
			return;
		
		// Carrega todos os moldes
		$scandir = @scandir('emails/' . $formato);
		if (!$scandir) return;
		$moldes = array();
		foreach ($scandir as $cada)
			if (substr($cada, -5) == '.html')
				$moldes[] = $cada;
		
		// Pega um deles
		$molde = file_get_contents('emails/' . $formato . '/' . $moldes[mt_rand(0, count($moldes)-1)]);
		
		// Preenche o molde
		Email::$dados = $dados;
		$mensagem = preg_replace_callback('@\$(\w+?)\b@', array('Email', 'inflarMolde'), $molde);
		Email::$dados = NULL;
		
		// Separa assunto e mensagem
		$pos = strpos($mensagem, "\r\n");
		$assunto = substr($mensagem, 0, $pos);
		$mensagem = substr($mensagem, $pos+2);
		
		// Envia para os destinatarios
		$headers = "From: \"Bandeco - Sitegui\"<bandeco@sitegui.com.br>";
		$headers .= "\r\nReply-To: \"Guilherme Souza\"<sitegui@sitegui.com.br>";
		$headers .= "\r\nContent-type: text/html; charset=UTF-8";
		foreach ($destinatarios as $cada) {
			$para = '"' . ($cada['nome'] ? addslashes($cada['nome']) : $cada['email']) . '" <' . $cada['email'] . '>';
			$mensagem2 = preg_replace('@\$nome\b@', $cada['nome'], $mensagem);
			$mensagem2 = preg_replace('@\$chave\b@', $cada['chave'], $mensagem2);
			mail($para, $assunto, $mensagem2, $headers);
		}
	}

	// Função auxiliar para preencher o molde do email
	private static function inflarMolde($matches) {
		$nome = $matches[1];
		if (isset(Email::$dados[$nome]))
			return Email::$dados[$nome];
		else if ($nome != 'nome' && $nome != 'chave')
			return '&lt;' . $nome . '&gt;';
		else
			return $matches[0];
	}
}