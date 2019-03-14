<?php

namespace LaraCrud\Console\Commands;

use function GuzzleHttp\Psr7\str;
use Illuminate\Support\Facades\App;
use LaraMake\Console\Commands\Abstracts\BladeMaker;
use LaraMake\Console\Commands\Abstracts\HtmlMaker;
use LaraRestApi\Models\ApiModel;
use LaraSupport\Facades\LaraDB;
use SebastianBergmann\CodeCoverage\Report\PHP;

class MakeBladeViews extends BladeMaker
{
    /**
     * @var string
     */
    public $commandName = 'crud-blade-view';

    public $ignoreTables = [
        'migrations',
        'password_reset'
    ];

    public function configure()
    {
        if (empty($this->rootPath)) {
            $this->rootPath = config('lara_crud.viewRootPath', 'pages');
        }
        $this->rootPath = 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $this->rootPath;
        parent::configure();
    }

    public function getRelativeRootPath($path)
    {
        $root = config('lara_crud.viewRootPath', 'pages');
        if ($root) {
            $root .= '.';
        }

        $root .= 'partials.';
        return $root . $path;
    }

    public function getCrudPartialBladePath($path)
    {
        $path = $this->getRelativeRootPath($path);

        return str_replace(DIRECTORY_SEPARATOR, '.', $path);
    }

    public function getResourceFormPartialBladePath($resource)
    {
        $root = config('lara_crud.viewRootPath', 'pages');
        $path = $root . '.' . $resource . '.partials.form';
        return str_replace(DIRECTORY_SEPARATOR, '.', $path);
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

        foreach ($dbStructure as $table => $columnsInfo) {
            if (in_array($table, $this->ignoreTables)) {
                continue;
            }
            if (!in_array($table, $dbTables)) {
                continue;
            }
            $pattern  = str_slug($table);
            $pattern  = str_plural($pattern);

            $blade = "@extends('layouts.app')" . PHP_EOL . PHP_EOL;
            $blade .= "@section('content')" . PHP_EOL;

            $crudPartialRoot = $this->getCrudPartialBladePath('crud');
            $blade .= TAB . "@include('%s')" . PHP_EOL;
            $blade .= "@endsection" . PHP_EOL;
            $content = sprintf($blade, $crudPartialRoot . '.index');
            $this->__pattern = $pattern . DIRECTORY_SEPARATOR . 'index';
            $this->createFileBy($this->__pattern, $content);

            $content = sprintf($blade, $crudPartialRoot . '.show');
            $this->__pattern = $pattern . DIRECTORY_SEPARATOR . 'show';
            $this->createFileBy($this->__pattern, $content);

            $partialFormPath = $this->getResourceFormPartialBladePath($pattern);
            $content = sprintf(
                $blade,
                "lara-view::sections.title', ['_title' => 'Update ' . " . 'title_case($item->getTable())' ."])" . PHP_EOL
                . TAB . "@include('" . $partialFormPath );
            $this->__pattern = $pattern . DIRECTORY_SEPARATOR . 'edit';


            $this->createFileBy($this->__pattern, $content);

            $this->__pattern = $pattern . DIRECTORY_SEPARATOR . 'create';
            $content = sprintf(
                $blade,
                "lara-view::sections.title', ['_title' => 'Create ' . " . 'title_case($itemName)' ."])" . PHP_EOL
                . TAB . "@include('" . $partialFormPath );
            $this->createFileBy($this->__pattern, $content);

            $this->__pattern = $pattern . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'form';
            $content = $this->getFormContent($table, $columnsInfo);
            $this->createFileBy($this->__pattern, $content);
        }
    }


    public function getFormContent($table, $columnsInfo)
    {
        $html = '{!! LaraForm::create(!empty($item) ? $item : null, ["action" => !empty($item) ? ["update", $item->id] : "store"]) !!}' . PHP_EOL . PHP_EOL;

        foreach ($columnsInfo as $column => $info) {
            if (in_array($column, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            $template = "{!! LaraForm::input('%s') !!}" . PHP_EOL . PHP_EOL;
            if ('enum' == $info['type']) {
                $values = $info['column_type'];
                $values = str_replace('enum(', '', $values);
                $values = str_replace(')', '', $values);
                $values = str_replace("'", '', $values);
                $values = explode(",", $values);

                $template = "{!!" . PHP_EOL
                    . TAB . "LaraForm::select('%s', [" . PHP_EOL
                    . TAB . TAB . "'options' => [" . PHP_EOL;

                foreach ($values as $value) {
                    $template .= TAB . TAB . TAB . "'$value' => ' " . ucfirst($value) .  "'," . PHP_EOL;
                }

                $template .= TAB . TAB .  "]". PHP_EOL
                    . TAB . "])" . PHP_EOL
                    . "!!}" . PHP_EOL . PHP_EOL;
            } elseif(in_array($info['type'], ['char', 'varchar'])) {
                // default template
            } elseif(in_array($info['type'], ['int', 'smallint', 'tinyint', 'double', 'decimal'])) {
                if (ends_with($column, '_id')) {
                    $table = str_replace_last('_id', '', $column);
                    $table = str_plural($table);
                    $template = "{!! LaraForm::select('%s', ['options' => $" . $table . "]) !!}" . PHP_EOL . PHP_EOL;
                } elseif(ends_with($column, '_count')) {
                    continue;
                } else {
                    $template = "{{--@TODO fix " . $info['type'] . "--}}" . PHP_EOL;
                    $template .= "{!! LaraForm::input('%s') !!}" . PHP_EOL . PHP_EOL;
                }
            } elseif(in_array($info['type'], ['text'])) {
                $template = "{!! LaraForm::textarea('%s') !!}" . PHP_EOL . PHP_EOL;
            } elseif(in_array($info['type'], ['datetime', 'date'])) {
                $template = "{{--@TODO fix " . $info['type'] . "--}}" . PHP_EOL;
                $template .= "{!! LaraForm::input('%s') !!}" . PHP_EOL . PHP_EOL;
            } else {

                dd($info, $column, $this->__pattern);
            }

            $html .= sprintf($template, $column);
        }
        $html .= "{!! LaraForm::submit() !!}" . PHP_EOL . PHP_EOL;
        $html .= "{!! LaraForm::end() !!}" . PHP_EOL;
        return $html;
    }

}