<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Doctor\Auth\AuthController;
use App\Http\Controllers\Doctor\Account\AccountController;
use App\Http\Controllers\Doctor\Slot\SlotsController;
use App\Http\Controllers\Doctor\Appointment\AppointmentController;
use App\Http\Controllers\Doctor\Sessions\SessionsController;
use App\Http\Controllers\Doctor\Home\HomeController;
use App\Http\Controllers\Doctor\Miscellaneous\GeneralController;
use App\Http\Controllers\Doctor\Password\ForgetPasswordController;
use App\Http\Controllers\Doctor\Notification\NotificationController;
use App\Http\Controllers\Doctor\Chat\ChatController;
use App\Http\Controllers\Doctor\ReminderSetting\ReminderSettingController;
use App\Http\Controllers\Doctor\Prescription\PrescriptionController;
use App\Http\Controllers\Doctor\MedicalReport\MedicalReportController;
use App\Http\Controllers\Doctor\QueryReport\QueryReportController;
use App\Http\Controllers\Doctor\Zoho\ZohoController;

/*
|--------------------------------------------------------------------------
| Doctor API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['prefix' => 'auth'], function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:doctor');
    Route::post('verification-email', [AuthController::class, 'verifyEmail']);
});

Route::group(['prefix' => 'password-recovery'], function () {
    Route::post('verify-email', [ForgetPasswordController::class, 'verifyEmail']);
    Route::post('verify-code', [ForgetPasswordController::class, 'verifyCode']);
    Route::post('update-password', [ForgetPasswordController::class, 'updatePassword']);
});


//Webex Routes
Route::get('/webex/callback', [AppointmentController::class, 'webexCallback']);
Route::post('/webex/guest-token', [AppointmentController::class, 'generateGuestToken']);

//ZOHO Routes

// Initiate OAuth flow
Route::get('/zoho/connect', [ZohoController::class, 'redirectToZoho'])
    ->name('zoho.redirect');

// Handle callback from Zoho
Route::get('/zoho/callback', [ZohoController::class, 'handleCallback'])
    ->name('zoho.callback');

// Token refresh endpoint
Route::post('/zoho/refresh', [ZohoController::class, 'refreshToken'])
    ->name('zoho.refresh');

// Departments
Route::get('/zoho/departments', [ZohoController::class, 'departments'])
    ->name('zoho.departments');

// Departments Create
Route::post('/zoho/departments/create', [ZohoController::class, 'createDepartments'])
    ->name('zoho.departments.create');

// Operators
Route::get('/zoho/operators', [ZohoController::class, 'operators'])
    ->name('zoho.operators');

// Operators create
Route::post('/zoho/operators/create', [ZohoController::class, 'createOperators'])
    ->name('zoho.operators.create');

// Chat
// Get all chats
Route::get('/zoho/get/chat', [ZohoController::class, 'getConversations']);
// Send message
Route::post('/zoho/chat/send', [ZohoController::class, 'sendMessage']);
// Get complete conversation
Route::get('/zoho/get/conversation/{id}', [ZohoController::class, 'getCompleteConversations']);


Route::group(['middleware' => ['auth:doctor', 'role:doctor']], function () {

    // accounts routes
    Route::prefix('account')
        ->controller(AccountController::class)
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/specialities', 'specialities');
            Route::post('/', 'update');
            Route::delete('/', 'destory');
            Route::post('/change-password', 'changePassword');
            Route::get('/home', [HomeController::class, 'index']);
        });

    // slots routes
    Route::prefix('slots')
        ->controller(SlotsController::class)->group(function () {
            Route::get('/',  'index');
            Route::get('/services',  'services');
            Route::get('/{id}',  'show');
            Route::post('/',  'create');
            Route::post('/{id}',  'update');
        });

    // appointments routes
    Route::prefix('appointments')
        ->controller(AppointmentController::class)
        ->group(function () {
            Route::get('/',  'index');
            Route::get('/calender', 'getMonthlyAppointments');
            Route::get('/{id}',  'show');
            Route::post('/generate-token',  'createCall');
            Route::post('/cancel/{id}',  'cancel');
            Route::post('/status/{id}/{action}', 'updateAppointmentStatus'); //cancle or complete
            Route::post('/notes/{id}',  'notes');
        });

    // sessions routes
    Route::prefix('sessions')
        ->controller(SessionsController::class)
        ->group(function () {
            Route::get('/',  'index');
            Route::get('/calender', 'getMonthlyAppointments');
            Route::get('/{id}',  'show');
            Route::post('/cancel/{id}',  'cancel');
            Route::post('/notes/{id}',  'notes');
        });

    // Notifications routes
    Route::group([
        'controller' => NotificationController::class,
        'prefix' => 'notifications'
    ], function () {
        Route::get('/', 'index');
        Route::post('/{id?}', 'update');
        Route::post('/', 'create');
    });

    // reminder setting routes
    Route::group([
        'controller' => ReminderSettingController::class,
        'prefix' => 'reminders'
    ], function () {
        Route::get('/', 'index');
        Route::post('/', 'create');
    });

    // chat routes
    Route::prefix('chats')
        ->controller(ChatController::class)
        ->group(function () {
            Route::get('/', 'index');
            Route::post('/',  'create');
            Route::post('/send',  'send');
            Route::get('/{id}', 'show');
        });

    Route::group(['controller' => HomeController::class, 'prefix' => 'charts'], function () {
        //payment - user - order - payments
        Route::get('/{type}', 'chart')->where('type', 'user|doctor|nurse|physician|appointment');
    });

    // Prescriptions routes
    Route::group([
        'controller' => PrescriptionController::class,
        'prefix' => 'prescriptions'
    ], function () {
        Route::get('/', 'index');
        Route::get('/patients', 'patients');
        Route::post('/', 'store');
        Route::get('/{id}', 'show');
        Route::delete('/{id}', 'destroy');
    });

    //medical records
    Route::prefix('medical-records')
        ->controller(MedicalReportController::class)
        ->group(function () {
            Route::get('/',  'index');
            Route::get('/{id}',  'show');
            Route::post('/',  'create');
            Route::post('/{id}',  'update');
            Route::delete('/{id}', 'destroy');
        });


    // Custom Report management routes
    Route::group([
        'controller' => QueryReportController::class,
        'prefix' => 'reports'
    ], function () {
        Route::get('/', 'index');
        Route::get('/{id}', 'show');
        Route::post('/{id}', 'resolved');
    });
});


// general apis routes
Route::group(['prefix' => 'general'], function () {
    Route::get('/specialty', [GeneralController::class, 'specialtyListing']);
    Route::get('/languages', [GeneralController::class, 'languages']);
});
