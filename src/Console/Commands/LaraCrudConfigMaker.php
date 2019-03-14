<?php

namespace TODOLaraMake\Console\Commands\Makers;

use TODOLaraMake\Console\Commands\Abstracts\PhpMaker;
use LaraSupport\Facades\LaraDB;

class LaraCrudConfigMaker extends PhpMaker
{
    /**
     * @var string
     */
    public $commandName = 'lara-crud-config';

    /**
     * @var array
     */
    public $keyWords = [
        'sidebar'
    ];

    /**
     * Default arguments
     *
     * @var array
     */
    protected $defaultArguments = [
        'pattern=config' . DIRECTORY_SEPARATOR . 'lara_view'
    ];

    /**
     * @return string
     */
    public function getStubContent()
    {
        $path = base_path('config' . DIRECTORY_SEPARATOR . 'lara_view.php');
        $configContent = $this->files->get($path);

        $configContent = str_replace(
            "'sidebars' => [",
            "'sidebars' => [" . PHP_EOL . TAB . TAB . $this->getStructuredKeyWord('sidebar'),
            $configContent
        );
        return $configContent;
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
//        $tables = starts_with($pattern, '_db:')
//            ? $this->getPatternsByPrefix('_db:')
//            : $tables = array_keys($dbStructure);


        $this->__pattern = 'config' . DIRECTORY_SEPARATOR . 'lara_view';
        $this->__sidebar = array_except(array_keys($dbStructure), $this->ignoreTables);
        $this->createFileBy($this->__pattern, $content);
    }



    /**
     * @param $content
     * @param $key
     * @param $input
     * @return mixed
     */
    public function replaceSideBarKeyWord($content, $key, $input)
    {
        if ($input) {
            foreach ($input as $sidebar) {
                $sidebar = str_slug(lcfirst(str_plural($sidebar)));
                if (!str_contains($content, $sidebar)) {
                    $content = str_replace($key, "'admin." . $sidebar . "'," . PHP_EOL. TAB . TAB . $key, $content);
                }
            }
        }
        $content = str_replace(PHP_EOL . TAB . TAB . $key, '', $content);

        return $content;
    }
}
