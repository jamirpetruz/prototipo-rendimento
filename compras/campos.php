<?php
/**
 * Compras: definição dos campos e fórmulas.
 * Para incluir um campo novo basta adicioná-lo aqui (a coluna é criada automaticamente no SQLite).
 */

function compras_campos(): array
{
    return [
        'data_producao'      => ['label' => 'Data de Produção', 'type' => 'date', 'required' => true],
        'base'               => ['label' => 'Base', 'type' => 'text'],
        'codigo'             => ['label' => 'Cód.', 'type' => 'text'],
        'fornecedor'         => ['label' => 'Fornecedor', 'type' => 'text', 'required' => true],
        'porto'              => ['label' => 'Porto', 'type' => 'text'],
        'regiao'             => ['label' => 'Região', 'type' => 'text'],
        'tipo'               => ['label' => 'Tipo', 'type' => 'text'],
        'certificacao'       => ['label' => 'Certificação', 'type' => 'text'],
        'motorista'          => ['label' => 'Motorista', 'type' => 'text'],
        'previsto_latas'     => ['label' => 'Previsto Latas', 'type' => 'int'],
        'realizado'          => ['label' => 'Realizado', 'type' => 'int'],
        'liquido_latas'      => ['label' => 'Líquido de Latas 14kg', 'type' => 'dec'],
        'preco_lata'         => ['label' => 'Preço Lata 14kg', 'type' => 'money'],
        'total'              => ['label' => 'Total', 'type' => 'calc', 'fmt' => 'money', 'hint' => 'Líquido × Preço lata'],
        'nota_amarela'       => ['label' => 'Nota Amarela', 'type' => 'text'],
        'frete'              => ['label' => 'Frete', 'type' => 'money', 'hint' => 'Valor total do frete da carga'],
        'preco_lata_frete'   => ['label' => 'Preço Lata + Frete', 'type' => 'calc', 'fmt' => 'money', 'hint' => 'Preço lata + (Frete ÷ Líquido)'],
        'total_frete'        => ['label' => 'Total + Frete', 'type' => 'calc', 'fmt' => 'money', 'hint' => 'Total + Frete'],
        'qtd_kg_pedido'      => ['label' => 'Quantidade Kg Pedido', 'type' => 'dec'],
        'preco_kg_pedido'    => ['label' => 'Preço de Kg Pedido', 'type' => 'calc', 'fmt' => 'dec6', 'hint' => 'Preço lata ÷ 14'],
        'responsavel_compra' => ['label' => 'Responsável Compra', 'type' => 'text'],
        'status_nf'          => ['label' => 'Status NF', 'type' => 'select', 'options' => ['Pendente', 'Emitida', 'Recebida', 'Lançada', 'Cancelada']],
        'media'              => ['label' => 'Média', 'type' => 'dec'],
        'meta_previsto'      => ['label' => 'Meta do Previsto', 'type' => 'dec'],
        'falta_passando'     => ['label' => 'Falta / Passando', 'type' => 'calc', 'fmt' => 'int', 'hint' => 'Realizado − Previsto'],
    ];
}

/** Campos calculados, gravados junto com a compra (mesmas fórmulas do JS em compras/tela.php). */
function compras_calcular(array $d): array
{
    $n = fn(string $k): float => (float)($d[$k] ?? 0);

    $freteLata             = $n('liquido_latas') > 0 ? $n('frete') / $n('liquido_latas') : 0;
    $d['total']            = $n('liquido_latas') * $n('preco_lata');
    $d['preco_lata_frete'] = $n('preco_lata') + $freteLata;
    $d['total_frete']      = $d['total'] + $n('frete');
    $d['preco_kg_pedido']  = $n('preco_lata') / 14;
    $d['falta_passando']   = $n('realizado') - $n('previsto_latas');

    return $d;
}
