<?php

use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;



Route::middleware('guest')->group(function () {

    // Register
    Route::get('/register', [AuthController::class, 'showRegister'])
        ->name('register');

    Route::post('/register', [AuthController::class, 'register']);


    // Login
    Route::get('/login', [AuthController::class, 'showLogin'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'login']);
});




Route::get('/', function () {

    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');




Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');




Route::middleware('auth')->group(function () {

    Route::get('/dashboard', function () {

        return view('admin.layouts.app');
    })->name('dashboard');
});




Route::middleware('auth')
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get('/', function () {
            return redirect()->route('dashboard');
        })
            ->middleware('permission:admin.access')
            ->name('dashboard');


        /*
        | ROLES
        */

        Route::get('/roles', [RoleController::class, 'index'])
            ->name('roles.index')
            ->middleware('permission:roles.manage');

        Route::get('/roles/create', [RoleController::class, 'create'])
            ->name('roles.create')
            ->middleware('permission:roles.create');

        Route::post('/roles', [RoleController::class, 'store'])
            ->name('roles.store')
            ->middleware('permission:roles.create');

        Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])
            ->name('roles.edit')
            ->middleware('permission:roles.edit');

        Route::put('/roles/{role}', [RoleController::class, 'update'])
            ->name('roles.update')
            ->middleware('permission:roles.edit');

        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])
            ->name('roles.destroy')
            ->middleware('permission:roles.delete');


        /*
        | PERMISSIONS
        */

        Route::get('/permissions', [PermissionController::class, 'index'])
            ->name('permissions.index')
            ->middleware('permission:permissions.manage');

        Route::get('/permissions/create', [PermissionController::class, 'create'])
            ->name('permissions.create')
            ->middleware('permission:permissions.create');

        Route::post('/permissions', [PermissionController::class, 'store'])
            ->name('permissions.store')
            ->middleware('permission:permissions.create');

        Route::get('/permissions/{permission}/edit', [PermissionController::class, 'edit'])
            ->name('permissions.edit')
            ->middleware('permission:permissions.edit');

        Route::put('/permissions/{permission}', [PermissionController::class, 'update'])
            ->name('permissions.update')
            ->middleware('permission:permissions.edit');

        Route::delete('/permissions/{permission}', [PermissionController::class, 'destroy'])
            ->name('permissions.destroy')
            ->middleware('permission:permissions.delete');


        /*
        | MENUS
        */

        Route::get('/menus', [MenuController::class, 'index'])
            ->name('menus.index')
            ->middleware('permission:menus.manage');

        Route::get('/menus/create', [MenuController::class, 'create'])
            ->name('menus.create')
            ->middleware('permission:menus.create');

        Route::post('/menus', [MenuController::class, 'store'])
            ->name('menus.store')
            ->middleware('permission:menus.create');

        Route::get('/menus/{menu}/edit', [MenuController::class, 'edit'])
            ->name('menus.edit')
            ->middleware('permission:menus.edit');

        Route::put('/menus/{menu}', [MenuController::class, 'update'])
            ->name('menus.update')
            ->middleware('permission:menus.edit');

        Route::delete('/menus/{menu}', [MenuController::class, 'destroy'])
            ->name('menus.destroy')
            ->middleware('permission:menus.delete');
    });
