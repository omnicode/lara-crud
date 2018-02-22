<?php

namespace LaraCrud\ServiceProvider;

use LaraSupport\LaraServiceProvider;
use LaraView\ServiceProvider\LaraViewServiceProvider;

class LaraCrudServiceProvider extends LaraServiceProvider
{
    /**
     *
     */
    public function boot()
    {
        $this->mergeConfig(__DIR__);
        $this->loadViews(__DIR__);
        $this->loadRoutes(__DIR__);
    }

    /**
     *
     */
    public function register()
    {
        $this->registerProviders(LaraViewServiceProvider::class);
    }
}
