<?php
/** Funções utilitárias: escape, formatação pt-BR, URLs, redirecionamento e mensagens. */

function h($v): string
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

/** Formata um valor para exibição (pt-BR). */
function fmt($v, string $type): string
{
    if ($v === null || $v === '') {
        return '';
    }
    return match ($type) {
        'money' => 'R$ ' . number_format((float)$v, 2, ',', '.'),
        'dec'   => number_format((float)$v, 2, ',', '.'),
        'dec6'  => number_format((float)$v, 6, ',', '.'),
        'int'   => number_format((float)$v, 0, ',', '.'),
        'pct'   => number_format((float)$v, 2, ',', '.') . '%',
        'frac'  => number_format((float)$v * 100, 2, ',', '.') . '%', // fração (0,34 → 34%)
        'date'  => ($d = DateTime::createFromFormat('Y-m-d', (string)$v)) ? $d->format('d/m/Y') : (string)$v,
        default => (string)$v,
    };
}

function url(array $params): string
{
    return '?' . http_build_query($params);
}

function redirect(array $params): never
{
    header('Location: ' . url($params));
    exit;
}

function flash(string $type, string $msg): void
{
    $_SESSION['flash'][] = [$type, $msg];
}

function take_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function require_post(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Método não permitido');
    }
}

/** Converte texto digitado em número (aceita vírgula decimal); vazio vira null. */
function numero_ou_null(?string $v): ?float
{
    $v = trim((string)$v);
    return $v === '' ? null : (float)str_replace(',', '.', $v);
}

function texto_ou_null(?string $v): ?string
{
    $v = trim((string)$v);
    return $v === '' ? null : $v;
}
