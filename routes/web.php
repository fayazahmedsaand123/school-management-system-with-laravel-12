<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\Admin\ClassController;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\ExamController;
use App\Http\Controllers\Admin\MarkController;
use App\Http\Controllers\Admin\FeeController;
use App\Http\Controllers\Admin\LibraryController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\NoticeController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\AssignmentController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Admin\MessageController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\NotificationController;



// ==================== PUBLIC ROUTES ====================
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about', [HomeController::class, 'about'])->name('about');
Route::get('/teachers', [HomeController::class, 'teachers'])->name('public.teachers');
Route::get('/admission', [HomeController::class, 'admission'])->name('admission');
Route::post('/admission/apply', [HomeController::class, 'applyAdmission'])->name('admission.apply');
Route::get('/contact', [HomeController::class, 'contact'])->name('contact');
Route::post('/contact', [HomeController::class, 'sendContact'])->name('contact.send');
Route::get('/gallery', [HomeController::class, 'gallery'])->name('gallery');
Route::get('/news', [HomeController::class, 'news'])->name('news');
Route::get('/news/{slug}', [HomeController::class, 'newsDetail'])->name('news.detail');
Route::get('/events', [HomeController::class, 'events'])->name('events');
Route::get('/faq', [HomeController::class, 'faq'])->name('faq');
Route::get('/fee-structure', [HomeController::class, 'feeStructure'])->name('fee.structure');
Route::get('/privacy-policy', [HomeController::class, 'privacy'])->name('privacy');
Route::get('/terms', [HomeController::class, 'terms'])->name('terms');

// ==================== AUTH ROUTES ====================
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/register', [LoginController::class, 'showRegister'])->name('register');
    Route::post('/register', [LoginController::class, 'register']);
    Route::get('/forgot-password', [LoginController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [LoginController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [LoginController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [LoginController::class, 'resetPassword'])->name('password.update');
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// ==================== ADMIN & STAFF ROUTES ====================
Route::middleware(['auth'])->prefix('dashboard')->name('dashboard.')->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('index');

    // Users
    Route::resource('users', UserController::class)->middleware('role:super_admin|admin');

    // Students
    Route::get('students', [StudentController::class, 'index'])
        ->name('students.index')
        ->middleware('permission:view students');

    Route::get('students/create', [StudentController::class, 'create'])
        ->name('students.create')
        ->middleware('permission:create students');

    Route::post('students', [StudentController::class, 'store'])
        ->name('students.store')
        ->middleware('permission:create students');

    Route::get('students/{student}', [StudentController::class, 'show'])
        ->name('students.show')
        ->middleware('permission:view students');

    Route::get('students/{student}/edit', [StudentController::class, 'edit'])
        ->name('students.edit')
        ->middleware('permission:edit students');

    Route::put('students/{student}', [StudentController::class, 'update'])
        ->name('students.update')
        ->middleware('permission:edit students');

    Route::delete('students/{student}', [StudentController::class, 'destroy'])
        ->name('students.destroy')
        ->middleware('permission:delete students');

    // Teachers
    Route::get('teachers', [TeacherController::class, 'index'])
        ->name('teachers.index')
        ->middleware('permission:view teachers');

    Route::get('teachers/create', [TeacherController::class, 'create'])
        ->name('teachers.create')
        ->middleware('permission:create teachers');

    Route::post('teachers', [TeacherController::class, 'store'])
        ->name('teachers.store')
        ->middleware('permission:create teachers');

    Route::get('teachers/{teacher}', [TeacherController::class, 'show'])
        ->name('teachers.show')
        ->middleware('permission:view teachers');

    Route::get('teachers/{teacher}/edit', [TeacherController::class, 'edit'])
        ->name('teachers.edit')
        ->middleware('permission:edit teachers');

    Route::put('teachers/{teacher}', [TeacherController::class, 'update'])
        ->name('teachers.update')
        ->middleware('permission:edit teachers');

    Route::delete('teachers/{teacher}', [TeacherController::class, 'destroy'])
        ->name('teachers.destroy')
        ->middleware('permission:delete teachers');

    Route::get('teachers/{teacher}/salary', [TeacherController::class, 'salary'])
        ->name('teachers.salary')
        ->middleware('permission:view teachers');

    // Classes
    Route::get('classes', [ClassController::class, 'index'])->name('classes.index')->middleware('permission:view classes');
    Route::get('classes/create', [ClassController::class, 'create'])->name('classes.create')->middleware('permission:create classes');
    Route::post('classes', [ClassController::class, 'store'])->name('classes.store')->middleware('permission:create classes');
    Route::get('classes/{class}', [ClassController::class, 'show'])->name('classes.show')->middleware('permission:view classes');
    Route::get('classes/{class}/edit', [ClassController::class, 'edit'])->name('classes.edit')->middleware('permission:edit classes');
    Route::put('classes/{class}', [ClassController::class, 'update'])->name('classes.update')->middleware('permission:edit classes');
    Route::delete('classes/{class}', [ClassController::class, 'destroy'])->name('classes.destroy')->middleware('permission:delete classes');

    // Sections
    Route::get('sections', [SectionController::class, 'index'])->name('sections.index')->middleware('permission:view classes');
    Route::get('sections/create', [SectionController::class, 'create'])->name('sections.create')->middleware('permission:create classes');
    Route::post('sections', [SectionController::class, 'store'])->name('sections.store')->middleware('permission:create classes');
    Route::get('sections/{section}', [SectionController::class, 'show'])->name('sections.show')->middleware('permission:view classes');
    Route::get('sections/{section}/edit', [SectionController::class, 'edit'])->name('sections.edit')->middleware('permission:edit classes');
    Route::put('sections/{section}', [SectionController::class, 'update'])->name('sections.update')->middleware('permission:edit classes');
    Route::delete('sections/{section}', [SectionController::class, 'destroy'])->name('sections.destroy')->middleware('permission:delete classes');

    // Subjects
    Route::get('subjects', [SubjectController::class, 'index'])->name('subjects.index')->middleware('permission:view classes');
    Route::get('subjects/create', [SubjectController::class, 'create'])->name('subjects.create')->middleware('permission:create classes');
    Route::post('subjects', [SubjectController::class, 'store'])->name('subjects.store')->middleware('permission:create classes');
    Route::get('subjects/{subject}', [SubjectController::class, 'show'])->name('subjects.show')->middleware('permission:view classes');
    Route::get('subjects/{subject}/edit', [SubjectController::class, 'edit'])->name('subjects.edit')->middleware('permission:edit classes');
    Route::put('subjects/{subject}', [SubjectController::class, 'update'])->name('subjects.update')->middleware('permission:edit classes');
    Route::delete('subjects/{subject}', [SubjectController::class, 'destroy'])->name('subjects.destroy')->middleware('permission:delete classes');

    // Attendance
    Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index')->middleware('permission:view attendance');
    Route::get('attendance/mark', [AttendanceController::class, 'markForm'])->name('attendance.mark')->middleware('permission:mark attendance');
    Route::post('attendance/mark', [AttendanceController::class, 'store'])->name('attendance.store')->middleware('permission:mark attendance');
    Route::get('attendance/report', [AttendanceController::class, 'report'])->name('attendance.report')->middleware('permission:view attendance');
    Route::get('attendance/ajax/students', [AttendanceController::class, 'getStudentsByClass'])->name('attendance.ajax.students');

    // Exams
    Route::resource('exams', ExamController::class)->middleware('permission:view exams');
    Route::get('marks/entry', [MarkController::class, 'entryForm'])->name('marks.entry')->middleware('permission:view marks');
    Route::post('marks/entry', [MarkController::class, 'store'])->name('marks.store')->middleware('permission:create marks');
    Route::get('marks/report-card/{student}/{exam}', [MarkController::class, 'reportCard'])->name('marks.report-card')->middleware('permission:view marks');

    // Fees
    Route::resource('fees', FeeController::class)->middleware('permission:view fees');
    Route::get('fees/{fee}/receipt', [FeeController::class, 'receipt'])->name('fees.receipt');
    Route::post('fees/{fee}/collect', [FeeController::class, 'collect'])->name('fees.collect')->middleware('permission:collect fees');

    // Library
    Route::prefix('library')->name('library.')->group(function () {
        Route::resource('books', LibraryController::class)->names([
            'index'   => 'books.index',
            'create'  => 'books.create',
            'store'   => 'books.store',
            'show'    => 'books.show',
            'edit'    => 'books.edit',
            'update'  => 'books.update',
            'destroy' => 'books.destroy',
        ]);
        Route::get('issue', [LibraryController::class, 'issueForm'])->name('issue.form');
        Route::post('issue', [LibraryController::class, 'issueBook'])->name('issue');
        Route::post('return/{issue}', [LibraryController::class, 'returnBook'])->name('return');
    });

    // Assignments
    Route::resource('assignments', AssignmentController::class)->middleware('permission:view assignments');
    Route::post('assignments/{assignment}/submit', [AssignmentController::class, 'submit'])->name('assignments.submit');

    // Notices
    Route::resource('notices', NoticeController::class)->middleware('permission:view notices');

    // Events
    Route::resource('events', EventController::class)->middleware('permission:view events');

    // Messages
    Route::resource('messages', MessageController::class)->only(['index','show','destroy']);

    // Reports
    Route::get('reports/students', [ReportController::class, 'students'])->name('reports.students')->middleware('permission:view reports');    
    Route::get('reports/attendance', [ReportController::class, 'attendance'])->name('reports.attendance')->middleware('permission:view reports');
    Route::get('reports/fees', [ReportController::class, 'fees'])->name('reports.fees')->middleware('permission:view reports');
    Route::get('reports/exams', [ReportController::class, 'exams'])->name('reports.exams')->middleware('permission:view reports');
    Route::get('reports/export/{type}/{format}', [ReportController::class, 'export'])->name('reports.export')->middleware('permission:view reports');

    // Profile
    Route::get('profile', [ProfileController::class, 'index'])->name('profile');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('profile/change-password', [ProfileController::class, 'changePassword'])->name('password.change');
    Route::put('profile/change-password', [ProfileController::class, 'updatePassword'])->name('password.update');

    // Notifications
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::delete('notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::delete('notifications', [NotificationController::class, 'destroyAll'])->name('notifications.destroy-all');
    Route::get('notifications/count', [NotificationController::class, 'unreadCount'])->name('notifications.count');
    Route::get('notifications/latest', [NotificationController::class, 'latest'])->name('notifications.latest');

    // Settings
    Route::get('settings', [SettingController::class, 'index'])->name('settings.index')->middleware('role:super_admin|admin');
    Route::post('settings', [SettingController::class, 'update'])->name('settings.update')->middleware('role:super_admin|admin');

    // Ajax Routes
    Route::prefix('ajax')->name('ajax.')->group(function () {
        Route::get('sections/{classId}', [\App\Http\Controllers\Admin\AjaxController::class, 'getSections'])->name('sections');
        Route::get('students-attendance', [\App\Http\Controllers\Admin\AjaxController::class, 'getStudentsForAttendance'])->name('students.attendance');
        Route::get('students-marks', [\App\Http\Controllers\Admin\AjaxController::class, 'getStudentsForMarks'])->name('students.marks');
        Route::get('students-search', [\App\Http\Controllers\Admin\AjaxController::class, 'searchStudents'])->name('students.search');
    });
});