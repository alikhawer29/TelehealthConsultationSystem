<?php

use App\Http\Controllers\Admin\Banner\BannerController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\User\Auth\AuthController;
use App\Http\Controllers\User\Cart\CartController;
use App\Http\Controllers\User\Home\HomeController;
use App\Http\Controllers\User\Nurse\NurseController;
use App\Http\Controllers\User\Report\ReportController;
use App\Http\Controllers\User\Review\ReviewController;
use App\Http\Controllers\User\Account\AccountController;
use App\Http\Controllers\User\Address\AddressController;
use App\Http\Controllers\User\Contact\ContactController;
use App\Http\Controllers\User\Doctors\DoctorsController;
use App\Http\Controllers\User\Payment\PaymentController;
use App\Http\Controllers\User\Service\ServiceController;
use App\Http\Controllers\User\Physician\PhysicianController;
use App\Http\Controllers\User\Miscellaneous\GeneralController;
use App\Http\Controllers\User\Appointment\AppointmentController;
use App\Http\Controllers\User\Password\ForgetPasswordController;
use App\Http\Controllers\User\Notification\NotificationController;
use App\Http\Controllers\User\Advertisement\AdvertisementController;
use App\Http\Controllers\User\Chat\ChatController;
use App\Http\Controllers\User\MedicalOrder\MedicalOrderController;
use App\Http\Controllers\User\MedicalReport\MedicalReportController;
use App\Http\Controllers\User\ReminderSetting\ReminderSettingController;
use App\Http\Controllers\User\Prescription\PrescriptionController;
use App\Http\Controllers\User\CheckSession\CheckSessionAttendedUserLogController;
use App\Http\Controllers\User\Page\PageController;

/*
|--------------------------------------------------------------------------
| User API Routes
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
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:user');
});

Route::group(['prefix' => 'password-recovery'], function () {
    Route::post('verify-email', [ForgetPasswordController::class, 'verifyEmail']);
    Route::post('verify-code', [ForgetPasswordController::class, 'verifyCode']);
    Route::post('update-password', [ForgetPasswordController::class, 'updatePassword']);
});

//Webex Routes
Route::post('/webex/guest-token', [AppointmentController::class, 'getWebexGuestAccessToken']);
Route::get('/webex/guest-service-token', [AppointmentController::class, 'getServiceAppTokenGuest']);


Route::group(['middleware' => ['auth:user', 'role:user']], function () {

    // accounts routes
    Route::prefix('account')
        ->controller(AccountController::class)
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/family-members', 'familyMemberList');
            Route::post('/', 'update');
            Route::post('/add-family', 'familyMember');
            Route::post('/family/{id}', 'familyMemberEdit');
            Route::post('/remove-family/{id}', 'familyMemberRemove');
            Route::delete('/', 'destory');
            Route::post('/change-password', 'changePassword');
            Route::post('/insurance', 'insurance');

            Route::get('/home', [HomeController::class, 'index']);
        });

    // Doctors routes
    Route::prefix('doctors')
        ->controller(DoctorsController::class)
        ->group(function () {
            Route::get('/', 'index');
            Route::post('/',  'create');
            Route::get('/services/{id}',  'services');
            Route::post('/{id}',  'update');
            Route::get('/{id}', 'show');
            Route::delete('/{id}', 'destroy');
            Route::get('/slots/{id}',  'slots');
        });

    // Services routes (Homecare / Lab)
    Route::prefix('services')
        ->controller(ServiceController::class)
        ->group(function () {
            Route::get('/', 'index');
            Route::post('/',  'create');
            Route::get('/lab-slots',  'labSlots');
            Route::get('/iv-drip-slots',  'ivDripSlots');
            Route::post('/{id}',  'update');
            Route::get('/{id}', 'show');
            Route::delete('/{id}', 'destroy');
            // Route::get('/slots/{id}',  'slots');
            Route::get('/slots/all',  'slots');
        });

    // appointments routes
    Route::prefix('appointments')
        ->controller(AppointmentController::class)
        ->group(function () {
            Route::get('/',  'index');
            Route::get('/calender', 'getMonthlyAppointments');
            Route::get('/upcoming', 'upcomingAppointments');
            Route::get('/{id}',  'show');
            Route::post('/',  'create');
            Route::post('/payment',  'purchase');
            Route::post('/{id}',  'update');
            Route::post('/cancel/{id}',  'cancel');
        });

    //medical records
    Route::prefix('medical-records')
        ->controller(MedicalReportController::class)
        ->group(function () {
            Route::get('/',  'index');
            Route::post('/',  'create');
            Route::post('/{id}',  'update');
            Route::delete('/{id}', 'destroy');
        });



    //cart routes
    Route::prefix('cart')
        ->controller(CartController::class)
        ->group(function () {
            Route::get('/count', 'count');
            Route::get('/', 'index');
            Route::get('/details/{id}', 'details');
            Route::post('/checkout', 'checkout');
            Route::delete('/{item_id}', 'destory');
            Route::delete('/flush/all', 'flushCart');
            Route::post('/', 'update');
        });



    // address routes
    Route::prefix('address')
        ->controller(AddressController::class)
        ->group(function () {
            Route::get('/', 'index');
            Route::post('/',  'create');
            Route::post('/{id}',  'update');
            Route::post('/status/{id}',  'status');
            Route::delete('/{id}', 'destory');
        });

    // medical order routes
    Route::prefix('medical-orders')
        ->controller(MedicalOrderController::class)
        ->group(function () {
            Route::get('/', 'index');
            Route::post('/',  'create');
            Route::post('/{id}',  'update');
            Route::post('/status/{id}',  'status');
            Route::delete('/{id}', 'destory');
        });

    // physician routes
    Route::prefix('physicians')
        ->controller(PhysicianController::class)
        ->group(function () {
            Route::get('/',  'index');
            Route::get('/{id}',  'show');
        });

    // nurse routes
    Route::prefix('nurses')
        ->controller(NurseController::class)
        ->group(function () {
            Route::get('/',  'index');
            Route::get('/{id}',  'show');
        });

    // advertisements routes
    Route::prefix('advertisements')->controller(AdvertisementController::class)
        ->group(function () {
            Route::get('/',  'index');
            Route::get('/{id}',  'show');
            Route::post('/',  'create');
        });


    Route::prefix('payments')->controller(PaymentController::class)
        ->group(function () {
            Route::post('/',  'create'); // Generate a PaymentIntent
            Route::post('/confirm-payment',  'confirmPayment'); // Update payment status after success
        });

    //review routes
    Route::prefix('reviews')
        ->controller(ReviewController::class)
        ->group(function () {
            Route::post('/', 'create');
        });

    //report routes
    Route::prefix('report')
        ->controller(ReportController::class)
        ->group(function () {
            Route::post('/', 'create');
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

    // Feedback routes
    // Route::group([
    //     'controller' => ContactController::class,
    //     'prefix' => 'contact-us'
    // ], function () {
    //     Route::get('/', 'index');
    //     Route::get('/information', 'siteInformation');
    //     Route::post('/', 'create');
    // });

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
            Route::get('/{id}/{message_type}', 'show');
        });

    // Prescriptions routes
    Route::group([
        'controller' => PrescriptionController::class,
        'prefix' => 'prescriptions'
    ], function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{id}', 'show');
        Route::delete('/{id}', 'destroy');
    });

    Route::group([
        'controller' => CheckSessionAttendedUserLogController::class,
        'prefix' => 'check-session-logs'
    ], function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{id}', 'show');
        Route::get('/appointment/{appointmentId}', 'getAppointmentLogs');
        Route::get('/user/{userId}', 'getUserLogs');
        Route::get('/check/{appointmentId}/{userId}', 'checkAttendance');
    });
});

Route::group([
    'controller' => ContactController::class,
    'prefix' => 'contact-us'
], function () {
    Route::get('/', 'index');
    Route::get('/information', 'siteInformation');
    Route::post('/', 'create');
});

Route::prefix('banner')
    ->controller(BannerController::class)->group(function () {
        Route::get('/',  'banners');
    });

Route::post('/webex/meeting-joined', [AppointmentController::class, 'storeMeeting']);


Route::prefix('pages')
    ->controller(PageController::class)
    ->group(function () {
        Route::get('/', 'index');
    });

// general apis routes
Route::group(['prefix' => 'general'], function () {
    Route::post('/contact-us', [ContactController::class, 'create']);
    Route::get('/reminder-type', [GeneralController::class, 'reminderTypes']);
    Route::get('/about-us', [GeneralController::class, 'aboutUs']);
    Route::get('/doctors', [GeneralController::class, 'doctorsLisitng']);
    Route::get('/doctors/{id}', [GeneralController::class, 'doctorsDetail']);

    Route::get('/services', [GeneralController::class, 'serviceLisitng']);
    Route::get('/services/{id}', [GeneralController::class, 'serviceDetail']);

    Route::get('/bundles', [GeneralController::class, 'bundleLisitng']);
    Route::get('/bundles/{id}', [GeneralController::class, 'bundleDetail']);

    Route::get('/specialty', [GeneralController::class, 'specialtyListing']);
    Route::get('/home', [GeneralController::class, 'index']);
    Route::get('/countries', [GeneralController::class, 'countries']);
    Route::get('/states', [GeneralController::class, 'states']);
    Route::get('/cities', [GeneralController::class, 'cities']);
    Route::get('/locations', [GeneralController::class, 'locations']);

    Route::get('/top-doctors', [GeneralController::class, 'topDoctors']);;
    Route::get('/recommended-doctors', [GeneralController::class, 'recommendedDoctors']);;
    Route::get('/recent-doctors', [GeneralController::class, 'recentDoctors']);
    Route::get('/reviews/{id}', [GeneralController::class, 'reviews']);;
});
