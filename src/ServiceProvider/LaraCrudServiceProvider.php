<?php
namespace LaraCrud\ServiceProvider;

use Illuminate\Support\ServiceProvider;
use LaraLink\Components\LinkRoute;
use LaraLink\Links\ItemActionLink;
use LaraTest\Console\Commands\MakeTestController;
use LaraTest\Console\Commands\MakeTestModel;

class LaraCrudServiceProvider extends ServiceProvider
{
    /**
     *
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'../../routes.php');

        $configPath = __DIR__ . '/../../config/lara_crud.php';
        $this->mergeConfigFrom($configPath, 'lara_crud');

        $path = __DIR__.'/../../resources/views';
        $this->loadViewsFrom($path, 'lara-crud');

        $this->publishes([
            $path => resource_path('views/vendor/lara-crud'),
            $configPath => config_path('lara_crud.php')
        ]);
    }

}
