<?php
/** Planilha de Rendimento: telas de listagem, cadastro e exportação CSV. */

const RENDIMENTO_MESES = ['01' => 'Jan', '02' => 'Fev', '03' => 'Mar', '04' => 'Abr', '05' => 'Mai', '06' => 'Jun',
                          '07' => 'Jul', '08' => 'Ago', '09' => 'Set', '10' => 'Out', '11' => 'Nov', '12' => 'Dez'];

function rendimento_tela_lista(array $linhas, array $filtros, array $anos, array $fabricas, int $pendentes): void
{
    $cols = rendimento_colunas();

    // Resumo
    $totLatas = array_sum(array_map(fn($r) => (float)$r['latas'], $linhas));
    $totProd = array_sum(array_map(fn($r) => (float)$r['produzido'], $linhas));
    $abaixo = count(array_filter($linhas, 'rendimento_abaixo_padrao'));

    layout_start('Rendimento', 'rendimento');
    ?>
    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
        <h4 class="me-auto mb-0"><i class="bi bi-graph-up-arrow"></i> Planilha de Rendimento
            <small class="text-muted fs-6">(<?= count($linhas) ?> linhas)</small></h4>
        <a class="btn btn-sm btn-outline-success" href="<?= url(['e' => 'rendimento', 'a' => 'export'] + array_filter($filtros)) ?>">
            <i class="bi bi-filetype-csv"></i> Exportar CSV</a>
        <a class="btn btn-sm btn-primary" href="<?= url(['e' => 'rendimento', 'a' => 'form']) ?>"><i class="bi bi-plus-lg"></i> Nova linha</a>
    </div>

    <?php if ($pendentes): ?>
        <div class="alert alert-warning py-2">
            <i class="bi bi-exclamation-triangle"></i> Há <strong><?= $pendentes ?></strong> produção(ões) ainda sem linha de rendimento.
            <a href="<?= url(['e' => 'producao']) ?>">Ver produções</a>
        </div>
    <?php endif; ?>

    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3"><div class="card card-body py-2"><small class="text-muted">Total latas</small><strong><?= fmt($totLatas, 'int') ?></strong></div></div>
        <div class="col-6 col-md-3"><div class="card card-body py-2"><small class="text-muted">Total produzido (kg)</small><strong><?= fmt($totProd, 'dec') ?></strong></div></div>
        <div class="col-6 col-md-3"><div class="card card-body py-2"><small class="text-muted">Rendimento médio (ponderado)</small><strong><?=
            $totLatas > 0 ? fmt($totProd / $totLatas, 'dec') . ' kg/lata' : '—' ?></strong></div></div>
        <div class="col-6 col-md-3"><div class="card card-body py-2"><small class="text-muted">Abaixo do padrão</small><strong class="<?= $abaixo ? 'text-danger' : '' ?>"><?= $abaixo ?></strong></div></div>
    </div>

    <form class="row g-2 align-items-end mb-3" method="get">
        <input type="hidden" name="e" value="rendimento">
        <div class="col-auto">
            <label class="form-label small mb-0">Ano</label>
            <select class="form-select form-select-sm" name="ano">
                <option value="">Todos</option>
                <?php foreach ($anos as $a): ?><option <?= $filtros['ano'] === $a ? 'selected' : '' ?>><?= h($a) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto">
            <label class="form-label small mb-0">Mês</label>
            <select class="form-select form-select-sm" name="mes">
                <option value="">Todos</option>
                <?php foreach (RENDIMENTO_MESES as $n => $m): ?><option value="<?= $n ?>" <?= $filtros['mes'] === $n ? 'selected' : '' ?>><?= $m ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto">
            <label class="form-label small mb-0">Fábrica</label>
            <select class="form-select form-select-sm" name="fabrica">
                <option value="">Todas</option>
                <?php foreach ($fabricas as $f): ?><option <?= $filtros['fabrica'] === $f ? 'selected' : '' ?>><?= h($f) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto">
            <label class="form-label small mb-0">Busca</label>
            <input class="form-control form-control-sm" name="q" value="<?= h($filtros['q']) ?>" placeholder="Lote, NF, produto, fornecedor...">
        </div>
        <div class="col-auto">
            <button class="btn btn-sm btn-secondary"><i class="bi bi-funnel"></i> Filtrar</button>
            <a class="btn btn-sm btn-link" href="<?= url(['e' => 'rendimento']) ?>">Limpar</a>
        </div>
        <!--
        <div class="col-auto ms-auto legend small">
            Origem: <span style="background:#1d4e89">Produção</span>
            <span style="background:#1e6b45">Compras</span>
            <span style="background:#8a5a00">Campo próprio</span>
            <span style="background:#5b3c88">Cálculo</span>
            <button type="button" class="btn btn-sm btn-outline-secondary ms-2" id="btn-calc"><i class="bi bi-calculator"></i> <span>Ocultar cálculos</span></button>
        </div>
                -->
    </form>

    <div class="table-wrap">
        <table class="table table-sm table-striped table-hover align-middle" id="tbl-rend">
            <thead>
            <tr>
                <?php foreach ($cols as $c): ?>
                    <th class="src-<?= $c[3] ?>" <?= isset($c[4]) ? 'title="' . h($c[4]) . '"' : '' ?>><?= h($c[0]) ?></th>
                <?php endforeach; ?>
                <th class="actions text-end">Ações</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$linhas) linha_vazia(count($cols) + 1, 'Nenhuma linha. Cadastre produções e compras e gere as linhas de rendimento.'); ?>
            <?php foreach ($linhas as $r):
                $abaixoPadrao = rendimento_abaixo_padrao($r);
                $nfDiverge = $r['compra_id'] && $r['p_nf'] && $r['nota_amarela'] && $r['p_nf'] !== $r['nota_amarela'];
                ?>
                <tr>
                    <?php foreach ($cols as [, $chave, $formato, $origem]):
                        $cls = formato_numerico($formato) ? 'num' : '';
                        $cls .= $origem === 'X' ? ' c-X' : '';
                        if ($chave === 'rend' && $r['rendimento_padrao'] !== null && $r['rend'] !== null) {
                            $cls .= $abaixoPadrao ? ' text-danger fw-bold' : ' text-success fw-bold';
                        }
                        $extra = '';
                        if ($chave === 'nota_amarela' && $nfDiverge) {
                            $extra = ' <i class="bi bi-exclamation-circle text-warning" title="NF da produção (' . h($r['p_nf']) . ') diferente da compra"></i>';
                        }
                        if ($chave === 'nota_amarela' && !$r['compra_id']) {
                            $extra = '<span class="badge text-bg-secondary">sem compra</span>';
                        }
                        ?>
                        <td class="<?= $cls ?>"><?= h(fmt($r[$chave] ?? null, $formato)) . $extra ?></td>
                    <?php endforeach; ?>
                    <td class="actions text-end">
                        <?php botao_editar('rendimento', (int)$r['id']) ?>
                        <?php botao_excluir('rendimento', (int)$r['id'], 'Excluir esta linha da planilha? (Produção e Compra não são apagadas)') ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <script>
        (() => {
            const tbl = document.getElementById('tbl-rend');
            const btn = document.getElementById('btn-calc');
            const apply = (hide) => {
                tbl.classList.toggle('hide-calc', hide);
                btn.querySelector('span').textContent = hide ? 'Mostrar cálculos' : 'Ocultar cálculos';
            };
            let hide = false;
            try { hide = localStorage.getItem('rend-hide-calc') === '1'; } catch (e) {}
            apply(hide);
            btn.addEventListener('click', () => {
                hide = !hide;
                apply(hide);
                try { localStorage.setItem('rend-hide-calc', hide ? '1' : '0'); } catch (e) {}
            });
        })();
    </script>
    <?php
    layout_end();
}

function rendimento_tela_form(array $linha, array $producoes, array $compras, array $erros = []): void
{
    $id = (int)($linha['id'] ?? 0);

    $pInfo = fn($p) => [
        'Data' => fmt($p['data'], 'date'), 'Lote' => $p['lote'], 'Código' => $p['codigo'], 'Produto' => $p['produto'],
        'NF Amarela' => $p['nf_amarela'], 'Latas' => fmt($p['latas'], 'int'), 'ORG-GOV' => $p['org_gov'], 'Tipo' => $p['tipo'],
        'Produzido' => fmt($p['produzido'], 'dec'), 'Sólido' => fmt($p['solido'], 'dec'), 'Padrão cor' => fmt($p['padrao_cor'], 'dec'),
        'Cor' => fmt($p['cor_envase'], 'dec'), 'Sabor' => $p['sabor'], 'Rendimento real' => fmt($p['rendimento'], 'dec') . ' kg/lata',
    ];
    $cInfo = fn($c) => [
        'Data' => fmt($c['data_producao'], 'date'), 'NF Amarela' => $c['nota_amarela'], 'Fornecedor' => $c['fornecedor'],
        'Região' => $c['regiao'], 'Líquido latas' => fmt($c['liquido_latas'], 'dec'), 'Preço 14kg' => fmt($c['preco_lata'], 'money'),
        'Frete (total)' => fmt($c['frete'], 'money'),
        'Linhas usando' => $c['linhas'] . ($c['linhas'] > 0 ? ' (frete rateado pelas latas se > 1)' : ''),
    ];

    layout_start('Rendimento', 'rendimento');
    ?>
    <div class="d-flex align-items-center mb-3">
        <h4 class="me-auto mb-0"><i class="bi bi-graph-up-arrow"></i> <?= $id ? "Editar linha de rendimento #$id" : 'Nova linha de rendimento' ?></h4>
        <a class="btn btn-sm btn-outline-secondary" href="<?= url(['e' => 'rendimento']) ?>"><i class="bi bi-arrow-left"></i> Voltar</a>
    </div>

    <?php alertas_erro($erros) ?>

    <form method="post" action="<?= url(['e' => 'rendimento', 'a' => 'save']) ?>" id="rend-form">
        <input type="hidden" name="id" value="<?= $id ?>">
        <div class="row g-3">
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header text-white" style="background:#1d4e89"><i class="bi bi-gear-wide-connected"></i> Produção</div>
                    <div class="card-body">
                        <select class="form-select mb-3" name="producao_id" id="sel-producao" required>
                            <option value="">Selecione a produção...</option>
                            <?php foreach ($producoes as $p): ?>
                                <option value="<?= $p['id'] ?>" data-nf="<?= h($p['nf_amarela']) ?>"
                                        data-info="<?= h(json_encode($pInfo($p), JSON_UNESCAPED_UNICODE)) ?>"
                                    <?= (int)($linha['producao_id'] ?? 0) === (int)$p['id'] ? 'selected' : '' ?>>
                                    <?= h(fmt($p['data'], 'date') . ' · Lote ' . $p['lote'] . ' · ' . $p['produto'] . ($p['nf_amarela'] ? ' · NF ' . $p['nf_amarela'] : '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <dl class="preview row mb-0" id="prev-producao"></dl>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header text-white" style="background:#1e6b45"><i class="bi bi-cart3"></i> Compra</div>
                    <div class="card-body">
                        <div class="input-group mb-3">
                            <select class="form-select" name="compra_id" id="sel-compra">
                                <option value="">(sem compra vinculada)</option>
                                <?php foreach ($compras as $c): ?>
                                    <option value="<?= $c['id'] ?>" data-nf="<?= h($c['nota_amarela']) ?>"
                                            data-info="<?= h(json_encode($cInfo($c), JSON_UNESCAPED_UNICODE)) ?>"
                                        <?= (int)($linha['compra_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>>
                                        <?= h(($c['nota_amarela'] ? 'NF ' . $c['nota_amarela'] : 'Sem NF') . ' · ' . $c['fornecedor'] . ' · ' . fmt($c['data_producao'], 'date')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="btn btn-outline-success" id="btn-match" title="Vincular pela NF Amarela da produção"><i class="bi bi-link-45deg"></i> NF</button>
                        </div>
                        <div id="match-msg" class="small mb-2"></div>
                        <dl class="preview row mb-0" id="prev-compra"></dl>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header text-white" style="background:#8a5a00"><i class="bi bi-pencil-square"></i> Campos próprios</div>
                    <div class="card-body">
                        <?php foreach (rendimento_campos_texto() as $nome => $rotulo): ?>
                            <div class="mb-3">
                                <label class="form-label"><?= h($rotulo) ?></label>
                                <input class="form-control" name="<?= $nome ?>" value="<?= h($linha[$nome] ?? '') ?>" list="dl-<?= $nome ?>" autocomplete="off">
                                <?= datalist("dl-$nome", db_distintos('rendimento', $nome)) ?>
                            </div>
                        <?php endforeach; ?>
                        <div class="mb-3">
                            <label class="form-label">Rendimento Padrão</label>
                            <div class="input-group">
                                <input type="number" step="any" class="form-control" name="rendimento_padrao" value="<?= h($linha['rendimento_padrao'] ?? '') ?>" placeholder="ex.: 7,00">
                                <span class="input-group-text">kg/lata</span>
                            </div>
                            <div class="form-text">Comparado com o rendimento real (Produzido ÷ Latas).</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Frete Macapá</label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="number" step="any" class="form-control" name="frete_macapa" value="<?= h($linha['frete_macapa'] ?? '') ?>">
                            </div>
                            <div class="form-text">Opcional. Somado em "Negociado + Frete/Rendimento".</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-3 d-flex gap-2">
            <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Salvar</button>
            <a class="btn btn-outline-secondary" href="<?= url(['e' => 'rendimento']) ?>">Cancelar</a>
        </div>
    </form>

    <script>
        (() => {
            const selP = document.getElementById('sel-producao');
            const selC = document.getElementById('sel-compra');
            const msg = document.getElementById('match-msg');
            const esc = (s) => String(s ?? '').replace(/[&<>"]/g, (c) => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;'}[c]));

            function preview(sel, target) {
                const opt = sel.selectedOptions[0];
                const info = opt && opt.dataset.info ? JSON.parse(opt.dataset.info) : null;
                document.getElementById(target).innerHTML = info
                    ? Object.entries(info).map(([k, v]) => `<dt class="col-5">${esc(k)}</dt><dd class="col-7">${esc(v) || '—'}</dd>`).join('')
                    : '<dd class="text-muted">Nada selecionado.</dd>';
            }

            function matchByNF(auto) {
                const nf = selP.selectedOptions[0]?.dataset.nf;
                if (!nf) { if (!auto) msg.innerHTML = '<span class="text-muted">A produção não tem NF Amarela.</span>'; return; }
                const found = [...selC.options].find((o) => o.dataset.nf && o.dataset.nf === nf);
                if (found) {
                    selC.value = found.value;
                    msg.innerHTML = `<span class="text-success"><i class="bi bi-check-circle"></i> Compra encontrada pela NF ${esc(nf)}.</span>`;
                } else {
                    msg.innerHTML = `<span class="text-warning"><i class="bi bi-exclamation-triangle"></i> Nenhuma compra com NF ${esc(nf)}.</span>`;
                }
                preview(selC, 'prev-compra');
            }

            selP.addEventListener('change', () => { preview(selP, 'prev-producao'); matchByNF(true); });
            selC.addEventListener('change', () => { msg.innerHTML = ''; preview(selC, 'prev-compra'); });
            document.getElementById('btn-match').addEventListener('click', () => matchByNF(false));

            preview(selP, 'prev-producao');
            preview(selC, 'prev-compra');
            if (selP.value && !selC.value) matchByNF(true);
        })();
    </script>
    <?php
    layout_end();
}

/** Envia a planilha em CSV (separador ; e BOM UTF-8, para abrir direto no Excel). */
function rendimento_tela_csv(array $linhas): never
{
    $cols = rendimento_colunas();

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="rendimento_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, array_column($cols, 0), ';');
    foreach ($linhas as $r) {
        $linha = [];
        foreach ($cols as [, $chave, $formato]) {
            $v = $r[$chave] ?? null;
            $linha[] = match (true) {
                $v === null || $v === '' => '',
                $formato === 'date' => fmt($v, 'date'),
                $formato === 'frac' => number_format((float)$v * 100, 2, ',', ''),
                in_array($formato, ['dec', 'money', 'pct'], true) => number_format((float)$v, 2, ',', ''),
                $formato === 'dec6' => number_format((float)$v, 6, ',', ''),
                default => $v,
            };
        }
        fputcsv($out, $linha, ';');
    }
    fclose($out);
    exit;
}
