<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Auth\AuthController;
use App\Http\Controllers\Admin\Chat\ChatController;
use App\Http\Controllers\Admin\Home\HomeController;
use App\Http\Controllers\Admin\User\UserController;
use App\Http\Controllers\Admin\Slot\SlotsController;
use App\Http\Controllers\Admin\Banner\BannerController;
use App\Http\Controllers\Admin\Bundle\BundleController;
use App\Http\Controllers\Admin\Account\AccountController;
use App\Http\Controllers\Admin\Service\ServiceController;
use App\Http\Controllers\Admin\Payments\PaymentController;
use App\Http\Controllers\Admin\Feedback\FeedbackController;
use App\Http\Controllers\Admin\Insurance\InsuranceController;
use App\Http\Controllers\Admin\Speciality\SpecialityController;
use App\Http\Controllers\Admin\Appointment\AppointmentController;
use App\Http\Controllers\Admin\Password\ForgetPasswordController;
use App\Http\Controllers\Admin\QueryReport\QueryReportController;
use App\Http\Controllers\Admin\MedicalOrder\MedicalOrderController;
use App\Http\Controllers\Admin\Notification\NotificationController;
use App\Http\Controllers\Admin\ReminderSetting\ReminderSettingController;
use App\Http\Controllers\Admin\SiteInformation\SiteInformationController;
use App\Http\Controllers\Admin\HealthProfessional\HealthProfessionalController;
use App\Http\Controllers\Admin\MedicalReport\MedicalReportController;
use App\Http\Controllers\Admin\Page\PageController;
use App\Http\Controllers\Admin\Prescription\PrescriptionController;
use App\Http\Controllers\Doctor\Miscellaneous\GeneralController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/



//no auth routes
Route::name('auth.admin')->prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->name('login');
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:admin');
});

//forgot password routes
Route::group(['prefix' => 'password-recovery'], function () {
    Route::post('verify-email', [ForgetPasswordController::class, 'verifyEmail']);
    Route::post('verify-code', [ForgetPasswordController::class, 'verifyCode']);
    Route::post('update-password', [ForgetPasswordController::class, 'updatePassword']);
});

Route::group(['middleware' => ['auth:admin', 'role:admin']], function () {

    // accounts routes
    Route::group(['prefix' => 'account'], function () {
        Route::get('/', [AccountController::class, 'index']);
        Route::post('/', [AccountController::class, 'update']);
        Route::delete('/', [AccountController::class, 'destroy']);
        Route::post('/change-password', [AccountController::class, 'changePassword']);
        Route::get('/home', [HomeController::class, 'index']);
        Route::get('/calender', [HomeController::class, 'calender']);
    });

    // user management routes
    Route::group([
        'controller' => UserController::class,
        'prefix' => 'users'
    ], function () {
        Route::get('/', 'index');
        Route::get('/services2', 'services2');
        Route::get('/{id}', 'show');
        Route::post('/{id}', 'status');
        Route::get('/services/{id}', 'services');
    });

    // health professional management routes
    Route::group([
        'controller' => HealthProfessionalController::class,
        'prefix' => 'health-professional'
    ], function () {
        Route::get('/', 'index');
        Route::get('/appointment', 'appointments');
        Route::get('/{id}', 'show');
        Route::post('/', 'create');
        Route::post('/{id}/update', 'update');
        Route::post('/{id}', 'status');
        Route::delete('/{id}', 'destroy');
    });

    // appointments routes
    Route::prefix('appointments')
        ->controller(AppointmentController::class)
        ->group(function () {
            Route::get('/',  'index');
            Route::get('/calender', 'getMonthlyAppointments');
            Route::get('/physicians', 'physicians');
            Route::get('/nurses', 'nurses');
            Route::get('/notifications/{id}', 'allNewAppointments');
            Route::get('/service/notifications/{type}', 'serviceNewAppointments');
            Route::post('/physician', 'assignPhysician');
            Route::get('/{id}',  'show');
            Route::post('/',  'create');
            Route::post('/payment',  'purchase');
            Route::post('/{id}',  'update');
            Route::post('/cancel/{id}',  'cancel');
        });

    // service management routes
    Route::group([
        'controller' => ServiceController::class,
        'prefix' => 'services'
    ], function () {
        Route::get('/', 'index');
        Route::get('/{id}', 'show');
        Route::post('/{id}/status', 'status');
        Route::post('/', 'create');
        Route::post('/{id}', 'update');
        Route::delete('/{id}', 'destroy');
    });

    // slots routes
    Route::prefix('slots')
        ->controller(SlotsController::class)->group(function () {
            Route::get('/',  'index');
            Route::get('/check-slots-availablity',  'checkSlotsAvailablity');
            Route::get('/history',  'history');
            Route::get('/services',  'services');
            Route::get('/{id}',  'show');
            Route::post('/',  'create');
            Route::post('/{id}',  'update');
            Route::delete('/{id}', 'destroy');
        });

    // Bundle Service routes
    Route::prefix('lab-bundles')
        ->controller(BundleController::class)->group(function () {
            Route::get('/',  'index');
            Route::get('/services',  'services');
            Route::post('/{id}/status', 'status');
            Route::get('/{id}',  'show');
            Route::post('/',  'create');
            Route::post('/{id}',  'update');
        });


    // Spceiality routes
    Route::prefix('speciality')
        ->controller(SpecialityController::class)->group(function () {
            Route::get('/',  'index');
            Route::post('/{id}/status', 'status');
            Route::get('/{id}',  'show');
            Route::post('/',  'create');
            Route::post('/{id}',  'update');
        });

    Route::prefix('banner')
        ->controller(BannerController::class)->group(function () {
            Route::get('/',  'index');
            Route::post('/{id}/status', 'status');
            Route::get('/{id}',  'show');
            Route::post('/',  'create');
            Route::post('/{id}',  'update');
            Route::delete('/{id}', 'destroy');
        });

    // medical order routes
    Route::prefix('medical-orders')
        ->controller(MedicalOrderController::class)
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/{id}',  'show');
            Route::post('/{id}',  'update');
        });


    // insurance routes
    Route::prefix('insurance')
        ->controller(InsuranceController::class)->group(function () {
            Route::get('/',  'index');
            Route::post('/{id}/status/{status}', 'status');
            Route::get('/{id}',  'show');
            Route::post('/',  'create');
            Route::post('/{id}',  'update');
        });



    // Notifications routes
    Route::group([
        'controller' => NotificationController::class,
        'prefix' => 'notifications'
    ], function () {
        Route::get('/', 'index');
        Route::post('/{id?}', 'update');
    });


    // Feedback management routes
    Route::group([
        'controller' => FeedbackController::class,
        'prefix' => 'feedback'
    ], function () {
        Route::get('/', 'index');
        Route::get('/{id}', 'show');
        Route::post('/{id}', 'create');
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

    // Site Information management routes
    Route::group([
        'controller' => SiteInformationController::class,
        'prefix' => 'site-information'
    ], function () {
        Route::get('/', 'index');
        Route::get('/{id}', 'show');
        Route::post('/', 'create');
        Route::post('/{id}', 'update');
        Route::delete('/{id}', 'destroy');
    });

    Route::group(['controller' => HomeController::class, 'prefix' => 'charts'], function () {
        //payment - user - order - payments
        Route::get('/{type}', 'chart')->where('type', 'user|doctor|nurse|physician|appointment');
    });


    // payments routes
    Route::group([
        'controller' => PaymentController::class,
        'prefix' => 'payments'
    ], function () {
        Route::get('/', 'index');
        Route::get('/earnings', 'earnings');

        Route::get('/{id}',  'show');
    });

    // reminder setting routes
    Route::group([
        'controller' => ReminderSettingController::class,
        'prefix' => 'reminders'
    ], function () {
        Route::get('/', 'index');
        Route::post('/', 'create');
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

    // chat routes
    Route::prefix('chats')
        ->controller(ChatController::class)
        ->group(function () {
            Route::get('/', 'index');
            Route::post('/',  'create');
            Route::post('/send',  'send');
            Route::get('/{id}', 'show');
        });

    Route::prefix('pages')
        ->controller(PageController::class)
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/{id}', 'show');
            Route::post('/', 'store');
            Route::post('/{id}/update', 'update');
            Route::delete('/{id}', 'destroy');
            Route::post('/{id}/status', 'status');
        });

    // Prescriptions routes
    Route::group([
        'controller' => PrescriptionController::class,
        'prefix' => 'prescriptions'
    ], function () {
        Route::get('/', 'index');
        Route::get('/patients', 'patients');
        Route::get('/users', 'users');
        Route::post('/', 'store');
        Route::get('/{id}', 'show');
        Route::delete('/{id}', 'destroy');
    });

    Route::group(['prefix' => 'general'], function () {
        Route::get('/specialities', [GeneralController::class, 'specialities']);
        Route::get('/languages', [GeneralController::class, 'languages']);
    });
});
