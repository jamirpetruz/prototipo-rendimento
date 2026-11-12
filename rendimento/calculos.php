<?php
/**
 * Colunas calculadas da planilha de rendimento: porte das fórmulas da aba "DataBase"
 * (colunas AF a BE) do arquivo "RENDIMENTO ATUAL 2026".
 *
 * Várias fórmulas somam dados de OUTRAS linhas (por dia/fábrica, fábrica+tipo, frete rateado),
 * por isso o cálculo é feito sobre o conjunto completo de linhas, e só depois se aplicam filtros.
 *
 * Entrada (por linha): data (Y-m-d), fabrica, tipo, latas, produzido, solido, preco_14kg,
 *   frete_un, frete_div, frete_grupo, frete_macapa.
 * Convenção: null = célula vazia/erro no Excel ("" ou #DIV/0!); IFERROR(...;0) devolve 0.
 */

/** Definição das colunas calculadas: chave => [coluna Excel, rótulo, formato, fórmula legível]. */
function rendimento_colunas_calculadas(): array
{
    return [
        'frete_lata'      => ['AF', 'FRETE/LATA', 'money', 'Frete UN ÷ Latas; ou Frete DIV ÷ soma das latas que dividem o mesmo frete'],
        'frete_total'     => ['AG', 'FRETE TOTAL', 'money', 'Frete/Lata × Latas'],
        'rend'            => ['AI', 'REND.', 'dec', 'Produção ÷ Latas'],
        'rend_pct'        => ['AJ', 'REND.(%)', 'frac', 'Rend. ÷ 14'],
        'polpa_real'      => ['AK', 'POLPA REAL (%)', 'dec', 'Sólido × Rend. ÷ 14'],
        'polpa_esp'       => ['AL', 'POLPA ESP (%)', 'dec', 'Média de Polpa Real ponderada pela Produção, mesma Fábrica+Tipo, datas até Data+7'],
        'massa'           => ['AM', 'CUSTO (MASSA)', 'dec', 'Polpa Real × Produção (auxiliar da Polpa Esp)'],
        'preco_13kg'      => ['AN', 'R$ 13KG', 'money', 'R$ 14kg ÷ 14 × 13'],
        'sugerido'        => ['AO', 'SUGERIDO R$', 'money', 'R$ 14kg ÷ Polpa Esp × Polpa Real'],
        'sugerido_13kg'   => ['AP', 'SUGERIDO 13KG (R$)', 'money', 'Sugerido ÷ 13 × 14'],
        'custo_massa'     => ['AQ', 'CUSTO/MASSA (%)', 'dec', 'R$ 14kg ÷ Polpa Real'],
        'lata_rend'       => ['AR', 'LATA / REND (R$)', 'money', 'Negociado + Frete ÷ Rend.'],
        'total_lata_dia'  => ['AS', 'TOTAL LATA / DIA', 'dec', 'Soma das latas do mesmo dia e fábrica'],
        'pond_dia'        => ['AT', 'POND. DIA', 'frac', 'Latas ÷ Total Lata/Dia'],
        'pond_massa'      => ['AU', 'POND. / MASSA (R$)', 'dec', 'Pond. Dia × Custo/Massa'],
        'media_dia'       => ['AV', 'MÉDIA TOTAL (R$) / DIA', 'dec', 'Soma de Pond./Massa do mesmo dia e fábrica'],
        'sugestao_preco'  => ['AW', 'SUGESTÃO PREÇO', 'money', '(Média/Dia − Custo/Massa) × Rend. + R$ 14kg'],
        'sugerido_medio'  => ['AX', 'SUGERIDO MÉDIO', 'money', 'Média de Sugestão Preço e Sugerido R$'],
        'a_pagar'         => ['AY', 'A PAGAR', 'money', 'Menor entre Sugerido Médio e R$ 14kg'],
        'preco_pago'      => ['AZ', 'PREÇO PAGO', 'money', 'Frete/Lata + R$ 14kg'],
        'a_pagar_frete'   => ['BA', 'A PAGAR + FRETE', 'money', 'A Pagar + Frete/Lata'],
        'negociado_frete' => ['BB', 'NEGOCIADO + FRETE', 'money', 'R$ 14kg + Frete/Lata'],
        'custo'           => ['BC', 'CUSTO', 'money', 'Negociado + Frete ÷ Rend.'],
        'negociado_rend'  => ['BD', 'NEGOCIADO + FRETE/RENDIMENTO', 'money', 'Lata/Rend + Frete/Lata + Frete Macapá'],
        'sug_fornecedor2' => ['BE', 'SUG. FORNECEDOR 2', 'money', 'Menor entre Sugerido R$ e R$ 14kg'],
    ];
}

function rendimento_calcular(array $rows): array
{
    $div = fn($a, $b) => ($a === null || $b === null || (float)$b == 0.0) ? null : $a / $b;
    $mul = fn($a, $b) => ($a === null || $b === null) ? null : $a * $b;
    $add = fn(...$v) => in_array(null, $v, true) ? null : array_sum($v);
    $key = fn(...$v) => mb_strtolower(implode("\x1f", array_map(fn($x) => (string)$x, $v))); // SUMIFS ignora maiúsc./minúsc.

    // Normaliza entradas (célula vazia conta como 0 nas contas do Excel)
    foreach ($rows as &$r) {
        foreach (['latas', 'produzido', 'solido', 'preco_14kg', 'frete_macapa'] as $k) {
            $r[$k] = (float)($r[$k] ?? 0);
        }
        // dia serial; data inválida/vazia fica fora dos SUMIFS por data (como no Excel)
        $d = DateTime::createFromFormat('!Y-m-d', (string)$r['data'], new DateTimeZone('UTC'));
        $r['_ts'] = $d ? intdiv($d->getTimestamp(), 86400) : null;
    }
    unset($r);

    // AF: latas que dividem cada frete (mesmo grupo, Frete UN vazio, mesmo Frete DIV)
    $latasFrete = [];
    foreach ($rows as $r) {
        if ($r['frete_un'] === null && $r['frete_div']) {
            $k = $key($r['frete_grupo'], $r['frete_div']);
            $latasFrete[$k] = ($latasFrete[$k] ?? 0) + $r['latas'];
        }
    }

    // Passo 1: colunas que só dependem da própria linha
    foreach ($rows as &$r) {
        $S = $r['latas'];
        $r['frete_lata'] = !$r['frete_div']
            ? $div((float)($r['frete_un'] ?? 0), $S)
            : $div((float)$r['frete_div'], $latasFrete[$key($r['frete_grupo'], $r['frete_div'])] ?? 0);
        $r['frete_total'] = $mul($r['frete_lata'], $S);
        $r['rend']        = $div($r['produzido'], $S);
        $r['rend_pct']    = $div($r['rend'], 14);
        $r['polpa_real']  = $r['rend'] === null ? null : $r['solido'] * $r['rend'] / 14;
        $r['massa']       = $mul($r['polpa_real'], $r['produzido']);
        $r['preco_13kg']  = $r['preco_14kg'] / 14 * 13;
        $r['custo_massa'] = $div($r['preco_14kg'], $r['polpa_real']);
        $r['negociado_frete'] = $add($r['preco_14kg'], $r['frete_lata']);
        $r['lata_rend']   = $div($r['negociado_frete'], $r['rend']);
    }
    unset($r);

    // AL: por Fábrica+Tipo, soma de Massa e Produção das linhas com data <= data+7
    $grupos = [];
    foreach ($rows as $i => $r) {
        if ($r['_ts'] !== null) {
            $grupos[$key($r['fabrica'] . $r['tipo'])][] = $i;
        }
    }
    foreach ($grupos as $idx) {
        usort($idx, fn($a, $b) => $rows[$a]['_ts'] <=> $rows[$b]['_ts']);
        $acM = $acV = [];
        $sM = $sV = 0.0;
        $err = false;
        foreach ($idx as $n => $i) {
            $err = $err || $rows[$i]['massa'] === null; // erro em Massa contamina o SUMIFS
            $sM += (float)$rows[$i]['massa'];
            $sV += $rows[$i]['produzido'];
            $acM[$n] = $err ? null : $sM;
            $acV[$n] = $sV;
        }
        foreach ($idx as $i) {
            $lim = $rows[$i]['_ts'] + 7;
            $lo = 0;
            $hi = count($idx) - 1;
            while ($lo < $hi) { // última posição com data <= lim
                $mid = intdiv($lo + $hi + 1, 2);
                if ($rows[$idx[$mid]]['_ts'] <= $lim) {
                    $lo = $mid;
                } else {
                    $hi = $mid - 1;
                }
            }
            $rows[$i]['polpa_esp'] = $div($acM[$lo], $acV[$lo]);
        }
    }

    // AS: total de latas por dia + fábrica
    $latasDia = [];
    foreach ($rows as $r) {
        $k = $key($r['data'], $r['fabrica']);
        $latasDia[$k] = ($latasDia[$k] ?? 0) + $r['latas'];
    }
    foreach ($rows as &$r) {
        $r['polpa_esp']      ??= null;
        $r['sugerido']       = $div($r['preco_14kg'], $r['polpa_esp']) === null || $r['polpa_real'] === null
            ? 0.0 : $r['preco_14kg'] / $r['polpa_esp'] * $r['polpa_real'];
        $r['sugerido_13kg']  = $r['sugerido'] / 13 * 14;
        $r['total_lata_dia'] = $latasDia[$key($r['data'], $r['fabrica'])];
        $r['pond_dia']       = $div($r['latas'], $r['total_lata_dia']);
        $r['pond_massa']     = $mul($r['pond_dia'], $r['custo_massa']) ?? 0.0;
    }
    unset($r);

    // AV: soma de Pond./Massa por dia + fábrica
    $massaDia = [];
    foreach ($rows as $r) {
        $k = $key($r['data'], $r['fabrica']);
        $massaDia[$k] = ($massaDia[$k] ?? 0) + $r['pond_massa'];
    }
    foreach ($rows as &$r) {
        $AB = $r['preco_14kg'];
        $r['media_dia']       = $massaDia[$key($r['data'], $r['fabrica'])];
        $r['sugestao_preco']  = ($r['custo_massa'] === null || $r['rend'] === null)
            ? 0.0 : ($r['media_dia'] - $r['custo_massa']) * $r['rend'] + $AB;
        $r['sugerido_medio']  = ($r['sugestao_preco'] + $r['sugerido']) / 2;
        $r['a_pagar']         = min($r['sugerido_medio'], $AB);
        $r['preco_pago']      = $add($r['frete_lata'], $AB);
        $r['a_pagar_frete']   = $add($r['a_pagar'], $r['frete_lata']);
        $r['custo']           = $div($r['negociado_frete'], $r['rend']);
        $r['negociado_rend']  = $add($r['lata_rend'], $r['frete_lata'], $r['frete_macapa']);
        $r['sug_fornecedor2'] = min($r['sugerido'], $AB);
        unset($r['_ts']);
    }
    unset($r);

    return $rows;
}

/** Rendimento real (Produção ÷ Latas) abaixo do rendimento padrão informado na linha. */
function rendimento_abaixo_padrao(array $r): bool
{
    return $r['rend'] !== null && $r['rendimento_padrao'] !== null && $r['rend'] < $r['rendimento_padrao'];
}
