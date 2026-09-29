<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web routes
|--------------------------------------------------------------------------
|
| The SPA shell is served for every non-API route so that Vue Router
| handles client-side navigation.
*/

Route::get('/{any?}', fn () => view('app'))->where('any', '.*')->name('spa');
