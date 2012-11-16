<?php
class Email {
	// Constantes de formatos de e-mail
	const CHAVE = 'chave'; // Recuperação de chave
	const SEMANA = 'semana'; // Resumo do cardápio da semana
	const RUIM = 'ruim'; // Prato mudou para um ruim
	const BOM = 'bom'; // Prato mudou para um bom
	
	// Armazena os dados enviados, usados para preencher o molde
	private static $dados = NULL;
	
	// Não pode instanciar
	private function __construct() {}
	
	// Enviar e-mail nos formatos salvos no diretório emails/
	// $destinatarios é:
	//   um e-mail (string) ou
	//   uma array em que cada elemento é:
	//     um e-mail (string) ou
	//     uma array com os índices nome e email
	// $formato é uma das constantes da classe
	// $dados é uma array associativa usada para substituir os valores no molde
	public static function enviar($destinatarios, $formato, $dados) {
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
		
		// Monta a lista de destinatarios
		// TODO: dividir em grupos de cerca de 50 destinatários
		$para = array();
		if (is_array($destinatarios))
			foreach ($destinatarios as $cada)
				if (is_array($cada))
					$para[] = '"' . ($cada['nome'] ? addslashes($cada['nome']) : $cada['email']) . '" <' . $cada['email'] . '>';
				else
					$para[] = $cada;
		else
			$para = array($destinatarios);
		
		// Envia
		mail(implode(', ', $para), $assunto, $mensagem, "From: sitegui@sitegui.com.br\r\nContent-type: text/html; charset=UTF-8");
	}

	// Função auxiliar para preencher o molde do email
	private static function inflarMolde($matches) {
		$nome = $matches[1];
		if (isset(Email::$dados[$nome]))
			return Email::$dados[$nome];
		return '&lt;' . $nome . '&gt;';
	}
}