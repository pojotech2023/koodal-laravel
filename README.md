# Backend — Laravel REST API

The single source of business logic for the website, admin panel and Flutter app.

## Run

```bash
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
php artisan serve                                    # :8000
php artisan queue:work redis --tries=3               # separate terminal
php artisan schedule:work                            # separate terminal
php artisan reverb:start                             # separate terminal (chat)
```

Seeded admin: `admin@matrimony.test` / `Password@123` (local only — the seeder
refuses to run in production).

## Layout

| Path | Holds |
| --- | --- |
| `app/Actions/` | one business operation per class, invokable, transactional |
| `app/Services/<Domain>/` | reusable domain capabilities and integrations |
| `app/Support/` | small stateless helpers (`ApiResponse`, `AuditLogger`) |
| `app/Http/Controllers/Api/V1/` | thin: validate → call an Action → return a Resource |
| `app/Http/Resources/` | where field-level visibility is applied |
| `config/matrimony.php` | every product knob — weights, limits, defaults |
| `routes/api_v1_*.php` | split by audience: public, user, admin |

No `Repositories/` — Eloquent is the repository.

## Rules

1. No business logic in controllers. If it branches on business state, it is an Action.
2. Permissions, never role names: `$user->hasPermission('users.suspend')`.
3. Never trust a client payment callback — verify the signature server-side.
4. Gated fields are **absent** from a Resource, not null.
5. Verification documents live on the private disk and never get a public URL.

Full conventions: [../../docs/04-backend-guide.md](../../docs/04-backend-guide.md)

## Before committing

```bash
./vendor/bin/pint && php artisan test
```
