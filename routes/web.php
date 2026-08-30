<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentAccountController;
use App\Http\Controllers\BusController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FeeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\WhatsAppController;
use App\Http\Controllers\WhatsAppConfigController;


/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {

    return redirect()->route(
        'login'
    );

});


/*
|--------------------------------------------------------------------------
| Guest / Authentication Routes
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Login page
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/login',
        [AuthController::class, 'show']
    )->name('login');


    /*
    |--------------------------------------------------------------------------
    | Login submit
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/login',
        [AuthController::class, 'login']
    )->name('login.submit');

});


/*
|--------------------------------------------------------------------------
| Authenticated Application
|--------------------------------------------------------------------------
*/

Route::middleware('simple.auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Logout
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/logout',
        [AuthController::class, 'logout']
    )->name('logout');


    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/dashboard',
        [DashboardController::class, 'index']
    )->name('dashboard');


    /*
    |--------------------------------------------------------------------------
    | Students
    |--------------------------------------------------------------------------
    */

    Route::resource(
        'students',
        StudentController::class
    )->except([
        'show',
    ]);


    /*
    |--------------------------------------------------------------------------
    | Student Account
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/students/{student}/account',
        [StudentAccountController::class, 'show']
    )->name('students.account');


    /*
    |--------------------------------------------------------------------------
    | Buses
    |--------------------------------------------------------------------------
    */

    Route::resource(
        'buses',
        BusController::class
    )->except([
        'show',
    ]);


    /*
    |--------------------------------------------------------------------------
    | Payments
    |--------------------------------------------------------------------------
    */

    Route::resource(
        'payments',
        PaymentController::class
    )->only([
        'index',
        'create',
        'store',
    ]);


    /*
    |--------------------------------------------------------------------------
    | Expenses
    |--------------------------------------------------------------------------
    */

    Route::resource(
        'expenses',
        ExpenseController::class
    )->only([
        'index',
        'create',
        'store',
    ]);


    /*
    |--------------------------------------------------------------------------
    | Fees
    |--------------------------------------------------------------------------
    |
    | /fees
    |     = Fee Records
    |
    | /fees/generate
    |     = Generate Fees
    |
    */

    Route::get(
        '/fees',
        [FeeController::class, 'index']
    )->name('fees.index');


    Route::get(
        '/fees/generate',
        [FeeController::class, 'showGenerate']
    )->name('fees.generate');


    Route::post(
        '/fees/generate',
        [FeeController::class, 'generate']
    )->name('fees.generate.store');


    /*
    |--------------------------------------------------------------------------
    | Reports
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/reports',
        [ReportController::class, 'index']
    )->name('reports');


    /*
    |--------------------------------------------------------------------------
    | WhatsApp Configuration
    |--------------------------------------------------------------------------
    */

    Route::prefix('whatsapp')
        ->name('whatsapp.')
        ->group(function () {

            /*
            |--------------------------------------------------------------------------
            | Configuration page
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/configuration',
                [
                    WhatsAppConfigController::class,
                    'page',
                ]
            )->name('configuration');


            /*
            |--------------------------------------------------------------------------
            | Configuration data
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/configuration/data',
                [
                    WhatsAppConfigController::class,
                    'show',
                ]
            )->name('configuration.data');


            /*
            |--------------------------------------------------------------------------
            | Save configuration
            |--------------------------------------------------------------------------
            */

            Route::post(
                '/configuration',
                [
                    WhatsAppConfigController::class,
                    'store',
                ]
            )->name('configuration.store');


            /*
            |--------------------------------------------------------------------------
            | Test configuration
            |--------------------------------------------------------------------------
            */

            Route::post(
                '/configuration/test',
                [
                    WhatsAppConfigController::class,
                    'test',
                ]
            )->name('configuration.test');


            /*
            |--------------------------------------------------------------------------
            | Enable / disable WhatsApp
            |--------------------------------------------------------------------------
            */

            Route::post(
                '/configuration/toggle',
                [
                    WhatsAppConfigController::class,
                    'toggle',
                ]
            )->name('configuration.toggle');


            /*
            |--------------------------------------------------------------------------
            | WhatsApp history / dashboard
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/',
                [
                    WhatsAppController::class,
                    'index',
                ]
            )->name('index');


            /*
            |--------------------------------------------------------------------------
            | Send WhatsApp page
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/create',
                [
                    WhatsAppController::class,
                    'create',
                ]
            )->name('create');


            /*
            |--------------------------------------------------------------------------
            | Send WhatsApp message
            |--------------------------------------------------------------------------
            */

            Route::post(
                '/',
                [
                    WhatsAppController::class,
                    'store',
                ]
            )->name('store');


            /*
            |--------------------------------------------------------------------------
            | Compatibility send route
            |--------------------------------------------------------------------------
            |
            | Used by the existing WhatsApp student action.
            |
            */

            Route::post(
                '/send/{student}',
                [
                    WhatsAppController::class,
                    'send',
                ]
            )->name('send');


            /*
            |--------------------------------------------------------------------------
            | Show one WhatsApp log
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/{whatsappLog}',
                [
                    WhatsAppController::class,
                    'show',
                ]
            )->name('show');


            /*
            |--------------------------------------------------------------------------
            | Delete one WhatsApp log
            |--------------------------------------------------------------------------
            */

            Route::delete(
                '/{whatsappLog}',
                [
                    WhatsAppController::class,
                    'destroy',
                ]
            )->name('destroy');

        });

});