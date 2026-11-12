<?php
/** Compras: acesso ao banco. */

function compras_migrar(PDO $pdo): void
{
    db_sincronizar_tabela($pdo, 'compras', compras_campos());
}

function compras_listar(string $busca = ''): array
{
    [$where, $params] = campos_busca_sql(compras_campos(), $busca);
    $st = db()->prepare("SELECT * FROM compras $where ORDER BY data_producao DESC, id DESC");
    $st->execute($params);
    return $st->fetchAll();
}

function compras_buscar(int $id): ?array
{
    return db_buscar('compras', $id);
}

/** Insere (id = 0) ou atualiza a compra, recalculando os campos derivados. Devolve o id. */
function compras_salvar(array $dados, int $id = 0): int
{
    $dados = compras_calcular($dados);
    if ($id) {
        db_atualizar('compras', $id, $dados);
        return $id;
    }
    return db_inserir('compras', $dados);
}

/** Exclui a compra. As linhas de rendimento vinculadas ficam sem compra (ON DELETE SET NULL). */
function compras_excluir(int $id): void
{
    db_excluir('compras', $id);
}
