<?php

namespace LaraCrud\Console\Commands;

use LaraMake\Console\Commands\Abstracts\ClassMaker;
use LaraMake\Exceptions\LaraCommandException;
use LaraRepo\Contracts\RepositoryInterface;
use LaraRepo\Eloquent\AbstractRepository;
use LaraSupport\Facades\LaraDB;

class MakeRepository extends ClassMaker
{
//$ php artisan l:crud-repository-interface _db
//$ php artisan l:crud-repository-interface _db:table_names
//$ php artisan l:crud-repository-interface _db:table_names_separate_with_comma

    /**
     * @var string
     */
    public $commandName = 'crud-repository';

    /**
     * @var string
     */
    public $instance = 'Repository';

    /**
     * @var string
     */
    public $rootPath = null;


    /**
     * @var string
     */
    protected $description = 'Make flexible Models with extends ' . AbstractRepository::GROUP;

    /**
     * @var bool
     */
    public $makeBase = true;

    /**
     * @var string
     */
    public $parent = AbstractRepository::class;

    public $__model;

    public function configure()
    {
        if (empty($this->rootPath)) {
            $this->rootPath = config('lara_crud.root_namespaces.repository', '');
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
        $this->__implements = [RepositoryInterface::class];
        if (false == $this->createBasePattern($content)) {
            // @TODO show dont saved message
            return false;
        }
        $this->__abstract = false;

        $data = [];
        foreach ($dbStructure as $table => $columnsInfo) {
            if (in_array($table, $this->ignoreTables)) {
                continue;
            }
            if (!in_array($table, $dbTables)) {
                continue;
            }
            $pattern = str_singular(title_case($table));
            $pattern = str_replace('_', '', $pattern);
            $this->__pattern = $pattern . 'Repository';
            $interface = str_replace('Eloquent', 'Contracts', $this->__namespace);
            $interface .= DIRECTORY_SEPARATOR . $pattern . 'RepositoryInterface';
            $modelClass = config('lara_crud.root_namespaces.model', '');
            $modelClass .= DIRECTORY_SEPARATOR . $pattern;
            $this->__use = [$modelClass];
            $this->__implements = [$interface];
            $this->__method['public'] = [];
            $this->__method['public'][] = [
                'name' => 'modelClass',
                'content' => sprintf('return %s::class;', $pattern)
            ];
            $this->createFileBy($this->__pattern, $content);
        }
    }


    public function replaceModelKeyWord($content, $keyWord, $input)
    {
        return str_replace($keyWord, $input, $content);
    }

}