# Coffee Shop Management App

A management app for a coffee shop — full-stack project with a separate backend and frontend.

## About

This is a **vibe-coded** project — built fast and loosely rather than heavily planned out. Structure and features are still taking shape.

📄 [Cahier de charge](https://github.com/kabouwa/Coffee-Shop-Management-App/blob/main/backend/README.md) — full project spec (SaaS multi-tenant coffee shop management app)

## Stack

**Backend** (`backend/`)
- Laravel 13
- Laravel Sanctum (auth)
- Scramble (API documentation)

**Frontend** (`frontend/`)
- React 19
- Vite

## Setup

**Backend**
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

**Frontend**
```bash
cd frontend
npm install
npm run dev
```

## Notes

Early-stage, vibe-coded project — expect things to be rough around the edges and to change quickly as features get added.
