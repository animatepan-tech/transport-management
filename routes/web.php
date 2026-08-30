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


/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {

    return redirect()->route('login');

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
    |          = Fee Records
    |
    | /fees/generate
    |          = Generate Fees page
    |
    | POST /fees/generate
    |          = Generate fee records
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
    | WhatsApp
    |--------------------------------------------------------------------------
    |
    | WhatsApp credentials are NOT managed here.
    |
    | The active Meta configuration comes from:
    |
    | .env
    |   ↓
    | config/whatsapp.php
    |   ↓
    | WhatsAppManager
    |   ↓
    | MetaWhatsAppProvider
    |
    */


    /*
    |--------------------------------------------------------------------------
    | WhatsApp History / Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/whatsapp',
        [WhatsAppController::class, 'index']
    )->name('whatsapp.index');


    /*
    |--------------------------------------------------------------------------
    | WhatsApp Message Creation
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/whatsapp/create',
        [WhatsAppController::class, 'create']
    )->name('whatsapp.create');


    /*
    |--------------------------------------------------------------------------
    | Send WhatsApp Message
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/whatsapp',
        [WhatsAppController::class, 'store']
    )->name('whatsapp.store');


    /*
    |--------------------------------------------------------------------------
    | Compatibility / Student Send Route
    |--------------------------------------------------------------------------
    |
    | Existing student/WhatsApp UI can continue to use:
    |
    | POST /whatsapp/send/{student}
    |
    */

    Route::post(
        '/whatsapp/send/{student}',
        [WhatsAppController::class, 'send']
    )->name('whatsapp.send');


    /*
    |--------------------------------------------------------------------------
    | Show One WhatsApp Log
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/whatsapp/{whatsappLog}',
        [WhatsAppController::class, 'show']
    )->name('whatsapp.show');


    /*
    |--------------------------------------------------------------------------
    | Delete One WhatsApp Log
    |--------------------------------------------------------------------------
    */

    Route::delete(
        '/whatsapp/{whatsappLog}',
        [WhatsAppController::class, 'destroy']
    )->name('whatsapp.destroy');

});