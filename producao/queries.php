<?php
/** Produção: acesso ao banco. */

function producao_migrar(PDO $pdo): void
{
    db_sincronizar_tabela($pdo, 'producao', producao_campos());
}

/** Produções com o id da linha de rendimento correspondente (rendimento_id, null se ainda não gerada). */
function producao_listar(string $busca = ''): array
{
    [$where, $params] = campos_busca_sql(producao_campos(), $busca, 'p');
    $st = db()->prepare("SELECT p.*, r.id AS rendimento_id
                         FROM producao p
                         LEFT JOIN rendimento r ON r.producao_id = p.id
                         $where
                         ORDER BY p.data DESC, p.id DESC");
    $st->execute($params);
    return $st->fetchAll();
}

function producao_buscar(int $id): ?array
{
    return db_buscar('producao', $id);
}

/** Insere (id = 0) ou atualiza a produção, recalculando os campos derivados. Devolve o id. */
function producao_salvar(array $dados, int $id = 0): int
{
    $dados = producao_calcular($dados);
    if ($id) {
        db_atualizar('producao', $id, $dados);
        return $id;
    }
    return db_inserir('producao', $dados);
}

/** Exclui a produção. Devolve false se ela já tiver linha na planilha de rendimento (ON DELETE RESTRICT). */
function producao_excluir(int $id): bool
{
    try {
        db_excluir('producao', $id);
        return true;
    } catch (PDOException) {
        return false;
    }
}
