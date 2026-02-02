# Fitness Foods Parser - API REST

API REST desenvolvida para gerenciar informações nutricionais de produtos alimentícios da Open Food Facts, permitindo que equipe de nutricionistas da Fitness Foods LC possam revisar rapidamente os dados dos alimentos publicados.

## 🚀 Tecnologias Utilizadas

- **PHP 8.2**
- **Laravel 12**
- **PostgreSQL 16**
- **Redis 7**
- **Docker & Docker Compose**
- **Nginx**
- **Supervisor** (para gerenciamento de processos)
- **Cron** (para importações agendadas)

## 📋 Status do Projeto

🚧 **Em desenvolvimento** - Configuração inicial do Docker concluída

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
```
```

### 3. Acessar a aplicação

A API estará disponível em: **http://localhost:8080**

## 🧪 Testando a API

### Postman Collection

Para facilitar os testes, você pode importar a collection do Postman que está na raiz do projeto (`postman_collection.json`):

1. Abra o Postman
2. Clique em **Import**
3. Selecione o arquivo `postman_collection.json`
4. Todas as rotas estarão configuradas e prontas para uso

**API Key padrão:** `fitness_foods_secret_key_2026`

---

>  This is a challenge by [Coodesh](https://coodesh.com/)
