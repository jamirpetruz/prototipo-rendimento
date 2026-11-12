<?php
/** Conexão com o SQLite e funções de SQL compartilhadas pelos módulos. */

/** Módulos do sistema, na ordem de criação das tabelas (rendimento depende de compras e produção). */
const MODULOS = ['compras', 'producao', 'rendimento'];

function db(): PDO
{
    static $pdo = null;
    if ($pdo) {
        return $pdo;
    }

    $dir = __DIR__ . '/../data';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $pdo = new PDO('sqlite:' . $dir . '/rendimento.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');

    // Cada módulo cria/atualiza a própria tabela em <modulo>/queries.php
    foreach (MODULOS as $modulo) {
        ($modulo . '_migrar')($pdo);
    }

    return $pdo;
}

function sql_type(string $type): string
{
    return match ($type) {
        'int' => 'INTEGER',
        'dec', 'money', 'calc' => 'REAL',
        default => 'TEXT',
    };
}

function db_colunas(PDO $pdo, string $tabela): array
{
    return array_column($pdo->query("PRAGMA table_info($tabela)")->fetchAll(), 'name');
}

/** Cria a tabela a partir da definição de campos e acrescenta colunas novas que ainda não existam. */
function db_sincronizar_tabela(PDO $pdo, string $tabela, array $campos): void
{
    $cols = ['id INTEGER PRIMARY KEY AUTOINCREMENT'];
    foreach ($campos as $nome => $f) {
        $cols[] = $nome . ' ' . sql_type($f['type']);
    }
    $cols[] = 'criado_em TEXT DEFAULT CURRENT_TIMESTAMP';
    $cols[] = 'atualizado_em TEXT';
    $pdo->exec("CREATE TABLE IF NOT EXISTS $tabela (" . implode(', ', $cols) . ')');

    $existentes = db_colunas($pdo, $tabela);
    foreach ($campos as $nome => $f) {
        if (!in_array($nome, $existentes, true)) {
            $pdo->exec("ALTER TABLE $tabela ADD COLUMN $nome " . sql_type($f['type']));
        }
    }
}

function db_buscar(string $tabela, int $id): ?array
{
    $st = db()->prepare("SELECT * FROM $tabela WHERE id = ?");
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

function db_inserir(string $tabela, array $dados): int
{
    $cols = array_keys($dados);
    db()->prepare("INSERT INTO $tabela (" . implode(', ', $cols) . ') VALUES (:' . implode(', :', $cols) . ')')
        ->execute($dados);
    return (int)db()->lastInsertId();
}

function db_atualizar(string $tabela, int $id, array $dados): void
{
    $set = implode(', ', array_map(fn($c) => "$c = :$c", array_keys($dados)));
    db()->prepare("UPDATE $tabela SET $set, atualizado_em = CURRENT_TIMESTAMP WHERE id = :id")
        ->execute($dados + ['id' => $id]);
}

function db_excluir(string $tabela, int $id): void
{
    db()->prepare("DELETE FROM $tabela WHERE id = ?")->execute([$id]);
}

/** Valores distintos já cadastrados numa coluna (sugestões de preenchimento). */
function db_distintos(string $tabela, string $coluna): array
{
    return db()->query("SELECT DISTINCT $coluna FROM $tabela WHERE $coluna IS NOT NULL AND $coluna <> '' ORDER BY $coluna LIMIT 100")
        ->fetchAll(PDO::FETCH_COLUMN);
}
