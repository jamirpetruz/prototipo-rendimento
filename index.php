<?php
declare(strict_types=1);

session_start();
require __DIR__ . '/core/bootstrap.php';

// Roteador: ?e=<módulo>&a=<ação>  →  <módulo>/acoes.php :: <módulo>_rota(<ação>)
$modulo = $_GET['e'] ?? 'rendimento';
$acao = $_GET['a'] ?? 'list';

if (!in_array($modulo, MODULOS, true)) {
    http_response_code(404);
    exit('Página não encontrada');
}

($modulo . '_rota')($acao);
