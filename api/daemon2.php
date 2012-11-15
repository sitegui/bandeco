<?php
// Esse script roda a cada meia hora para recalcular as notas

require_once 'mysql.inc.php';
require_once 'query.2.1.php';

set_time_limit(90);

// Limpa o cache antigo de notas
new Query('UPDATE refeicoes SET nota=NULL, votos=0');
new Query('UPDATE pratos SET nota=NULL, votos=0');
new Query('UPDATE familias SET nota=NULL, votos=0');

// Calcula as notas
$notasRefeicoes = 'SELECT r.id AS refeicao, AVG(v.voto) AS nota, COUNT(*) AS votos
FROM refeicoes AS r
JOIN votos AS v ON v.refeicao=r.id
GROUP BY (r.id)';
$notasPratos = 'SELECT p.id AS prato, AVG(v.voto) AS nota, COUNT(*) AS votos
FROM pratos AS p
JOIN refeicoes AS r ON r.prato=p.id
JOIN votos AS v ON v.refeicao=r.id
GROUP BY (p.id)';
$notasFamilas = 'SELECT f.id AS familia, AVG(v.voto) AS nota, COUNT(*) AS votos
FROM familias AS f
JOIN pratos AS p ON p.familia=f.id
JOIN refeicoes AS r ON r.prato=p.id
JOIN votos AS v ON v.refeicao=r.id
GROUP BY (f.id)';

// Atualiza as notas
new Query("UPDATE refeicoes AS r
JOIN ($notasRefeicoes) AS temp ON temp.refeicao=r.id
SET r.nota=temp.nota, r.votos=temp.votos");
new Query("UPDATE pratos AS p
JOIN ($notasPratos) AS temp ON temp.prato=p.id
SET p.nota=temp.nota, p.votos=temp.votos");
new Query("UPDATE familias AS f
JOIN ($notasFamilas) AS temp ON temp.familia=f.id
SET f.nota=temp.nota, f.votos=temp.votos");
