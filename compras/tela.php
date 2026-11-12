<?php
/** Compras: telas de listagem e cadastro. */

function compras_tela_lista(array $compras, string $busca): void
{
    $campos = compras_campos();
    layout_start('Compras', 'compras');
    ?>
    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
        <h4 class="me-auto mb-0"><i class="bi bi-cart3"></i> Compras
            <small class="text-muted fs-6">(<?= count($compras) ?> registros)</small></h4>
        <form class="d-flex gap-2" method="get">
            <input type="hidden" name="e" value="compras">
            <input class="form-control form-control-sm" name="q" value="<?= h($busca) ?>" placeholder="Buscar...">
            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
        </form>
        <a class="btn btn-sm btn-primary" href="<?= url(['e' => 'compras', 'a' => 'form']) ?>"><i class="bi bi-plus-lg"></i> Nova compra</a>
    </div>

    <div class="table-wrap">
        <table class="table table-sm table-striped table-hover align-middle">
            <thead>
            <tr>
                <th>#</th>
                <?php campos_cabecalho($campos) ?>
                <th class="actions text-end">Ações</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$compras) linha_vazia(count($campos) + 2); ?>
            <?php foreach ($compras as $c):
                $falta = $c['falta_passando'] === null ? '' : ($c['falta_passando'] < 0 ? 'text-danger fw-semibold' : 'text-success fw-semibold');
                ?>
                <tr>
                    <td class="text-muted"><?= $c['id'] ?></td>
                    <?php campos_celulas($campos, $c, ['falta_passando' => $falta]) ?>
                    <td class="actions text-end">
                        <?php botao_editar('compras', (int)$c['id']) ?>
                        <?php botao_excluir('compras', (int)$c['id'], 'Excluir esta compra? As linhas de rendimento vinculadas ficarão sem compra.') ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
    layout_end();
}

function compras_tela_form(array $compra, array $erros = []): void
{
    $id = (int)($compra['id'] ?? 0);
    layout_start('Compras', 'compras');
    ?>
    <div class="d-flex align-items-center mb-3">
        <h4 class="me-auto mb-0"><i class="bi bi-cart3"></i> <?= $id ? "Editar compra #$id" : 'Nova compra' ?></h4>
        <a class="btn btn-sm btn-outline-secondary" href="<?= url(['e' => 'compras']) ?>"><i class="bi bi-arrow-left"></i> Voltar</a>
    </div>

    <?php alertas_erro($erros) ?>

    <form method="post" action="<?= url(['e' => 'compras', 'a' => 'save']) ?>" id="form-compras" class="card card-body">
        <input type="hidden" name="id" value="<?= $id ?>">
        <div class="row g-3">
            <?php campos_inputs('compras', compras_campos(), $compra) ?>
        </div>
        <div class="mt-4 d-flex gap-2">
            <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Salvar</button>
            <a class="btn btn-outline-secondary" href="<?= url(['e' => 'compras']) ?>">Cancelar</a>
        </div>
    </form>

    <script>
        // Mesmas fórmulas de compras_calcular() (compras/campos.php)
        ligarCalculos('form-compras', (v) => {
            const freteLata = v.liquido_latas > 0 ? v.frete / v.liquido_latas : 0;
            const total = v.liquido_latas * v.preco_lata;
            return {
                total,
                preco_lata_frete: v.preco_lata + freteLata,
                total_frete: total + v.frete,
                preco_kg_pedido: v.preco_lata / 14,
                falta_passando: v.realizado - v.previsto_latas,
            };
        });
    </script>
    <?php
    layout_end();
}
