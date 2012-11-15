<?php
// Importa todos dados da estrutura antiga para a nova do banco de dados

require_once 'query.2.1.php';

Query::$conexao = new MySQLi('localhost', 'root', '', 'bandeco');
Query::$conexao->set_charset('utf8');

// Limpa as novas
new Query('TRUNCATE TABLE ouvintes');
new Query('TRUNCATE TABLE avisos');
new Query('TRUNCATE TABLE votos');
new Query('TRUNCATE TABLE refeicoes');
new Query('TRUNCATE TABLE pratos');
new Query('TRUNCATE TABLE familias');

// Copia e cola
new Query('INSERT INTO familias SELECT * FROM bandeco2.familias');
new Query('INSERT INTO pratos SELECT * FROM bandeco2.pratos');
new Query('INSERT INTO refeicoes SELECT *, NULL FROM bandeco2.refeicoes');
new Query('INSERT INTO votos SELECT refeicao, ra, data, voto FROM bandeco2.votos');

// Atualiza a coluna 'proxima' das refeições
$proximas = 'SELECT id, (SELECT id FROM refeicoes AS r2 WHERE r2.data>r.data ORDER BY r2.data LIMIT 1) AS proxima FROM refeicoes AS r';
new Query("UPDATE refeicoes AS r
JOIN ($proximas) AS p ON p.id=r.id
SET r.proxima=p.proxima");
