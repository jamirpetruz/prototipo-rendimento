<?php
/** Planilha de Rendimento: trata as requisições (?e=rendimento&a=...) ligando queries e telas. */

function rendimento_rota(string $acao): void
{
    match ($acao) {
        'form'   => rendimento_acao_form(),
        'save'   => rendimento_acao_salvar(),
        'delete' => rendimento_acao_excluir(),
        'export' => rendimento_tela_csv(rendimento_listar(rendimento_filtros())),
        default  => rendimento_acao_lista(),
    };
}

function rendimento_filtros(): array
{
    return [
        'ano'     => trim($_GET['ano'] ?? ''),
        'mes'     => trim($_GET['mes'] ?? ''),
        'fabrica' => trim($_GET['fabrica'] ?? ''),
        'q'       => trim($_GET['q'] ?? ''),
    ];
}

function rendimento_acao_lista(): void
{
    $filtros = rendimento_filtros();
    rendimento_tela_lista(
        rendimento_listar($filtros),
        $filtros,
        rendimento_anos(),
        rendimento_fabricas(),
        rendimento_producoes_pendentes(),
    );
}

function rendimento_acao_form(): void
{
    $id = (int)($_GET['id'] ?? 0);
    $linha = ['producao_id' => (int)($_GET['producao_id'] ?? 0) ?: null]; // vindo do botão "Gerar" da Produção
    if ($id && !($linha = rendimento_buscar($id))) {
        flash('warning', 'Linha não encontrada.');
        redirect(['e' => 'rendimento']);
    }
    rendimento_mostrar_form($linha);
}

function rendimento_mostrar_form(array $linha, array $erros = []): void
{
    $id = (int)($linha['id'] ?? 0);
    rendimento_tela_form($linha, rendimento_producoes_disponiveis($id), rendimento_compras_disponiveis($id), $erros);
}

function rendimento_acao_salvar(): void
{
    require_post();
    $id = (int)($_POST['id'] ?? 0);
    $dados = [
        'producao_id' => (int)($_POST['producao_id'] ?? 0) ?: null,
        'compra_id'   => (int)($_POST['compra_id'] ?? 0) ?: null,
    ];
    foreach (rendimento_campos_numero() as $nome) {
        $dados[$nome] = numero_ou_null($_POST[$nome] ?? null);
    }
    foreach (rendimento_campos_texto() as $nome => $_) {
        $dados[$nome] = texto_ou_null($_POST[$nome] ?? null);
    }

    if (!$dados['producao_id']) {
        rendimento_mostrar_form($dados + ['id' => $id], ['Selecione a produção.']);
        return;
    }

    try {
        $novoId = rendimento_salvar($dados, $id);
    } catch (PDOException) {
        rendimento_mostrar_form($dados + ['id' => $id], ['Esta produção já possui uma linha na planilha de rendimento.']);
        return;
    }
    flash('success', $id ? "Linha #$id atualizada." : "Linha #$novoId adicionada à planilha.");
    redirect(['e' => 'rendimento']);
}

function rendimento_acao_excluir(): void
{
    require_post();
    $id = (int)($_POST['id'] ?? 0);
    rendimento_excluir($id);
    flash('success', "Linha #$id removida da planilha.");
    redirect(['e' => 'rendimento']);
}
