<?php


Route::group(['prefix' => 'edujugon','middleware' => 'auth:web'], function (){
    Route::post('fetch/filters', 'VueTableController@fetchFilters');
    Route::post('fetch/columns', 'VueTableController@fetchColumns');
    Route::post('update/{id}', 'VueTableController@update');
    Route::post('massive-update/', 'VueTableController@massiveUpdate');
    Route::post('fetch/{field}/{dir}/{column?}', 'VueTableController@fetch');
};