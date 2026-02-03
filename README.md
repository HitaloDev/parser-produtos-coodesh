# Fitness Foods Parser - API REST

API REST desenvolvida para gerenciar informações nutricionais de produtos alimentícios da Open Food Facts, permitindo que equipe de nutricionistas da Fitness Foods LC possam revisar rapidamente os dados dos alimentos publicados.

## 🚀 Tecnologias Utilizadas

- **PHP 8.2**
- **Laravel 12**
- **PostgreSQL 16**
- **Redis 7**
- **Elasticsearch 8.11**
- **Docker & Docker Compose**
- **Nginx**
- **Supervisor** (para gerenciamento de processos)
- **Cron** (para importações agendadas)

## ✨ Funcionalidades

- ✅ **REST API** com Laravel 12
- ✅ **CRUD de Produtos** (Listagem, Detalhes, Atualização, Exclusão)
- ✅ **Busca Avançada** com Elasticsearch (fuzzy matching, relevância)
- ✅ **Importação Automática** via CRON diário do Open Food Facts
- ✅ **Paginação** em todos os endpoints de listagem
- ✅ **Autenticação** via API Key
- ✅ **Histórico de Importações** com registro de sucessos e falhas
- ✅ **Sistema de Alertas** para falhas de importação (Logs, Email, Slack)
- ✅ **Documentação OpenAPI 3.0** com Swagger UI
- ✅ **Testes Unitários e de Integração** (28 testes implementados)
- ✅ **Docker** com PostgreSQL, Redis, Elasticsearch e Nginx

## 📋 Diferenciais Implementados

- ✅ **Diferencial 1:** Configurar um endpoint de busca com ElasticSearch
- ✅ **Diferencial 3:** Configurar um sistema de alerta se tem alguma falha durante o sincronismo dos produtos
- ✅ **Diferencial 4:** Descrever a documentação da API utilizando o conceito de Open API 3.0
- ✅ **Diferencial 5:** Escrever Unit Tests para os endpoints GET e PUT do CRUD

## 🛠️ Pré-requisitos

- Docker 20.10+
- Docker Compose 2.0+
- Git

## 📦 Instalação e Configuração

### 1. Clone o repositório

```bash
git clone git@github.com:HitaloDev/parser-produtos-coodesh.git
cd parser-produtos-coodesh
```

### 2. Configure e inicie o projeto

#### ⭐ Opção 1: Usando o script de setup (RECOMENDADO para Linux/Mac/WSL)

```bash
chmod +x setup.sh
./setup.sh
docker-compose up -d
```

O script irá automaticamente:
- Copiar o arquivo `.env`
- Ajustar permissões necessárias
- Construir os containers Docker
- Instalar dependências do Composer
- Gerar chave da aplicação
- Executar migrações do banco de dados
- Configurar Elasticsearch e indexar produtos

#### Opção 2: Instalação manual

```bash
# Copiar arquivo de ambiente
cp .env.example .env

# Ajustar permissões (Linux/Mac/WSL)
chmod -R 775 storage bootstrap/cache

# Construir e iniciar containers
docker-compose up -d --build

# Instalar dependências
docker-compose exec app composer install

# Gerar chave da aplicação
docker-compose exec app php artisan key:generate

# Executar migrações
docker-compose exec app php artisan migrate

# Configurar Elasticsearch (criar índice e indexar produtos)
docker-compose exec app php artisan elasticsearch:setup
```

### 3. Acessar a aplicação

A API estará disponível em: **http://localhost:8080**

**Serviços:**
- API: http://localhost:8080
- PostgreSQL: localhost:5432
- Redis: localhost:6379
- Elasticsearch: http://localhost:9200

## 🧪 Testando a API

### Testes Automatizados

O projeto inclui testes unitários e de integração para garantir a qualidade do código:

```bash
# Executar todos os testes
docker-compose exec app php artisan test

# Executar apenas testes de feature (API)
docker-compose exec app php artisan test --testsuite=Feature

# Executar apenas testes unitários
docker-compose exec app php artisan test --testsuite=Unit
```

**Cobertura de testes:**
- ✅ Autenticação via API Key
- ✅ Endpoints GET e PUT
- ✅ Validação de dados
- ✅ Paginação
- ✅ Tratamento de erros

### Postman Collection

Para facilitar os testes, você pode importar a collection do Postman que está na raiz do projeto (`postman_collection.json`):

1. Abra o Postman
2. Clique em **Import**
3. Selecione o arquivo `postman_collection.json`
4. Todas as rotas estarão configuradas e prontas para uso

**API Key padrão:** `fitness_foods_secret_key_2026`

## ⏰ CRON e Importação de Dados

### Importação Automática

O sistema importa automaticamente 100 produtos de cada arquivo do Open Food Facts **diariamente às 03:00**.

### Importação Manual

Para importar produtos manualmente:

```bash
docker-compose exec app php artisan app:import-products
```

### Histórico de Importações

Todas as importações são registradas na tabela `import_histories` com:
- Nome do arquivo
- Status (pending, processing, completed, failed)
- Total de produtos
- Produtos importados com sucesso
- Produtos com falha
- Mensagens de erro
- Tempo de início e fim

### Sistema de Alertas

Em caso de falha na importação, o sistema envia alertas via:
- **Logs:** `storage/logs/import-alerts.log`
- **Email:** Configure `MAIL_ALERTS_ENABLED=true` no `.env`
- **Slack:** Configure `SLACK_WEBHOOK_URL` no `.env`

## 🔍 Busca com Elasticsearch

A busca avançada utiliza Elasticsearch com:

- **Fuzzy Matching:** Tolera erros de digitação
- **Multi-field Search:** Busca em nome, marca, categorias, labels e ingredientes
- **Relevância por Peso:** Nome do produto tem 3x mais relevância
- **Paginação:** Resultados paginados
- **Score:** Cada resultado tem um score de relevância

**Exemplo:**
```bash
curl -X GET "http://localhost:8080/api/products/search?q=chocolate" \
  -H "X-API-Key: fitness_foods_secret_key_2026"
```

Veja documentação completa em [`ELASTICSEARCH.md`](./ELASTICSEARCH.md)

## 📖 Documentação da API

### OpenAPI 3.0 (Swagger)

A documentação completa da API está disponível no formato OpenAPI 3.0 em `docs/api.yml`.

**Para visualizar:**

#### Opção 1: Swagger Editor Online
1. Acesse [https://editor.swagger.io/](https://editor.swagger.io/)
2. Cole o conteúdo de `docs/api.yml`

#### Opção 2: Swagger UI via Docker
```bash
docker run -p 8081:8080 -e SWAGGER_JSON=/docs/api.yml -v $(pwd)/docs:/docs swaggerapi/swagger-ui
```
Acesse: http://localhost:8081

## 🧪 Testando a API

### Testes Automatizados

O projeto inclui **28 testes** (unitários e de integração):

```bash
docker-compose exec app php artisan test
```

**Cobertura de testes:**
- ✅ Autenticação via API Key
- ✅ Endpoints GET, PUT, DELETE
- ✅ Validação de dados
- ✅ Paginação
- ✅ Tratamento de erros
- ✅ Modelo de dados e casting

Veja detalhes em [`tests/README.md`](./tests/README.md)

### Postman Collection

Collection com todos os endpoints está disponível em `postman_collection.json`:

1. Abra o Postman
2. Clique em **Import**
3. Selecione o arquivo `postman_collection.json`
4. Todas as rotas estarão configuradas

**API Key padrão:** `fitness_foods_secret_key_2026`

## 📊 Banco de Dados

### Tabelas

- **products:** Produtos importados do Open Food Facts
  - Campos: code (PK), status, imported_t, product_name, brands, etc.
  - Status: draft, published, trash
  
- **import_histories:** Histórico de importações
  - Campos: filename, status, total_products, imported_products, etc.
  - Status: pending, processing, completed, failed

### Migrations

```bash
# Executar migrations
docker-compose exec app php artisan migrate

# Reverter última migration
docker-compose exec app php artisan migrate:rollback

# Ver status das migrations
docker-compose exec app php artisan migrate:status
```

### Erro de permissão
```bash
# Ajustar permissões
chmod -R 775 storage bootstrap/cache
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
```

### Testes falhando
```bash
# Limpar cache
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear

# Rodar novamente
docker-compose exec app php artisan test
```

## 📄 Licença

Este projeto foi desenvolvido como parte de um desafio técnico.

---

>  This is a challenge by [Coodesh](https://coodesh.com/)
