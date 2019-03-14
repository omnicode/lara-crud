<?php

namespace LaraCrud\Console\Commands;

use Illuminate\Support\ServiceProvider;
use LaraMake\Console\Commands\Abstracts\ClassMaker;
use LaraSupport\Facades\LaraDB;

class MakeRepositoryServiceProvider extends ClassMaker
{
//$ php artisan l:crud-repository-interface _db
//$ php artisan l:crud-repository-interface _db:table_names
//$ php artisan l:crud-repository-interface _db:table_names_separate_with_comma

    /**
     * @var string
     */
    public $commandName = 'crud-repository-service-provider';

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
        'bind',
    ];

    /**
     * @var string
     */
    public $stub = 'repository-service-provider.stub';


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

            $pattern = str_singular(title_case($table));
            $pattern = str_replace('_', '', $pattern);
            $interface = config('lara_crud.root_namespaces.repository-interface', '') . DIRECTORY_SEPARATOR . $pattern . 'RepositoryInterface';
            $repository = config('lara_crud.root_namespaces.repository', '') . DIRECTORY_SEPARATOR . $pattern . 'Repository';
            $interfaceShort = $this->getNamespaceBaseName($interface, $this->__use);
            $repositoryShort = $this->getNamespaceBaseName($repository, $this->__use);
            $this->__bind[$interfaceShort] = $repositoryShort;
        }


        $this->__pattern = 'RepositoryServiceProvider';
        $this->__confirm = true;
        $this->createFileBy($this->__pattern, $content);

    }


    /**
     * @param $type
     * @param $pattern
     * @param string $command
     */
    protected function makeDBCorrespondType($type, $pattern, $command = '') {
        if (empty($command)) {
            $command = lcfirst($type);
        }

        $this->call(self::LARA_MAKE . $command, ['pattern' => $pattern . $type, '--confirm' => true]); // todo fix multiple time save same file
    }

    public function replaceBindKeyWord($content, $keyWord, $input)
    {
        $str = '';
        foreach ($input as $interface => $repo) {
            $str .= sprintf('$this->app->bind(%s::class, %s::class);', $interface, $repo) . PHP_EOL  . TAB . TAB;
        }
        $str = rtrim($str,  PHP_EOL . TAB . TAB );
        return str_replace($keyWord, $str, $content);
    }

}
