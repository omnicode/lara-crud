<?php

return [
    'root_path' => [
        'controllers' => 'Http' . DIRECTORY_SEPARATOR . 'Controllers',
        'view' => '',
        'home_view' => 'lara-crud::home',
    ],

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