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
        $this->loadRoutesFrom(__DIR__ . DIRECTORY_SEPARATOR . '..'. DIRECTORY_SEPARATOR .'routes.php');

        $configPath = $this->getPackagePath() . 'config' . DIRECTORY_SEPARATOR . 'lara_crud.php';
        $this->mergeConfigFrom($configPath, 'lara_crud');

        $path = $this->getPackagePath() . 'resources' . DIRECTORY_SEPARATOR . 'views';
        $this->loadViewsFrom($path, 'lara-crud');

        $this->publishes([
            $path => resource_path('views' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'lara-crud'),
            $configPath => config_path('lara_crud.php')
        ]);

    }

    /**
     * @return string
     */
    protected function getPackagePath()
    {
        return  __DIR__ . DIRECTORY_SEPARATOR . '..'. DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR ;
    }

}
