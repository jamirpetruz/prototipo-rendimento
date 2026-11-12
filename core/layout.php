<?php
/** Cabeçalho/rodapé HTML comuns a todas as telas. */

function layout_start(string $title, string $active): void
{
    $nav = [
        'rendimento' => ['Rendimento', 'bi-graph-up-arrow'],
        'producao'   => ['Produção', 'bi-gear-wide-connected'],
        'compras'    => ['Compras', 'bi-cart3'],
    ];
    ?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($title) ?> · Módulo Rendimento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; }
        .table-wrap { max-height: calc(100vh - 230px); overflow: auto; background: #fff; border: 1px solid #dee2e6; border-radius: .5rem; }
        .table-wrap table { margin: 0; font-size: .85rem; }
        .table-wrap thead th { position: sticky; top: 0; z-index: 2; background: #212529; color: #fff; white-space: nowrap; font-weight: 600; }
        .table-wrap td { white-space: nowrap; }
        .table-wrap .actions { position: sticky; right: 0; background: #fff; z-index: 1; }
        .table-wrap thead th.actions { z-index: 3; background: #212529; }
        th.src-P { background: #1d4e89 !important; }
        th.src-C { background: #1e6b45 !important; }
        th.src-R { background: #8a5a00 !important; }
        th.src-X { background: #5b3c88 !important; cursor: help; }
        td.c-X { background-color: rgba(91, 60, 136, .05); }
        .hide-calc .src-X, .hide-calc .c-X { display: none; }
        .legend span { display: inline-block; padding: .1rem .5rem; border-radius: .25rem; color: #fff; font-size: .75rem; }
        .num { text-align: right; }
        .calc { background: #eef3f8 !important; }
        .preview dt { font-weight: 500; color: #6c757d; font-size: .8rem; }
        .preview dd { margin-bottom: .35rem; }
    </style>
    <script>
        function fmtJS(val, type) {
            const n = (d) => val.toLocaleString('pt-BR', { minimumFractionDigits: d, maximumFractionDigits: d });
            if (type === 'money') return 'R$ ' + n(2);
            if (type === 'pct') return n(2) + '%';
            if (type === 'int') return n(0);
            if (type === 'dec6') return n(6);
            return n(2);
        }

        /**
         * Recalcula os campos [data-calc] do formulário a cada digitação.
         * formulas(v, form) recebe os valores numéricos do form (v.campo) e devolve {campo: valor}.
         */
        function ligarCalculos(formId, formulas) {
            const form = document.getElementById(formId);
            const v = new Proxy({}, {
                get: (_, k) => {
                    const n = parseFloat(form.elements[k]?.value);
                    return isNaN(n) ? 0 : n;
                },
            });
            const run = () => {
                for (const [k, val] of Object.entries(formulas(v, form))) {
                    const el = form.querySelector(`[data-calc="${k}"]`);
                    if (el) el.value = (val === null || !isFinite(val)) ? '' : fmtJS(val, el.dataset.fmt);
                }
            };
            form.addEventListener('input', run);
            run();
        }
    </script>
</head>
<body>
<nav class="navbar navbar-expand navbar-dark bg-dark mb-3">
    <div class="container-fluid">
        <a class="navbar-brand" href="?"><i class="bi bi-droplet-half"></i> Módulo Rendimento</a>
        <ul class="navbar-nav">
            <?php foreach ($nav as $k => [$label, $icon]): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $k === $active ? 'active fw-semibold' : '' ?>" href="<?= url(['e' => $k]) ?>">
                        <i class="bi <?= $icon ?>"></i> <?= $label ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</nav>
<main class="container-fluid pb-4">
    <?php foreach (take_flashes() as [$type, $msg]): ?>
        <div class="alert alert-<?= h($type) ?> alert-dismissible fade show">
            <?= h($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endforeach;
}

function layout_end(): void
{
    ?>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
    <?php
}
