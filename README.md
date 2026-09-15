# WPS Micro Application

Application skeleton for building sites with the WPS Micro framework.

## Requirements

- PHP 8.3 or higher
- Composer
- Node.js 20.19 or higher and npm
- Docker and Docker Compose for the bundled environment

## Create An Application

```bash
composer create-project webpagestudio/wps-micro-skeleton my-site
cd my-site
cp .env_example .env
php wps app:setup
npm ci
npm run build
```

The application depends on `webpagestudio/wps-micro`, so framework releases can
be installed without replacing application controllers, models, routes, views,
or migrations:

```bash
composer update webpagestudio/wps-micro
```

## Run Locally

With Docker:

```bash
docker compose up --build
```

Then open `http://localhost`.

With PHP's built-in server:

```bash
php -S localhost:8000 -t public public/index.php
```

The sample application uses a database. When PHP runs outside Docker, configure
`DB_HOST=127.0.0.1` and the forwarded MariaDB port in `.env`; the default
`mariadb` hostname resolves only inside the Compose network.

## Application Structure

- `app/Controllers` - HTTP controllers
- `app/Middleware` - application middleware
- `app/Models` - persistence models
- `app/Services` - business workflows
- `bootstrap/app.php` - framework bootstrap
- `config/app.php` - application and infrastructure configuration
- `database/migrations` - ordered database migrations
- `routes/web.php` - explicit web routes
- `resources/views` - Twig layouts, pages, partials, and macros
- `resources/css`, `resources/js` - Vite and Tailwind entry points
- `public` - the only web-accessible directory
- `storage` - generated caches and logs
- `tests` - application tests
- `wps` - console entry point

## Console

```bash
php wps
php wps route:list
php wps app:setup
php wps app:check
php wps app:check --database
php wps make:controller Product
php wps make:model Product
php wps make:migration create_products_table
php wps migrate
php wps migrate:rollback
```

Generated classes use the application namespace and never modify the installed
framework package under `vendor/`.

`app:setup` prepares `.env` only if it is missing and creates writable storage
directories. It does not overwrite secrets, run migrations, or load demo data.
`app:check` checks configuration, storage, and built assets. Add `--database` to
test connectivity without printing connection secrets.

## Updating An Existing Application

The skeleton now requires stable `webpagestudio/wps-micro:^3.1`. After updating
the application files, install locked dependencies, build assets, and migrate:

```bash
composer install
npm ci
npm run build
php wps app:check --database
php wps migrate
```

Inside Docker, use `docker compose exec fpm php wps ...` for console commands.
Back up your database before applying migrations. Historical migrations remain
intact; the new migration adds a nullable owner and an index to `home_messages`.
Existing ownerless demo records remain public and cannot be edited through the
private CRUD. New user messages appear only in their owner's account.

Rolling back the ownership migration removes ownership metadata. Do not serve
the application during rollback: restore a compatible database backup when
downgrading, otherwise previously private rows become ownerless.

## Example Workflow

Register, log in, and open **Account > My messages** to create, edit, or delete
your own messages. Foreign and missing message IDs both return 404. Mutations
use POST, PUT, and DELETE with CSRF protection; GET requests never change data.
Lists show ten records per page. The public home page keeps the original model
example without exposing private messages.

Optional demonstration data is separate from new schema migrations:

```bash
php wps db:seed --user=you@example.com
```

Register that account first. This command creates no users or passwords and
skips examples whose titles already exist for that user. It refuses production
execution unless `--force` is supplied. It never runs automatically at boot.

The original released migration still contains its historical welcome record;
it is retained for compatibility, not used as a pattern for new seed data.

## Routes

Register routes in `routes/web.php`:

```php
use App\Controllers\ControllerProduct;
use WpsMicro\Core\Router;

return static function (Router $router): void {
    $router->get('/products/{id}', [ControllerProduct::class, 'actionShow'])
        ->whereNumber('id')->name('products.show');
};
```

Generate links with `route('products.show', {id: product.id})` in Twig or
`$router->url('products.show', ['id' => $id])` in PHP. The built-in account and
message routes use names, groups, and middleware from framework 3.1.

## Views

Controllers render templates from `resources/views`:

```php
return $this->render('products/show.twig', [
    'product' => $product,
]);
```

Templates can extend layouts and include reusable partials:

```twig
{% extends 'layouts/app.twig' %}

{% block content %}
    {% include 'products/_card.twig' with { product: product } %}
{% endblock %}
```

Use `layouts/account.twig` and its `account_content` block for protected pages.
`components/field.twig` renders inputs or textareas with old input, validation
messages, labels, and accessible error associations. Password values are never
repopulated. `components/flash.twig` is rendered once by the base layout.
`components/pagination.twig` takes `page`, `pages`, and a `route_name`.

```twig
{% include 'components/field.twig' with {
    name: 'title', label: 'Title', value: message.title|default(''), maxlength: 255
} only %}
```

## Quality Checks

```bash
composer quality
npm run build
```

PHPStan level 6 checks application code without a suppression baseline. PHPUnit
covers registration/login/logout, private CRUD, ownership, CSRF, validation,
pagination, setup, seeding, and route listing. CI also applies and rolls back
the real migrations against a disposable MariaDB database. The application
targets PHP 8.3, 8.4, and 8.5. Commit `composer.lock` for reproducible deployments.

## Production

Create the production environment and start the immutable images:

```bash
cp .env.production.example .env.production
docker compose --env-file .env.production -f docker-compose.production.yaml up -d --build
```

The production Dockerfile builds frontend assets and Composer dependencies in
separate stages. The final images contain only runtime application files.
