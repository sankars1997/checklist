<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class ceecontroller extends Controller
{
// public function cee_management(){
//     $empRole = session('EmpRole', '');
//     return view('cee.cee_management',compact('empRole'));
// }

public function cee_management()
{
    $empRole = session('EmpRole'); // ex: "REAPEN"
    $codes = str_split($empRole, 2); // ["RE", "AP", "EN"]

    $menu = DB::table('cee_oamsmenu')
            ->whereIn('permission_code', $codes)
            ->where('status', 1)
            ->orderBy('sort_order')
            ->get();

    return view('cee.cee_management', compact('menu', 'empRole'));
}

// public function tc_admn()
// {
//     $colleges = DB::table('collegedetails')
//         ->select('CollegeCode', 'CollegeDesc')
//         ->orderBy('CollegeCode')
//         ->get();

//     return view('cee.tc_admn', compact('colleges'));
// }

// public function tcupdate(Request $request)
// {
//     $request->validate([
//         'CollegeCode' => 'required',
//     ]);

//     $collegeCode = $request->CollegeCode;

//     $tc = $ad = $ap = false;

//     if ($request->tc == 'TC') {
//         $tc = DB::table('employee')
//             ->where('CollegeCode', $collegeCode)
//             ->where('TC_Issued', 'Y')
//             ->update(['TC_Enable' => 'Y']);
//     }

//     if ($request->ad == 'AD') {
//         $ad = DB::table('employee')
//             ->where('CollegeCode', $collegeCode)
//             ->update(['Admn_Enable' => 'Y']);
//     }

//     if ($request->ap == 'AP' || $request->ad == 'AD') {
//         $ap = DB::table('employee')
//             ->where('CollegeCode', $collegeCode)
//             ->whereIn('EmpType', ['A', 'P'])
//             ->update(['AdmnApproved' => 'E']);
//     }

//     $success = ($tc || $ad) && $ap;

//     return back()->with('success', $success ? 'Successfully Updated' : 'Nothing Updated');
// }

    // ---------------------
    // Main TC / Admission Page
    // ---------------------
    
    
/////////////////////////////////////////////////////////////////////////


    public function contact(Request $request){
        $cl_type = $request->input('cl_type', '');
        $cl_group = $request->input('cl_group', '');
        $cse_type = $request->input('cse_type', '');
        $chk = $request->input('chk', []);

        // Get distinct Counsel Groups
        $counselGroups = [];
        if ($cse_type) {
            $counselGroups = DB::table('coursemaster')
                ->select('CounselGroup')
                ->distinct()
                ->where('CourseType', $cse_type)
                ->orderBy('CounselGroup')
                ->get();
        }

        // If form is submitted, fetch college data
        $colleges = [];
        if ($request->has('list_data')) {
            $query = DB::table('collegedetails');

            // College type filter
            if ($cl_type && $cl_type != 'A') {
                $query->where('CollegeType', $cl_type);
            }

            // College group filter
            if ($cl_group && $cl_group != 'A') {
                $query->where('CollegeGroup', $cl_group);
            }

            // Course filter (for medical)
            if ($chk) {
                $query->whereIn('CollegeCode', function($sub) use ($chk) {
                    $sub->select('CollegeCode')
                        ->from('coursedetails')
                        ->whereIn('CourseCode', $chk);
                });
            }

            // Course type filter
            if ($cse_type) {
                $query->whereIn('CollegeCode', function($sub) use ($cse_type) {
                    $sub->select('CollegeCode')
                        ->from('coursedetails')
                        ->where('CourseType', $cse_type);
                });
            }

            $colleges = $query->get();
        }

        return view('cee.collegecontact', compact('cl_type', 'cl_group', 'cse_type', 'chk', 'counselGroups', 'colleges'));
    
    }

   

    public function collegeapproval(Request $request)
    {
        $clg_gp = $request->input('clg_gp');       // College group (B, D, E, etc.)
        $cl_course = $request->input('cl_course'); // Course type (UG/PG/etc.)
        $approvalFilter = $request->input('approval_filter'); // Filter Approved/Not Approved

        $colleges = collect();
        $totalResults = 0;

        if ($request->has('list_data') && $clg_gp && $cl_course) {

            // Dynamic DB connection
            $dynamicDb = $this->getDynamicDB($cl_course);

            // Check if 'approval' column exists
            $columns = $dynamicDb->select("SHOW COLUMNS FROM collegemaster");
            $columnNames = collect($columns)->pluck('Field')->toArray();
            $hasApproval = in_array('approval', $columnNames);

            // Fetch colleges from dynamic DB
            $colleges = $dynamicDb->table('collegemaster')
                ->when($hasApproval, fn($q) => $q->select('CollegeDesc', 'CollegeCode', 'approval'))
                ->when(!$hasApproval, fn($q) => $q->select('CollegeDesc', 'CollegeCode'))
                ->where('CollegeGroup', 'like', $clg_gp)
                ->orderBy($hasApproval ? 'approval' : 'CollegeCode', 'desc')
                ->get();

            // Fetch additional details from central DB
            foreach ($colleges as $college) {
                $details = DB::connection('mysql')
                    ->table('collegedetails')
                    ->where('CollegeCode', $college->CollegeCode)
                    ->first();

                $college->PPhone = $details->PPhone ?? '';
                $college->PMobile = $details->PMobile ?? '';
                $college->Admin_Name = $details->Admin_Name ?? '';
                $college->Admin_Mobile = $details->Admin_Mobile ?? '';
                $college->Mobile = $details->Mobile ?? '';

                // Determine approval status
                if ($hasApproval) {
                    if ($college->approval === 'A') {
                        $college->status = 'Approved';
                        $college->color = '#003300';
                    } elseif ($college->approval === 'E') {
                        $college->status = 'Not Approved';
                        $college->color = '#FF3300';
                    } else {
                        $college->status = 'N/A';
                        $college->color = '#000000';
                    }
                } else {
                    $college->status = 'N/A';
                    $college->color = '#000000';
                }
            }

            // Apply approval filter if selected
            if ($approvalFilter) {
                $colleges = $colleges->filter(fn($c) => $c->status === $approvalFilter);
            }

            $totalResults = $colleges->count();
        }

        return view('cee.collegeapproval', [
            'clg_gp' => $clg_gp,
            'cl_course' => $cl_course,
            'colleges' => $colleges,
            'totalResults' => $totalResults,
            'approvalFilter' => $approvalFilter,
            'request'=>$request
        ]);
    }


    //////////////////fee transfer verification
    public function feetransfer_verification(Request $request)
    {
        $exmname   = $request->input('exmname');
        $yearnm   = $request->input('yearnm');
        $verifynm = $request->input('verifynm');

        // Dropdown data
        $exams = DB::table('fee_transfer')
            ->select('ExamName')
            ->distinct()
            ->get();

        $years = DB::table('fee_transfer')
            ->select('Year')
            ->distinct()
            ->get();

        $statuses = DB::table('fee_transfer')
            ->select('Verified')
            ->where('Verified', '<>', '')
            ->distinct()
            ->get();

        $results = [];

        if ($request->has('list_data')) {
            $results = DB::table('fee_transfer as f')
                ->join('collegedetails as c', 'c.CollegeCode', '=', 'f.College')
                ->select(
                    'f.Year',
                    'f.ExamName',
                    'f.Verified',
                    'f.EmployeeCd',
                    'c.CollegeCode',
                    'c.CollegeDesc'
                )
                ->where('f.Year', $yearnm)
                ->where('f.ExamName', $exmname)
                ->where('f.Verified', $verifynm)
                ->distinct()
                ->get();
        }

        return view('cee.collegefeeverification', compact(
            'exams',
            'years',
            'statuses',
            'results',
            'exmname',
            'yearnm',
            'verifynm'
        ));
    }

    public function feetransfer_status()
    {
        $colleges = DB::table('collegedetails as a')
            ->join('fee_transfer as b', 'a.CollegeCode', '=', 'b.College')
            ->select(
                'a.CollegeCode',
                'a.CollegeGroup',
                'a.CollegeDesc',
                'a.PMobile',
                'a.PEmail',
                'a.Admin_Mobile',
                'a.Admin_Email',
                'b.Verified'
            )
            ->where('b.Year', 2021)
            ->distinct()
            ->orderBy('b.Verified', 'asc')
            ->get();

        return view('cee.feetransfer_status', compact('colleges'));
    }


    public function account_status()
    {
        $colleges = DB::select("
    SELECT DISTINCT 
        a.CollegeCode,
        a.CollegeGroup,
        a.CollegeDesc,
        a.PMobile,
        a.PEmail,
        a.Admin_Mobile,
        a.Admin_Email,
        b.Status
    FROM collegedetails a
    JOIN account_details b ON a.CollegeCode = b.CollegeCode
    WHERE a.CollegeCode IN (SELECT CollegeCode FROM keam)
    ORDER BY b.Status ASC
");


        return view('cee.account_status', compact('colleges'));
    }


    public function collegeseatverification(Request $request)
    {
        // Session token
        $tk = session('tks');

        // Dropdown values
        $collegeGroups = DB::select("
            SELECT DISTINCT CollegeGroup 
            FROM seat_verification
        ");

        $results = [];
        $total = 0;

        if ($request->has('list_data')) {
            $clg_gp = $request->clg_gp;

            $results = DB::select("
                SELECT sv.*, cd.CollegeDesc
                FROM seat_verification sv
                JOIN collegedetails cd ON cd.CollegeCode = sv.CollegeCode
                WHERE sv.CollegeGroup = ?
                ORDER BY sv.Verified
            ", [$clg_gp]);

            $total = count($results);
        }

        return view('cee.collegeseatverification', compact(
            'tk',
            'collegeGroups',
            'results',
            'total'
        ));
    }

   
public function courseconfirm(Request $request)
    {
        // Session value
        $tk = Session::get('tks');

    $clg_gp    = $request->input('clg_gp');
    $cl_course = $request->input('cl_course');
    $cl_mm     = $request->input('cl_mm');

    // ✅ DEFAULT mysql connection
    $results = DB::table('course_verification')
        ->select('CollegeCode', 'Course_details', 'Verified')
        ->groupBy('CollegeCode', 'Course_details', 'Verified')
        ->orderBy('CollegeCode')
        ->get();

    return view('cee.courseconfirm', compact(
        'tk',
        'clg_gp',
        'cl_course',
        'cl_mm',
        'results'
    ));}


    public function coursecollegetc(Request $request)
{
    $cl_group  = $request->cl_group;
    $cl_course = $request->cl_course;
    $chk       = $request->chk ?? [];

    $courses   = [];
    $colleges  = collect();
    $total     = 0;
    $success   = false;

    /* -------------------------------
       COURSE LIST
    -------------------------------- */
    if ($cl_group) {
        $courses = DB::table('degreelevel')
            ->where('CollegeGroup', $cl_group)
            ->orderBy('CourseType')
            ->get();
    }

    /* -------------------------------
       LIST COLLEGES
    -------------------------------- */
    if ($request->has('list_data') && $cl_course) {

        /* ---------- MEDICAL ---------- */
        /* ---------- MEDICAL + KM ---------- */
if ($cl_group === 'M' || $cl_course === 'KM') {

    $colleges = DB::table('coursedetails')
        ->select('CollegeCode')
        ->when(!empty($chk), function ($q) use ($chk) {
            $q->whereIn('CourseCode', $chk);
        })
        ->whereIn('CollegeCode', function ($q) {
            $q->select('CollegeCode')
              ->from('collegedetails')
              ->where('CollegeGroup', 'M');
        })
        ->distinct()
        ->get();


        }

        /* ---------- NON-MEDICAL ---------- */
        else {

            $db = $this->getDynamicDB($cl_course);

            $query = $db->table('collegemaster')
                ->select('CollegeCode', 'CollegeDesc')
                ->whereIn('CollegeCode', function ($q) {
                    $q->select('CollegeCode')
                      ->from('allotmentdetails')
                      ->where('JoinStatus_1', '=', 'Y')     // ✅ FIXED
                      ->whereRaw("MID(Allot_1,5,3) != ''")
                      ->where('Allot_1', '!=', '');          // ✅ FIXED
                })
                ->distinct();

            /*
            |--------------------------------------------------------------------------
            | IMPORTANT BUSINESS RULE
            |--------------------------------------------------------------------------
            | LE application DB does NOT have `approval` column
            | KM and others do
            */
            if ($cl_course !== 'LE') {
                $query->orderByDesc('approval');
            }

            $colleges = $query->get();
        }

        $total = $colleges->count();
    }

    /* -------------------------------
       ENABLE (MEDICAL)
    -------------------------------- */
    if ($request->has('enable_medical') && !empty($request->college_codes)) {

        foreach ($request->college_codes as $code) {

            if ($request->tc) {
                DB::table('employee')
                    ->where('CollegeCode', $code)
                    ->where('TC_Issued', 'Y')
                    ->update(['TC_Enable' => 'Y']);
            }

            if ($request->ad) {
                DB::table('employee')
                    ->where('CollegeCode', $code)
                    ->update(['Admn_Enable' => 'Y']);
            }

            if ($request->ap || $request->ad) {
                DB::table('employee')
                    ->where('CollegeCode', $code)
                    ->whereIn('EmpType', ['A', 'P'])
                    ->update(['AdmnApproved' => 'E']);
            }
        }

        $success = true;
    }

    /* -------------------------------
       ENABLE (NON-MEDICAL)
    -------------------------------- */
    if ($request->has('enable_non_medical') && !empty($request->college_codes)) {

        $db = $this->getDynamicDB($cl_course);

        foreach ($request->college_codes as $code) {

            if ($request->tc1) {
                $db->table('collegemaster')
                    ->where('CollegeCode', $code)
                    ->update(['tc' => 'Y']);
            }

            if ($request->ad1) {
                $db->table('collegemaster')
                    ->where('CollegeCode', $code)
                    ->update([
                        'admission_admin'     => 'Y',
                        'admission_principal' => 'Y',
                        'admission_normal'    => 'Y',
                    ]);
            }

            if ($request->ap1 || $request->ad1) {
                $db->table('collegemaster')
                    ->where('CollegeCode', $code)
                    ->update(['approval' => 'E']);
            }
        }

        $success = true;
    }

    return view('cee.coursecollegetc', compact(
        'cl_group',
        'cl_course',
        'courses',
        'colleges',
        'total',
        'success'
    ));
}

public function vacancyVerification(Request $request)
{
    $cl_mm     = $request->input('cl_mm');
    $cl_status = $request->input('cl_status'); // new filter

    $courses = DB::connection('mysql')
        ->table('vacancy_verification')
        ->select('coursecode', 'coursedesc')
        ->distinct()
        ->orderBy('coursecode')
        ->get();

    $collegesData = [];

    if ($request->has('list_data')) {

        $query = DB::connection('mysql')->table('vacancy_verification');

        // Filter by course
        if ($cl_mm && $cl_mm != "All") {
            $query->where('coursecode', $cl_mm);
        }

        // Filter by simplified verification
        if ($cl_status && $cl_status != "") {
            if ($cl_status == 'verified') {
                $query->where('verified', 'V'); // only V is verified
            } else {
                $query->where('verified', '<>', 'V'); // anything else = not verified
            }
        }

        $collegesData = $query->orderBy('coursecode')->get();

        // Join with collegedetails and set status text/color
        $collegesData = $collegesData->map(function($row) {
            $college = DB::connection('mysql')
                ->table('collegedetails')
                ->where('CollegeCode', $row->collegecode)
                ->first();

            $row->CollegeType = $college->CollegeType ?? '';
            $row->CollegeCode1 = $college->CollegeCode ?? '';
            $row->CollegeDesc = $college->CollegeDesc ?? '';
            $row->CollegeGroup = $college->CollegeGroup ?? '';

            // Simplified status
            if($row->verified == 'V'){
                $row->status_text = 'Verified';
                $row->status_color = '#003300';
            } else {
                $row->status_text = 'Not Verified';
                $row->status_color = '#FF3300';
            }

            return $row;
        });
    }

    return view('cee.collegevacancyverification', compact(
        'cl_mm', 'cl_status', 'courses', 'collegesData'
    ));
}





/////coursetc

////////////////dynamic db
private function getDynamicDB(string $cl_course)
{
    $row = DB::connection('mysql')
        ->table('defvalues')
        ->where('CType', $cl_course)
        ->where('ExamStatus', 'Y')
        ->first();

    if (!$row) {
        abort(404, "Application DB not found for course {$cl_course}");
    }

    DB::purge('dynamic');

    config([
        'database.connections.dynamic' => [
            'driver'    => 'mysql',
            'host'      => $row->HostName,
            'port'      => 3306,
            'database'  => $row->ApplicationDB,
            'username'  => 'dba@cee.k',
            'password'  => 'e_treme#ICEworld',
            'charset'   => 'utf8',
            'collation' => 'utf8_general_ci',
            'strict'    => false,
            'engine'    => null,
        ],
    ]);

    DB::reconnect('dynamic');

    return DB::connection('dynamic');
}


//     /* ==========================================================
//        MAIN TC / ADMISSION / APPROVAL PAGE
//     ========================================================== */
//     public function tc_admn(Request $request)
//     {
//         $college = null;
//         $status = null;
//         $tc_enabled = $ad_enabled = $ap_enabled = false;
    
//         $clg_gp   = $request->clg_gp;
//         $cl_course = $request->cl_course;
//         $clg_code = $request->clg_code;
    
//         /* ---------- SHOW COLLEGE DETAILS ---------- */
//         if ($clg_gp && $cl_course && $clg_code) {
    
//             // College details ALWAYS from dynamic DB
//             $db = $this->getDynamicDB($cl_course);
    
//             $college = $db->table('collegemaster')
//                 ->where('CollegeCode', $clg_code)
//                 ->first();
    
//             if ($college) {
//                 $status = $db->table('collegemaster')
//                     ->select('tc','admission_admin','approval')
//                     ->where('CollegeCode', $clg_code)
//                     ->first();
    
//                 $tc_enabled = $status->tc === 'Y';
//                 $ad_enabled = $status->admission_admin === 'Y';
//                 $ap_enabled = $status->approval === 'E';
//             }
//         }
    
//         /* ---------- ENABLE TC / ADMISSION / APPROVAL ---------- */
//         if ($request->has('enable_data') && $cl_course && $clg_code) {
    
//             $db = $this->getDynamicDB($cl_course);
    
//             if ($request->tc === 'TC') {
//                 $db->table('collegemaster')
//                     ->where('CollegeCode', $clg_code)
//                     ->update(['tc' => 'Y']);
//             }
    
//             if ($request->ad === 'AD') {
//                 $db->table('collegemaster')
//                     ->where('CollegeCode', $clg_code)
//                     ->update([
//                         'admission_admin'      => 'Y',
//                         'admission_principal'  => 'Y',
//                         'admission_normal'     => 'Y',
//                     ]);
//             }
    
//             if ($request->ap === 'AP' || $request->ad === 'AD') {
//                 $db->table('collegemaster')
//                     ->where('CollegeCode', $clg_code)
//                     ->update(['approval' => 'E']);
//             }
    
//             return redirect()
//                 ->route('cee.tc_admn')
//                 ->with('success', 'Details Updated Successfully');
//         }
    
//         return view('cee.tc_admn', compact(
//             'college',
//             'tc_enabled',
//             'ad_enabled',
//             'ap_enabled',
//             'clg_gp',
//             'cl_course',
//             'clg_code'
//         ));
//     }
    

//     /* ==========================================================
//        AJAX: COURSES DROPDOWN (CENTRAL DB)
//     ========================================================== */
//     public function getCourses(Request $request)
//     {
//         return DB::connection('mysql')
//             ->table('degreelevel')
//             ->where('CollegeGroup', $request->clg_gp)
//             ->get();
//     }

//     /* ==========================================================
//        AJAX: COLLEGES DROPDOWN (CENTRAL DB ONLY)
//     ========================================================== */
//     public function getColleges(Request $request)
//     {
//         $clg_gp    = $request->clg_gp;
//         $cl_course = $request->cl_course;

//         if (!$clg_gp || !$cl_course) {
//             return response()->json([]);
//         }

//         // Engineering: filter via KEAM
//         if ($clg_gp === 'E') {
//             return DB::connection('mysql')
//                 ->table('collegedetails')
//                 ->whereIn('CollegeCode', function ($q) use ($cl_course) {
//                     $q->select('CollegeCode')
//                       ->from('keam')
//                       ->where('CourseCode', 'like', "%{$cl_course}%");
//                 })
//                 ->get();
//         }

//         // ALL OTHER COURSES: plain central DB
//         return DB::connection('mysql')
//             ->table('collegedetails')
//             ->where('CollegeGroup', $clg_gp)
//             ->get();
//     }




public function tc_admn(Request $request)
    {
        $clg_gp   = $request->clg_gp;
        $exam     = $request->cl_course; // CourseType (for dynamic DB)
        $clg_code = $request->clg_code;
        $cl_course = $request->cl_course;

        $courses  = collect();
        $colleges = collect();
        $college  = null;

        $tc_enabled = $ad_enabled = $ap_enabled = false;

        /* LOAD COURSES + COLLEGES (MAIN DB) */
        if ($clg_gp) {
            $courses = DB::table('degreelevel')
                ->where('CollegeGroup', $clg_gp)
                ->get();

            $colleges = DB::table('collegedetails')
                ->orderBy('CollegeCode')
                ->get();
        }

        /* COLLEGE DETAILS (MAIN DB) */
        if ($clg_code) {
            $college = DB::table('collegedetails')
                ->where('CollegeCode', $clg_code)
                ->first();
        }

        /* STATUS FLAGS (DYNAMIC DB for collegemaster) */
        if ($exam && $clg_code) {
            $dynamicDB = $this->getDynamicDB($exam); // sets up dynamic connection

            $status = $dynamicDB->table('collegemaster')
                ->select('tc','admission_admin','approval')
                ->where('CollegeCode', $clg_code)
                ->first();

            if ($status) {
                $tc_enabled = $status->tc === 'Y';
                $ad_enabled = $status->admission_admin === 'Y';
                $ap_enabled = $status->approval === 'E';
            }
        }

        /* ENABLE ACTION */
        if ($request->has('enable_data') && $exam && $clg_code) {

            // 1️⃣ EMPLOYEE updates → MAIN DB
            if ($request->tc === 'TC') {
                DB::table('employee')
                    ->where('CollegeCode', $clg_code)
                    ->where('TC_Issued', 'Y')
                    ->update(['TC_Enable' => 'Y']);
            }

            if ($request->ad === 'AD') {
                DB::table('employee')
                    ->where('CollegeCode', $clg_code)
                    ->update(['Admn_Enable' => 'Y']);
            }

            if ($request->ap === 'AP' || $request->ad === 'AD') {
                DB::table('employee')
                    ->where('CollegeCode', $clg_code)
                    ->whereIn('EmpType', ['A','P'])
                    ->update(['AdmnApproved' => 'E']);
            }

            // 2️⃣ COLLEGEMASTER updates → DYNAMIC DB
            if ($request->tc === 'TC') {
                $dynamicDB->table('collegemaster')
                    ->where('CollegeCode', $clg_code)
                    ->update(['tc' => 'Y']);
            }

            if ($request->ad === 'AD') {
                $dynamicDB->table('collegemaster')
                    ->where('CollegeCode', $clg_code)
                    ->update(['admission_admin' => 'Y']);
            }

            if ($request->ap === 'AP') {
                $dynamicDB->table('collegemaster')
                    ->where('CollegeCode', $clg_code)
                    ->update(['approval' => 'E']);
            }

            return redirect()
                ->route('cee.tc_admn', $request->only('clg_gp','cl_course','clg_code'))
                ->with('success', 'Details Updated Successfully');
        }

        return view('cee.tc_admn', compact(
            'clg_gp','exam','courses','colleges','college','clg_code',
            'tc_enabled','ad_enabled','ap_enabled','cl_course'
        ));
    }

    // AJAX: Get courses for selected College Group
    public function getCourses(Request $request)
    {
        return DB::table('degreelevel')
            ->where('CollegeGroup', $request->clg_gp)
            ->select('CourseType','ExamName')
            ->get();
    }

    // AJAX: Get colleges (all for now)
    public function getCollegesByCourse(Request $request)
{
    $exam   = $request->exam;   // CollegeGroup
    $course = $request->course; // CourseType

    if (!$exam || !$course) {
        return response()->json([]);
    }

    // 🔹 Dynamic DB based on course
    $dynamicDB = $this->getDynamicDB($course);

    // 🔹 Get college codes from collegemaster
    $collegeCodes = $dynamicDB->table('collegemaster')
        ->where('CollegeGroup', $exam)
        ->pluck('CollegeCode');

    if ($collegeCodes->isEmpty()) {
        return response()->json([]);
    }

    // 🔹 Fetch college details from MAIN DB
    $colleges = DB::table('collegedetails')
        ->whereIn('CollegeCode', $collegeCodes)
        ->orderBy('CollegeCode')
        ->get(['CollegeCode', 'CollegeDesc']);

    return response()->json($colleges);
}


////////////////////////////////////////////////////password


public function showResetForm(Request $request)
    {
        $colleges = DB::connection('mysql')
            ->table('employee')
            ->select('CollegeCode')
            ->distinct()
            ->whereNotNull('CollegeCode')
            ->orderBy('CollegeCode')
            ->get();

        return view('cee.college_admin_reset', [
            'colleges' => $colleges,
            'msg' => $request->session()->get('msg'),
            'success' => $request->session()->get('success'),
            'newPassword' => $request->session()->get('newPassword'),
        ]);
    }

    // Fetch College/Admin Details
    public function getDetails(Request $request)
    {
        $request->validate([
            'CollegeCode' => 'required',
        ]);

        $CollegeCode = $request->CollegeCode;

        $ColgDetails = DB::connection('mysql')
            ->table('collegedetails')
            ->where('CollegeCode', $CollegeCode)
            ->first();

        $EmpDetails = DB::connection('mysql')
            ->table('employee')
            ->where('CollegeCode', $CollegeCode)
            ->where('EmpType', 'A')
            ->first();

        $colleges = DB::connection('mysql')
            ->table('employee')
            ->select('CollegeCode')
            ->distinct()
            ->whereNotNull('CollegeCode')
            ->orderBy('CollegeCode')
            ->get();

        return view('cee.college_admin_reset', [
            'ColgDetails' => $ColgDetails,
            'EmpDetails' => $EmpDetails,
            'colleges' => $colleges,
        ]);
    }

    // Reset Password
    public function resetPassword(Request $request)
    {
        $request->validate([
            'CollegeCode' => 'required',
        ]);
    
        $CollegeCode = $request->CollegeCode;
    
        $newPassword = $this->generatePassword(8);
        $hashedPassword = bcrypt($newPassword);
    
        DB::connection('mysql')
            ->table('employee')
            ->where('CollegeCode', $CollegeCode)
            ->where('EmpType', 'A')
            ->update([
                'Password' => $hashedPassword,
                'EmpLogged' => 0,
            ]);
    
        // Load College & Admin details
        $ColgDetails = DB::connection('mysql')->table('collegedetails')
            ->where('CollegeCode', $CollegeCode)
            ->first();
    
        $EmpDetails = DB::connection('mysql')->table('employee')
            ->where('CollegeCode', $CollegeCode)
            ->where('EmpType', 'A')
            ->first();
    
        return redirect()->route('college_admin_reset')
            ->with([
                'success' => 'Password reset successfully.',
                'newPassword' => $newPassword,
                'ColgDetails' => $ColgDetails,
                'EmpDetails' => $EmpDetails,
            ]);
    }
    
    // Send Mail
    public function sendMail(Request $request)
    {
        $request->validate([
            'colg_email' => 'required|email',
            'admin_empname' => 'required',
            'admin_newPass' => 'required',
        ]);

        $email = $request->colg_email;
        $empName = $request->admin_empname;
        $newPass = $request->admin_newPass;

        Mail::send([], [], function ($message) use ($email, $empName, $newPass) {
            $message->to($email)
                ->subject('Password for College Portal of CEE')
                ->from(config('mail.from.address'), config('mail.from.name'))
                ->setBody("
                    <p>Hello $empName,</p>
                    <p>Your new college portal password is: <b>$newPass</b></p>
                    <p>Regards,<br>CEE Admin</p>
                ", 'text/html');
        });

        return redirect()->route('college_admin_reset')
            ->with('success', 'Password sent via email successfully.');
    }

    // Random password generator
    private function generatePassword($length = 8)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890@#$%';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[rand(0, strlen($chars) - 1)];
        }
        return $password;
    }
///////////////////////////////college document viewing
public function document(Request $request)
{
    $colleges = DB::table('basic_documents')
        ->select('CollegeCode')
        ->distinct()
        ->orderBy('CollegeCode')
        ->get();

    $years = range(2015, date('Y'));
    $showDocument = $request->has('submit');

    return view('cee.document', compact('colleges', 'years', 'showDocument'));
}

// Stream BLOB document
public function streamDocument(Request $request)
{
    $request->validate([
        'college' => 'required',
        'year'    => 'required',
        'document'=> 'required'
    ]);

    $allowedDocs = [
        'Minority','Minority1','Other','Other1',
        'Approval','Approval1','University','University1',
        'Government','Government1','Gender'
    ];

    if (!in_array($request->document, $allowedDocs)) {
        abort(403, 'Invalid document type');
    }

    $row = DB::table('basic_documents')
        ->select($request->document . ' as doc')
        ->where('CollegeCode', $request->college)
        ->where('Year', $request->year)
        ->first();

    if (!$row || !$row->doc) {
        abort(404, 'Document not found in database');
    }

    $blob = $row->doc;

    // If your BLOB is Base64-encoded, decode it
    $blob = base64_decode($blob);

    // Detect file type
    $header = substr($blob, 0, 4);
    if ($header === "%PDF") {
        $type = 'application/pdf';
        $disposition = 'inline';
    } elseif (substr($blob, 0, 2) === "\xFF\xD8") {
        $type = 'image/jpeg';
        $disposition = 'inline';
    } elseif (substr($blob, 0, 8) === "\x89PNG\x0D\x0A\x1A\x0A") {
        $type = 'image/png';
        $disposition = 'inline';
    } else {
        $type = 'application/octet-stream';
        $disposition = 'attachment';
    }

    return response($blob)
        ->header('Content-Type', $type)
        ->header('Content-Disposition', $disposition.'; filename="'.$request->document.'.pdf"');
}
    ////////////////////////////////////////////


    public function coursevisefeeverification(Request $request)
    {
        $tk = Session::get('tks');
    
        $clg_gp    = $request->input('clg_gp');
        $cl_course = $request->input('cl_course');
        $cl_mm     = $request->input('cl_mm');
    
        $query = DB::table('coursedetails as cd')
            ->join('collegedetails as cl', 'cd.CollegeCode', '=', 'cl.CollegeCode')
            ->join('coursemaster as cm', 'cd.CourseCode', '=', 'cm.CourseCode')
            ->select(
                'cd.*',
                'cl.CollegeDesc',
                'cm.CourseDesc'
            );
    
        // Optional filters
        if (!empty($clg_gp)) {
            $query->where('cd.CollegeCode', $clg_gp);
        }
    
        if (!empty($cl_course)) {
            $query->where('cd.CourseCode', $cl_course);
        }
    
        if (!empty($cl_mm)) {
            $query->where('cd.CourseType', $cl_mm);
        }
    
        $results = $query->orderBy('cd.UpdateTime', 'desc')->get();
    
        return view('cee.coursevisefeeverification', compact('results'));
    }

}


