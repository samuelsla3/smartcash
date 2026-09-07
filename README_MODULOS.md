# SmartCash — módulos complementares

## Escopo
Este pacote implementa os módulos pedidos: Dashboard, Categorias, Cartões, Investimentos,
Objetivos Financeiros, Notificações, Relatórios e APIs auxiliares.

## Dependências de integração
1. PHP 8+ recomendado.
2. MySQL compatível com o schema fornecido.
3. `config/database.php` deve criar uma instância PDO chamada `$pdo`.
4. `includes/auth.php` deve ser carregado pelo núcleo. Os módulos esperam que o usuário
   autenticado tenha `$_SESSION['id_usuario']`.
5. O projeto deve manter exatamente os nomes de tabelas e campos fornecidos no enunciado.

Exemplo mínimo esperado de integração em `config/database.php` (não substitua o arquivo
do outro integrante sem alinhar as credenciais):

```php
$pdo = new PDO(
    'mysql:host=localhost;dbname=smartcash;charset=utf8mb4',
    'root',
    '',
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
);
```

## Dashboard
O dashboard chama `api/dashboard.php` por `fetch()`. O endpoint filtra todas as consultas
pelo `id_usuario` da sessão.

O gráfico usa Chart.js via CDN:
`https://cdn.jsdelivr.net/npm/chart.js`

Se o computador de testes estiver sem Internet, baixe/forneça o arquivo do Chart.js
localmente e altere a tag `<script>` de `dashboard/index.php`.

## Observação sobre CATEGORIA
O schema fornecido não possui `id_usuario` em CATEGORIA. Portanto, estas categorias são
tratadas como categorias do sistema, compartilhadas entre usuários. As consultas não
inventam uma coluna `id_usuario` que não existe.

## Observação sobre CARTAO
Não existe tabela de fatura. O módulo respeita isso: cartões apenas mantêm os dados
do cartão e as despesas continuam na tabela DESPESA, relacionadas por `id_cartao`.

## Segurança
Os módulos usam:
- PDO/prepared statements;
- validação no PHP;
- `htmlspecialchars()` nas saídas;
- CSRF nos formulários de alteração;
- `id_usuario` em consultas de dados privados;
- checagem de propriedade antes de editar/excluir contas associadas.

## Teste sugerido no Laragon
1. Coloque a pasta em `C:\laragon\www\smartcash`.
2. Coloque os arquivos do núcleo (`database.php` e `auth.php`) nos caminhos esperados.
3. Crie/import o banco com o schema oficial.
4. Faça login com um usuário de teste.
5. Cadastre contas, categorias e movimentações pelo núcleo.
6. Abra `dashboard/index.php`.
7. Teste cada CRUD e depois altere o usuário da sessão para confirmar isolamento dos dados.
