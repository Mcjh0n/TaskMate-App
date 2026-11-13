# TaskMate

TaskMate is a proposed campus task marketplace for the Central Mindanao University community. It helps users understand tasks and record agreements. Payments move directly between users; TaskMate does not process, hold, recover, or guarantee payment.

## Local setup

The `.env` file contains local configuration and secrets and is git-ignored. Keep it local.

```powershell
composer install
npm install
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
composer run dev
```

Local synthetic accounts use the shared default password `password`. Change it if you use these accounts beyond local development.
