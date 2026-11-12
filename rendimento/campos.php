<?php
/**
 * Planilha de Rendimento: cada linha liga uma Produção (obrigatória) a uma Compra
 * (normalmente pela NF Amarela) e guarda os campos próprios abaixo.
 * As colunas calculadas (aba DataBase do Excel) ficam em rendimento/calculos.php.
 */

/** Campos próprios de texto (com sugestões de valores já usados). */
function rendimento_campos_texto(): array
{
    return [
        'fabrica'            => 'Fábrica',
        'regiao_macro'       => 'Região Macro',
        'nucleo_responsavel' => 'Núcleo Responsável',
    ];
}

/** Campos próprios numéricos. */
function rendimento_campos_numero(): array
{
    return ['rendimento_padrao', 'frete_macapa'];
}

/**
 * Colunas da planilha: [rótulo, chave, formato, origem, dica].
 * Origem: P = Produção, C = Compras, R = campo próprio, X = calculada.
 */
function rendimento_colunas(): array
{
    $cols = [
        ['DATA', 'data', 'date', 'P'],
        ['ANO', 'ano', 'text', 'P'],
        ['MÊS', 'mes', 'text', 'P'],
        ['DIA', 'dia', 'text', 'P'],
        ['FÁBRICA', 'fabrica', 'text', 'R'],
        ['REGIÃO MACRO', 'regiao_macro', 'text', 'R'],
        ['NF AMARELA', 'nota_amarela', 'text', 'C'],
        ['LOTE', 'lote', 'text', 'P'],
        ['CÓDIGO', 'p_codigo', 'text', 'P'],
        ['PRODUTO', 'produto', 'text', 'P'],
        ['FORNECEDOR', 'c_fornecedor', 'text', 'C'],
        ['NÚCLEO RESPONSÁVEL', 'nucleo_responsavel', 'text', 'R'],
        ['LATAS', 'latas', 'int', 'P'],
        ['ORG-GOV', 'org_gov', 'text', 'P'],
        ['TIPO', 'p_tipo', 'text', 'P'],
        ['PRODUÇÃO', 'produzido', 'dec', 'P'],
        ['SÓLIDO', 'solido', 'dec', 'P'],
        ['PADRÃO COR', 'padrao_cor', 'dec', 'P'],
        ['COR', 'cor_envase', 'dec', 'P'],
        ['SABOR', 'sabor', 'text', 'P'],
        ['PREÇO 14KG', 'preco_lata', 'money', 'C'],
        ['FRETE UN', 'frete_un', 'money', 'C'],
        ['FRETE DIV', 'frete_div', 'money', 'C'],
        ['FRETE MACAPÁ', 'frete_macapa', 'money', 'R'],
        ['RENDIMENTO PADRÃO', 'rendimento_padrao', 'dec', 'R'],
    ];
    foreach (rendimento_colunas_calculadas() as $chave => [$xl, $rotulo, $formato, $formula]) {
        $cols[] = [$rotulo, $chave, $formato, 'X', "Excel $xl: $formula"];
    }
    return $cols;
}
