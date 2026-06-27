---
layout: default
title: Installation Guide
---

# Installation Guide

## Requirements

Install the following before running the project:

- PHP 8.3 or later
- Composer
- MySQL or another configured Laravel-supported database
- Git
- Required PHP extensions for Laravel

Optional tools:

- Postman
- Docker, when using the included Docker setup

## 1. Clone the Repository

```bash
git clone <repository-url>
cd tocaan-project
```

Replace `<repository-url>` with the actual repository URL.

## 2. Install PHP Dependencies

```bash
composer install
```

## 3. Create the Environment File

Linux or macOS:

```bash
cp .env.example .env
```

Windows Command Prompt:

```bat
copy .env.example .env
```

Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

## 4. Generate the Application Key

```bash
php artisan key:generate
```

## 5. Configure the Database

Update the following values in `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tocaan_project
DB_USERNAME=root
DB_PASSWORD=
```

Create the database before running migrations.

## 6. Generate the JWT Secret

```bash
php artisan jwt:secret
```

This command adds the JWT secret to `.env`.

## 7. Run Migrations and Seeders

```bash
php artisan migrate --seed
```

Seeders should create the required master data and test records, including products, currencies, payment methods, and any assessment user configured by the project.

To rebuild the database from zero:

```bash
php artisan migrate:fresh --seed
```

> This command deletes existing database data.

## 8. Clear Cached Configuration

```bash
php artisan optimize:clear
```

## 9. Start the Application

```bash
php artisan serve
```

The application normally runs at:

```text
http://127.0.0.1:8000
```

The API base URL is:

```text
http://127.0.0.1:8000/api
```

## 10. Open the Postman Workspace

Use the following workspace for the API requests and examples:

[Open Tocaan Postman Workspace](https://martian-shadow-736975.postman.co/workspace/1fe9452d-f7b9-4ca3-a0a5-8e15ccfa4db8)

Create or select a Postman environment and define:

```text
base_url = http://127.0.0.1:8000/api
token = <JWT token returned after login>
```

Use the token as a Bearer token for protected endpoints.

## 11. Run Tests

```bash
php artisan test
```

## Common Problems

### Database connection fails

Check the database name, username, password, host, and port in `.env`.

### JWT command is unavailable

Confirm that project dependencies were installed successfully:

```bash
composer install
php artisan list
```

Then confirm that the installed JWT package exposes the `jwt:secret` command.

### Old environment values are still used

```bash
php artisan optimize:clear
```

### Permission errors on Linux

Laravel must be able to write to `storage` and `bootstrap/cache`:

```bash
chmod -R ug+rwx storage bootstrap/cache
```
