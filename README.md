# NH Hotel Management Database System

A Laravel 12 hotel-management application and university database assignment. MySQL is the demonstration database; PHPUnit feature tests use a separate in-memory SQLite database.

## Requirements

- PHP 8.2+, Composer, MySQL 8.0.16+ (for enforced `CHECK` constraints), and PHP extensions `pdo_mysql` and `pdo_sqlite`
- Node.js/npm only when rebuilding the optional frontend assets

## MySQL configuration

The documented project defaults are:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hotel_management
DB_USERNAME=root
DB_PASSWORD=
```

In VS Code, open the existing `.env` and add or update those `DB_*` lines only. Enter your **actual local MySQL password** after `DB_PASSWORD=` on your own computer. Do not copy a sample/guessed password, post the value in chat, or overwrite `.env` with `.env.example`. The checked-in `.env.example` intentionally leaves the password empty.

Create the database only if it does not already exist. In MySQL Workbench, run:

```sql
CREATE DATABASE IF NOT EXISTS hotel_management
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

From PowerShell, you can instead use the MySQL client, which prompts for the password without putting it in the command:

```powershell
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS hotel_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Check the config without printing secrets:

```powershell
php artisan tinker --execute="echo config('database.default').' / '.config('database.connections.mysql.database').PHP_EOL;"
```

Expected output: `mysql / hotel_management`. After entering the correct credentials, test database access without changing schema/data:

```powershell
php artisan db:show --database=mysql
```

The previous `using password: NO` message means Laravel sent no password. In this project `.env` the `DB_PASSWORD` setting is currently missing; add that key and enter your actual password locally. If the account intentionally has no password, write an explicit empty `DB_PASSWORD=` line. Laravel does not change MySQL account credentials.

## Apache, phpMyAdmin, and MySQL are separate

- MySQL listens for database connections on port **3306**. Check before starting another instance:

  ```powershell
  Get-NetTCPConnection -State Listen -LocalPort 3306 -ErrorAction SilentlyContinue
  Get-Service | Where-Object { $_.Name -match 'mysql|maria' }
  Test-NetConnection 127.0.0.1 -Port 3306
  ```

- Apache serves websites/phpMyAdmin over HTTP, normally port **80**. `localhost refused to connect` while opening phpMyAdmin means the web server is not listening at that address/port, or phpMyAdmin is not installed/configured there; it does not by itself mean MySQL is stopped. Check Apache separately in the control panel/service used by your local stack. Do not change ports or restart/reinstall a database service to troubleshoot Apache.
- Do not start a second MySQL server if port 3306 already has a listener. The Laravel demo below uses its own local PHP server, so phpMyAdmin/Apache is optional.

## Safe setup and migration choices

First install dependencies if needed:

```powershell
composer install
```

Do not regenerate an existing `APP_KEY`. For a genuinely fresh Laravel setup only, generate a key if the `.env` `APP_KEY` is blank.

Choose **one** database setup path:

### Option A — Laravel application (recommended for the web demonstration)

For a brand-new/empty assignment database:

```powershell
php artisan migrate --seed
php artisan admin:create
```

The migrations create framework/hotel tables and constraints; they do not run `migrate:fresh`, wipe the database, or drop existing data. The newest migration changes the reservation-payment foreign key from cascade-delete to restrict-delete and adds data checks; it does not rewrite payment rows. Before running migrations on any existing MySQL database, review the pending migration list, back up that database, and get approval for the schema changes. The repeatable seeder inserts or updates only named demo records; do not seed an existing database if those sample keys may belong to records you need to preserve.

Create the first administrator with `php artisan admin:create`. Enter the administrator's name and unique email, then enter and confirm a password at the hidden prompts. The password must be at least 12 characters and contain uppercase, lowercase, and numeric characters. The command refuses to create another administrator once one exists, and only a password hash is stored. Use `php artisan hotel:user` to create additional staff accounts.

### Option B — standalone SQL demonstration

For a fresh database-only presentation, run `database/mysql/hotel_management.sql` in MySQL Workbench or from PowerShell:

```powershell
cmd /c "mysql -u root -p < database\mysql\hotel_management.sql"
```

The script uses `CREATE TABLE IF NOT EXISTS`, never drops/truncates data, and inserts demo examples only when their sample keys are absent. It is intended for a fresh `hotel_management` database; it will not reconcile a pre-existing schema with different definitions. It does not create staff accounts. Do not run both setup options against the same fresh database.

## Run and demonstrate the application

In PowerShell, from the project directory:

```powershell
php artisan serve --host 127.0.0.1 --port 8080
```

Open **http://127.0.0.1:8080** and sign in using the staff account you created. Administrators can manage room inventory/types. Staff can register guests, create and update reservations, assign rooms, check guests in/out, cancel eligible bookings, record payments, and view reports. Prices and payment totals are shown in Ethiopian Birr (ETB).

Suggested instructor demonstration:

1. Sign in; show the dashboard summary and room types/rates.
2. Register a fictional guest, or use one of the Ethiopian demo guests.
3. Create a reservation with one or more rooms and optional services.
4. Show the confirmation code and reservation-room assignment; try an overlapping date booking to demonstrate the validation block.
5. Demonstrate check-in on the stay date and check-out from an in-house stay; show cancellation for an eligible unpaid booking.
6. Record a partial payment and show the ETB balance; try an amount over the remaining balance to demonstrate rejection.
7. Open Reports to show reservation status and payment totals.

## Report and verification

See [docs/PROJECT_REPORT.md](docs/PROJECT_REPORT.md) for the five assignment sections, ER diagram, relational model, cardinalities/degrees, table definitions, and worked 1NF/2NF/3NF examples.

Run tests and inspect routes:

```powershell
php artisan test
php artisan route:list
```

`phpunit.xml` forces tests to SQLite `:memory:`. These checks do not migrate or seed your MySQL database. The authenticated application is intended for a local classroom demonstration; do not expose it to public networks.

To inspect the migration status of your configured MySQL database, first enter the correct credentials and get approval before connecting to an existing database, then run `php artisan migrate:status --database=mysql`. This command reads migration metadata but does not apply migrations.
