[![Build Status](https://travis-ci.com/profclems/postman-collection-generator.svg?branch=master)](https://travis-ci.com/profclems/postman-collection-generator)
[![Latest Stable Version](https://poser.pugx.org/phpunit/phpunit/v)](//packagist.org/packages/phpunit/phpunit) 
[![Total Downloads](https://poser.pugx.org/phpunit/phpunit/downloads)](//packagist.org/packages/phpunit/phpunit) 
[![Latest Unstable Version](https://poser.pugx.org/phpunit/phpunit/v/unstable)](//packagist.org/packages/phpunit/phpunit) 
[![License](https://poser.pugx.org/phpunit/phpunit/license)](//packagist.org/packages/phpunit/phpunit)
# Laravel Routes to Postman Collection Generator
This package automatically generates postman collection from laravel api/web routes with just a single command

## Postman Schema
Supports postman collection [Schema v2.1.0](https://schema.getpostman.com/json/collection/v2.1.0/collection.json)

## Installation

Install this bundle through [Composer](https://getcomposer.org/):

```bash
composer require profclems/postman-collection-generator
```
Add the PostmanCollectionServiceProvider to providers in the config/app.php

```php
'providers' => [
    ...
    \Profclems\PostmanCollectionGenerator\PostmanCollectionServiceProvider::class,
];
```
## Usage

To generate collection for api routes, run
```bash
php artisan postman:collection:export NameForCollection --api
```
This will generate a ```yyyy_mm_dd_his_NameForCollection_api.json``` in your Laravel ```storage/app``` folder.

To generate collection for web routes, run
```bash
php artisan postman:collection:export NameForCollection --web
```
This will generate a ```yyyy_mm_dd_his_NameForCollection_web.json``` in your Laravel ```storage/app``` folder.


Change `NameForCollection` to the name you want the collection file saved as.

## Options
By default, the url is set to ```{{base_url}}``` which is a postman variable that can be set in your postman environment.
```bash
--api or --web to specify the type of route to export
--url to specify the url for the collection. Eg. --url=localhost
--port to specify the port. Eg --port=8000
```

## Awards & Nominations
[![PHP Classes Innovative Award Nominee](https://www.phpclasses.org/award/innovation/nominee.gif "PHP Classes Innovative Award June 2020 Nominee")](https://www.phpclasses.org/package/11687-PHP-Generate-routes-for-an-API-or-Web-applications.html)
