<?php

namespace LaraCrud\Console\Commands;

use LaraMake\Console\Commands\Abstracts\ClassMaker;
use LaraSupport\Facades\LaraDB;
use LaraViewComposer\ViewComposer\ListComposer;

class MakeViewComposer extends ClassMaker
{

//$ php artisan l:model [test2] --choice-default --confirm --use [Carbon/Carbon,LaraSupport/Str,App\\Models,Illuminate/Database/Eloquent/Model] --abstract --path app/Models --implements Illuminate\\Contracts\\Queue\\ShouldQueue --trait [LaraModel\\Traits\\ModelExtrasTrait,Illuminate\\Database\\Eloquent\\SoftDeletes] --constant [testName=test,as=as,name=name] --property [public=[0=name,description=some___description]] --method [public=[[arguments=[0=name,str=sas],content=return___true____,name=aa]]]

    /**
     * @var string
     */
    public $commandName = 'crud-view-composer';

    /**
     * @var string
     */
    public $instance = 'Composer';

    public $keyWords = [
        'namespace',
        'use',
        'abstract' => 'abstract',
        'pattern',
        'parent',
        'implements',
        'trait',
        'constant',
        'property',
        'method',
        'repository',
    ];

    /**
     * @var string
     */
    protected $description = 'Make flexible Models with extends LaraModel\Models\LaraModel';

    /**
     * @var string
     */
    public $stub = 'view-composer.stub';


    /**
     * @var bool
     */
    public $makeBase = true;

    /**
     * @var string
     */
    public $parent = ListComposer::class;

    public function configure()
    {
        if (empty($this->rootPath)) {
            $this->rootPath = config('lara_crud.root_namespaces.view-composer', '');
        }

        parent::configure();
    }

    /**
     * @param string $path
     * @return string
     */
    public function getStubContent($path = __DIR__)
    {
        return parent::getStubContent($path);
    }

    /**
     * @param $pattern
     * @param $content
     * @return bool
     */
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
        $this->__property = [];
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
            $this->__pattern = $pattern . 'Composer';

            $interface = config('lara_crud.root_namespaces.repository-interface', '');


            $interface .= DIRECTORY_SEPARATOR . str_singular($pattern) . 'RepositoryInterface';
            $this->__use = [$interface];
            $this->__repository = str_singular($pattern) . 'RepositoryInterface' . "::class";
            $this->createFileBy($this->__pattern, $content);
        }
    }

    public function replaceRepositoryKeyWord($content, $keyWord, $input)
    {
        if (empty($input)) {
            $input = '[' . PHP_EOL . TAB . TAB . PHP_EOL . TAB . ']';
        }
        return str_replace($keyWord, $input, $content);
    }

}