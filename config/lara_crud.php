<?php
return [
    'root_path' => [
        'controllers' => 'Http' . DIRECTORY_SEPARATOR . 'Controllers',
        'view' => 'lara-view::crud',
        'home_view' => 'lara-crud::home',
    ],

    'root_namespaces' => [
        'constant' => 'App' . DIRECTORY_SEPARATOR . 'Config',
        'view-composer' => 'App' . DIRECTORY_SEPARATOR . 'Http' . DIRECTORY_SEPARATOR . 'ViewComposers' . DIRECTORY_SEPARATOR . 'Crud',
        'controller' => 'App' . DIRECTORY_SEPARATOR . 'Http' . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . 'Crud',
        'model' => 'App' . DIRECTORY_SEPARATOR . 'Models' . DIRECTORY_SEPARATOR . 'Crud',
        'service' => 'App' . DIRECTORY_SEPARATOR . 'Services' . DIRECTORY_SEPARATOR . 'Crud',
        'validator' => 'App' . DIRECTORY_SEPARATOR . 'Validators' . DIRECTORY_SEPARATOR . 'Crud',
        'repository-interface' => 'App' . DIRECTORY_SEPARATOR . 'Repositories' . DIRECTORY_SEPARATOR . 'Crud' . DIRECTORY_SEPARATOR . 'Contracts',
        'repository' => 'App' . DIRECTORY_SEPARATOR . 'Repositories' . DIRECTORY_SEPARATOR . 'Crud' . DIRECTORY_SEPARATOR . 'Eloquent',
        'repository-service-provider' => 'app' . DIRECTORY_SEPARATOR . 'Providers'
    ],

    'route' => [
        'group' => [
            'namespace' => 'Crud',
            'prefix' => 'crud',
            'as' => 'crud.'
        ],
    ],

    'suffix' => [
        'controller' => 'Controller',
        'model' => '',
        'service' => 'Service',
        'validator' => 'Validator',
        'repository-interface' => 'RepositoryInterface',
        'repository' => 'Repository'
    ],

    'viewRootPath' => 'pages',

    'index' => [
        'sort' => ['id' => 'asc']
    ],

    'action_view' => [
        'view' => 'show',
    ],

    'forbidden' => [
        'view' => 'lara-crud::forbidden',
        'button' => [
            'title' => 'Go To Home Page',
            'route' => 'lara-crud-home',
        ]
    ],
];