<?php
/**
 * Produção: definição dos campos e fórmulas.
 * Para incluir um campo novo basta adicioná-lo aqui (a coluna é criada automaticamente no SQLite).
 */

function producao_campos(): array
{
    return [
        'data'         => ['label' => 'Data', 'type' => 'date', 'required' => true],
        'lote'         => ['label' => 'Lote', 'type' => 'text', 'required' => true],
        'codigo'       => ['label' => 'Cód.', 'type' => 'text'],
        'produto'      => ['label' => 'Produto', 'type' => 'text'],
        'motorista'    => ['label' => 'Motorista', 'type' => 'text'],
        'nf_amarela'   => ['label' => 'Nº NF Amarela', 'type' => 'text'],
        'fornecedor'   => ['label' => 'Fornecedor', 'type' => 'text'],
        'regiao'       => ['label' => 'Região', 'type' => 'text'],
        'latas'        => ['label' => 'Latas', 'type' => 'int'],
        'kg_in_natura' => ['label' => 'Kg In Natura', 'type' => 'dec', 'hint' => 'Se vazio: Latas × 14'],
        'org_gov'      => ['label' => 'ORG-GOV', 'type' => 'text'],
        'tipo'         => ['label' => 'Tipo', 'type' => 'text'],
        'produzido'    => ['label' => 'Produzido', 'type' => 'dec'],
        'sobra'        => ['label' => 'Sobra', 'type' => 'dec'],
        'rendimento'   => ['label' => 'Rendimento', 'type' => 'calc', 'fmt' => 'dec', 'hint' => 'Produzido ÷ Latas (kg por lata)'],
        'solido'       => ['label' => 'Sólido', 'type' => 'dec'],
        'padrao_cor'   => ['label' => 'Padrão Cor Qual.', 'type' => 'dec'],
        'cor_envase'   => ['label' => 'Cor Envase', 'type' => 'dec'],
        'sabor'        => ['label' => 'Sabor', 'type' => 'text'],
        'observacoes'  => ['label' => 'Observações', 'type' => 'textarea'],
    ];
}

/** Campos calculados, gravados junto com a produção (mesmas fórmulas do JS em producao/tela.php). */
function producao_calcular(array $d): array
{
    $latas = (float)($d['latas'] ?? 0);

    if (($d['kg_in_natura'] ?? null) === null && $latas > 0) {
        $d['kg_in_natura'] = $latas * 14;
    }
    // Rendimento = kg produzidos por lata (ex.: 6.219 ÷ 891 = 6,98)
    $d['rendimento'] = $latas > 0 ? (float)($d['produzido'] ?? 0) / $latas : null;

    return $d;
}
