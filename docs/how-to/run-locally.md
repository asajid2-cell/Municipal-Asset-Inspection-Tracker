# Run the App Locally

## Run with PHP and SQLite

1. Install dependencies if needed.

```powershell
composer install
```

2. Prepare the local database.

```powershell
New-Item -ItemType Directory -Force .\writable\database | Out-Null
New-Item -ItemType File -Force .\writable\database\dev.sqlite | Out-Null
```

3. Run migrations and seed data.

```powershell
php spark migrate
php spark db:seed DatabaseSeeder
```

4. Start the app.

```powershell
php spark serve
```

5. Open `http://localhost:8080/login`.

## Run with Docker

```powershell
docker compose build
docker compose up
```

Then:

- open `http://localhost:8080/login`
- run migrations and seed data inside the container if your mounted workspace is fresh

Example:

```powershell
docker compose exec app php spark migrate
docker compose exec app php spark db:seed DatabaseSeeder
```

## Reset the demo database

```powershell
php spark migrate:refresh
php spark db:seed DatabaseSeeder
```

Use this when:

- you want a clean demo state
- tests or exploratory work changed the seed data
- you want predictable IDs and records again
