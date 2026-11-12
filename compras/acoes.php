<?php
/** Compras: trata as requisições (?e=compras&a=...) ligando queries e telas. */

function compras_rota(string $acao): void
{
    match ($acao) {
        'form'   => compras_acao_form(),
        'save'   => compras_acao_salvar(),
        'delete' => compras_acao_excluir(),
        default  => compras_acao_lista(),
    };
}

function compras_acao_lista(): void
{
    $busca = trim($_GET['q'] ?? '');
    compras_tela_lista(compras_listar($busca), $busca);
}

function compras_acao_form(): void
{
    $id = (int)($_GET['id'] ?? 0);
    $compra = [];
    if ($id && !($compra = compras_buscar($id))) {
        flash('warning', 'Compra não encontrada.');
        redirect(['e' => 'compras']);
    }
    compras_tela_form($compra);
}

function compras_acao_salvar(): void
{
    require_post();
    $id = (int)($_POST['id'] ?? 0);
    $dados = campos_ler_post(compras_campos());

    if ($erros = campos_validar(compras_campos(), $dados)) {
        compras_tela_form($dados + ['id' => $id], $erros);
        return;
    }

    $novoId = compras_salvar($dados, $id);
    flash('success', $id ? "Compra #$id atualizada." : "Compra #$novoId cadastrada.");
    redirect(['e' => 'compras']);
}

function compras_acao_excluir(): void
{
    require_post();
    $id = (int)($_POST['id'] ?? 0);
    compras_excluir($id);
    flash('success', "Compra #$id excluída.");
    redirect(['e' => 'compras']);
}
