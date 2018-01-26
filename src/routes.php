<?php

    Route::group(['namespace' => 'LaraCrud\Controllers'], function () {
        Route::get('lara-crud-home', 'LaraController@home')->name('lara-crud-home');
    });


