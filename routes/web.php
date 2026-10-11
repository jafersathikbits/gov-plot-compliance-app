<?php

use App\Http\Controllers\CaseSetupController;
use App\Http\Controllers\ClaimController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\PolicyTemplateController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SubmissionController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('role:super_admin|state_admin')->prefix('policies')->name('policies.')->group(function () {
        Route::get('/', [PolicyTemplateController::class, 'index'])->name('index');
        Route::get('/create', [PolicyTemplateController::class, 'create'])->name('create');
        Route::post('/', [PolicyTemplateController::class, 'store'])->name('store');
    });

    Route::prefix('cases')->name('cases.')->group(function () {
        Route::get('/', [CaseSetupController::class, 'index'])->name('index');
        Route::middleware('role:super_admin|state_admin')->group(function () {
            Route::get('/create', [CaseSetupController::class, 'create'])->name('create');
            Route::post('/', [CaseSetupController::class, 'store'])->name('store');
            Route::post('/{caseId}/assign', [CaseSetupController::class, 'assign'])->name('assign');
            Route::post('/{caseId}/close', [CaseSetupController::class, 'close'])->name('close');
        });
        Route::get('/{caseId}', [CaseSetupController::class, 'show'])->name('show');
    });

    Route::prefix('submissions')->name('submissions.')->group(function () {
        Route::middleware('role:allottee')->group(function () {
            Route::get('/create/{caseId}', [SubmissionController::class, 'create'])->name('create');
            Route::post('/store/{caseId}', [SubmissionController::class, 'store'])->name('store');
        });
        Route::middleware('role:district_officer|inspection_officer|state_admin|super_admin')->group(function () {
            Route::get('/queue', [SubmissionController::class, 'queue'])->name('queue');
            Route::get('/review/{submissionId}', [SubmissionController::class, 'review'])->name('review');
            Route::post('/accept/{submissionId}', [SubmissionController::class, 'accept'])->name('accept');
            Route::post('/return/{submissionId}', [SubmissionController::class, 'returnWithRemarks'])->name('return');
        });
    });

    Route::prefix('claims')->name('claims.')->group(function () {
        Route::middleware('role:district_officer|state_admin|super_admin')->group(function () {
            Route::get('/', [ClaimController::class, 'index'])->name('index');
            Route::get('/create/{caseId}', [ClaimController::class, 'create'])->name('create');
            Route::post('/store/{caseId}', [ClaimController::class, 'store'])->name('store');
            Route::get('/review/{claimId}', [ClaimController::class, 'review'])->name('review');
            Route::post('/approve/{claimId}', [ClaimController::class, 'approve'])->name('approve');
            Route::post('/clarify/{claimId}', [ClaimController::class, 'clarify'])->name('clarify');
            Route::get('/memo/{claimId}', [ClaimController::class, 'memoPdf'])->name('memo');
        });
    });

    Route::middleware('role:state_admin|super_admin')->prefix('legal')->name('legal.')->group(function () {
        Route::post('/notice/{caseId}', [LegalController::class, 'issueNotice'])->name('notice');
        Route::post('/extension/{caseId}', [LegalController::class, 'grantExtension'])->name('extension');
        Route::post('/review/{caseId}', [LegalController::class, 'flagLegalReview'])->name('review');
        Route::post('/terminate/{caseId}', [LegalController::class, 'terminate'])->name('terminate');
    });

    Route::middleware('role:super_admin|state_admin|district_officer')->prefix('reports')->name('reports.')->group(function () {
        Route::get('/monthly', [ReportController::class, 'monthly'])->name('monthly');
        Route::get('/non-compliance', [ReportController::class, 'nonCompliance'])->name('nonCompliance');
        Route::get('/case-certificate/{caseId}', [ReportController::class, 'certificate'])->name('certificate');
    });
});

require __DIR__.'/auth.php';
