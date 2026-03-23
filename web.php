<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\logincontroller;
use App\Http\Controllers\admincontroller;
use App\Http\Controllers\viewcontroller;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\ceecontroller;
use App\Http\Controllers\ChecklistController;
use App\Http\Controllers\ChecklistMasterController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\EmployeeController;

Route::get('test', function () {
    return view('test');
});



Route::get('/', [loginController::class, 'login'])->name('login');
Route::post('loginsave', [loginController::class, 'loginsave'])->name('loginsave');
Route::post('logout', [loginController::class, 'logout'])->name('logout');
Route::get('/refresh-captcha', function () {
    $captcha = rand(1000, 9999);
    session(['captcha_number' => $captcha]);

    $digits = collect(str_split($captcha))->map(function ($digit, $index) {
        $colors = ['#e74c3c', '#3498db', '#27ae60', '#f1c40f'];
        $color = $colors[$index % count($colors)];
        return "<span style='color:$color;font-weight:bold;font-size:24px;margin:0 3px;'>$digit</span>";
    })->implode('');

    return response()->json(['captcha' => $digits]);
})->name('refresh.captcha');



    Route::get('password/change', [loginController::class, 'changePassword'])->name('changepassword');
    Route::post('password/change', [loginController::class, 'updatePassword'])->name('password.update');



    Route::middleware(['auth'])->group(function () {
        // Route::get('/home', function () {
        //     return view('home');
        // })->name('home');
        Route::get('/home', [AdminController::class, 'home'])->name('home');
    
        Route::get('/select-exam', [ExamController::class, 'show'])->name('exam.select');
        Route::post('/select-exam', [ExamController::class, 'submit'])->name('exam.submit');
    
        // Your redirect routes, for example:
        Route::get('/adminhome', function () {
            return view('admin.home');
        })->name('adminhome');
    
        Route::get('/adminhome_test', function () {
            return view('admin.home_test');
        })->name('adminhome.test');
    
        Route::get('/normal_user', function () {
            return view('user.home');
        })->name('normal.user');
    
    
       
       
    
        Route::get('/college/clg_details', [AdminController::class, 'clg_details'])->name('clg_details');
        Route::put('/college/clg_detailsupdate', [AdminController::class, 'clg_detailsupdate'])->name('clg_detailsupdate');
        Route::get('/contact/edit', [AdminController::class, 'contact_edit'])->name('contact.edit');
        Route::put('/contact/update', [AdminController::class, 'contact_update'])->name('contact.update');
       
    Route::get('/college/course', [AdminController::class, 'course_details'])->name('course');
    Route::post('/courses/verify/{status}', [AdminController::class, 'courseverify'])->name('courseverify');
    
    
    
    
    Route::post('/college/course_add', [AdminController::class, 'course_add'])->name('course_add');
    Route::post('/college/course_delete', [AdminController::class, 'course_delete'])->name('course_delete');
    Route::get('/college/collegeview', [viewcontroller::class, 'showHomeView'])->name('homeview');
    Route::get('/exam/{examName}/tables', [viewcontroller::class, 'examdetails'])->name('examdetails');
    Route::get('/newcourse', [AdminController::class, 'newcourse'])->name('newcourse');
    Route::post('/newcoursesave', [AdminController::class, 'newcoursesave'])->name('newcoursesave');
    
    
    /////accountdetails
    
    Route::get('/show_account_details', [AdminController::class, 'show_account_details'])->name('accountdetails');
    Route::get('/edit_account_details', [AdminController::class, 'edit_account_details'])->name('accountdetailsedit');
    Route::post('/submit_account_details', [AdminController::class, 'submit_account_details'])->name('accountdetailssave');
    Route::get('/confirm_account_details', [AdminController::class, 'confirm_account_details'])->name('confirmaccountdetails');
    // Route::get('/basic_details_2', [AdminController::class, 'basic_details_2'])->name('basicdetails2');
    // Route::get('/college/update', [CollegeController::class, 'updatecollegebasicdetails2'])->name('basicdetails2update');
    //     Route::post('/college/updated', [CollegeController::class, 'update'])->name('college.update');
    Route::get('/print_upload_basicdetails', [AdminController::class, 'printPage'])
        ->name('print_upload_basicdetails');
    
    Route::match(['get', 'post'], '/cap/basic-details', [AdminController::class, 'index'])
        ->name('CAP.basic_details_2');
        Route::get('/view-doc/{type}', [AdminController::class, 'viewDoc'])
        ->name('view.doc');
        Route::get('/print-upload-basicdetails', [AdminController::class, 'printUploadBasicDetails'])->name('print_upload_basicdetails');
    Route::post('/print-upload-basicdetails', [AdminController::class, 'uploadAndVerify'])->name('upload_and_verify');
    Route::get('/print-basicdetails-pdf', [AdminController::class, 'printBasicDetails'])->name('print_basicdetails_pdf');
    
    
    //law
    Route::match(['get', 'post'], 'basicdetails_law',
        [AdminController::class, 'basicdetails_law']
    )->name('CAP.basicdetails_law');
    //pharmacy
    Route::match(['get', 'post'], 'basicdetails_bp',
        [AdminController::class, 'basicdetails_bp']
        )->name('CAP.basicdetails_bp');
     Route::match(['get', 'post'], 'basicdetails_m',
        [AdminController::class, 'basicdetails_m']
        )->name('CAP.basicdetails_m');    
    
    
    
        ///////////////////////////////reports
        Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
    Route::post('/reports/fee-transfer', [ReportsController::class, 'feeTransfer'])->name('reports.fee-transfer');
    Route::post('/reports/fee-concession', [ReportsController::class, 'feeConcession'])
        ->name('reports.feeconcession');
    
    // Show report form
    Route::get('/reports/lig', [ReportsController::class, 'ligForm'])->name('reports.lig.form');
    
    // Generate and display the LIG report (POST)
    Route::post('/reports/lig-print', [ReportsController::class, 'generateLigReport'])->name('reports.lig.print');
    Route::post('/reports/aicte', [ReportsController::class, 'aicteVerification'])
        ->name('reports.aicte');
    // Show the report form
    Route::get('/reports/aicte', [ReportsController::class, 'showAicteForm'])->name('reports.aicte');
    
    Route::get('/report/lig-print', [ReportController::class, 'ligPrintReport'])
        ->name('reports.lig-veri');
    ////////////////////////////////////////////ceeuser
    Route::get('/cee_management', [ceecontroller::class, 'cee_management'])
        ->name('cee_management');
    //     Route::get('/tc_admn', [ceecontroller::class, 'tc_admn'])->name('cee.tc_admn');
    //     Route::post('/tc_admn_fetch', [ceecontroller::class, 'fetch'])->name('college.fetch');
    // Route::post('/tc_admn/enable', [ceecontroller::class, 'enable'])->name('college.enable');
    
        Route::match(['get', 'post'], '/collegecontact', [ceeController::class, 'contact'])->name('collegecontact');   
        Route::match(['get','post'], '/college-approval', [ceecontroller::class, 'collegeapproval'])
        ->name('collegeapproval');
        ///////////////////////
        Route::match(['get','post'], '/college-fee-transfer-verification', 
        [ceecontroller::class, 'feetransfer_verification']
    )->name('collegefeeverification');
    //////////////////////////
    Route::get('/college-status', [ceecontroller::class, 'feetransfer_status'])
        ->name('feetransfer_status');
    
        Route::get('/account_details', [ceecontroller::class, 'account_status'])
         ->name('account_status');
    
         Route::match(['get', 'post'], '/seat-verification', [ceecontroller::class, 'collegeseatverification'])
        ->name('collegeseatverification');
        Route::match(['get','post'], '/course-approval', [ceecontroller::class, 'coursecollegetc'])
        ->name('coursecollegetc');
    
        
    
    Route::match(['get','post'], '/course-verification', [ceecontroller::class, 'courseconfirm'])
        ->name('courseconfirm');

        Route::match(['get','post'], '/course-visefeeverification', [ceecontroller::class, 'coursevisefeeverification'])
        ->name('coursevisefeeverification');
        Route::match(['get','post'],'vacancyverification', [Ceecontroller::class, 'vacancyVerification'])->name('collegevacancyverification');
    
        //////////////tc
        Route::match(['get','post'], '/tc_admn', [ceecontroller::class, 'tc_admn'])
        ->name('cee.tc_admn');
        // web.php
        Route::get('/cee/get-courses', [CeeController::class, 'getCourses'])
        ->name('cee.getCourses');
    
    Route::get('/cee/get-colleges-by-course', [CeeController::class, 'getCollegesByCourse'])
        ->name('cee.getCollegesByCourse');
    
    /////////////////////////////////password
    
    Route::get('college-admin-reset', [CeeController::class, 'showResetForm'])
            ->name('college_admin_reset');
    
        // Fetch college/admin details (POST)
        Route::post('college-admin-reset/details', [CeeController::class, 'getDetails'])
            ->name('college_admin_reset.details');
    
        // Reset password (POST)
        Route::post('college-admin-reset/reset-password', [CeeController::class, 'resetPassword'])
            ->name('college_admin_reset.reset');
    
        // Send email (POST)
        Route::post('college-admin-reset/send-mail', [CeeController::class, 'sendMail'])
            ->name('college_admin_reset.sendMail');
    
    ///////////////////////////////////////////////////////////////////////////////////////       ////fee
    
         // Fee entry page (GET + POST)
    Route::match(['get', 'post'], '/fee-details', 
    [AdminController::class, 'feedetails']
    )->name('fee_details');
    
    // Show fee details (view-only page)
    Route::match(['get', 'post'], '/show-fee-details', 
    [AdminController::class, 'show_fee_details']
    )->name('show_fee_details');
    
    // Completion page (after finalize)
    Route::match(['get', 'post'], '/fee-details-completed', 
    [AdminController::class, 'feeDetailsCompleted']
    )->name('fee_details_completed');
    
    // PDF print ONLY
    Route::get('/fee-details-print', 
    [AdminController::class, 'viewFeeDetails']
    )->name('fee_details_print');
    
    Route::match(['get', 'post'], '/docs-upload', [AdminController::class, 'docsUpload'])
        ->name('docs_upload');
    
    
    // Store & preview document (base64 in DB)
    Route::post('/docs-upload/store', [AdminController::class, 'storeFeeDocument'])
        ->name('docs_upload_store');
    
    // Finalize upload
    Route::post('/docs-upload/finalize', [AdminController::class, 'finalizeFeeDocument'])
        ->name('docs_upload_finalize');
    
    // View PDF from DB
    Route::get('/docs-view', [AdminController::class, 'docsView'])
        ->name('docs_view');
    
    // After finalize redirect
    Route::get('/show-fee-details', [AdminController::class, 'show_fee_details'])
        ->name('show_fee_details');
      
////////////////////////////////////////////////////////////////////checklist
Route::get('/checklist', [ChecklistController::class, 'index'])->name('checklist.index');

// AJAX route to get sections for a main heading + exam
Route::post('/checklist/sections', [ChecklistController::class, 'getSections'])->name('checklist.sections');

// Form submission route
Route::post('/checklist/save', [ChecklistController::class, 'save'])->name('checklist.save');
 /////////////////////////////////////////////////////////////////////   
 Route::get('/documents', [CeeController::class, 'document'])->name('document');

// Stream the document for inline preview
Route::get('/documents/stream', [CeeController::class, 'streamDocument'])->name('document.stream');












///////////////////////////////////mmmmmmmmmmmmm



Route::get('/checklistmaster',[ChecklistMasterController::class,'index'])->name('checklistmaster');

/* MAIN */
Route::get('/get-main',[ChecklistMasterController::class,'getMain']);
Route::post('/add-main',[ChecklistMasterController::class,'addMain']);
Route::post('/update-main',[ChecklistMasterController::class,'updateMain']);
Route::post('/delete-main',[ChecklistMasterController::class,'deleteMain']);
Route::post('/toggle-main',[ChecklistMasterController::class,'toggleMain']);

/* SECTIONS */
Route::get('/get-sections/{main_id}',[ChecklistMasterController::class,'getSections']);
Route::post('/add-section',[ChecklistMasterController::class,'addSection']);
Route::post('/update-section',[ChecklistMasterController::class,'updateSection']);
Route::post('/delete-section',[ChecklistMasterController::class,'deleteSection']);
Route::post('/toggle-section',[ChecklistMasterController::class,'toggleSection']);

/* SUBITEMS */
Route::get('/get-subitems/{section_id}',[ChecklistMasterController::class,'getSubitems']);
Route::post('/add-subitem',[ChecklistMasterController::class,'addSubitem']);
Route::post('/update-subitem',[ChecklistMasterController::class,'updateSubitem']);
Route::post('/delete-subitem',[ChecklistMasterController::class,'deleteSubitem']);
Route::post('/toggle-subitem',[ChecklistMasterController::class,'toggleSubitem']);

    

Route::get('/exams', [ExamController::class, 'index'])->name('exams.index');
Route::post('/exams/store', [ExamController::class, 'store'])->name('exams.store');
Route::post('/exams/update/{id}', [ExamController::class, 'update'])->name('exams.update');
Route::post('/exams/toggle/{id}', [ExamController::class, 'toggle'])->name('exams.toggle');
Route::match(['get','post'], '/employees', [EmployeeController::class, 'index'])->name('employees.index');
Route::post('/employees/edit/{id}', [EmployeeController::class, 'edit']);

});


////////////////////////////////

//////////////////////checklist view
Route::get('/checklist_dashboard', [ChecklistController::class, 'viewDashboard'])->name('checklist.dashboard');
Route::post('/checklist/fetch-mainheadings', 
    [ChecklistController::class, 'fetchMainHeadings']
)->name('checklist.fetchMainHeadings');


Route::post('/checklist/fetch-sections', [ChecklistController::class, 'fetchSections'])->name('checklist.fetchSections');
Route::post('/checklist/fetch-subitems', [ChecklistController::class, 'fetchSubitems'])->name('checklist.fetchSubitems');
    
