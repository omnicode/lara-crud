<?php

namespace LaraCrud\Console\Commands;

use LaraMake\Console\Commands\Abstracts\PhpMaker;
use LaraMake\Exceptions\LaraCommandException;
use LaraSupport\Facades\LaraDB;

class MakeRoute extends PhpMaker
{
    /**
     * @var string
     */
    public $commandName = 'crud-routes';

    /**
     * @var array
     */
    public $commandOptions = [
        'resource='
    ];

    /**
     * @var array
     */
    public $keyWords = [
        'resource'
    ];

    /**
     * Default arguments
     *
     * @var array
     */
    protected $defaultArguments = [
        'pattern=routes' . DIRECTORY_SEPARATOR . 'web'
    ];

    /**
     * @param string $path
     * @return mixed|string
     */
    public function getStubContent($path = __DIR__)
    {
        $path = base_path('routes' . DIRECTORY_SEPARATOR . 'web.php');
        $routeContent = $this->files->get($path);
        if (!ends_with($routeContent, PHP_EOL . PHP_EOL)) {
            $routeContent .= PHP_EOL;
        }

        if (!ends_with($routeContent, PHP_EOL . PHP_EOL)) {
            $routeContent .= PHP_EOL;
        }

        $routeGroupConfig = config('lara_crud.route.group');
        $group = '';
        if ($routeGroupConfig) {
            $group = "Route::group([";
            foreach ($routeGroupConfig as $key => $value) {
                $group .= "'$key' => '$value', ";
            }
            $group = rtrim($group, ', ');
            $group .= "], function () {" . PHP_EOL;
        }
        if ($group) {
            if (!str_contains($routeContent, $group)) {
                $routeContent .= $group . TAB . '{{resource}}' . PHP_EOL . '});' . PHP_EOL . PHP_EOL;
            } else {
                $routeContent = str_replace($group , $group . TAB . '{{resource}}' . PHP_EOL, $routeContent);
            }
        } else {
            $routeContent .= '{{resource}}' . PHP_EOL ;
        }

        return $routeContent;
    }

    /**
     * @param $content
     * @return mixed
     */
    public function fixNewLinesOfStub($content)
    {
        return $content;
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
        $resources = array_diff($tables, $this->ignoreTables);
        $this->__pattern = 'routes' . DIRECTORY_SEPARATOR . 'web';
        $this->__resource = implode(',', $resources);
        $this->createFileBy($this->__pattern, $content);
    }

    /**
     * @param $content
     * @param $key
     * @param $input
     * @return mixed
     */
    public function replaceResourceKeyWord($content, $key, $input)
    {
        $resource = $input;
        if ($resource) {
            $resources = explode(',', $resource);
            foreach ($resources as $resource) {
                $resource = str_slug(str_plural($resource));
                $resourceText = $this->getResourceTemplate($resource);
                if (strpos($content, $resourceText)) {
                    continue;
                }
                $content = str_replace($key, $resourceText . PHP_EOL  . TAB . $key, $content);
            }
        }
        $content = str_replace(PHP_EOL . TAB . $key, '', $content);
        return $content;
    }

    /**
     * @param $resource
     * @return string
     */
    public function getResourceTemplate($resource)
    {

        $pattern = str_plural(title_case($resource));
        $pattern = str_replace('-', '', $pattern);
        return sprintf("Route::resource('%s', '%sController');", str_slug(lcfirst(str_plural($resource))), str_singular($pattern));
    }

    /**
     * @TODO generalize
     *
     * @param $key
     * @param $change
     * @param $content
     * @return mixed
     */
    public function replaceWithNew($key, $change, $content)
    {
        if (strpos($content, $change)) {
            return $content;
        }

        return str_replace(
            [
                $key . '});',
                $key,
                '_TMP_'
            ],
            [
                TAB . $change . PHP_EOL . PHP_EOL . '_TMP_'  . '});',
                $change . PHP_EOL . PHP_EOL . $key,
                $key,
            ],
            $content
        );
    }
}
