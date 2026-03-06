<?php

use App\Http\Controllers\AcademyController;
use App\Http\Controllers\AdminMatrixController;
use App\Http\Controllers\AdminModuleController;
use App\Http\Controllers\AdminSettingsController;
use App\Http\Controllers\AdminMethodController;
use App\Http\Controllers\AdminSkillCategoryController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\EmployeeManagementController;
use App\Http\Controllers\ModuleAssignmentController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\TeacherController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::get('/demo', fn () => view('demo'))
    ->middleware(['auth', 'verified'])->name('demo');

Route::middleware(['auth', 'verified'])->group(function () {

    // Student Dashboard ("Meine Academy")
    Route::get('/dashboard', [AcademyController::class, 'dashboard'])->name('dashboard');

    // Enrollment
    Route::post('/enroll', [EnrollmentController::class, 'store'])->name('enroll');
    Route::patch('/enrollment/{enrollment}/cancel', [EnrollmentController::class, 'cancel'])->name('enrollment.cancel');
    Route::patch('/enrollment/{enrollment}/rebook', [EnrollmentController::class, 'rebook'])->name('enrollment.rebook');

    Route::get('/my-timeline', [AcademyController::class, 'timeline'])->name('academy.timeline');

    // Portfolio (Digital Portfolio)
    Route::get('/portfolio', [PortfolioController::class, 'index'])->name('portfolio.index');
    Route::post('/portfolio', [PortfolioController::class, 'store'])->name('portfolio.store');
    Route::get('/portfolio/{upload}/preview', [PortfolioController::class, 'preview'])->name('portfolio.preview');
    Route::get('/portfolio/{upload}/download', [PortfolioController::class, 'download'])->name('portfolio.download');
    Route::delete('/portfolio/{upload}', [PortfolioController::class, 'destroy'])->name('portfolio.destroy');

    // Quizzes
    Route::get('/quiz/{quiz}', [QuizController::class, 'show'])->name('quiz.show');
    Route::post('/quiz/{quiz}/submit', [QuizController::class, 'submit'])->name('quiz.submit');

    // Employee Management (People Manager)
    Route::middleware('can:manager')->prefix('manage')->name('manage.')->group(function () {
        Route::get('/employees', [EmployeeManagementController::class, 'index'])->name('employees.index');
        Route::get('/employees/{user}', [EmployeeManagementController::class, 'show'])->name('employees.show');
        Route::post('/employees/{user}/modules', [EmployeeManagementController::class, 'assignModule'])->name('employees.assignModule');
        Route::delete('/employees/{user}/modules/{module}', [EmployeeManagementController::class, 'removeModule'])->name('employees.removeModule');
        Route::post('/employees/{user}/modules/{module}/disable', [EmployeeManagementController::class, 'disableCareerModule'])->name('employees.disableCareerModule');
        Route::delete('/employees/{user}/modules/{module}/disable', [EmployeeManagementController::class, 'enableCareerModule'])->name('employees.enableCareerModule');
        Route::patch('/employees/{user}/career-level', [EmployeeManagementController::class, 'assignCareerLevel'])->name('employees.assignCareerLevel');
        Route::delete('/employees/{user}/career-path', [EmployeeManagementController::class, 'removeCareerPath'])->name('employees.removeCareerPath');
    });

    // Teacher Console
    Route::middleware('can:teacher')->group(function () {
        Route::get('/teacher', [TeacherController::class, 'dashboard'])->name('teacher.dashboard');
        Route::post('/teacher/sessions', [TeacherController::class, 'storeSession'])->name('teacher.sessions.store');
        Route::post('/teacher/sessions/{session}/confirm-attendance', [TeacherController::class, 'confirmAttendance'])->name('teacher.sessions.confirmAttendance');
        Route::delete('/teacher/sessions/{session}', [TeacherController::class, 'destroySession'])->name('teacher.sessions.destroy');
    });

    // Admin: Strukturverwaltung (Manager: admin, people_manager, head_of)
    Route::middleware('can:manager')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/modules', [AdminModuleController::class, 'index'])->name('modules.index');
        Route::get('/modules/create', [AdminModuleController::class, 'create'])->name('modules.create');
        Route::post('/modules', [AdminModuleController::class, 'store'])->name('modules.store');
        Route::get('/modules/{module}/edit', [AdminModuleController::class, 'edit'])->name('modules.edit');
        Route::put('/modules/{module}', [AdminModuleController::class, 'update'])->name('modules.update');
        Route::delete('/modules/{module}', [AdminModuleController::class, 'destroy'])->name('modules.destroy');
        Route::post('/modules/{module}/quiz', [AdminModuleController::class, 'storeQuiz'])->name('modules.quiz.store');
        Route::get('/paths/create', [AdminModuleController::class, 'createPath'])->name('paths.create');
        Route::post('/paths', [AdminModuleController::class, 'storePath'])->name('paths.store');
        Route::delete('/paths/{path}', [AdminModuleController::class, 'destroyPath'])->name('paths.destroy');
    });

    // Admin: Skill-Kategorien & Methoden (Teacher: admin, people_manager, head_of, trainer)
    Route::middleware('can:teacher')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/skill-categories', [AdminSkillCategoryController::class, 'index'])->name('skill-categories.index');
        Route::post('/skill-categories', [AdminSkillCategoryController::class, 'store'])->name('skill-categories.store');
        Route::put('/skill-categories/{skillCategory}', [AdminSkillCategoryController::class, 'update'])->name('skill-categories.update');
        Route::delete('/skill-categories/{skillCategory}', [AdminSkillCategoryController::class, 'destroy'])->name('skill-categories.destroy');

        Route::get('/methods', [AdminMethodController::class, 'index'])->name('methods.index');
        Route::post('/methods', [AdminMethodController::class, 'store'])->name('methods.store');
        Route::put('/methods/{method}', [AdminMethodController::class, 'update'])->name('methods.update');
        Route::delete('/methods/{method}', [AdminMethodController::class, 'destroy'])->name('methods.destroy');
    });

    // Admin: System (nur Admin)
    Route::middleware('can:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::patch('/users/{user}/career-path', [AdminUserController::class, 'assignCareerPath'])->name('users.assignCareerPath');
        Route::patch('/users/{user}/roles', [AdminUserController::class, 'updateRoles'])->name('users.updateRoles');
        Route::patch('/users/{user}/managed-teams', [AdminUserController::class, 'updateManagedTeams'])->name('users.updateManagedTeams');
        Route::post('/users/{user}/invite', [AdminUserController::class, 'sendInvitation'])->name('users.invite');
        Route::post('/users/{user}/reset-password', [AdminUserController::class, 'resetPassword'])->name('users.resetPassword');
        Route::post('/users/{user}/archive', [AdminUserController::class, 'archive'])->name('users.archive');
        Route::post('/users/{user}/restore', [AdminUserController::class, 'restore'])->name('users.restore');
        Route::post('/users/sync-personio', [AdminUserController::class, 'syncPersonio'])->name('users.syncPersonio');

        Route::get('/matrix', [AdminMatrixController::class, 'index'])->name('matrix.index');
        Route::patch('/matrix/{mapping}', [AdminMatrixController::class, 'updateMapping'])->name('matrix.update');
        Route::post('/matrix/sync', [AdminMatrixController::class, 'sync'])->name('matrix.sync');

        Route::get('/settings', [AdminSettingsController::class, 'index'])->name('settings.index');
    });

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
