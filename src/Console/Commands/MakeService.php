<?php

namespace LaraCrud\Console\Commands;

use LaraMake\Console\Commands\Abstracts\ClassMaker;
use LaraService\Services\LaraService;
use LaraSupport\Facades\LaraDB;

class MakeService extends ClassMaker
{
//$ php artisan l:crud-service _db
//$ php artisan l:crud-service _db:table_names
//$ php artisan l:crud-service _db:table_names_separate_with_comma

    /**
     * @var string
     */
    public $commandName = 'crud-service';

    /**
     * @var string
     */
    public $instance = 'Service';

    /**
     * @var string
     */
    protected $description = 'Make flexible Models with extends ' . LaraService::class;

    /**
     * @var bool
     */
    public $makeBase = true;

    /**
     * @var string
     */
    public $parent = LaraService::class;


    public function configure()
    {
        if (empty($this->rootPath)) {
            $this->rootPath = config('lara_crud.root_namespaces.service', '');
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
        $this->__abstract = true;
        if (false == $this->createBasePattern($content)) {
            // @TODO show dont saved message
            return false;
        }

        foreach ($dbStructure as $table => $columnsInfo) {
            if (in_array($table, $this->ignoreTables)) {
                continue;
            }
            if (!in_array($table, $dbTables)) {
                continue;
            }
            $pattern = str_singular(title_case($table));
            $pattern = str_replace('_', '', $pattern);
            $this->__pattern = $pattern . 'Service';

            $interface = config('lara_crud.root_namespaces.repository-interface', '') . DIRECTORY_SEPARATOR . $pattern . 'RepositoryInterface';
            $shortInterface = $pattern . 'RepositoryInterface';

            $validator = config('lara_crud.root_namespaces.validator', '') . DIRECTORY_SEPARATOR . $pattern . 'Validator';
            $shortValidator = $pattern . 'Validator';

            $this->__use = [$validator, $interface];
            $this->__method['public'] = [];
            $interfaceProp = '$' . lcfirst($pattern) . 'Repository';
            $validatorProp = '$' . lcfirst($shortValidator);

            $this->__method['public'][] = [
                'name' => '__construct',
                'content' => sprintf(
                    '$this->baseRepository = %s;%s$this->baseValidator = %s;',
                    $interfaceProp,
                    PHP_EOL . TAB . TAB,
                    $validatorProp
                ),
                'arguments' => [
                    sprintf('%s %s', $shortInterface, $interfaceProp),
                    sprintf('%s %s', $shortValidator, $validatorProp),
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