<?php
return [
    'root_path' => [
        'controllers' => 'Http' . DS . 'Controllers',
        'view' => 'lara-view::page',
        'home_view' => 'lara-crud::home',
    ],

    'index' => [
        'sort' => ['id' => 'asc']
    ],

    'action_view' => [
        'show' => 'show-example',
    ],

    'forbidden' => [
        'view' => 'lara-crud::forbidden',
        'button' => [
            'title' => 'Go To Home Page',
            'route' => 'lara-crud-home',
        ]
    ],

    //TODO delete
    'list' => [
        'countries',
        'currencies',
        'regions',
        'admin/owner_drawings'
    ]
];