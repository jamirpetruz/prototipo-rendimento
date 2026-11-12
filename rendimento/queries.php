<?php
/** Planilha de Rendimento: acesso ao banco. */

function rendimento_migrar(PDO $pdo): void
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS rendimento (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        producao_id INTEGER NOT NULL UNIQUE REFERENCES producao(id) ON DELETE RESTRICT,
        compra_id INTEGER REFERENCES compras(id) ON DELETE SET NULL,
        fabrica TEXT,
        regiao_macro TEXT,
        nucleo_responsavel TEXT,
        rendimento_padrao REAL,
        frete_macapa REAL,
        criado_em TEXT DEFAULT CURRENT_TIMESTAMP,
        atualizado_em TEXT
    )');
    if (!in_array('frete_macapa', db_colunas($pdo, 'rendimento'), true)) {
        $pdo->exec('ALTER TABLE rendimento ADD COLUMN frete_macapa REAL');
    }
}

/**
 * Linhas da planilha com os dados de Produção e Compras e as colunas calculadas.
 * Os cálculos usam TODAS as linhas (somas por dia/fábrica, fábrica+tipo e frete rateado);
 * os filtros são aplicados depois, para não alterar os resultados.
 */
function rendimento_listar(array $filtros): array
{
    $linhas = db()->query("SELECT r.*,
                   p.data, p.lote, p.codigo AS p_codigo, p.produto, p.latas, p.org_gov, p.tipo AS p_tipo,
                   p.produzido, p.solido, p.padrao_cor, p.cor_envase, p.sabor,
                   p.nf_amarela AS p_nf, p.kg_in_natura,
                   c.nota_amarela, c.fornecedor AS c_fornecedor, c.preco_lata, c.frete, c.liquido_latas,
                   (SELECT COUNT(*) FROM rendimento r2 WHERE r2.compra_id = r.compra_id) AS compra_linhas
            FROM rendimento r
            JOIN producao p ON p.id = r.producao_id
            LEFT JOIN compras c ON c.id = r.compra_id")->fetchAll();

    foreach ($linhas as &$r) {
        [$r['ano'], $r['mes'], $r['dia']] = $r['data'] ? explode('-', $r['data']) + [null, null, null] : [null, null, null];
        [$r['frete_un'], $r['frete_div']] = rendimento_frete($r['frete'], (int)$r['compra_linhas']);
        $r['frete_grupo'] = $r['compra_id'];
        $r['tipo']        = $r['p_tipo'];
        $r['preco_14kg']  = $r['preco_lata'];
    }
    unset($r);

    $linhas = array_values(array_filter(rendimento_calcular($linhas), fn($r) => rendimento_filtro_aceita($r, $filtros)));
    usort($linhas, fn($a, $b) => [$b['data'], $b['id']] <=> [$a['data'], $a['id']]);
    return $linhas;
}

function rendimento_filtro_aceita(array $r, array $f): bool
{
    if ($f['ano'] !== '' && $r['ano'] !== $f['ano']) return false;
    if ($f['mes'] !== '' && $r['mes'] !== str_pad($f['mes'], 2, '0', STR_PAD_LEFT)) return false;
    if ($f['fabrica'] !== '' && $r['fabrica'] !== $f['fabrica']) return false;
    if ($f['q'] !== '') {
        $texto = implode(' ', [$r['lote'], $r['produto'], $r['p_codigo'], $r['nota_amarela'], $r['c_fornecedor'], $r['nucleo_responsavel'], $r['regiao_macro'], $r['sabor']]);
        if (mb_stripos($texto, $f['q']) === false) return false;
    }
    return true;
}

/**
 * Frete da compra vinculada, como na aba DataBase:
 * - FRETE UN : a compra está ligada só a esta linha → o frete inteiro é desta produção;
 * - FRETE DIV: a compra está ligada a várias linhas → o frete é rateado pelas latas (ver FRETE/LATA).
 */
function rendimento_frete($frete, int $linhasDaCompra): array
{
    if ($frete === null || $linhasDaCompra === 0) {
        return [null, null];
    }
    return $linhasDaCompra > 1 ? [null, $frete] : [$frete, null];
}

function rendimento_buscar(int $id): ?array
{
    return db_buscar('rendimento', $id);
}

/** Insere (id = 0) ou atualiza a linha. Lança PDOException se a produção já tiver linha. */
function rendimento_salvar(array $dados, int $id = 0): int
{
    if ($id) {
        db_atualizar('rendimento', $id, $dados);
        return $id;
    }
    return db_inserir('rendimento', $dados);
}

/** Remove a linha da planilha (Produção e Compra não são apagadas). */
function rendimento_excluir(int $id): void
{
    db_excluir('rendimento', $id);
}

/** Produções que podem ser escolhidas: as que ainda não têm linha + a da linha $id. */
function rendimento_producoes_disponiveis(int $id): array
{
    $st = db()->prepare('SELECT * FROM producao WHERE id NOT IN (SELECT producao_id FROM rendimento WHERE id <> ?) ORDER BY data DESC, id DESC');
    $st->execute([$id]);
    return $st->fetchAll();
}

/** Compras com a quantidade de OUTRAS linhas que já usam cada uma (linhas > 0 → frete rateado). */
function rendimento_compras_disponiveis(int $id): array
{
    $st = db()->prepare('SELECT c.*, (SELECT COUNT(*) FROM rendimento r WHERE r.compra_id = c.id AND r.id <> ?) AS linhas
                         FROM compras c ORDER BY data_producao DESC, id DESC');
    $st->execute([$id]);
    return $st->fetchAll();
}

function rendimento_anos(): array
{
    return db()->query("SELECT DISTINCT strftime('%Y', data) FROM producao WHERE data IS NOT NULL ORDER BY 1 DESC")
        ->fetchAll(PDO::FETCH_COLUMN);
}

function rendimento_fabricas(): array
{
    return db_distintos('rendimento', 'fabrica');
}

/** Quantidade de produções ainda sem linha na planilha. */
function rendimento_producoes_pendentes(): int
{
    return (int)db()->query('SELECT COUNT(*) FROM producao WHERE id NOT IN (SELECT producao_id FROM rendimento)')->fetchColumn();
}
