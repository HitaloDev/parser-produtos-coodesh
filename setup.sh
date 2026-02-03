#!/bin/bash

echo "🚀 Iniciando configuração do projeto Fitness Foods Parser..."

# Copiar .env se não existir
if [ ! -f .env ]; then
    echo "📋 Copiando arquivo .env..."
    cp .env.example .env
fi

echo "🔧 Ajustando permissões..."
chmod -R 775 storage bootstrap/cache

echo "🐳 Construindo containers Docker..."
docker-compose build

echo "📦 Instalando dependências do Composer..."
docker-compose run --rm app composer install

echo "🔑 Gerando chave da aplicação..."
docker-compose run --rm app php artisan key:generate

echo "🗄️  Executando migrações do banco de dados..."
docker-compose run --rm app php artisan migrate --force

echo "🔍 Configurando Elasticsearch..."
docker-compose run --rm app php artisan elasticsearch:setup

echo "🔧 Ajustando permissões finais..."
docker-compose run --rm app chmod -R 775 /var/www/storage /var/www/bootstrap/cache
docker-compose run --rm app chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

echo "✅ Setup concluído!"
echo ""
echo "Para iniciar o projeto, execute:"
echo "  docker-compose up -d"
echo ""
echo "A API estará disponível em: http://localhost:8080"
echo ""
echo "Para ver os logs:"
echo "  docker-compose logs -f"
echo ""
echo "Para parar os containers:"
echo "  docker-compose down"
