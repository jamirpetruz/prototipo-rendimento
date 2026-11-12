<?php
/** Produção: trata as requisições (?e=producao&a=...) ligando queries e telas. */

function producao_rota(string $acao): void
{
    match ($acao) {
        'form'   => producao_acao_form(),
        'save'   => producao_acao_salvar(),
        'delete' => producao_acao_excluir(),
        default  => producao_acao_lista(),
    };
}

function producao_acao_lista(): void
{
    $busca = trim($_GET['q'] ?? '');
    producao_tela_lista(producao_listar($busca), $busca);
}

function producao_acao_form(): void
{
    $id = (int)($_GET['id'] ?? 0);
    $producao = [];
    if ($id && !($producao = producao_buscar($id))) {
        flash('warning', 'Produção não encontrada.');
        redirect(['e' => 'producao']);
    }
    producao_tela_form($producao);
}

function producao_acao_salvar(): void
{
    require_post();
    $id = (int)($_POST['id'] ?? 0);
    $dados = campos_ler_post(producao_campos());

    if ($erros = campos_validar(producao_campos(), $dados)) {
        producao_tela_form($dados + ['id' => $id], $erros);
        return;
    }

    $novoId = producao_salvar($dados, $id);
    flash('success', $id ? "Produção #$id atualizada." : "Produção #$novoId cadastrada.");
    redirect(['e' => 'producao']);
}

function producao_acao_excluir(): void
{
    require_post();
    $id = (int)($_POST['id'] ?? 0);
    if (producao_excluir($id)) {
        flash('success', "Produção #$id excluída.");
    } else {
        flash('danger', "Não foi possível excluir a produção #$id: ela está vinculada a uma linha da planilha de rendimento.");
    }
    redirect(['e' => 'producao']);
}
