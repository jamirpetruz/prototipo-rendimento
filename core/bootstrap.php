<?php
/** Carrega o núcleo e todos os módulos. Usado por index.php e seed.php. */
declare(strict_types=1);

$raiz = dirname(__DIR__);

require $raiz . '/core/helpers.php';
require $raiz . '/core/db.php';
require $raiz . '/core/layout.php';
require $raiz . '/core/componentes.php';

// Cada módulo: campos (definição + fórmulas), queries (SQL), tela (HTML), acoes (requisições)
foreach (MODULOS as $modulo) {
    foreach (['campos', 'calculos', 'queries', 'tela', 'acoes'] as $parte) {
        $arquivo = "$raiz/$modulo/$parte.php";
        if (is_file($arquivo)) {
            require $arquivo;
        }
    }
}
