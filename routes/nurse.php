<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\User\Auth\AuthController;
use App\Http\Controllers\Nurse\Account\AccountController;
use App\Http\Controllers\Nurse\Appointment\AppointmentController;
use App\Http\Controllers\Nurse\Home\HomeController;
use App\Http\Controllers\Nurse\Miscellaneous\GeneralController;
use App\Http\Controllers\Nurse\Password\ForgetPasswordController;
use App\Http\Controllers\Nurse\Notification\NotificationController;
use App\Http\Controllers\Nurse\Chat\ChatController;
use App\Http\Controllers\Nurse\ReminderSetting\ReminderSettingController;
use App\Http\Controllers\Nurse\MedicalReport\MedicalReportController;
use App\Http\Controllers\Nurse\Prescription\PrescriptionController;

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
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:nurse');
    Route::post('verification-email', [AuthController::class, 'verifyEmail']);
});

Route::group(['prefix' => 'password-recovery'], function () {
    Route::post('verify-email', [ForgetPasswordController::class, 'verifyEmail']);
    Route::post('verify-code', [ForgetPasswordController::class, 'verifyCode']);
    Route::post('update-password', [ForgetPasswordController::class, 'updatePassword']);
});


Route::group(['middleware' => ['auth:nurse', 'role:nurse']], function () {

    // accounts routes
    Route::prefix('account')
        ->controller(AccountController::class)
        ->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'update');
            Route::delete('/', 'destory');
            Route::post('/change-password', 'changePassword');
            Route::get('/home', [HomeController::class, 'index']);
        });

    // appointments routes
    Route::prefix('appointments')
        ->controller(AppointmentController::class)
        ->group(function () {
            Route::get('/',  'index');
            Route::get('/calender', 'getMonthlyAppointments');
            Route::get('/{id}',  'show');
            Route::post('/status/{id}/{action}', 'updateAppointmentStatus'); //cancle or complete
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

    Route::prefix('medical-records')
        ->controller(MedicalReportController::class)
        ->group(function () {
            Route::get('/',  'index');
            Route::get('/{id}',  'show');
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
});


// general apis routes
Route::group(['prefix' => 'general'], function () {
    Route::get('/languages', [GeneralController::class, 'languages']);
});
