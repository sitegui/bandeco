<?php
// Esse script roda a cada meia hora para recalcular as notas

require_once 'extrair.inc.php';
require_once 'mysql.inc.php';
require_once '../query/1.1.php';
require_once 'Data.class.php';

set_time_limit(90);

// Atualiza as notas das refeições
$id = 'refeicoes.id';
query("UPDATE refeicoes SET nota=
(SELECT SUM(voto)/COUNT(*) FROM votos WHERE refeicao=$id), votos=
(SELECT COUNT(*) FROM votos WHERE refeicao=$id)");

// Atualiza as notas do pratos
$ids = 'SELECT id FROM refeicoes WHERE prato=pratos.id';
query("UPDATE pratos SET nota=
(SELECT SUM(voto)/COUNT(*) FROM votos WHERE refeicao IN ($ids)), votos=
(SELECT COUNT(*) FROM votos WHERE refeicao IN ($ids))");

// Atualiza as notas das famílias
$ids = 'SELECT id FROM refeicoes WHERE prato IN (SELECT id FROM pratos WHERE familia=familias.id)';
query("UPDATE familias SET nota=
(SELECT SUM(voto)/COUNT(*) FROM votos WHERE refeicao IN ($ids)), votos=
(SELECT COUNT(*) FROM votos WHERE refeicao IN ($ids))");
