<?php

use App\Http\Controllers\VerificationController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\AiKeyController;
use App\Http\Controllers\MyAiKeyController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\AnswerController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\StatusController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : view('landing');
});

Route::view('/privasi', 'privacy')->name('privacy');
Route::view('/ketentuan', 'terms')->name('terms');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');

    Route::post('/auth/google', [SocialAuthController::class, 'redirect'])->middleware('throttle:10,1')->name('auth.google');
    Route::get('/auth/google/callback', [SocialAuthController::class, 'callback'])->name('auth.google.callback');

    Route::get('/lupa-password', [PasswordController::class, 'request'])->name('password.request');
    Route::post('/lupa-password', [PasswordController::class, 'email'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordController::class, 'update'])->middleware('throttle:5,1')->name('password.update');
});
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])
    ->middleware(['signed', 'throttle:10,1'])->name('verification.verify');

Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [VerificationController::class, 'notice'])->name('verification.notice');
    Route::post('/email/verification-notification', [VerificationController::class, 'resend'])
        ->middleware('throttle:3,1')->name('verification.send');

    Route::get('/akun', [AccountController::class, 'show'])->name('account.show');
    Route::put('/akun', [AccountController::class, 'update'])->name('account.update');
    Route::put('/akun/password', [AccountController::class, 'updatePassword'])->name('account.password');
    Route::delete('/akun', [AccountController::class, 'destroy'])->name('account.destroy');
    Route::get('/akun/ekspor', [AccountController::class, 'export'])->name('account.export');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [ExamController::class, 'index'])->name('dashboard');

    Route::get('/exams/create', [ExamController::class, 'create'])->name('exams.create');
    Route::post('/exams', [ExamController::class, 'store'])->name('exams.store');
    Route::get('/exams/{exam}', [ResultController::class, 'show'])->name('exams.show');
    Route::get('/exams/{exam}/export', [ResultController::class, 'export'])->name('exams.export');
    Route::post('/exams/{exam}/retry-failed', [ResultController::class, 'retryFailed'])->name('exams.retry');
    Route::get('/exams/{exam}/live', [ExamController::class, 'live'])->name('exams.live');

    Route::get('/exams/{exam}/edit', [ExamController::class, 'edit'])->name('exams.edit');
    Route::post('/exams/{exam}/reanalyze', [ExamController::class, 'reanalyze'])->name('exams.reanalyze');
    Route::put('/exams/{exam}/fields', [ExamController::class, 'saveAll'])->name('exams.fields');
    Route::post('/exams/{exam}/activate', [ExamController::class, 'activate'])->name('exams.activate');
    Route::post('/exams/{exam}/close', [ExamController::class, 'close'])->name('exams.close');
    Route::post('/exams/{exam}/sync', [ExamController::class, 'sync'])->name('exams.sync');
    Route::delete('/exams', [ExamController::class, 'bulkDestroy'])->name('exams.bulk-destroy');    
    Route::delete('/exams/{exam}', [ExamController::class, 'destroy'])->name('exams.destroy');

    Route::put('/questions/{question}', [QuestionController::class, 'update'])->name('questions.update');
    Route::post('/questions/{question}/regrade', [QuestionController::class, 'regrade'])->name('questions.regrade');

    Route::post('/answers/{answer}/override', [AnswerController::class, 'override'])->name('answers.override');
    Route::post('/answers/{answer}/revert', [AnswerController::class, 'revert'])->name('answers.revert');

    Route::get('/google', [GoogleController::class, 'index'])->name('google.index');
    Route::get('/google/connect', [GoogleController::class, 'connect'])->name('google.connect');
    Route::get('/google/callback', [GoogleController::class, 'callback'])->name('google.callback');
    Route::post('/google/test', [GoogleController::class, 'test'])->name('google.test');

    Route::get('/kunci-ai', [MyAiKeyController::class, 'index'])->name('mykeys.index');
    Route::post('/kunci-ai', [MyAiKeyController::class, 'store'])->name('mykeys.store');
    Route::post('/kunci-ai/uji', [MyAiKeyController::class, 'test'])->middleware('throttle:6,1')->name('mykeys.test');
    Route::delete('/kunci-ai/{aiKey}', [MyAiKeyController::class, 'destroy'])->name('mykeys.destroy');

    Route::middleware(EnsureAdmin::class)->group(function () {
        Route::get('/admin/status', [StatusController::class, 'index'])->name('admin.status');
        Route::post('/admin/mail-test', [StatusController::class, 'testMail'])->middleware('throttle:3,1')->name('admin.mail-test');

        Route::get('/keys', [AiKeyController::class, 'index'])->name('keys.index');
        Route::post('/keys', [AiKeyController::class, 'store'])->name('keys.store');
        Route::post('/keys/{aiKey}/toggle', [AiKeyController::class, 'toggle'])->name('keys.toggle');
        Route::post('/keys/{aiKey}/test', [AiKeyController::class, 'test'])->name('keys.test');
        Route::delete('/keys/{aiKey}', [AiKeyController::class, 'destroy'])->name('keys.destroy');
    });
});