# Módulo Rendimento (protótipo PHP + SQLite)

CRUD de **Compras**, **Produção** e da **Planilha de Rendimento** (resultado das duas + campos próprios).

## Rodar

```bash
php seed.php            # opcional: dados de exemplo
php -S localhost:8080   # abrir http://localhost:8080
```

Requer PHP 8.1+ com `pdo_sqlite`. O banco é criado automaticamente em `data/rendimento.sqlite`.

## Estrutura

```
index.php            roteador: ?e=<módulo>&a=<ação> → <módulo>_rota()
seed.php             dados de exemplo
core/                compartilhado: bootstrap, db (conexão + SQL genérico), helpers, layout, componentes
compras/             campos.php (campos + fórmulas) · queries.php (SQL) · tela.php (HTML) · acoes.php (requisições)
producao/            idem
rendimento/          idem + calculos.php (fórmulas da aba DataBase)
```

Para adicionar um campo em Compras ou Produção, inclua-o em `<módulo>/campos.php` — a coluna é criada sozinha.

## Regras assumidas (revisar)

Fórmulas conferidas com as planilhas de exemplo:

- **Rendimento (produção)** = Produzido ÷ Latas, em kg por lata (6.219 ÷ 891 = 6,98). Se Kg in natura ficar vazio, vale Latas × 14.
- **Compras**:
  - FRETE = valor total da carga.
  - Total = Líquido × Preço lata.
  - Preço Lata + Frete = Preço lata + Frete ÷ Líquido.
  - Total + Frete = Total + Frete.
  - Preço Kg Pedido = Preço lata ÷ 14.
  - Falta/Passando = Realizado − Previsto.

- **Quantidade Kg Pedido** é número digitado. Na planilha original ela aparece formatada como R$. Ainda a confirmar.

## Colunas calculadas da planilha de rendimento

Porte das fórmulas da aba **DataBase** (colunas AF–BE) de `RENDIMENTO ATUAL 2026.xlsx`, em `rendimento/calculos.php`.
O porte foi conferido contra os valores do Excel nas 1.657 linhas. As únicas diferenças vêm de erros que já existem no arquivo:

- `#REF!` nas linhas 1418–1420;
- uma data digitada como texto na linha 1275.

Frete (vem da compra vinculada):

- **FRETE UN**: a compra está ligada a uma única linha de rendimento. Frete/Lata = Frete ÷ Latas.
- **FRETE DIV**: a compra está ligada a várias linhas. O frete é rateado: Frete ÷ soma das latas dessas linhas.
  No Excel, o grupo do rateio é Data + Fornecedor + mesmo valor de Frete(Div); aqui, o grupo é a compra vinculada.
- **FRETE MACAPÁ**: campo próprio opcional, somado em "Negociado + Frete/Rendimento".

Várias colunas somam outras linhas:

- Total Lata/Dia e Média/Dia: mesmo dia + fábrica.
- Polpa Esp: mesma fábrica + tipo, datas ≤ data + 7.

Por isso o cálculo usa sempre a planilha inteira, e os filtros da tela são aplicados depois.
Passe o mouse no cabeçalho roxo para ver a fórmula de cada coluna.

Pontos herdados do Excel (revisar):

- **Polpa Esp** soma todo o histórico até data + 7, sem limite inicial. Pode ser que a ideia fosse uma janela de 7 dias.
- **Sugerido 13kg** = Sugerido ÷ 13 × 14. É o inverso de "R$ 13kg" (÷ 14 × 13).
- **ORG-GOV** não existia na tabela de produção original: foi adicionado como campo da Produção.
- Cada produção gera no máximo 1 linha de rendimento; a compra é vinculada pela NF Amarela (automático) ou manualmente.
