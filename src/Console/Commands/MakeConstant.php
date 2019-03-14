<?php

namespace LaraCrud\Console\Commands;

use LaraCrud\Controllers\LaraController;
use LaraMake\Console\Commands\Abstracts\ClassMaker;
use LaraMake\Console\Commands\Abstracts\TxtMaker;
use LaraSupport\Facades\LaraDB;

class MakeConstant extends TxtMaker
{


    /**
     * @var string
     */
    public $extension = '.php';

//$ php artisan l:crud-controller _db
//$ php artisan l:crud-controller _db:table_names
//$ php artisan l:crud-controller _db:table_names_separate_with_comma
    /**
     * @var string
     */
    public $commandName = 'crud-constant';

    /**
     * @var string
     */
    public $instance = 'Controller';

    /**
     * @var string
     */
    protected $description = 'Make flexible Models with extends ' . LaraController::class;

    public $ignoreTables = [
        'migrations',
        'password_reset'
    ];

    public function configure()
    {
        if (empty($this->rootPath)) {
            $this->rootPath = config('lara_crud.root_namespaces.constant', '');
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

        $constants = [];
        foreach ($dbStructure as $table => $columnsInfo) {
            if (in_array($table, $this->ignoreTables)) {
                continue;
            }
            if (!in_array($table, $dbTables)) {
                continue;
            }

            foreach ($columnsInfo as $column => $info) {
                if ('enum' != $info['type']) {
                    continue;
                }

                $values = $info['column_type'];
                $values = str_replace('enum(', '', $values);
                $values = str_replace(')', '', $values);
                $values = str_replace("'", '', $values);
                $values = explode(",", $values);

                if (['active', 'inactive'] == $values) {
                    $constName = 'ConstActiveInactive';
                }elseif (['yes', 'no'] == $values) {
                    $constName = 'ConstYesNo';
                } else {
                    $constName = str_slug($table);
                    $constName = title_case($constName);
                    $constName = str_singular($constName);
                    $constName = str_replace('-', '', $constName);
                    $constName = 'Const' . $constName . str_replace('-', '', title_case(str_slug($column)));
                }
                foreach ($values as $value) {
                    $constants[$constName][strtoupper($value)] = $value;
                }
            }
        }
        if (!starts_with($content, '<?php' . PHP_EOL . PHP_EOL)) {
            $content = '<?php' . PHP_EOL . PHP_EOL . $content;
        }
        $content .= $this->getConstContent($constants);
        $this->__pattern = 'constants';
        $this->createFileBy($this->__pattern, $content);
    }

    public function getConstContent($constants)
    {
        $html = '';
        foreach ($constants as $constant => $values) {
            $html .= 'class ' . $constant . PHP_EOL;
            $html .= '{' . PHP_EOL;
            foreach ($values as $key => $value) {
                $html .= TAB . "const $key = '$value';" . PHP_EOL;
            }

            $html .= '}' . PHP_EOL . PHP_EOL;
        }
        return $html;
    }

}