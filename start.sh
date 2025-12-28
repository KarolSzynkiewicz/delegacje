#!/bin/bash

# Stocznia - Quick Start Script for Docker/Sail
# This script helps you get started with the application quickly

set -e

echo "🚢 Stocznia - Laravel Sail Quick Start"
echo "======================================="
echo ""

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo "❌ Docker is not installed. Please install Docker Desktop first:"
    echo "   https://www.docker.com/products/docker-desktop/"
    exit 1
fi

# Check if Docker is running
if ! docker info &> /dev/null; then
    echo "❌ Docker is not running. Please start Docker Desktop."
    exit 1
fi

echo "✓ Docker is installed and running"
echo ""

# Check if .env exists
if [ ! -f .env ]; then
    echo "📝 Creating .env file from .env.example..."
    cp .env.example .env
    echo "✓ .env file created"
else
    echo "✓ .env file already exists"
fi

echo ""
echo "🐳 Starting Docker containers..."
echo "   This may take a few minutes on first run..."
echo ""

# Install composer dependencies if vendor doesn't exist (bootstrap for Sail)
if [ ! -d "vendor" ]; then
    echo "📦 Installing PHP dependencies via temporary container..."
    docker run --rm \
        -u "$(id -u):$(id -g)" \
        -v "$(pwd):/var/www/html" \
        -w /var/www/html \
        laravelsail/php83-composer:latest \
        composer install --ignore-platform-reqs
fi

# Start containers
./vendor/bin/sail up -d

# Fix permissions for storage and bootstrap/cache (common WSL/Docker issue)
echo ""
echo "🔒 Fixing file permissions..."
./vendor/bin/sail root-shell -c "chmod -R 777 storage bootstrap/cache"
./vendor/bin/sail artisan storage:link

echo ""
echo "⏳ Waiting for MySQL to be ready..."
sleep 10

# Check if vendor directory exists
if [ ! -d "vendor" ] || [ ! -f "vendor/autoload.php" ]; then
    echo ""
    echo "📦 Installing PHP dependencies..."
    ./vendor/bin/sail composer install
fi

# Generate app key if not set
if ! grep -q "APP_KEY=base64:" .env; then
    echo ""
    echo "🔑 Generating application key..."
    ./vendor/bin/sail artisan key:generate
fi

# Check if database is migrated
echo ""
echo "🗄️  Setting up database..."
./vendor/bin/sail artisan migrate --seed --force

# Install NPM dependencies if needed
if [ ! -d "node_modules" ]; then
    echo ""
    echo "📦 Installing Node dependencies..."
    ./vendor/bin/sail npm install
fi

# Build assets
echo ""
echo "🎨 Building frontend assets..."
./vendor/bin/sail npm run build

echo ""
echo "✅ Setup complete!"
echo ""
echo "🌐 Application is running at: http://localhost"
echo ""
echo "🔑 Test login credentials:"
echo "   Email: test@example.com"
echo "   Password: password123"
echo ""
echo "📚 Useful commands:"
echo "   ./sail up -d          # Start containers"
echo "   ./sail down           # Stop containers"
echo "   ./sail artisan ...    # Run artisan commands"
echo "   ./sail logs           # View logs"
echo ""
echo "📖 For more information, see DOCKER_SETUP.md"
echo ""
