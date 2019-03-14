<?php

namespace LaraCrud\Console\Commands;

use LaraCrud\Controllers\LaraController;
use LaraMake\Console\Commands\Abstracts\ClassMaker;
use LaraSupport\Facades\LaraDB;

class MakeController extends ClassMaker
{
//$ php artisan l:crud-controller _db
//$ php artisan l:crud-controller _db:table_names
//$ php artisan l:crud-controller _db:table_names_separate_with_comma
    /**
     * @var string
     */
    public $commandName = 'crud-controller';

    /**
     * @var string
     */
    public $instance = 'Controller';

    /**
     * @var string
     */
    protected $description = 'Make flexible Models with extends ' . LaraController::class;

    /**
     * @var bool
     */
    public $makeBase = true;

    /**
     * @var string
     */
    public $parent = LaraController::class;

    public function configure()
    {
        if (empty($this->rootPath)) {
            $this->rootPath = config('lara_crud.root_namespaces.controller', '');
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

        $this->__confirm = true;
//        $this->__property['protected']['viewRootPath'] = $suffix ? lcfirst($suffix) . '.pages' : 'pages';
        $this->__property['protected']['viewRootPath'] = config('lara_crud.viewRootPath', 'pages');

        if (false == $this->createBasePattern($content)) {
            // @TODO show dont saved message
            return false;
        }

        $this->__property = [];

        foreach ($dbStructure as $table => $columnsInfo) {
            if (in_array($table, $this->ignoreTables)) {
                continue;
            }
            if (!in_array($table, $dbTables)) {
                continue;
            }
            $pattern = str_singular(title_case($table));
            $pattern = str_replace('_', '', $pattern);
            $this->__pattern = $pattern . 'Controller';


            $service = config('lara_crud.root_namespaces.service', '') . DIRECTORY_SEPARATOR . $pattern . 'Service';
            $this->__use = [$service];
            $this->__method['public'] = [];
            $serviceProp = '$' . lcfirst($pattern) . 'Service';

            $this->__method['public'][] = [
                'name' => '__construct',
                'content' => sprintf(
                    'parent::__construct();%s$this->baseService = %s;',
                    PHP_EOL . TAB . TAB,
                    $serviceProp
                ),
                'arguments' => [
                    sprintf('%s %s', str_singular($pattern) . 'Service', $serviceProp),
                ]
            ];
            $this->createFileBy($this->__pattern, $content);
        }
    }

    public function replaceModelKeyWord($content, $keyWord, $input)
    {
        return str_replace($keyWord, $input, $content);
    }

}