<?php

namespace LaraCrud\ServiceProvider;

use LaraCrud\Console\Commands\MakeAll;
use LaraCrud\Console\Commands\MakeComposerServiceProvider;
use LaraCrud\Console\Commands\MakeConstant;
use LaraCrud\Console\Commands\MakeController;
use LaraCrud\Console\Commands\MakeModel;
use LaraCrud\Console\Commands\MakeRepository;
use LaraCrud\Console\Commands\MakeRepositoryInterface;
use LaraCrud\Console\Commands\MakeRepositoryServiceProvider;
use LaraCrud\Console\Commands\MakeRoute;
use LaraCrud\Console\Commands\MakeService;
use LaraCrud\Console\Commands\MakeValidator;
use LaraCrud\Console\Commands\MakeBladeViews;
use LaraCrud\Console\Commands\MakeViewComposer;
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
        $this->runningInConsole([
            MakeAll::class,
            MakeModel::class,
            MakeValidator::class,
            MakeRepositoryInterface::class,
            MakeRepository::class,
            MakeRepositoryServiceProvider::class,
            MakeComposerServiceProvider::class,
            MakeService::class,
            MakeController::class,
            MakeRoute::class,
            MakeBladeViews::class,
            MakeViewComposer::class,
            MakeConstant::class
        ]);
    }

    /**
     *
     */
    public function register()
    {
        $this->registerProviders(LaraViewServiceProvider::class);
    }
}
