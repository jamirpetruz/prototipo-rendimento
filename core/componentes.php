<?php
/**
 * Componentes reutilizados pelas telas e ações dos módulos, gerados a partir da definição de campos
 * (<modulo>/campos.php). Tipos: text, date, int, dec, money, select, textarea, calc.
 */

/** Formato de exibição do campo (campos calculados usam a chave 'fmt'). */
function campo_formato(array $f): string
{
    return $f['type'] === 'calc' ? $f['fmt'] : $f['type'];
}

function formato_numerico(string $formato): bool
{
    return in_array($formato, ['int', 'dec', 'dec6', 'money', 'pct', 'frac'], true);
}

// ---------------------------------------------------------------- tabelas

function campos_cabecalho(array $campos): void
{
    foreach ($campos as $f) {
        $title = isset($f['hint']) ? ' title="' . h($f['hint']) . '"' : '';
        echo "<th$title>" . h(mb_strtoupper($f['label'])) . '</th>';
    }
}

/** Células da linha; $classes permite destacar campos específicos (ex.: ['campo' => 'text-danger']). */
function campos_celulas(array $campos, array $linha, array $classes = []): void
{
    foreach ($campos as $nome => $f) {
        $formato = campo_formato($f);
        $cls = trim((formato_numerico($formato) ? 'num ' : '') . ($classes[$nome] ?? ''));
        echo '<td class="' . $cls . '">' . h(fmt($linha[$nome] ?? null, $formato)) . '</td>';
    }
}

function linha_vazia(int $colunas, string $mensagem = 'Nenhum registro.'): void
{
    echo '<tr><td colspan="' . $colunas . '" class="text-center text-muted py-4">' . h($mensagem) . '</td></tr>';
}

function botao_editar(string $modulo, int $id): void
{
    echo '<a class="btn btn-sm btn-outline-primary" title="Editar" href="' . url(['e' => $modulo, 'a' => 'form', 'id' => $id]) . '"><i class="bi bi-pencil"></i></a> ';
}

function botao_excluir(string $modulo, int $id, string $confirmacao = 'Excluir este registro?'): void
{
    ?>
    <form method="post" action="<?= url(['e' => $modulo, 'a' => 'delete']) ?>" class="d-inline" onsubmit="return confirm(<?= h(json_encode($confirmacao, JSON_UNESCAPED_UNICODE)) ?>)">
        <input type="hidden" name="id" value="<?= $id ?>">
        <button class="btn btn-sm btn-outline-danger" title="Excluir"><i class="bi bi-trash"></i></button>
    </form>
    <?php
}

function alertas_erro(array $erros): void
{
    foreach ($erros as $erro) {
        echo '<div class="alert alert-danger py-2">' . h($erro) . '</div>';
    }
}

// ---------------------------------------------------------------- formulários

function datalist(string $id, array $valores): string
{
    $html = '<datalist id="' . h($id) . '">';
    foreach ($valores as $v) {
        $html .= '<option value="' . h($v) . '">';
    }
    return $html . '</datalist>';
}

/** Inputs do formulário (campos de texto sugerem valores já cadastrados na $tabela). */
function campos_inputs(string $tabela, array $campos, array $linha): void
{
    foreach ($campos as $nome => $f):
        $val = $linha[$nome] ?? '';
        $req = !empty($f['required']);
        $label = h($f['label']) . ($req ? ' <span class="text-danger">*</span>' : '');
        $hint = isset($f['hint']) ? '<div class="form-text">' . h($f['hint']) . '</div>' : '';
        ?>
        <?php if ($f['type'] === 'textarea'): ?>
            <div class="col-12">
                <label class="form-label"><?= $label ?></label>
                <textarea class="form-control" name="<?= $nome ?>" rows="3"><?= h($val) ?></textarea>
            </div>
        <?php else: ?>
            <div class="col-sm-6 col-md-4 col-xl-3">
                <label class="form-label"><?= $label ?></label>
                <?php switch ($f['type']):
                    case 'calc': ?>
                        <input class="form-control calc" readonly tabindex="-1" data-calc="<?= $nome ?>" data-fmt="<?= $f['fmt'] ?>" value="<?= h(fmt($val, $f['fmt'])) ?>">
                        <?php break;
                    case 'select': ?>
                        <select class="form-select" name="<?= $nome ?>" <?= $req ? 'required' : '' ?>>
                            <option value="">—</option>
                            <?php foreach ($f['options'] as $opt): ?>
                                <option <?= (string)$val === $opt ? 'selected' : '' ?>><?= h($opt) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php break;
                    case 'date': ?>
                        <input type="date" class="form-control" name="<?= $nome ?>" value="<?= h($val) ?>" <?= $req ? 'required' : '' ?>>
                        <?php break;
                    case 'int': case 'dec': case 'money': ?>
                        <div class="input-group">
                            <?php if ($f['type'] === 'money'): ?><span class="input-group-text">R$</span><?php endif; ?>
                            <input type="number" class="form-control" name="<?= $nome ?>" value="<?= h($val) ?>"
                                   step="<?= $f['type'] === 'int' ? '1' : 'any' ?>" <?= $req ? 'required' : '' ?>>
                        </div>
                        <?php break;
                    default: ?>
                        <input class="form-control" name="<?= $nome ?>" value="<?= h($val) ?>" list="dl-<?= $nome ?>" autocomplete="off" <?= $req ? 'required' : '' ?>>
                        <?= datalist("dl-$nome", db_distintos($tabela, $nome)) ?>
                    <?php endswitch; ?>
                <?= $hint ?>
            </div>
        <?php endif;
    endforeach;
}

/** Lê do POST os campos editáveis (calculados são ignorados), convertendo números. */
function campos_ler_post(array $campos): array
{
    $dados = [];
    foreach ($campos as $nome => $f) {
        if ($f['type'] === 'calc') {
            continue;
        }
        $v = $_POST[$nome] ?? null;
        $dados[$nome] = match ($f['type']) {
            'int' => ($n = numero_ou_null($v)) === null ? null : (int)$n,
            'dec', 'money' => numero_ou_null($v),
            default => texto_ou_null($v),
        };
    }
    return $dados;
}

function campos_validar(array $campos, array $dados): array
{
    $erros = [];
    foreach ($campos as $nome => $f) {
        if (!empty($f['required']) && ($dados[$nome] ?? null) === null) {
            $erros[] = "Campo obrigatório: {$f['label']}";
        }
    }
    return $erros;
}

/** Cláusula WHERE de busca textual nos campos de texto/data: [sql, parâmetros]. */
function campos_busca_sql(array $campos, string $busca, string $alias = ''): array
{
    if ($busca === '') {
        return ['', []];
    }
    $likes = $params = [];
    foreach ($campos as $nome => $f) {
        if (in_array($f['type'], ['text', 'select', 'textarea', 'date'], true)) {
            $likes[] = ($alias ? "$alias." : '') . "$nome LIKE ?";
            $params[] = "%$busca%";
        }
    }
    return [' WHERE ' . implode(' OR ', $likes), $params];
}
