<?php

namespace LaraCrud\Console\Commands;

use Illuminate\Support\ServiceProvider;
use LaraMake\Console\Commands\Abstracts\ClassMaker;
use LaraSupport\Facades\LaraDB;

class MakeComposerServiceProvider extends ClassMaker
{
//$ php artisan l:crud-repository-interface _db
//$ php artisan l:crud-repository-interface _db:table_names
//$ php artisan l:crud-repository-interface _db:table_names_separate_with_comma

    /**
     * @var string
     */
    public $commandName = 'crud-composer-service-provider';

    /**
     * @var string
     */
    public $instance = 'ServiceProvider';

    /**
     * @var string
     */
    protected $description = 'Make flexible Models with extends ' . ServiceProvider::class;

    /**
     * @var bool
     */
    public $makeBase = false;

    public $keyWords = [
        'use',
        'method',
        'call',
    ];

    /**
     * @var string
     */
    public $stub = 'composer-service-provider.stub';


    /**
     * @param string $path
     * @return string
     */
    public function getStubContent($path = __DIR__)
    {
        return parent::getStubContent($path);
    }

    public function configure()
    {
        if (empty($this->rootPath)) {
            $this->rootPath = config('lara_crud.root_namespaces.repository-service-provider', '');
        }

        parent::configure();
    }

    protected function makeBasedDb($pattern, $content)
    {
        $dbStructure = LaraDB::getDBStructure();
        $dbTables = array_keys($dbStructure);
        if ($pattern == config('lara_make.by_database')) {
            $tables = $dbTables;
        } else {
            // @TODO ':' make dynamically
            $str = str_replace_first(config('lara_make.by_database') . ':', '', $pattern);
            $tables = explode(',', $str);
            $diffTables = array_diff($tables, $dbTables);
            if($diffTables) {
                $message = $this->attentionSprintF(
                    'Only %s table can make by database. %s tables absent in your db please fix it',
                    implode(',', $dbStructure),
                    implode(',', $diffTables)
                );
                throw new LaraCommandException($message);
            }
        }
        $dbTables = array_diff($tables, $this->ignoreTables);

        $data = [];
        foreach ($dbStructure as $table => $columnsInfo) {
            if (in_array($table, $this->ignoreTables)) {
                continue;
            }

            if (!in_array($table, $dbTables)) {
                continue;
            }

            foreach (array_keys($columnsInfo) as $column) {
                if (ends_with($column, ['_id'])) {
                    $data[$column][] = $table;
                }
            }
        }

        foreach ($data as $column => $tables) {
            $title = str_replace_last('_id', '', $column);
            $title = str_replace('_', '', $title);
            $callMethod = str_plural($title);
            $callMethod = ucfirst($callMethod);
            $callMethod = 'set' . $callMethod;

            $this->__call[] = $callMethod;

            $composer = config('lara_crud.root_namespaces.view-composer', '');

            $composer = $composer . DIRECTORY_SEPARATOR . title_case($title) . 'Composer';
            $methodContent = "view()->composer(". PHP_EOL
                . TAB . TAB . TAB . "[" . PHP_EOL;
            foreach ($tables as $table) {
                $methodContent .= TAB . TAB . TAB . TAB . "'pages." . str_slug($table) . ".partials.form',". PHP_EOL;
            }

            $methodContent .= TAB . TAB . TAB . "]," . PHP_EOL
                . TAB . TAB . TAB . basename($composer) . '::class'. PHP_EOL
                . TAB . TAB . ");" . PHP_EOL;

            $this->__use[] = $composer;

            $this->__method['public'][] = [
                'name' => $callMethod,
                'content' => $methodContent
            ];
        }



        $this->__pattern = 'ComposerServiceProvider';
        $this->__confirm = true;
        $this->createFileBy($this->__pattern, $content);

    }

    public function replaceCallKeyWord($content, $keyWord, $input)
    {
        $str = '';
        foreach ($input as $method) {
            $str .= '$this->' . $method . '();'. PHP_EOL  . TAB . TAB;
        }
        $str = rtrim($str,  PHP_EOL . TAB . TAB );
        return str_replace($keyWord, $str, $content);
    }

}
