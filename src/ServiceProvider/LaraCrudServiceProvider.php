<?php
namespace LaraCrud\ServiceProvider;

use Illuminate\Support\ServiceProvider;
use LaraLink\Components\LinkRoute;
use LaraLink\Links\ItemActionLink;
use LaraSupport\LaraDB;
use LaraSupport\LaraServiceProvider;
use LaraSupport\Str;
use LaraTest\Console\Commands\MakeTestController;
use LaraTest\Console\Commands\MakeTestModel;

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
        
    }
}
