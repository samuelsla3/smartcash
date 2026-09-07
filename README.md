# SmartCash — núcleo financeiro

## Escopo

Este pacote implementa o núcleo solicitado:

- cadastro de usuário;
- login/logout e sessão;
- contas bancárias;
- recebimentos;
- despesas por conta ou cartão;
- atualização de saldo;
- atualização de limite disponível do cartão;
- transferências entre contas;
- proteção CSRF nos POSTs;
- controle de propriedade por `id_usuario`.

Não implementa dashboard, relatórios, investimentos, objetivos,
notificações ou CRUD completo de cartões, pois esses módulos serão
integrados por outro integrante.

## Instalação no Laragon

1. Crie a pasta:

   `C:\laragon\www\smartcash`

2. Copie o conteúdo deste pacote para essa pasta.

3. Crie/use o banco MySQL `smartcash` com a estrutura definida pelo projeto.

4. Em `config/database.php`, ajuste:
   - nome do banco;
   - usuário;
   - senha.

   O padrão está preparado para o Laragon com MySQL local:
   - host: `127.0.0.1`
   - usuário: `root`
   - senha vazia.

5. Acesse:

   `http://localhost/smartcash/`

## Dados esperados de tabelas auxiliares

As telas dependem dos registros existentes nas tabelas:

- BANCO
- TIPO_CONTA
- TIPO_RECEBIMENTO
- TIPO_DESPESA
- CATEGORIA

Cartões também precisam existir em CARTAO para que a opção
de despesa por cartão apareça.

## Regras financeiras

### Recebimento

Cadastro:
`saldo_atual = saldo_atual + valor`

Exclusão:
`saldo_atual = saldo_atual - valor`

Edição:
1. desfaz o valor antigo;
2. altera o recebimento;
3. aplica o valor novo.

Tudo dentro de transaction.

### Despesa por conta

Cadastro:
`saldo_atual = saldo_atual - valor`

Exclusão:
`saldo_atual = saldo_atual + valor`

Edição:
1. desfaz o efeito anterior;
2. valida o novo saldo;
3. grava a nova despesa;
4. aplica o novo efeito.

### Despesa por cartão

Cadastro:
`limite_disponivel = limite_disponivel - valor`

Exclusão:
`limite_disponivel = limite_disponivel + valor`

Edição segue a mesma lógica de desfazer/aplicar.

### Transferência

Origem:
`saldo_atual = saldo_atual - valor`

Destino:
`saldo_atual = saldo_atual + valor`

Transferência não é DESPESA.

## Observação sobre cartões

O módulo de despesas usa somente os campos já definidos em CARTAO:

- id_cartao
- id_usuario
- id_banco
- id_conta
- nome_cartao
- dia_vencimento
- limite_total
- limite_disponivel

O CRUD de cartões não faz parte deste pacote.

## Segurança aplicada

- PDO;
- prepared statements;
- `password_hash()`;
- `password_verify()`;
- `session_regenerate_id(true)` no login;
- cookies de sessão com `HttpOnly` e `SameSite`;
- CSRF em operações POST;
- validação de entrada;
- filtragem por usuário logado;
- `htmlspecialchars()` na saída HTML;
- transactions para alterações financeiras compostas;
- `SELECT ... FOR UPDATE` nas operações financeiras críticas.

## Importante sobre `data`

A coluna `DESPESA.data` é referenciada como `` `data` `` nas consultas
para evitar ambiguidades com palavras reservadas/funções do SQL.

## Dependências de integração

O pacote pressupõe que o banco já tenha sido criado conforme o
modelo fornecido pelo projeto. Ele não cria nem modifica tabelas.
