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
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\AdminMilestoneController;
use App\Http\Controllers\SkillOverviewController;
use App\Http\Controllers\TrainerTerminController;
use App\Http\Controllers\TrainerSchulungController;
use App\Http\Controllers\TrainerTeilnehmerController;
use App\Livewire\Admin\CareerLevelRatesManager;
use App\Livewire\Admin\CLevelDashboard;
use App\Livewire\Admin\PlanBudgetPage;
use App\Livewire\Admin\PeopleManagerDashboard;
use App\Livewire\Admin\TeamBudgetOverview;
use App\Livewire\Admin\TrainingBookingManager;
use App\Livewire\MyBudgetStatus;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::get('/demo', fn () => view('demo'))
    ->middleware(['auth', 'verified'])->name('demo');

Route::middleware(['auth', 'verified'])->group(function () {

    // === Globale Suche & Benachrichtigungen ===
    Route::get('/search', SearchController::class)->name('search');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.readAll');

    // === Lernen (alle Rollen) ===
    Route::get('/dashboard', [AcademyController::class, 'dashboard'])->name('dashboard');
    Route::get('/module/{module}', [AcademyController::class, 'showModule'])->name('academy.module.show');
    Route::get('/my-timeline', [AcademyController::class, 'timeline'])->name('academy.timeline');
    Route::get('/my-development/budget', MyBudgetStatus::class)->name('my-budget-status');
    Route::get('/admin/employee/{userId}/budget', MyBudgetStatus::class)->name('admin.employee-budget');

    Route::post('/enroll', [EnrollmentController::class, 'store'])->name('enroll');
    Route::patch('/enrollment/{enrollment}/cancel', [EnrollmentController::class, 'cancel'])->name('enrollment.cancel');
    Route::patch('/enrollment/{enrollment}/rebook', [EnrollmentController::class, 'rebook'])->name('enrollment.rebook');

    // === Schulungskatalog ===
    Route::get('/skill-overview', [SkillOverviewController::class, 'index'])->name('academy.skill-overview');
    Route::post('/skill-overview/{module}/interest', [SkillOverviewController::class, 'expressInterest'])->name('academy.interest.store');
    Route::delete('/skill-overview/{module}/interest', [SkillOverviewController::class, 'withdrawInterest'])->name('academy.interest.destroy');

    Route::get('/portfolio', [PortfolioController::class, 'index'])->name('portfolio.index');
    Route::post('/portfolio', [PortfolioController::class, 'store'])->name('portfolio.store');
    Route::get('/portfolio/{upload}/preview', [PortfolioController::class, 'preview'])->name('portfolio.preview');
    Route::get('/portfolio/{upload}/download', [PortfolioController::class, 'download'])->name('portfolio.download');
    Route::delete('/portfolio/{upload}', [PortfolioController::class, 'destroy'])->name('portfolio.destroy');
    Route::get('/portfolio/material/{material}/download', [PortfolioController::class, 'downloadMaterial'])->name('portfolio.material.download');

    Route::get('/quiz/{quiz}', [QuizController::class, 'show'])->name('quiz.show');
    Route::post('/quiz/{quiz}/submit', [QuizController::class, 'submit'])->name('quiz.submit');

    // === Mitarbeiterorga (People Manager / Head of / Admin) ===
    Route::middleware('can:manager')->group(function () {
        // Team-Ampel Dashboard
        Route::get('/admin/dashboard/team', PeopleManagerDashboard::class)->name('admin.dashboard.team');
        
        // Team Budget Übersicht
        Route::get('/admin/dashboard/team/{teamId}', TeamBudgetOverview::class)->name('admin.team-budget');
        
        // Weiterbildung einbuchen
        Route::get('/admin/training-bookings', TrainingBookingManager::class)->name('admin.training-bookings');

        Route::prefix('manage')->name('manage.')->group(function () {
            Route::get('/employees', [EmployeeManagementController::class, 'index'])->name('employees.index');
            Route::get('/employees/{user}', [EmployeeManagementController::class, 'show'])->name('employees.show');
            Route::post('/employees/{user}/modules', [EmployeeManagementController::class, 'assignModule'])->name('employees.assignModule');
            Route::delete('/employees/{user}/modules/{module}', [EmployeeManagementController::class, 'removeModule'])->name('employees.removeModule');
            Route::post('/employees/{user}/modules/{module}/disable', [EmployeeManagementController::class, 'disableCareerModule'])->name('employees.disableCareerModule');
            Route::delete('/employees/{user}/modules/{module}/disable', [EmployeeManagementController::class, 'enableCareerModule'])->name('employees.enableCareerModule');
            Route::patch('/employees/{user}/career-level', [EmployeeManagementController::class, 'assignCareerLevel'])->name('employees.assignCareerLevel');
            Route::delete('/employees/{user}/career-path', [EmployeeManagementController::class, 'removeCareerPath'])->name('employees.removeCareerPath');
            Route::patch('/employees/{user}/interests/{interest}/note', [EmployeeManagementController::class, 'noteInterest'])->name('employees.noteInterest');
            Route::post('/employees/{user}/interests/{interest}/assign', [EmployeeManagementController::class, 'assignFromInterest'])->name('employees.assignFromInterest');
            Route::post('/employees/{user}/milestones/{milestone}/disable', [EmployeeManagementController::class, 'disableMilestone'])->name('employees.disableMilestone');
            Route::delete('/employees/{user}/milestones/{milestone}/disable', [EmployeeManagementController::class, 'enableMilestone'])->name('employees.enableMilestone');
        });

        Route::prefix('admin')->name('admin.')->group(function () {
            Route::get('/matrix', [AdminMatrixController::class, 'index'])->name('matrix.index');
            Route::patch('/matrix/{mapping}', [AdminMatrixController::class, 'updateMapping'])->name('matrix.update');
            Route::post('/matrix/sync', [AdminMatrixController::class, 'sync'])->name('matrix.sync');
            Route::post('/matrix/auto-map', [AdminMatrixController::class, 'autoMap'])->name('matrix.autoMap');

            Route::resource('milestones', AdminMilestoneController::class)->except(['show']);
        });
    });

    // === Schulungsmanagement (Schulungsmanager / Admin) ===
    Route::middleware('can:schulungsmanager')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/modules', [AdminModuleController::class, 'index'])->name('modules.index');
        Route::get('/modules/create', [AdminModuleController::class, 'create'])->name('modules.create');
        Route::post('/modules', [AdminModuleController::class, 'store'])->name('modules.store');
        Route::get('/modules/{module}/edit', [AdminModuleController::class, 'edit'])->name('modules.edit');
        Route::put('/modules/{module}', [AdminModuleController::class, 'update'])->name('modules.update');
        Route::delete('/modules/{module}', [AdminModuleController::class, 'destroy'])->name('modules.destroy');
        Route::get('/paths/create', [AdminModuleController::class, 'createPath'])->name('paths.create');
        Route::post('/paths', [AdminModuleController::class, 'storePath'])->name('paths.store');
        Route::get('/paths/{path}', [AdminModuleController::class, 'showPath'])->name('paths.show');
        Route::get('/paths/{path}/edit', [AdminModuleController::class, 'editPath'])->name('paths.edit');
        Route::put('/paths/{path}', [AdminModuleController::class, 'updatePath'])->name('paths.update');
        Route::delete('/paths/{path}', [AdminModuleController::class, 'destroyPath'])->name('paths.destroy');

        Route::get('/skill-categories', [AdminSkillCategoryController::class, 'index'])->name('skill-categories.index');
        Route::get('/skill-categories/{skillCategory}', [AdminSkillCategoryController::class, 'show'])->name('skill-categories.show');
        Route::post('/skill-categories', [AdminSkillCategoryController::class, 'store'])->name('skill-categories.store');
        Route::put('/skill-categories/{skillCategory}', [AdminSkillCategoryController::class, 'update'])->name('skill-categories.update');
        Route::delete('/skill-categories/{skillCategory}', [AdminSkillCategoryController::class, 'destroy'])->name('skill-categories.destroy');

        Route::get('/methods', [AdminMethodController::class, 'index'])->name('methods.index');
        Route::post('/methods', [AdminMethodController::class, 'store'])->name('methods.store');
        Route::put('/methods/{method}', [AdminMethodController::class, 'update'])->name('methods.update');
        Route::delete('/methods/{method}', [AdminMethodController::class, 'destroy'])->name('methods.destroy');
    });

    // === Trainer-Konsole (Trainer / Admin) ===
    Route::middleware('can:trainer')->prefix('trainer')->name('trainer.')->group(function () {
        Route::get('/termine', [TrainerTerminController::class, 'index'])->name('termine.index');
        Route::post('/termine', [TrainerTerminController::class, 'store'])->name('termine.store');
        Route::post('/termine/check-availability', [TrainerTerminController::class, 'checkAvailability'])->name('termine.check-availability');
        Route::post('/termine/{session}/sync-calendar', [TrainerTerminController::class, 'syncCalendar'])->name('termine.sync-calendar');
        Route::put('/termine/{session}', [TrainerTerminController::class, 'update'])->name('termine.update');
        Route::delete('/termine/{session}', [TrainerTerminController::class, 'destroy'])->name('termine.destroy');
        Route::post('/termine/anfrage/{enrollment}/assign', [TrainerTerminController::class, 'assignRequest'])->name('termine.assign-request');
        Route::post('/termine/anfrage/{enrollment}/decline', [TrainerTerminController::class, 'declineRequest'])->name('termine.decline-request');

        Route::get('/schulungen', [TrainerSchulungController::class, 'index'])->name('schulungen.index');
        Route::get('/schulungen/{module}', [TrainerSchulungController::class, 'show'])->name('schulungen.show');
        Route::put('/schulungen/{module}', [TrainerSchulungController::class, 'update'])->name('schulungen.update');
        Route::post('/schulungen/{module}/quiz', [TrainerSchulungController::class, 'storeQuiz'])->name('schulungen.quiz.store');
        Route::post('/schulungen/{module}/materials', [TrainerSchulungController::class, 'storeMaterial'])->name('schulungen.materials.store');
        Route::post('/schulungen/{module}/links', [TrainerSchulungController::class, 'storeLink'])->name('schulungen.links.store');
        Route::delete('/schulungen/materials/{material}', [TrainerSchulungController::class, 'destroyMaterial'])->name('schulungen.materials.destroy');

        Route::get('/teilnehmer', [TrainerTeilnehmerController::class, 'index'])->name('teilnehmer.index');
        Route::post('/teilnehmer/{session}/confirm', [TrainerTeilnehmerController::class, 'confirmAttendance'])->name('teilnehmer.confirm');
    });

    // === Budget-Controlling (Admin / C-Level) ===
    Route::middleware('can:admin')->group(function () {
        Route::get('/admin/dashboard/budgets', CLevelDashboard::class)->name('admin.dashboard.budgets');
        Route::get('/admin/dashboard/budgets/plan', PlanBudgetPage::class)->name('admin.dashboard.plan-budgets');
        Route::get('/admin/budget/rates', CareerLevelRatesManager::class)->name('admin.budget.rates');
    });

    // === System (nur Admin) ===
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

        Route::get('/settings', [AdminSettingsController::class, 'index'])->name('settings.index');
    });

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

require __DIR__.'/auth.php';
