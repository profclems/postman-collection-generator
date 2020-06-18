# PostmanCollectionGenerator
This package automatically generates postman collection from laravel api/web routes with just a single command

## Installation

Install this bundle through [Composer](https://getcomposer.org/):

```bash
composer require profclems/postman-collection-generator
```

## Usage

To generate collection for api routes, run
```bash
php artisan postman:collection:export NameForCollection --api
```

To generate collection for web routes, run
```bash
php artisan postman:collection:export NameForCollection --web
```
Change `NameForCollection` to the name you want the collection file saved as.

## Options
```bash
--api or --web to specify the type of route to export
--url to specify the url for the collection. Eg. --url=localhost
--port to specify the port. Eg --port=8000
```
