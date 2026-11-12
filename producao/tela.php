<?php
/** Produção: telas de listagem e cadastro. */

function producao_tela_lista(array $producoes, string $busca): void
{
    $campos = producao_campos();
    layout_start('Produção', 'producao');
    ?>
    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
        <h4 class="me-auto mb-0"><i class="bi bi-gear-wide-connected"></i> Produção
            <small class="text-muted fs-6">(<?= count($producoes) ?> registros)</small></h4>
        <form class="d-flex gap-2" method="get">
            <input type="hidden" name="e" value="producao">
            <input class="form-control form-control-sm" name="q" value="<?= h($busca) ?>" placeholder="Buscar...">
            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
        </form>
        <a class="btn btn-sm btn-primary" href="<?= url(['e' => 'producao', 'a' => 'form']) ?>"><i class="bi bi-plus-lg"></i> Nova produção</a>
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
            <?php if (!$producoes) linha_vazia(count($campos) + 2); ?>
            <?php foreach ($producoes as $p): ?>
                <tr>
                    <td class="text-muted"><?= $p['id'] ?></td>
                    <?php campos_celulas($campos, $p) ?>
                    <td class="actions text-end">
                        <?php if ($p['rendimento_id']): ?>
                            <a class="btn btn-sm btn-outline-success" title="Ver na planilha de rendimento"
                               href="<?= url(['e' => 'rendimento', 'a' => 'form', 'id' => $p['rendimento_id']]) ?>"><i class="bi bi-graph-up-arrow"></i></a>
                        <?php else: ?>
                            <a class="btn btn-sm btn-warning" title="Gerar linha de rendimento"
                               href="<?= url(['e' => 'rendimento', 'a' => 'form', 'producao_id' => $p['id']]) ?>"><i class="bi bi-plus-circle"></i></a>
                        <?php endif; ?>
                        <?php botao_editar('producao', (int)$p['id']) ?>
                        <?php botao_excluir('producao', (int)$p['id'], 'Excluir esta produção?') ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
    layout_end();
}

function producao_tela_form(array $producao, array $erros = []): void
{
    $id = (int)($producao['id'] ?? 0);
    layout_start('Produção', 'producao');
    ?>
    <div class="d-flex align-items-center mb-3">
        <h4 class="me-auto mb-0"><i class="bi bi-gear-wide-connected"></i> <?= $id ? "Editar produção #$id" : 'Nova produção' ?></h4>
        <a class="btn btn-sm btn-outline-secondary" href="<?= url(['e' => 'producao']) ?>"><i class="bi bi-arrow-left"></i> Voltar</a>
    </div>

    <?php alertas_erro($erros) ?>

    <form method="post" action="<?= url(['e' => 'producao', 'a' => 'save']) ?>" id="form-producao" class="card card-body">
        <input type="hidden" name="id" value="<?= $id ?>">
        <div class="row g-3">
            <?php campos_inputs('producao', producao_campos(), $producao) ?>
        </div>
        <div class="mt-4 d-flex gap-2">
            <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Salvar</button>
            <a class="btn btn-outline-secondary" href="<?= url(['e' => 'producao']) ?>">Cancelar</a>
        </div>
    </form>

    <script>
        // Mesmas fórmulas de producao_calcular() (producao/campos.php)
        ligarCalculos('form-producao', (v, form) => {
            const kgAuto = v.latas * 14;
            form.elements.kg_in_natura.placeholder = kgAuto ? kgAuto.toString() : '';
            return { rendimento: v.latas > 0 ? v.produzido / v.latas : null };
        });
    </script>
    <?php
    layout_end();
}
