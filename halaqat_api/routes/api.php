<?php

use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\MeController;
use App\Http\Controllers\Api\V1\Auth\RegisterStudentController;
use App\Http\Controllers\Api\V1\Auth\RegisterTeacherController;
use App\Http\Controllers\Api\V1\Halaqas\CreateHalaqaController;
use App\Http\Controllers\Api\V1\Halaqas\GetHalaqaController;
use App\Http\Controllers\Api\V1\Halaqas\ListHalaqasController;
use App\Http\Controllers\Api\V1\Halaqas\SetHalaqaStatusController;
use App\Http\Controllers\Api\V1\Halaqas\UpdateHalaqaController;
use App\Http\Controllers\Api\V1\Memberships\AssignStudentToHalaqaController;
use App\Http\Controllers\Api\V1\Memberships\ListHalaqaStudentsController;
use App\Http\Controllers\Api\V1\Memberships\RemoveStudentFromHalaqaController;
use App\Http\Controllers\Api\V1\Memberships\UpdateMembershipController;
use App\Http\Controllers\Api\V1\Progress\GetStudentAvailabilityController;
use App\Http\Controllers\Api\V1\Progress\GetStudentFollowUpPlanController;
use App\Http\Controllers\Api\V1\Progress\ListStudentMistakesController;
use App\Http\Controllers\Api\V1\Progress\ListStudentTrackingsController;
use App\Http\Controllers\Api\V1\Progress\UpdateStudentAvailabilityController;
use App\Http\Controllers\Api\V1\Progress\UpdateStudentFollowUpPlanController;
use App\Http\Controllers\Api\V1\Quran\QuranController;
use App\Http\Controllers\Api\V1\Registrations\CancelRegistrationRequestController;
use App\Http\Controllers\Api\V1\Registrations\CreateRegistrationRequestController;
use App\Http\Controllers\Api\V1\Registrations\GetRegistrationRequestController;
use App\Http\Controllers\Api\V1\Registrations\ListRegistrationRequestsController;
use App\Http\Controllers\Api\V1\Registrations\RegistrationDecisionController;
use App\Http\Controllers\Api\V1\Sessions\CreateSessionController;
use App\Http\Controllers\Api\V1\Sessions\CreateTaskController;
use App\Models\HalaqaMembership;
use App\Models\LiveSession;
use App\Models\RegistrationRequest;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('register/student', RegisterStudentController::class);
        Route::post('register/teacher', RegisterTeacherController::class);
        Route::post('login', LoginController::class);

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::post('logout', LogoutController::class);
        });
    });

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('me', MeController::class);

        Route::get('halaqas', ListHalaqasController::class);
        Route::post('halaqas', CreateHalaqaController::class);
        Route::get('halaqas/{halaqa}', GetHalaqaController::class);
        Route::patch('halaqas/{halaqa}', UpdateHalaqaController::class);
        Route::post('halaqas/{halaqa}/activate', [SetHalaqaStatusController::class, 'activate']);
        Route::post('halaqas/{halaqa}/deactivate', [SetHalaqaStatusController::class, 'deactivate']);
        Route::get('halaqas/{halaqa}/students', ListHalaqaStudentsController::class);
        Route::post('halaqas/{halaqa}/students', AssignStudentToHalaqaController::class);
        Route::patch('halaqas/{halaqa}/memberships/{membership}', UpdateMembershipController::class);
        Route::delete('halaqas/{halaqa}/memberships/{membership}', RemoveStudentFromHalaqaController::class);

        Route::get('registration-requests', ListRegistrationRequestsController::class);
        Route::post('registration-requests', CreateRegistrationRequestController::class);
        Route::get('registration-requests/{registrationRequest}', GetRegistrationRequestController::class);
        Route::delete('registration-requests/{registrationRequest}', CancelRegistrationRequestController::class);
        Route::post('registration-requests/{registrationRequest}/accept', [RegistrationDecisionController::class, 'accept']);
        Route::post('registration-requests/{registrationRequest}/reject', [RegistrationDecisionController::class, 'reject']);
        Route::post('registration-requests/{registrationRequest}/request-completion', [RegistrationDecisionController::class, 'requestCompletion']);

        Route::model('membership', HalaqaMembership::class);
        Route::model('registrationRequest', RegistrationRequest::class);
        Route::get('students/{student}/availability', GetStudentAvailabilityController::class);
        Route::put('students/{student}/availability', UpdateStudentAvailabilityController::class);
        Route::get('students/{student}/follow-up-plan', GetStudentFollowUpPlanController::class);
        Route::put('students/{student}/follow-up-plan', UpdateStudentFollowUpPlanController::class);
        Route::get('students/{student}/trackings', ListStudentTrackingsController::class);
        Route::get('students/{student}/mistakes', ListStudentMistakesController::class);
        Route::model('student', User::class);
        Route::get('quran/surahs', [QuranController::class, 'surahs']);
        Route::get('quran/pages/{pageNumber}', [QuranController::class, 'page'])->whereNumber('pageNumber');
        Route::get('quran/ayahs/{ayahId}', [QuranController::class, 'ayah'])->whereNumber('ayahId');
        Route::post('sessions', CreateSessionController::class);
        Route::post('sessions/{session}/tasks', CreateTaskController::class);
        Route::model('session', LiveSession::class);
    });
});
