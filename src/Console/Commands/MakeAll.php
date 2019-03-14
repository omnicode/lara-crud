<?php

namespace LaraCrud\Console\Commands;

use LaraMake\Console\Commands\Abstracts\ClassMaker;
use LaraMake\Exceptions\LaraCommandException;

class MakeAll extends ClassMaker
{

//$ php artisan l:crud-crud _db
//$ php artisan l:crud-crud _db:table_names
//$ php artisan l:crud-crud _db:table_names_separate_with_comma --with-relations
//$ php artisan l:crud-crud _db:cars,brands
//$ php artisan l:crud-crud _db:cars,brands --components=model

    /**
     * @var string
     */
    public $commandName = 'crud-all';

    /**
     * @var array
     */
    protected $defaultOptions = [
        'confirm',
        'confirm-back-slash',
        'confirm-overwrite',
        'choice-default',
        'path' => 'path=',
        'root-path' => 'root-path=',
        'components' => 'components='
    ];

    protected $__components;

    protected $availableComponents = [
        'model',
        'validator',
        'repository-interface',
        'repository',
        'repository-service-provider',
        'service',
        'controller',
        'route',
        'view-composer',
        'composer-service-provider',
        'blade-view',
        'constant',
    ];

    protected $defaultComponents = [
        'model',
        'validator',
        'repository-interface',
        'repository',
        'repository-service-provider',
        'service',
        'controller',
        'route',
        'view-composer',
        'composer-service-provider',
        'blade-view',
        'constant',
    ];

    protected function makeBasedDb($pattern, $content)
    {
        if (is_null($this->__components)) {
            $components = $this->defaultComponents;
//            $this->call(self::LARA_MAKE .'crud-controller', ['pattern' => '_db:migrations', '--confirm' => true, '--choice-default' => true]);
        } else {
            $components = explode(',', $this->__components);
            $diffComponents = array_diff($components, $this->availableComponents);
            if($diffComponents) {
                $message = $this->attentionSprintF(
                    'Only %s can pass.  fix it %s components',
                    implode(',', $this->availableComponents),
                    implode(',', $diffComponents)
                );
                throw new LaraCommandException($message);
            }
        }

        foreach ($components as $component) {
            if ($component == 'model') {
                $this->call(self::LARA_MAKE .'crud-' . $component, ['pattern' => $pattern, '--confirm' => true, '--choice-default' => true, '--detailed' => true]);
            } else {
                $this->call(self::LARA_MAKE .'crud-' . $component, ['pattern' => $pattern, '--confirm' => true, '--choice-default' => true]);
            }
        }
        $this->info('final');
        return true;
    }

}