<?php
/** Popula o banco com dados de exemplo. Uso: php seed.php */
require __DIR__ . '/core/bootstrap.php';

if (db()->query('SELECT COUNT(*) FROM producao')->fetchColumn() > 0) {
    exit("Banco já possui dados; seed ignorado.\n");
}

// --- Linhas reais enviadas como exemplo (NFs diferentes, por isso ficam sem vínculo) ---
compras_salvar([
    'data_producao' => '2026-11-06', 'base' => 'BELAIAÇA', 'codigo' => 'F05380',
    'fornecedor' => 'COOPERATIVA DOS AGRICULTORES E PRODUTORES DE ACAI DE CAMETA - COOPAPAC',
    'porto' => 'PORTO DO PRETO MELO CURUÇAMABABA', 'regiao' => 'CAMETA', 'tipo' => 'NATURAL',
    'certificacao' => 'BR / EU / NOP', 'motorista' => 'EDIVALDO - HZU2C17', 'previsto_latas' => 950, 'realizado' => 950,
    'liquido_latas' => 948.78, 'preco_lata' => 58, 'nota_amarela' => '117921', 'frete' => 2104.44,
    'qtd_kg_pedido' => 16100, 'responsavel_compra' => 'LABORATORIO',
]);
producao_salvar([
    'data' => '2026-10-31', 'lote' => '102146 B', 'codigo' => '23911', 'produto' => 'ACAI SUPERFRUIT GUA27 SR CPCA 2,9KG BD EX',
    'motorista' => 'JOSÉ MILTON', 'nf_amarela' => '91691', 'fornecedor' => 'COOPIMIR', 'regiao' => 'IG.MIRIM',
    'latas' => 891, 'kg_in_natura' => 12474, 'tipo' => 'MÉDIO 12%', 'produzido' => 6219, 'solido' => 12.1,
    'padrao_cor' => 1.3, 'cor_envase' => 3.58, 'sabor' => 'BOM',
]);

// --- Dados fictícios vinculados (compra ↔ produção pela NF Amarela) ---
$fornecedores = [['COOPAPAC', 'CAMETA'], ['COOPIMIR', 'IG.MIRIM'], ['COOPERATIVA EXEMPLO', 'ABAETETUBA']];
$produtos = [['23911', 'ACAI SUPERFRUIT GUA27 SR CPCA 2,9KG BD EX', 'MÉDIO 12%'], ['23850', 'ACAI POLPA GROSSO 1KG', 'GROSSO 14%'], ['23720', 'ACAI POLPA MÉDIO 1KG', 'MÉDIO 12%']];
$fabricas = ['FÁBRICA 1', 'FÁBRICA 2'];
$nucleos = ['NÚCLEO A', 'NÚCLEO B', 'NÚCLEO C'];

for ($i = 1; $i <= 6; $i++) {
    $data = date('Y-m-d', strtotime('2026-10-01 +' . ($i * 4) . ' days'));
    $nf = (string)(117900 + $i);
    [$forn, $reg] = $fornecedores[$i % 3];
    [$cod, $prod, $tipo] = $produtos[$i % 3];
    $latas = 820 + $i * 25;
    $liquido = $latas - 1.2 - $i * 0.15;

    $compraId = compras_salvar([
        'data_producao' => $data, 'base' => 'BELAIAÇA', 'codigo' => 'F0' . (5370 + $i), 'fornecedor' => $forn,
        'porto' => 'PORTO ' . $reg, 'regiao' => $reg, 'tipo' => 'NATURAL', 'certificacao' => $i % 2 ? 'BR / EU / NOP' : 'BR',
        'motorista' => 'MOTORISTA ' . $i, 'previsto_latas' => $latas + ($i % 2 ? 0 : 20), 'realizado' => $latas,
        'liquido_latas' => $liquido, 'preco_lata' => 56 + $i, 'nota_amarela' => $nf, 'frete' => round($latas * 2.2, 2),
        'qtd_kg_pedido' => round($liquido * 14), 'responsavel_compra' => $i % 2 ? 'LABORATORIO' : 'COMPRAS',
        'status_nf' => $i % 3 ? 'Recebida' : 'Pendente',
    ]);

    $prodId = producao_salvar([
        'data' => $data, 'lote' => (102100 + $i) . ' ' . chr(64 + $i % 3 + 1), 'codigo' => $cod, 'produto' => $prod,
        'motorista' => 'MOTORISTA ' . $i, 'nf_amarela' => $nf, 'fornecedor' => $forn, 'regiao' => $reg,
        'latas' => $latas, 'org_gov' => $i % 2 ? 'ORG' : 'CONV', 'tipo' => $tipo,
        'produzido' => round($latas * (6.6 + ($i % 4) * 0.2)), 'solido' => 11.8 + ($i % 3) * 0.3,
        'padrao_cor' => 1.2 + ($i % 3) * 0.1, 'cor_envase' => 3.4 + ($i % 4) * 0.08, 'sabor' => $i === 4 ? 'REGULAR' : 'BOM',
    ]);

    rendimento_salvar([
        'producao_id' => $prodId, 'compra_id' => $compraId, 'fabrica' => $fabricas[$i % 2],
        'regiao_macro' => $reg, 'nucleo_responsavel' => $nucleos[$i % 3], 'rendimento_padrao' => 7.0,
    ]);

    // A compra 5 abastece duas produções no mesmo dia → frete vira FRETE DIV (rateado pelas latas)
    if ($i === 5) {
        $prod2 = producao_salvar([
            'data' => $data, 'lote' => (102100 + $i) . ' Z', 'codigo' => $cod, 'produto' => $prod,
            'motorista' => 'MOTORISTA ' . $i, 'nf_amarela' => $nf, 'fornecedor' => $forn, 'regiao' => $reg,
            'latas' => 300, 'org_gov' => 'ORG', 'tipo' => $tipo, 'produzido' => 2070, 'solido' => 12.4,
            'padrao_cor' => 1.3, 'cor_envase' => 3.5, 'sabor' => 'BOM',
        ]);
        rendimento_salvar([
            'producao_id' => $prod2, 'compra_id' => $compraId, 'fabrica' => $fabricas[$i % 2],
            'regiao_macro' => $reg, 'nucleo_responsavel' => $nucleos[$i % 3], 'rendimento_padrao' => 7.0,
        ]);
    }
}

echo "Dados de exemplo inseridos.\n";
