# Testes Automatizados

Este projeto contém testes unitários e de feature para garantir a qualidade e funcionamento correto da API.

## 🧪 Estrutura de Testes

### Feature Tests (`tests/Feature/ProductApiTest.php`)
Testes de integração que testam os endpoints da API:
- **GET /products** - Listagem de produtos com paginação
- **GET /products/{code}** - Detalhes de um produto específico
- **PUT /products/{code}** - Atualização de produtos
- **DELETE /products/{code}** - Exclusão lógica (move para trash)

### Unit Tests (`tests/Unit/ProductTest.php`)
Testes unitários do modelo Product:
- Criação via Factory
- Atributos fillable
- Casting de tipos (Enum, DateTime, Float, Integer)
- Validação de unicidade
- Estados da Factory (draft, published, trash)

## 🚀 Executando os Testes

### Todos os testes:
```bash
docker-compose exec app php artisan test
```

### Somente Feature Tests:
```bash
docker-compose exec app php artisan test --testsuite=Feature
```

### Somente Unit Tests:
```bash
docker-compose exec app php artisan test --testsuite=Unit
```

### Teste específico:
```bash
docker-compose exec app php artisan test --filter=test_get_products_returns_paginated_list
```

### Com cobertura de código (verbose):
```bash
docker-compose exec app php artisan test --coverage
```

## 📊 Cobertura de Testes

Os testes cobrem:
- ✅ Autenticação via API Key
- ✅ Validação de dados de entrada
- ✅ Paginação
- ✅ Filtros (exclusão de itens trash)
- ✅ Códigos de status HTTP corretos
- ✅ Estrutura de resposta JSON
- ✅ Atualização parcial de dados
- ✅ Casting de tipos do modelo
- ✅ Estados do modelo via Factory

## 🎯 Casos de Teste

### Autenticação
- ✅ Requisições sem API Key retornam 401
- ✅ Requisições com API Key inválida retornam 401
- ✅ Requisições com API Key válida são aceitas

### GET /products
- ✅ Retorna lista paginada de produtos
- ✅ Exclui produtos com status "trash"
- ✅ Respeita parâmetro `per_page`
- ✅ Retorna estrutura JSON correta (data, links, meta)

### GET /products/{code}
- ✅ Retorna detalhes do produto
- ✅ Retorna 404 para produto inexistente
- ✅ Exclui produtos com status "trash"

### PUT /products/{code}
- ✅ Atualiza produto com sucesso
- ✅ Aceita atualizações parciais
- ✅ Valida campo `status` (enum)
- ✅ Valida campos numéricos
- ✅ Valida tamanho do `nutriscore_grade`
- ✅ Retorna 404 para produto inexistente
- ✅ Retorna 422 para dados inválidos

### DELETE /products/{code}
- ✅ Move produto para trash (exclusão lógica)
- ✅ Retorna mensagem de sucesso
- ✅ Atualiza status no banco de dados

## 🔧 Configuração de Teste

O arquivo `phpunit.xml` configura:
- Banco de dados: SQLite em memória
- Cache: Array driver
- Queue: Sync
- Mail: Array driver
- API Key de teste: `test_api_key`

Todos os testes usam o trait `RefreshDatabase` para garantir isolamento entre testes.

## 📝 Exemplo de Output

```
   PASS  Tests\Feature\ProductApiTest
  ✓ get products without api key returns unauthorized
  ✓ get products with invalid api key returns unauthorized
  ✓ get products returns paginated list
  ✓ get products excludes trashed items
  ✓ get products respects per page parameter
  ✓ get product by code returns product details
  ✓ put product updates product successfully
  ✓ put product validates status field

  Tests:    8 passed (20 assertions)
  Duration: 1.23s
```

## 🎓 Padrões Utilizados

- **AAA Pattern** (Arrange, Act, Assert)
- **Factory Pattern** para criação de dados de teste
- **RefreshDatabase** para isolamento de testes
- **Naming Convention**: `test_<what>_<when>_<expected>`
- **API Testing** com assertJson e assertJsonPath
