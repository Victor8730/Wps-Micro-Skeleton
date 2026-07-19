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
php wps make:controller Product
php wps make:model Product
php wps make:migration create_products_table
php wps migrate
php wps migrate:rollback
```

Generated classes use the application namespace and never modify the installed
framework package under `vendor/`.

## Routes

Register routes in `routes/web.php`:

```php
use App\Controllers\ControllerProduct;
use WpsMicro\Core\Router;

return static function (Router $router): void {
    $router->get('/products/{id}', [ControllerProduct::class, 'actionShow']);
    $router->post('/cart/add', [ControllerCart::class, 'actionAdd']);
};
```

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

## Production

Create the production environment and start the immutable images:

```bash
cp .env.production.example .env.production
docker compose --env-file .env.production -f docker-compose.production.yaml up -d --build
```

The production Dockerfile builds frontend assets and Composer dependencies in
separate stages. The final images contain only runtime application files.
