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
use App\Services\Msg91WhatsAppService;

Route::get('/test-msg91-whatsapp', function (
    Msg91WhatsAppService $whatsapp
) {
    $result = $whatsapp->sendTemplate(
        '917620918435',
        [
            'Test Parent',
            'Test Student',
            '01 Aug 2026 - 31 Aug 2026',
            '₹1,200.00',
        ]
    );

    return response()->json($result);
});

Route::prefix('whatsapp')
    ->name('whatsapp.')
    ->group(function () {

        Route::get('/configuration', [
            WhatsAppConfigController::class,
            'page',
        ])->name('configuration');

        Route::get('/configuration/data', [
            WhatsAppConfigController::class,
            'show',
        ])->name('configuration.data');

        Route::post('/configuration', [
            WhatsAppConfigController::class,
            'store',
        ])->name('configuration.store');

        Route::post('/configuration/test', [
            WhatsAppConfigController::class,
            'test',
        ])->name('configuration.test');

        Route::post('/configuration/toggle', [
            WhatsAppConfigController::class,
            'toggle',
        ])->name('configuration.toggle');
    });


Route::prefix('whatsapp')
    ->name('whatsapp.')
    ->group(function () {

        Route::get(
            '/',
            [WhatsAppController::class, 'index']
        )->name('index');

        Route::get(
            '/create',
            [WhatsAppController::class, 'create']
        )->name('create');

        Route::post(
            '/',
            [WhatsAppController::class, 'store']
        )->name('store');

        Route::get(
            '/{whatsappLog}',
            [WhatsAppController::class, 'show']
        )->name('show');

        Route::delete(
            '/{whatsappLog}',
            [WhatsAppController::class, 'destroy']
        )->name('destroy');
    });

Route::prefix('whatsapp')->group(function () {

    Route::get('/', [WhatsAppController::class, 'index'])
        ->name('whatsapp.index');

    Route::post('/send/{student}', [WhatsAppController::class, 'send'])
        ->name('whatsapp.send');

    Route::post('/send-due-reminders', [WhatsAppController::class, 'sendDueReminders'])
        ->name('whatsapp.sendDueReminders');

    Route::get('/history', [WhatsAppController::class, 'history'])
        ->name('whatsapp.history');
});
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

    Route::get('/login', [AuthController::class, 'show'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'login'])
        ->name('login.submit');

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

    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');


    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');


    /*
    |--------------------------------------------------------------------------
    | Students
    |--------------------------------------------------------------------------
    */

    Route::resource('students', StudentController::class)
        ->except(['show']);


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

    Route::resource('buses', BusController::class)
        ->except(['show']);


    /*
    |--------------------------------------------------------------------------
    | Payments
    |--------------------------------------------------------------------------
    */

    Route::resource('payments', PaymentController::class)
        ->only([
            'index',
            'create',
            'store',
        ]);


    /*
    |--------------------------------------------------------------------------
    | Expenses
    |--------------------------------------------------------------------------
    */

    Route::resource('expenses', ExpenseController::class)
        ->only([
            'index',
            'create',
            'store',
        ]);


    /*
    |--------------------------------------------------------------------------
    | Fees
    |--------------------------------------------------------------------------
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

});