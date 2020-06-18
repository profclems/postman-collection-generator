<?php

namespace Profclems\PostmanCollectionGenerator;

use Illuminate\Support\ServiceProvider;

class PostmanCollectionServiceProvider extends ServiceProvider
{
    /**
     * Register the command.
     */
    public function register()
    {
        $this->commands(ExportPostmanCollection::class);
    }
}
