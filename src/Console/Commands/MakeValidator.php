<?php

namespace LaraCrud\Console\Commands;

use LaraMake\Console\Commands\Abstracts\ClassMaker;
use LaraMake\Exceptions\LaraCommandException;
use LaraSupport\Facades\LaraDB;
use LaraValidation\LaraValidator;

class MakeValidator extends ClassMaker
{
//$ php artisan l:crud-validator _db
//$ php artisan l:crud-validator _db:table_names
//$ php artisan l:crud-validator _db:table_names_separate_with_comma

    /**
     * @var string
     */
    public $commandName = 'crud-validator';

    /**
     * @var string
     */
    public $instance = 'Validator';

    /**
     * @var string
     */
    protected $description = 'Make flexible Models with extends ' . LaraValidator::class;

    /**
     * @var bool
     */
    public $makeBase = true;

    /**
     * @var string
     */
    public $parent = LaraValidator::class;

    public $processKeyWordSuffix = [
//        'pattern' => 'Validator'
    ];

    /**
     * @var array
     */
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
        'validator'
    ];

    /**
     * @var string
     */
    public $stub = 'validator.stub';

    /**
     * @var
     */
    public $__validator;

    /**
     * @var
     */
    private $_tables;

    public function configure()
    {
        if (empty($this->rootPath)) {
            $this->rootPath = config('lara_crud.root_namespaces.validator', '');
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

        $tables = [];
        foreach (array_keys($dbStructure) as $table) {
            $pattern = str_singular(title_case($table));
            $pattern = str_replace('_', '', $pattern);
            $tables[$table] = $pattern;
        }

//        $tables['chassis'] = 'Chassis';

        foreach ($dbStructure as $table => $columnsData) {
            if (in_array($table, $this->ignoreTables)) {
                continue;
            }

            if (!in_array($table, $dbTables)) {
                continue;
            }

            if (in_array($table, ['migrations', 'password_resets'])) {

            }
            $pattern = $tables[$table] . 'Validator';
            $validator = [];
            foreach ($columnsData as $column => $data) {
                if ('auto_increment' == $data['extra']) {
                    continue;
                }

                if (in_array($column, $this->ignoreColumns)) {
                    continue;
                }

                if (isset($this->ignoreTableColumns[$table]) && in_array($column, $this->ignoreTableColumns[$table])) {
                    continue;
                }


                if (false == $data['is_nullable']) {
                    $validator['required'][] = $column;
                }

                if ('int' == $data['type']) {
                    $validator['numeric'][] = $column;
                    if (str_contains($data['column_type'], 'unsigned')) {
                        $validator['max'][$column] = 4294967295;
                        $validator['min'][$column] = 0;
                    } else {
                        $validator['max'][$column] = 2147483647 ;
                        $validator['min'][$column] = -2147483648;
                    }
                } elseif ('tinyint' == $data['type']) {
                    $validator['numeric'][] = $column;
                    if (str_contains($data['column_type'], 'unsigned')) {
                        $validator['max'][$column] = 256;
                        $validator['min'][$column] = 0;
                    } else {
                        $validator['max'][$column] = 127;
                        $validator['min'][$column] = -128;
                    }
                } elseif ('smallint' == $data['type']) {
                    $validator['numeric'][] = $column;
                    if (str_contains($data['column_type'], 'unsigned')) {
                        $validator['max'][$column] = 65535 ;
                        $validator['min'][$column] = 0;
                    } else {
                        $validator['max'][$column] = 32767  ;
                        $validator['min'][$column] = -32768;
                    }
                } elseif ('mediumint' == $data['type']) {
                    $validator['numeric'][] = $column;
                    if (str_contains($data['column_type'], 'unsigned')) {
                        $validator['max'][$column] = 65535 ;
                        $validator['min'][$column] = 0;
                    } else {
                        $validator['max'][$column] = 16777215;
                        $validator['min'][$column] = -8388608;
                    }
                } elseif ('year' == $data['type']) {
                    $validator['numeric'][] = $column;
                    $validator['max'][$column] = 2099;
                    $validator['min'][$column] = 1009;
                } elseif ('enum' == $data['type']) {
                    $enum = str_replace(['enum(',"'", ')'], [''], $data['column_type']);
                    $validator['enum'][$column] = $enum;
                } elseif ('datetime' == $data['type']) {
                    $validator['double'][] = $column;
                } elseif ('double' == $data['type']) {
                    $validator['double'][$column] = $data['column_type'];
                } elseif ('decimal' == $data['type']) {
                    $validator['decimal'][$column] = $data['column_type'];
                } elseif (in_array($data['type'], ['char', 'text', 'varchar', 'mediumtext'])) {
                    if ($data['type'] == 'text') {
//                        dd($data);
                    }

                    $validator['max'][$column] = $data['length'];
                } else {
//                    dd($data['type'], $data, $column, $table);
                }

                if (ends_with($column, '_id')) {
                    $_table = str_plural(str_replace_last('_id', '', $column));

                    if (isset($tables[$_table])) {
                        $validator['exists'][$column] = $tables[$_table];
                    } else {
                        $validator['exists-table'][$column] = $_table;
                    }
                }
            }

            $this->__use = [];
            $this->__validator = $validator;
            $this->createFileBy($pattern, $content);
        }

    }

    public function replaceValidatorKeyWord($content, $keyWord, $input)
    {

        $str = '';
        if (!empty($input)) {
            $str = '$this->validator';
            $_str = '$this->validator';

            if (!empty($input['required'])) {
                foreach ($input['required'] as $column) {
                    $str .=  PHP_EOL . TAB. TAB. TAB . sprintf("->required('%s')", $column);
                }
            }

            if (!empty($input['enum'])) {
                if ($_str !== $str) {
                    $str .=  PHP_EOL . TAB. TAB. TAB ;
                }
                foreach ($input['enum'] as $column => $value) {
                    $str .=  PHP_EOL . TAB. TAB. TAB . sprintf("->in('%s', '%s')", $column, $value);
                }
            }

            if (!empty($input['double'])) {
                if ($_str !== $str) {
                    $str .=  PHP_EOL . TAB. TAB. TAB ;
                }
                foreach ($input['double'] as $column => $info) {
                    $str .=  PHP_EOL . TAB. TAB. TAB . sprintf("//->double('%s', %s) //@TODO fix", $column, $info);
                }
            }

            if (!empty($input['decimal'])) {
                if ($_str !== $str) {
                    $str .=  PHP_EOL . TAB. TAB. TAB ;
                }
                foreach ($input['decimal'] as $column => $info) {
                    $str .=  PHP_EOL . TAB. TAB. TAB . sprintf("//->decimal('%s', %s) //@TODO fix", $column, $info);
                }
            }

            if (!empty($input['numeric'])) {
                if ($_str !== $str) {
                    $str .=  PHP_EOL . TAB. TAB. TAB ;
                }
                foreach ($input['numeric'] as $column) {
                    $str .=  PHP_EOL . TAB. TAB. TAB . sprintf("->numeric('%s')", $column);
                }
            }

            if (!empty($input['min'])) {
                if ($_str !== $str) {
                    $str .=  PHP_EOL . TAB. TAB. TAB ;
                }
                foreach ($input['min'] as $column => $value) {
                    $str .=  PHP_EOL . TAB. TAB. TAB . sprintf("->minLength('%s', %s)", $column, $value);
                }
            }

            if (!empty($input['max'])) {
                if ($_str !== $str) {
                    $str .=  PHP_EOL . TAB. TAB. TAB ;
                }
                foreach ($input['max'] as $column => $value) {
                    $str .=  PHP_EOL . TAB. TAB. TAB . sprintf("->maxLength('%s', %s)", $column, $value);
                }
            }

            if (!empty($input['date'])) {
                if ($_str !== $str) {
                    $str .=  PHP_EOL . TAB. TAB. TAB ;
                }
                foreach ($input['date'] as $column) {
                    $str .=  PHP_EOL . TAB. TAB. TAB . sprintf("->date('%s')", $column);
                }
            }

            if (!empty($input['exists-table'])) {
                if ($_str !== $str) {
                    $str .=  PHP_EOL . TAB. TAB. TAB ;
                }
                foreach ($input['exists-table'] as $column => $table) {
                    $str .=  PHP_EOL . TAB. TAB. TAB . sprintf("->exists('%s', '%s')// @TODO fix", $column, $table);
                }
                if (empty($input['exists'])) {
                    $str = str_replace_last('// @TODO fix', ';// @TODO fix', $str);
                }
            }

            if (!empty($input['exists'])) {
                if ($_str !== $str) {
                    $str .=  PHP_EOL . TAB. TAB. TAB ;
                }

                foreach ($input['exists'] as $column => $table) {
                    $fullClass = str_replace('Validators', 'Models', $this->__namespace) .DIRECTORY_SEPARATOR . $table;
                    $fullClass = str_replace('Admin' . DIRECTORY_SEPARATOR, '', $fullClass);
                    $table = $this->getNamespaceBaseName($fullClass, $this->__use);
                    $this->__use[] = $fullClass;
//                    if($this->__pattern == 'ChasiValidator') {
//                        dd($table, $fullClass, $this->__use);
//                    }
                    $str .=  PHP_EOL . TAB. TAB. TAB . sprintf("->exists('%s', [%s::class])", $column, $table);
                }
            }

            $str .= ';';
//            dd($str);
        }

        if (empty($str)) {
            return str_replace(TAB . TAB . $keyWord . PHP_EOL, '', $content);
        }
        return str_replace($keyWord, $str, $content);
    }


    /**
     * @param $type
     * @param string $command
     */
    protected function makeDBCorrespondType($type, $command = '') {
        if (empty($command)) {
            $command = lcfirst($type);
        }
        foreach (array_keys($this->_tables) as $index => $model) {
            $this->call(self::LARA_MAKE . $command, ['pattern' => $model . $type ]);
        }
    }


}