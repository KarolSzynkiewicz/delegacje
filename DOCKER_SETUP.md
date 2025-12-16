# Docker Setup with Laravel Sail

This project uses **Laravel Sail** for Docker-based development. Sail provides a simple command-line interface for interacting with Laravel's default Docker configuration.

## Prerequisites

- **Docker Desktop** (Windows/Mac) or **Docker Engine** (Linux)
- **Docker Compose** (usually included with Docker Desktop)

### Installation Links
- Windows/Mac: [Docker Desktop](https://www.docker.com/products/docker-desktop/)
- Linux: [Docker Engine](https://docs.docker.com/engine/install/)

## Quick Start

### 1. Clone the Repository

```bash
git clone https://github.com/KarolSzynkiewicz/delegacje.git
cd delegacje
git checkout feature/raporty
```

### 2. Copy Environment File

```bash
cp .env.example .env
```

The `.env` file is already configured for Docker/Sail with these settings:
- Database: MySQL (running in Docker container)
- DB Host: `mysql` (Docker service name)
- DB Username: `sail`
- DB Password: `password`
- DB Database: `laravel`

### 3. Start Docker Containers

**Option A: Using the sail wrapper script (recommended)**
```bash
./sail up -d
```

**Option B: Using vendor/bin/sail directly**
```bash
./vendor/bin/sail up -d
```

**Option C: Using docker compose directly**
```bash
docker compose up -d
```

The `-d` flag runs containers in detached mode (background).

### 4. Install Dependencies (First Time Only)

```bash
./sail composer install
./sail npm install
./sail npm run build
```

### 5. Generate Application Key

```bash
./sail artisan key:generate
```

### 6. Run Database Migrations

```bash
./sail artisan migrate --seed
```

This will create all necessary tables and populate them with test data.

### 7. Access the Application

Once containers are running, access the application at:
- **Application**: http://localhost
- **MySQL**: localhost:3306 (from host machine)

## Common Sail Commands

### Container Management
```bash
./sail up        # Start containers (foreground)
./sail up -d     # Start containers (background)
./sail down      # Stop containers
./sail restart   # Restart containers
```

### Laravel Artisan Commands
```bash
./sail artisan migrate       # Run migrations
./sail artisan migrate:fresh # Drop all tables and re-run migrations
./sail artisan db:seed       # Run database seeders
./sail artisan tinker        # Laravel REPL
./sail artisan queue:work    # Start queue worker
```

### Composer Commands
```bash
./sail composer install      # Install PHP dependencies
./sail composer update       # Update PHP dependencies
./sail composer require pkg  # Add new package
```

### NPM Commands
```bash
./sail npm install           # Install Node dependencies
./sail npm run dev           # Run Vite dev server
./sail npm run build         # Build for production
```

### Database Commands
```bash
./sail mysql                 # Access MySQL CLI
./sail mysql -u root -p      # Access as root user
```

### Shell Access
```bash
./sail shell                 # Access container bash shell
./sail root-shell            # Access as root user
```

### Logs
```bash
./sail logs                  # View all container logs
./sail logs laravel.test     # View app container logs
./sail logs mysql            # View MySQL logs
```

## Test Login Credentials

After running migrations with seed data:
- **Email**: test@example.com
- **Password**: password123

## Troubleshooting

### Port Already in Use

If port 80 is already in use, you can change it in `.env`:
```env
APP_PORT=8000
```

Then restart containers:
```bash
./sail down
./sail up -d
```

Access at: http://localhost:8000

### Permission Issues

If you encounter permission issues with storage or cache:
```bash
./sail artisan storage:link
./sail shell
chmod -R 775 storage bootstrap/cache
```

### Database Connection Issues

Make sure the MySQL container is healthy:
```bash
docker compose ps
```

If MySQL is not ready, wait a few seconds and try again.

### Reset Everything

To completely reset the project:
```bash
./sail down -v              # Stop and remove volumes
./sail up -d                # Start fresh
./sail artisan migrate:fresh --seed
```

## Production Deployment

For production, **do not use Sail**. Instead:

1. Use the provided `Dockerfile.backup` as a starting point
2. Set up proper environment variables
3. Use a managed database service (not Docker)
4. Configure proper web server (Nginx/Apache)
5. Enable caching and optimization

## Additional Resources

- [Laravel Sail Documentation](https://laravel.com/docs/sail)
- [Docker Documentation](https://docs.docker.com/)
- [Laravel Documentation](https://laravel.com/docs)

## Project Structure

```
delegacje/
├── docker-compose.yml       # Sail Docker configuration
├── sail                     # Sail wrapper script
├── .env                     # Environment configuration
├── vendor/laravel/sail/     # Sail package with Docker runtimes
└── ...
```

## Notes

- Sail uses **PHP 8.3** by default (configured in docker-compose.yml)
- MySQL **8.4** is used for the database
- All data is persisted in Docker volumes (`sail-mysql`)
- The application code is mounted as a volume for live reloading
