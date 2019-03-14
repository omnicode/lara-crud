<?php

namespace LaraCrud\Console\Commands;

use LaraMake\Console\Commands\Abstracts\InterfaceMaker;
use LaraRepo\Contracts\RepositoryInterface;
use LaraSupport\Facades\LaraDB;

class MakeRepositoryInterface extends InterfaceMaker
{
//$ php artisan l:crud-repository-interface _db
//$ php artisan l:crud-repository-interface _db:table_names
//$ php artisan l:crud-repository-interface _db:table_names_separate_with_comma

    /**
     * @var string
     */
    public $commandName = 'crud-repository-interface';

    /**
     * @var string
     */
    public $instance = 'RepositoryInterface';

    /**
     * @var string
     */
    public $rootPath = null;


    /**
     * @var string
     */
    protected $description = 'Make flexible Models with extends ' . RepositoryInterface::class;

    /**
     * @var bool
     */
    public $makeBase = true;

    /**
     * @var string
     */
    public $parents = RepositoryInterface::class;

    public $processKeyWordSuffix= [
//        'pattern' => 'Interface'
    ];

    public function configure()
    {
        if (empty($this->rootPath)) {
            $this->rootPath = config('lara_crud.root_namespaces.repository-interface', '');
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
        if (false == $this->createBasePattern($content)) {
            // @TODO show dont saved message
            return false;
        }

        foreach ($dbStructure as $table => $columnsInfo) {
            if (in_array($table, $this->ignoreTables) || !in_array($table, $dbTables)) {
                continue;
            }
            $this->__use = [];
            $this->__pattern = str_singular(title_case($table));
            $this->__pattern = str_replace('_', '', $this->__pattern) . 'RepositoryInterface';
            $this->createFileBy($this->__pattern, $content);
        }
    }
}