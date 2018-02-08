<?php
namespace LaraCrud\Controllers;

use Cake\Utility\Inflector;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class LaraController extends Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * @var
     */
    protected $baseService;

    /**
     * @var
     */
    protected $itemName;

    /**
     * @var
     */
    protected $viewRootPath;

    /**
     * @var
     */
    protected $viewDirectPath;

    /**
     * @var
     */
    protected $actionViewSuffix = [];

    /**
     * @var
     */
    protected $actionViewFullPath = [];

    /**
     * @var
     */
    protected $ignorePathStructure = true;

    /**
     * @var string
     */
    private $defaultViewRootPath = 'lara-view::crud';

    /**
     * LaraController constructor.
     */
    public function __construct()
    {
        $this->configureByController();
    }

    /**
     *
     */
    public function home()
    {
        return view(config('lara_crud.root_path.home_view'));
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $sort  = config('lara_crud.index.sort', []);
        list($items, $columns) = $this->baseService->paginate($sort);
        $view = $this->getMethodViewFullPath(__FUNCTION__);
        return view($view, compact('items', 'columns'));
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create()
    {
        $itemName = $this->itemName;
        $view = $this->getMethodViewFullPath(__FUNCTION__);
        return view($view, compact('itemName'));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        if ($this->baseService->create($request->all())) {
            $this->flashSuccess($this->itemName);
            return $this->redirectIndexRouteBased('store');
        }

        return $this->redirectWithErrors();
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function show($id)
    {
        $item = $this->baseService->findForShow($id);
        $view = $this->getMethodViewFullPath(__FUNCTION__);
        return empty($item) ? $this->notFound() : view($view, compact('item'));
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function edit($id)
    {
        $item = $this->baseService->find($id);
        $view = $this->getMethodViewFullPath(__FUNCTION__);
        return empty($item) ? $this->notFound() : view($view, compact('item'));
    }

    /**
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        if ($this->baseService->update($id, $request->all())) {
            $this->flashSuccess($this->itemName, true);
            return $this->redirectIndexRouteBased('update');
        }

        return $this->redirectWithErrors();
    }

    /**
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $deleted = $this->baseService->destroy($id);

        if ($deleted) {
            $this->flashSuccess($this->itemName, 'deleted');
        } else {
            $this->flashError($this->itemName, 'deleted');
        }

        return $this->redirectIndexRouteBased('destroy');
    }

    /**
     * @param string $message
     * @param string $model
     * @param $service
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirectWithErrors($message = '', $model = null, $service = null)
    {
        if (is_null($service)) {
            $service = $this->baseService;
        }

        if (is_null($model)) {
            $model = str_singular($this->itemName);
        }

        if (empty($message)) {
            $message = sprintf('%s can not be saved. Please see errors below', $model);
        }

        flash($message, 'info');
        return redirect()->back()->withInput()->withErrors($service->getValidationErrors());
    }

    /**
     * @param $method
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirectIndexRouteBased ($method)
    {
        $route =  str_replace_last($method, 'index', Route::currentRouteName());
        return redirect()->route($route);
    }

    /**
     * @param $item
     * @param bool $update
     */
    public function flashSuccess($item, $update = false)
    {
        if ($update) {
            $action = is_string($update) ? $update : 'updated';
        } else {
            $action = 'created';
        }

        flash(__(sprintf('%s has been successfully %s', $item, $action)), 'success');
    }

    /**
     * @param $item
     * @param bool $update
     */
    function flashError($item, $update = false)
    {

        if ($update) {
            $action = is_string($update) ? $update : 'updated';
        } else {
            $action = 'created';
        }

        flash(__(sprintf('%s can not be %s', $item, $action)), 'danger');
    }



    protected function configureByRoute()
    {
        //TODO
    }




    /**
     *
     */
    protected function configureByController()
    {
        $namespacePrefix = app()->getNamespace(). config('lara_crud.root_path.controllers') . DIRECTORY_SEPARATOR;
        $namespacePrefix = str_replace(DIRECTORY_SEPARATOR, '\\', $namespacePrefix);
        $namespaceEnd = str_replace_first($namespacePrefix, '', get_class($this));

        $pathComponent = explode('\\', $namespaceEnd);
        $pattern = array_pop($pathComponent);
        $pattern = str_replace_last('Controller', '', $pattern);
        $this->itemName = Inflector::humanize(Inflector::underscore($pattern));
        if (is_null($this->viewDirectPath)) {
            $this->setDirectPathBased($pathComponent);
        }

    }

    /**
     * @param $pathComponent
     */
    protected function setDirectPathBased($pathComponent)
    {
        if (is_null($this->viewRootPath)) {
            $this->viewRootPath = config('lara_crud.root_path.view', '');
        }

        if (!empty($this->viewRootPath) && !ends_with($this->viewRootPath, '.')) {
            $this->viewDirectPath .= $this->viewRootPath . '.';
        }

        if (!ends_with($this->viewDirectPath, $this->defaultViewRootPath . '.')) {
            $pathPart = '';
            if (!$this->ignorePathStructure) {
                $pathPart = !empty($pathComponent) ? strtolower(implode('.', $pathComponent)) . '.' : '';
            }
            $pathPart .=  str_slug($this->itemName, '-');
            $this->viewDirectPath .= $pathPart . '.';
        }
    }

    /**
     * @param string $method
     * @return string
     */
    protected function getMethodViewFullPath($method)
    {
        if (!empty($this->actionViewFullPath[$method])) {
            return $this->actionViewFullPath[$method];
        }

        $suffix = !empty($this->actionViewSuffix[$method])
            ? $this->actionViewSuffix[$method]
            : config('lara_crud.action_view.' . $method, $method);
        return $this->viewDirectPath . $suffix;
    }

    /**
     *
     */
    protected function notFound()
    {
        $message =  str_singular($this->itemName ). ' not found';
        return view(config('lara_crud.forbidden.view'), compact('message'));
    }

}
