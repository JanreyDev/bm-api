<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('welcome');
});

Route::any('/login', function (Request $request) {
    if ($request->expectsJson() || $request->is('api/*')) {
        return response()->json([
            'message' => 'Unauthenticated.',
        ], 401);
    }

    return response('Login route placeholder.', 401);
})->name('login');
