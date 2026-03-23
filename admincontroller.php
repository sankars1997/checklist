<?php

namespace App\Http\Controllers;
use App\Models\Employee;
use App\Models\Msg;
use Illuminate\Support\Facades\Hash;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Session;


class admincontroller extends Controller
{

    public function home(){
        
        $messages = DB::table('msg')
        ->where('MType', 'H')
        ->get();

    $finalMessages = [];

    foreach ($messages as $msg) {
        // Replace $CollegeCode with your actual logic
        $collegeCode = session('CollegeCode'); // or however you're storing it
        $query = str_replace("'", "", $msg->Qry) . "'$collegeCode'";

        $countResult = DB::select($query);

        if (!empty($countResult) && $countResult[0]->cnt > 0) {
            $finalMessages[] = [
                'msg' => $msg->Msg,
                'new' => $msg->New === 'Y',
            ];
        }
    }
    $modalRequired = !app('db')->table('collegedetails')
    ->where('CollegeCode', $collegeCode)
    ->where('Active', 'Y')
    ->exists();
    
        return view ('home',compact('finalMessages','modalRequired'));
    }


    // oaams
    public function selectexam()
    {
        // Get session variables
        $CollegeCode = session('CollegeCode');
        $masked = session('masked'); // assuming this is a string

        if (!$CollegeCode) {
            return redirect()->route('login')->with('error', 'Please login first.');
        }

        // Fetch LCourse for this college
        $collegeDetails = DB::table('collegedetails')
            ->where('CollegeCode', $CollegeCode)
            ->first();

        if (!$collegeDetails) {
            return back()->with('error', 'College details not found.');
        }

        // Split and filter courses
        $LCourse = str_split($collegeDetails->LCourse, 2);
        $maskedArr = str_split($masked ?? '', 2);

        // Remove masked courses from LCourse
        $filteredCourses = array_diff($LCourse, $maskedArr);

        // Query courses from degreelevel
        $courses = DB::table('degreelevel')
            ->whereIn('CourseType', $filteredCourses)
            ->where('Active', 'Y')
            ->get();

        return view('exam.select_exam', compact('courses'));
    }
// oams
    public function submit(Request $request)
    {
        $request->validate([
            'exam_name' => 'required|string',
        ], [
            'exam_name.required' => 'Please select a course',
        ]);

        $CollegeCode = session('CollegeCode');
        $EmpType = session('EmpType');

        if (!$CollegeCode || !$EmpType) {
            return redirect()->route('login')->with('error', 'Please login first.');
        }

        // Fetch LCourse
        $collegeDetails = DB::table('collegedetails')
            ->where('CollegeCode', $CollegeCode)
            ->first();

        if (!$collegeDetails) {
            return back()->with('error', 'College details not found.');
        }

        $LCourse = str_split($collegeDetails->LCourse, 2);

        // Store selected course & LCourse in session
        session([
            'LLBCourse' => $request->input('exam_name'),
            'LCourse' => $LCourse,
        ]);

        // Redirect logic based on EmpType and selected course
        if (in_array($EmpType, ['P', 'A'])) {
            if (in_array($request->input('exam_name'), ['BL','MP','MM','MD','PM','L5','L3','LM','PN','PA','MH'])) {
                return redirect()->route('adminhome.test');
            } else {
                return redirect()->route('adminhome');
            }
        } else {
            return redirect()->route('normal.user');
        }
    }


    public function clg_details()
    {
        $collegeCode = session('CollegeCode');

        if (!$collegeCode) {
            abort(403, 'College code not found in session.');
        }

        $college = DB::table('collegedetails')->where('CollegeCode', $collegeCode)->first();

        if (!$college) {
            abort(404, 'College not found');
        }

        $universities = DB::table('university')
            ->where('Status', 'Y')
            ->where('Type', 'U')
            ->orderBy('UName')
            ->get();

        $authorities = DB::table('university')
            ->where('Status', 'Y')
            ->where('Type', 'A')
            ->orderBy('UName')
            ->get();

        $CollegeType = match ($college->CollegeType) {
            'G' => 'Government',
            'N' => 'Govt. Self Financing',
            'S' => 'Pvt. Self-Financing',
            default => '',
        };

        if (in_array($collegeCode, ['SBC', 'TKM', 'MAC', 'NCE'])) {
            $CollegeType = 'Autonomus';
        }

        $CollegeGroup = match ($college->CollegeGroup) {
            'E' => 'Engineering',
            'M' => 'Medical',
            'R' => 'Architecture',
            'B' => 'B.Pharm',
            default => '',
        };

        $CType = $CollegeGroup . ' - ' . $CollegeType;

        return view('CAP.clg_details', compact(
            'college', 'universities', 'authorities', 'CType', 'collegeCode'
        ));
    }

   
    public function clg_detailsupdate(Request $request)
{
    $collegeCode = session('CollegeCode');

    if (!$collegeCode) {
        abort(403, 'College code not found in session.');
    }

    // Basic Laravel validation
    $request->validate([
        'est' => 'required|digits:4|integer',
        'Authority' => 'required',
    ]);

    // Additional custom validation: year must not be in the future
    $est = (int) $request->input('est');
    $currentYear = now()->year;

    if (empty($est)) {
        return redirect()->back()->with('alert', 'Established Year cannot be null or empty.');
    }

    if ($est > $currentYear) {
        // Set session alert message and redirect back
        return redirect()->back()->with('alert', 'Established Year cannot be in the future.');
    }

    // If everything is fine, update DB
    DB::table('collegedetails')
        ->where('CollegeCode', $collegeCode)
        ->update([
            'Estd' => $est,
            'University' => $request->input('University'),
            'Authority' => $request->input('Authority'),
            'Authority_arch' => $request->input('Authority_arch'),
            'Minority' => $request->input('Minority'),
            'EmployeeCd' => Auth::user()->EmployeeCd ?? 'system',
            'Updatetime' => now(),
            'IpAddress' => $request->ip(),
        ]);

    //     $menuId = DB::table('menus')
    //     ->where('route', 'clg_details') // the route of this page
    //     ->value('id');

    // DB::table('menu_status')->updateOrInsert(
    //     ['CollegeCode' => $collegeCode, 'menu_id' => $menuId],
    //     ['Status' => 'Y', 'UpdatedAt' => now()]
    // );

    return redirect()->route('contact.edit')->with('success', 'College details updated.');
}

    

public function contact_edit()
{
    $CollegeCode = session('CollegeCode');

    $contact = DB::table('collegedetails')->where('CollegeCode', $CollegeCode)->first();
    $districts = DB::table('District')->get();
    $taluks = DB::table('Taluk')->get();
    $villages = DB::table('Village')->get();

    return view('CAP.contact', compact('contact', 'districts', 'taluks', 'villages'));
}

public function contact_update(Request $request)
{
    $request->validate([
        'street' => 'required|string|max:100',
        'poname' => 'required|string|max:100',
        'district' => 'required|exists:District,Dist_Id',
        'taluk' => 'required|exists:Taluk,Taluk_Id',
        'village' => 'required|exists:Village,Village_Id',
        'pincode' => 'required|digits:6',
        'phoneno1' => 'required|string|max:13',
        'phoneno2' => 'required|string|max:13',
        'fax' => 'required|string|max:13',
        'email' => 'required|email',
        'website' => 'required',
        'mobile' => 'required|digits:10',
        'pname' => 'required|string|max:100',
        'pphone' => 'required|string|max:13',
        'pmobile' => 'required|digits:10',
        'pemail' => 'required|email',
        'adminname' => 'required|string|max:100',
        'adesign' => 'required|string|max:100',
        'amobile' => 'required|digits:10',
        'aemail' => 'required|email',
    ]);

    $CollegeCode = session('CollegeCode');

    $district = DB::table('District')->where('Dist_Id', $request->district)->value('District');
    $taluk = DB::table('Taluk')->where('Taluk_Id', $request->taluk)->value('Taluk');
    $village = DB::table('Village')->where('Village_Id', $request->village)->value('Village');

    $data = [
        'PName' => strtoupper($request->pname),
        'PPhone' => $request->pphone,
        'PMobile' => $request->pmobile,
        'PEmail' => $request->pemail,
        'StreetName' => strtoupper($request->street),
        'POName' => strtoupper($request->poname),
        'District' => $district,
        'Taluk' => $taluk,
        'Village' => $village,
        'Pincode' => $request->pincode,
        'Phone1' => $request->phoneno1,
        'Phone2' => $request->phoneno2,
        'Fax' => $request->fax,
        'Email' => $request->email,
        'Web' => $request->website,
        'Mobile' => $request->mobile,
        'Admin_Name' => strtoupper($request->adminname),
        'Admin_Designation' => $request->adesign,
        'Admin_Mobile' => $request->amobile,
        'Admin_Email' => $request->aemail,
        'EmployeeCd' => 'system',
        'Updatetime' => now(),
        'IpAddress' => $request->ip(),
        'ContactUpdtTime' => now(),
        'Active' => 'Y'
    ];

    DB::table('collegedetails')->where('CollegeCode', $CollegeCode)->update($data);

    return redirect()->route('course', ['tk' => $request->query('tk')])
    ->with('success', 'Contact details updated.');

    }

    // course part
    public function course_details(Request $request)
    {
        $CollegeCode = session('CollegeCode');
    $CollegeGroup = session('CollegeGroup');

    // Fetch course row
    
    $courseRow = DB::table('coursedetails')
        ->where('CollegeCode', $CollegeCode)
        ->first();

    $Verified = $courseRow->Verified ?? null;
    $tk = $request->query('tk'); // or generate it if needed

    $msg = null;
    if ($Verified == 'Y') {
        $msg = "The above course details have been verified by the college authority and declared correct.";
    } elseif ($Verified == 'N') {
        $msg = "Attention! You have declared that the seat details are NOT correct.";
    }


    
    // Normalize CollegeGroup
    if ($CollegeGroup === 'P' || $CollegeGroup === 'D') {
        $CollegeGroup = 'M';
    }

    // Get course details for this college
    $courses = DB::table('coursedetails')
        ->where('CollegeCode', $CollegeCode)
        ->get();

    // Get course descriptions by CourseCode for the given CollegeGroup
    $courseNames = DB::table('coursemaster')
        ->where('CounselGroup', $CollegeGroup)
        ->pluck('CourseDesc', 'CourseCode'); //


    return view('CAP.course', compact('Verified', 'CollegeCode', 'CollegeGroup', 'tk', 'msg','courses','courseNames'));
    }

    public function courseverify(Request $request, $status)
    {
        $collegeCode = $request->input('CollegeCode');
        $employeeCd = session('EmployeeCd');

        if ($request->has('sub1')) {
            DB::table('coursedetails')
                ->where('CollegeCode', $collegeCode)
                ->update([
                    'Verified' => 'Y',
                    'VerifiedTime' => now(),
                    'VEmployeeCd' => $employeeCd,
                ]);
        }

        if ($request->has('sub2')) {
            DB::table('coursedetails')
                ->where('CollegeCode', $collegeCode)
                ->update([
                    'Verified' => 'N',
                    'VerifiedTime' => now(),
                    'VEmployeeCd' => $employeeCd,
                ]);
        }

        return redirect()->route('course', ['tk' => $request->query('tk')]);

        
    }
    public function coursecreate(){
        
    }
   
    public function fill_course()
    {
        // Get values from session
        $CollegeCode = session('college_code');
        $CollegeGroup = session('college_group');

        // Normalize CollegeGroup
        if ($CollegeGroup === 'P' || $CollegeGroup === 'D') {
            $CollegeGroup = 'M';
        }

        // Get course details for this college
        $courses = DB::table('coursedetails')
            ->where('CollegeCode', $CollegeCode)
            ->get();

        // Get course descriptions by CourseCode for the given CollegeGroup
        $courseNames = DB::table('coursemaster')
            ->where('CounselGroup', $CollegeGroup)
            ->pluck('CourseDesc', 'CourseCode'); // returns ['C001' => 'BSc Physics', ...]

        // Return view
        return view('coursetable', compact('courses', 'courseNames'));
    }

///adding new course
public function newcourse(Request $request)
{
    $CollegeCode = session('CollegeCode');
    $CollegeGroup = session('CollegeGroup');

    // Normalize CollegeGroup
    if ($CollegeGroup == 'P' || $CollegeGroup == 'D') {
        $CollegeGroup = 'M';
    }

    // Handle AJAX request for course list
    if ($request->ajax() && $request->has('courseType')) {
        $courseType = $request->input('courseType');
    
        $courses = DB::table('coursemaster')
            ->where('CourseType', $courseType)
            ->where('CounselGroup', $CollegeGroup)
            ->select('CourseCode', 'CourseDesc')
            ->get();
    
        return response()->json($courses);
    }
    

    // Normal page load
    $courseDetails = DB::table('coursedetails')
        ->where('CollegeCode', $CollegeCode)
        ->get();

    $Verified = $courseDetails->first() ? $courseDetails->first()->Verified : '';

    $courseTypes = DB::table('coursemaster')
        ->distinct()
        ->where('CounselGroup', $CollegeGroup)
        ->pluck('CourseType');

    return view('newcourse', compact('courseDetails', 'Verified', 'courseTypes', 'CollegeGroup'));
}

public function newcoursesave(Request $request){
    $collegeCode = session('CollegeCode');
    $VEmployeeCd = Auth::user()->EmployeeCd ?? null;

    if (!$collegeCode) {
        return redirect()->back()->withErrors(['error' => 'College code not found in session.']);
    }

    // Validate input
    $validated = $request->validate([
        'course_type1'      => 'required|string',
        'CourseCode'        => 'required|string',
        'seat'              => 'required|integer',
        'year_commencement' => 'required|digits:4|integer|between:1900,' . date('Y'),
        'accredited'        => 'required|in:Y,N',
        'accredited_year'    => 'nullable|digits:4|integer|between:1900,' . date('Y'),
        'affiliation'       => 'required|in:Y,N',
        'affiliation_file'  => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
    ]);

    // Check if course exists
    $existing = DB::table('coursedetails')
        ->where('CollegeCode', $collegeCode)
        ->where('CourseCode', $validated['CourseCode'])
        ->first();

    // Prepare course data
    $data = [
        'CollegeCode'      => $collegeCode,
        'CourseType'       => $validated['course_type1'],
        'CourseCode'       => $validated['CourseCode'],
        'Seats'            => $validated['seat'],
        'CommenceYear'     => $validated['year_commencement'],
        'IsAccredited'     => $validated['accredited'],
        'AccrValidYear'    => $validated['accredited'] === 'Y' ? $validated['accredited_year'] : null,
        'IsAffiliated'     => $validated['affiliation'],
        'Verified'         => '',
        'Govt_Tuition_LIG' => $existing->Govt_Tuition_LIG ?? 0,
        'Govt_Tuition'     => $existing->Govt_Tuition ?? 0,
        'Govt_Special'     => $existing->Govt_Special ?? 0,
        'Govt_Scholarship' => $existing->Govt_Scholarship ?? 0,
        'Govt_Deposit'     => $existing->Govt_Deposit ?? 0,
        'Actual_Fee'       => $existing->Actual_Fee ?? 0,
        'Mang_Tuition'     => $existing->Mang_Tuition ?? 0,
        'Mang_Special'     => $existing->Mang_Special ?? 0,
        'Mang_Deposit'     => $existing->Mang_Deposit ?? 0,
        'NRI_Tuition'      => $existing->NRI_Tuition ?? 0,
        'NRI_Special'      => $existing->NRI_Special ?? 0,
        'NRI_Deposit'      => $existing->NRI_Deposit ?? 0,
        'FillDetails'      => $existing->FillDetails ?? 0,
        'Finalize'         => $existing->Finalize ?? 0,
        'Print'            => $existing->Print ?? 0,
        'Status'           => "N",
        'UpdateTime'       => now(),
        'VEmployeeCd'      => $VEmployeeCd,
        'VerifiedTime'     => now(),
    ];

    // Insert or update course
    if ($existing) {
        DB::table('coursedetails')
            ->where('CollegeCode', $collegeCode)
            ->where('CourseCode', $validated['CourseCode'])
            ->update($data);
    } else {
        DB::table('coursedetails')->insert($data);
    }

// ----------------------------
// MINORITY TABLE FIXED SECTION
// ----------------------------

// Load existing minority data
$existingMinority = DB::table('minority')->where('CollegeCode', $collegeCode)->first();

// Default Minority Flag
$minorityFlag = $existingMinority->MinorityStatus ?? 'N';

// Base data
$minorityData = [
    'CollegeCode'    => $collegeCode,
    'MinorityStatus' => $minorityFlag,
    'UpdateTime'     => now(),
];

// File columns
$fileCols = ['Minority','Gender','Approval','Other','University','Government','Upload'];

// Keep existing stored files or set empty
if ($existingMinority) {
    foreach ($fileCols as $col) {
        $minorityData[$col] = $existingMinority->{$col} ?? "";
    }
} else {
    foreach ($fileCols as $col) {
        $minorityData[$col] = "";
    }
}


// Handle uploaded files (store BINARY in DB instead of file path)
$uploadMap = [
    'affiliation_file' => 'Government',   // <-- ADD THIS
    'minoritydocs'     => 'Minority',
    'genderdocs'       => 'Gender',
    'approvaldocs'     => 'Approval',
    'otherdocs'        => 'Other',
    'unidocs'          => 'University',
    'govtdocs'         => 'Government',
];



foreach ($uploadMap as $input => $col) {
    if ($request->hasFile($input)) {

        $file = $request->file($input);

        if ($file->isValid()) {
            // Store actual PDF/image BINARY (LONGBLOB)
            $binary = file_get_contents($file->getRealPath());
            $minorityData[$col] = $binary;
        }
    }
}

// Insert or update minority table
DB::table('minority')->updateOrInsert(
    ['CollegeCode' => $collegeCode],
    $minorityData
);

return redirect()->back()->with('success', 'Course Added successfully!');
}




    public function show_account_details(Request $request)
    {
        $collegeCode = session('CollegeCode'); // Or fetch from auth/session
        $edit = $request->query('edit') == 1;

        $account = DB::table('account_details')
            ->where('CollegeCode', $collegeCode)
            ->first();

        $banks = DB::table('bankmaster')->get();

        return view('CAP.accountdetails', compact('account', 'edit', 'banks'));
    }

    public function edit_account_details(Request $request)
    {
        $collegeCode = session('CollegeCode'); // Or fetch from auth/session
        $edit = $request->query('edit') == 1;

        $account = DB::table('account_details')
            ->where('CollegeCode', $collegeCode)
            ->first();

        $banks = DB::table('bankmaster')->get();

        return view('CAP.accountdetailsedit', compact('account', 'edit', 'banks'));
    }


    public function submit_account_details(Request $request)
    {
        $collegeCode = session('CollegeCode');

        $request->validate([
            'cmbBank'        => 'required',
            'txtAccountNo'   => 'required|same:txtCAccountNo',
            'txtCAccountNo'  => 'required',
            'txtIFSC'        => 'required|same:txtCIFSC',
            'txtCIFSC'       => 'required',
            'txtBranch'      => 'required|string',
            'holdername'     => 'required|string',
            'holderdesig'    => 'required|string',
        ]);

        $exists = DB::table('account_details')
            ->where('CollegeCode', $collegeCode)
            ->exists();

        $data = [
            'BankName'   => $request->input('cmbBank'),
            'AccountNo'  => $request->input('txtAccountNo'),
            'IFSC_Code'  => $request->input('txtIFSC'),
            'BranchName' => $request->input('txtBranch'),
            'HolderName' => $request->input('holdername'),
            'HolderDesig'=> $request->input('holderdesig'),
        ];

        if ($exists) {
            DB::table('account_details')
                ->where('CollegeCode', $collegeCode)
                ->update($data);
        } else {
            $data['CollegeCode'] = $collegeCode;
            DB::table('account_details')->insert($data);
        }

        return redirect()->route('accountdetails')->with('success', 'Account details saved.');

    }

    public function confirm_account_details()
    {
        $collegeCode = session('CollegeCode');

        DB::table('account_details')
            ->where('CollegeCode', $collegeCode)
            ->update(['Status' => 'Y']);

            return redirect()->route('accountdetails')->with('success', 'Account details confirmed.');

    }
   /////basic details 
   
///////////////////////basic details
// public function index(Request $request)
// {
//     $CollegeCode  = session('CollegeCode');
//     $CollegeGroup = session('CollegeGroup');
//     $CollegeType  = session('CollegeType');
//     $year         = date('Y');
//     $EmployeeCd   = Auth::user()->EmployeeCd ?? null;
//     // $currentYear = date('Y');
//     // Helper function to get course name
//     $getCourseName = function ($CourseCode) use ($CollegeGroup) {
//         $res = DB::table('coursemaster')
//             ->select('CourseDesc')
//             ->where('CourseCode', $CourseCode)
//             ->where('CounselGroup', $CollegeGroup)
//             ->first();
//         return $res ? $res->CourseDesc : '';
//     };

//     // ================================
//     // POST Request Handling
//     // ================================
//     if ($request->isMethod('post')) {

//         $action = $request->input('action'); // 'save' or 'finalise'

//         // ================================
//         // FINALISE: Skip everything, redirect
//         // ================================
//         if ($action === 'finalise') {
//             return redirect()->route('print_upload_basicdetails')
//                              ->with('success', 'Finalised successfully!');
//         }

//         // ================================
//         // SAVE: Validation and DB update
//         // ================================
//         $existingMinority = DB::table('minority')->where('CollegeCode', $CollegeCode)->first();

//         // Basic validation rules
//         $rules = [
//             'coursecode' => 'required|array|min:1',
//             'university' => 'required|in:Y,N',
//             'cgender'    => 'required|in:M,W',
//             'minority'   => 'required|in:Y,N',
//             'remarks'    => 'nullable|string|max:1000',
//         ];

//         // Conditional rules based on input and existing data
//         if ($request->input('minority') === 'Y' && empty($existingMinority->Minority ?? null)) {
//             $rules['minoritydocs'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:2048';
//         }
//         if (empty($existingMinority->Gender ?? null)) {
//             $rules['genderdocs'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:2048';
//         }
        
//         if ($request->input('university') === 'Y' && empty($existingMinority->University ?? null)) {
//             $rules['unidocs'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:2048';
//         }
//         if (empty($existingMinority->Other ?? null)) {
//             $rules['otherdocs'] = 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048';
//         }

//         $request->validate($rules);

//         // ================================
//         // Course-level validations
//         // ================================
//         $errors = [];
//         foreach ($request->input('coursecode', []) as $course) {
//             $KTU     = intval($request->input('KTU_'.$course, 0));
//             $AICTE   = intval($request->input('AICTE_'.$course, 0));
//             $Govt    = intval($request->input('Govt_'.$course, 0));
//             $PKTU    = intval($request->input('PKTU_'.$course, 0));
//             $MGMTC   = intval($request->input('MGMTC_'.$course, 0));
//             $MNGCE   = intval($request->input('MNGCE_'.$course, 0));
//             $TRUST   = intval($request->input('TRUST_'.$course, 0));
//             $Hfee    = intval($request->input('Hfee_'.$course, 0));
//             $Lfee    = intval($request->input('Lfee_'.$course, 0));
//             $RegFees = intval($request->input('Regulated_Fees_'.$course, 0));
//             $Fees    = intval($request->input('Fees_'.$course, 0));

//             if ($KTU > $AICTE) $errors['KTU_'.$course] = "KTU seats cannot exceed AICTE seats for $course";
//             if ($Govt > $KTU) $errors['Govt_'.$course] = "Govt seats cannot exceed KTU seats for $course";
//             if ($Hfee > $KTU || $Lfee > $KTU) $errors['Hfee_'.$course] = "High/Low fee seats cannot exceed KTU seats for $course";
//             if ($TRUST > $KTU) $errors['TRUST_'.$course] = "Trust seats cannot exceed KTU seats for $course";

//             if ($CollegeType === 'S') {
//                 if ($Govt != ($MNGCE + $TRUST)) $errors['Govt_'.$course] = "Total Govt seats mismatch for $course";
//                 if ($KTU != ($MNGCE + $MGMTC + $TRUST)) $errors['KTU_'.$course] = "Total KTU seats mismatch for $course";
//             } elseif ($CollegeType === 'N') {
//                 if ($Govt != ($MNGCE + $Lfee + $Hfee)) $errors['Govt_'.$course] = "Total Govt seats mismatch for $course";
//                 if ($KTU != ($MGMTC + $MNGCE + $Lfee + $Hfee)) $errors['KTU_'.$course] = "Total KTU seats mismatch for $course";
//                 if ($RegFees > $Fees) $errors['Regulated_Fees_'.$course] = "Regulated Fees must be less than annual tuition fees for $course";
//             }
//         }

//         if (!empty($errors)) {
//             return redirect()->back()->withErrors($errors)->withInput();
//         }

//         // ================================
//         // Insert/Update Basic Details
//         // ================================
//         $remarks = $request->input('remarks');
//         $remarks = is_string($remarks) ? trim($remarks) : null;
        

//         foreach ($request->input('coursecode', []) as $course) {
//             $minorityFlag   = $request->input('minority', 'N');
//             $minorityStatus = $minorityFlag === 'Y' ? $request->input('minority_status', null) : 'N';

//             $data = [
//                 'Govt'            => $request->input('Govt_'.$course, 0),
//                 'KTU'             => $request->input('KTU_'.$course, 0),
//                 'AICTE'           => $request->input('AICTE_'.$course, 0),
//                 'BCI'             => $request->input('BCI_'.$course, 0),
//                 'PKTU'            => $request->input('PKTU_'.$course, 0),
//                 'TRUST'           => $request->input('TRUST_'.$course, 0),
//                 'MGMTC'           => $request->input('MGMTC_'.$course, 0),
//                 'MGM'             => $request->input('MGM_'.$course, 0),
//                 'MNGCE'           => $request->input('MNGCE_'.$course, 0),
//                 'TOTAL'           => $request->input('TOTAL_'.$course, 0),
//                 'Minority'        => $minorityFlag,
//                 'Minority_status' => $minorityStatus,
//                 'University'      => $request->input('university', 'N'),
//                 'auto'            => $request->input('auto', 0),
//                 'cgender'         => $request->input('cgender', ''),
//                 'Fees'            => $request->input('Fees_'.$course, 0),
//                 'Lfee'            => $request->input('Lfee_'.$course, 0),
//                 'Hfee'            => $request->input('Hfee_'.$course, 0),
//                 'Regulated_Fees'  => $request->input('Regulated_Fees_'.$course, 0),
//                 'ICAR'            => $request->input('ICAR_'.$course, 0),
//                 //'remarks'         => $request->input('remarks', 'Verified'),
//                 'remarks' => $remarks,
//                 'EmployeeID'      => $EmployeeCd,
//                 'UpdateTime'      => now(),
//                 ///
//                 'Year' => $year,

//                 'Newcourse'=>"N",
//                 'NMC'=> $request->input("NMC_$course")??0,
//                 'AIQ'=> $request->input("AIQ_$course")??0,
//                 'ICAR'=> $request->input("ICAR_$course")??0,
//                 'Nri_seat'=> $request->input("Nri_seat_$course")??0,
//                 'gen_fees'=> $request->input("gen_fees_$course")??0,
//                 'Nri_fees'=> $request->input("Nri_fees_$course")??0,
//                 'Nri_min'=> $request->input("Nri_min_$course")??0,
//                 'minority_seat'=> $request->input("minority_seat_$course")??0,
//                 'Status'         => $request->input("Status_$course") ?? 'N',
//                 'other_fees'=> $request->input("other_fees_$course")??0,
//                 'EWS'=> $request->input("EWS_$course")??0,
//                   'KUHS'             => $request->input("KUHS_$course") ?? 0,
//                 'CollegeCode' => $CollegeCode,
//             'CourseCode'     => $course
//             ];

//             $exists = DB::table('basicdetails')
//                 ->where('Year', $year)
//                 ->where('CollegeCode', $CollegeCode)
//                 ->where('CourseCode', $course)
//                 ->exists();

//             if ($exists) {
//                 DB::table('basicdetails')
//                     ->where('Year', $year)
//                     ->where('CollegeCode', $CollegeCode)
//                     ->where('CourseCode', $course)
//                     ->update($data);
//             } else {
//                 $data['Year'] = $year;
//                 $data['CollegeCode'] = $CollegeCode;
//                 $data['CourseCode'] = $course;
//                 DB::table('basicdetails')->insert($data);
//             }
//         }
//         $updatedGender = $request->input('cgender', null);
// if ($updatedGender) {
//     DB::table('collegedetails')
//         ->where('CollegeCode', $CollegeCode)
//         ->update(['CollegeGender' => $updatedGender]);
// }


//         // ================================
//         // Minority Table & File Uploads (only if Minority = Y)
//         // ================================
//         $minorityFlag   = $request->input('minority', 'N'); // 'Y' or 'N'
// $minorityStatus = $request->input('minority_status', null);

// // Prepare base data (always inserted whether Y or N)
// $minorityData = [
//     'CollegeCode'    => $CollegeCode,
//     'MinorityStatus' => $minorityFlag,
//     'UpdateTime'     => now(),
// ];

// // Keep previous uploaded file paths (if any)
// $fileCols = ['Minority','Gender','Approval','Other','University','Government','Upload'];

// if ($existingMinority) {
//     foreach ($fileCols as $col) {
//         if (!empty($existingMinority->{$col})) {
//             $minorityData[$col] = $existingMinority->{$col};
//         } else {
//             $minorityData[$col] = ""; // ensure key exists
//         }
//     }
// } else {
//     // ensure keys exist when inserting for the first time
//     foreach ($fileCols as $col) {
//         $minorityData[$col] = "";
//     }
// }

// // Handle uploaded files
// $uploadMap = [
//     'minoritydocs' => 'Minority',
//     'genderdocs'   => 'Gender',
//     'approvaldocs' => 'Approval',
//     'otherdocs'    => 'Other',
//     'unidocs'      => 'University',
//     'govtdocs'     => 'Government',
// ];

// foreach ($uploadMap as $input => $col) {
//     if ($request->hasFile($input)) {
//         $file = $request->file($input);
//         $filename = time().'_'.$file->getClientOriginalName();
//         $path = $file->storeAs('minority_docs', $filename, 'public');
//         $minorityData[$col] = $path;
//     }
// }

// // ALWAYS insert/update, even when minority = N
// DB::table('minority')->updateOrInsert(
//     ['CollegeCode' => $CollegeCode],
//     $minorityData
// );

// return redirect()->back()->with('success', 'Details and uploaded documents saved successfully!');
//     }
//     // ================================
//     // GET Section (Display Form)
//     // ================================
//     $row_coldetails = DB::table('collegedetails')->where('CollegeCode', $CollegeCode)->first();
//     $row_remarks = DB::table('basicdetails')
//     ->where('CollegeCode', $CollegeCode)
//     ->where('Year', $year)
//     ->first();

//     $qry_courses    = DB::table('coursedetails')->where('CollegeCode', $CollegeCode)->pluck('CourseCode');

//     $basicDetails = DB::table('basicdetails')
//     ->where('CollegeCode', $CollegeCode)
//     ->where('Year', $year)
//     ->get()
//     ->keyBy('CourseCode');


//     $uniStatus       = $basicDetails->first()->University ?? '';
//     $gender          = $row_coldetails->CollegeGender ?? '';
//     $minStatus       = $basicDetails->first()->Minority ?? '';
//     $minority_status = $basicDetails->first()->Minority_status ?? '';

//     $minoritydocs = DB::table('minority')->where('CollegeCode', $CollegeCode)->first();

//     $genUpload = $minUpload = $aprUpload = $othUpload = $uniUpload = $govUpload = 0;
//     if ($minoritydocs) {
//         $genUpload = !empty($minoritydocs->Gender) ? 1 : 0;
//         $minUpload = !empty($minoritydocs->Minority) ? 1 : 0;
//         $aprUpload = !empty($minoritydocs->Approval) ? 1 : 0;
//         $othUpload = !empty($minoritydocs->Other) ? 1 : 0;
//         $uniUpload = !empty($minoritydocs->University) ? 1 : 0;
//         $govUpload = !empty($minoritydocs->Government) ? 1 : 0;
//     }
//     $minorityReligions = DB::table('minority_religions')->pluck('name');

//     $gender = trim($row_coldetails->CollegeGender ?? '');
// $genderCode = strtoupper($gender);
// $genderDesc = DB::table('college_genders')
//     ->where('code', $genderCode)
//     ->value('description');
//     $genderOptions = DB::table('college_genders')->get();
//     return view('CAP.basic_details_2', compact(
//         'CollegeCode', 'CollegeGroup', 'CollegeType',
//         'row_coldetails', 'row_remarks', 'qry_courses',
//         'basicDetails', 'getCourseName',
//         'gender', 'genUpload', 'minUpload', 'uniStatus','genderCode','genderOptions','genderDesc',
//         'aprUpload', 'othUpload', 'uniUpload', 'govUpload',
//         'minoritydocs', 'minStatus', 'minority_status','minorityReligions'
//     ));
// }
////////////////////engineering
public function index(Request $request)
{
    $CollegeCode  = session('CollegeCode');
    $CollegeGroup = session('CollegeGroup');
    $CollegeType  = session('CollegeType');
    $EmployeeCd   = Auth::user()->EmployeeCd ?? null;
    $currentYear  = date('Y');

    // ================================
    // Helper function to get course name
    // ================================
    $getCourseName = function ($CourseCode) use ($CollegeGroup) {
        $res = DB::table('coursemaster')
            ->select('CourseDesc')
            ->where('CourseCode', $CourseCode)
            ->where('CounselGroup', $CollegeGroup)
            ->first();
        return $res ? $res->CourseDesc : '';
    };

    // ================================
    // POST Request Handling
    // ================================
    if ($request->isMethod('post')) {

        $action = $request->input('action'); // 'save' or 'finalise'

        if ($action === 'finalise') {
            return redirect()->route('print_upload_basicdetails')
                             ->with('success', 'Finalised successfully!');
        }

        // Save always targets CURRENT YEAR
        $year = $currentYear;

        // Fetch existing minority for validations
        // $existingMinority = DB::table('minority')->where('CollegeCode', $CollegeCode)->first();
        $existingMinority = DB::table('basic_documents')
    ->where('CollegeCode', $CollegeCode)
    ->where('Year', $currentYear)
    ->first();


        // ================================
        // Validation rules
        // ================================
        $rules = [
            'coursecode' => 'required|array|min:1',
            'university' => 'required|in:Y,N',
            'cgender'    => 'required|in:M,W',
            'minority'   => 'required|in:Y,N',
            'remarks'    => 'nullable|string|max:1000',
        ];

        $messages = [
            'minoritydocs.required' => 'Please upload Minority document.',
            'genderdocs.required'   => 'Please upload Gender document.',
            'unidocs.required'      => 'Please upload University document.',
            'minoritydocs.mimes'    => 'Minority document must be a PDF, JPG, or PNG file.',
            'genderdocs.mimes'      => 'Gender document must be a PDF, JPG, or PNG file.',
            'unidocs.mimes'         => 'University document must be a PDF, JPG, or PNG file.',
            'minoritydocs.max'      => 'Minority document size must not exceed 2 MB.',
            'genderdocs.max'        => 'Gender document size must not exceed 2 MB.',
            'unidocs.max'           => 'University document size must not exceed 2 MB.',
        ];
        

        if ($request->input('minority') === 'Y' && empty($existingMinority->Minority ?? null)) {
            $rules['minoritydocs'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:2048';
        }
        if (empty($existingMinority->Gender ?? null)) {
            $rules['genderdocs'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:2048';
        }
        if ($request->input('university') === 'Y' && empty($existingMinority->University ?? null)) {
            $rules['unidocs'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:2048';
        }
        if (empty($existingMinority->Other ?? null)) {
            $rules['otherdocs'] = 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048';
        }

        $request->validate($rules, $messages);


        // ================================
        // Course-level validations
        // ================================
        $errors = [];
        foreach ($request->input('coursecode', []) as $course) {
            $KTU     = intval($request->input('KTU_'.$course, 0));
            $AICTE   = intval($request->input('AICTE_'.$course, 0));
            $Govt    = intval($request->input('Govt_'.$course, 0));
            $PKTU    = intval($request->input('PKTU_'.$course, 0));
            $MGMTC   = intval($request->input('MGMTC_'.$course, 0));
            $MNGCE   = intval($request->input('MNGCE_'.$course, 0));
            $TRUST   = intval($request->input('TRUST_'.$course, 0));
            $Hfee    = intval($request->input('Hfee_'.$course, 0));
            $Lfee    = intval($request->input('Lfee_'.$course, 0));
            $RegFees = intval($request->input('Regulated_Fees_'.$course, 0));
            $Fees    = intval($request->input('Fees_'.$course, 0));

            if ($KTU > $AICTE) $errors['KTU_'.$course] = "KTU seats cannot exceed AICTE seats for $course";
            if ($Govt > $KTU) $errors['Govt_'.$course] = "Govt seats cannot exceed KTU seats for $course";
            if ($Hfee > $KTU || $Lfee > $KTU) $errors['Hfee_'.$course] = "High/Low fee seats cannot exceed KTU seats for $course";
            if ($TRUST > $KTU) $errors['TRUST_'.$course] = "Trust seats cannot exceed KTU seats for $course";

            if ($CollegeType === 'S') {
                if ($Govt != ($MNGCE + $TRUST)) $errors['Govt_'.$course] = "Total Govt seats mismatch for $course";
                if ($KTU != ($MNGCE + $MGMTC + $TRUST)) $errors['KTU_'.$course] = "Total KTU seats mismatch for $course";
            } elseif ($CollegeType === 'N') {
                if ($Govt != ($MNGCE + $Lfee + $Hfee)) $errors['Govt_'.$course] = "Total Govt seats mismatch for $course";
                if ($KTU != ($MGMTC + $MNGCE + $Lfee + $Hfee)) $errors['KTU_'.$course] = "Total KTU seats mismatch for $course";
                if ($RegFees > $Fees) $errors['Regulated_Fees_'.$course] = "Regulated Fees must be less than annual tuition fees for $course";
            }
        }

        if (!empty($errors)) {
            return redirect()->back()->withErrors($errors)->withInput();
        }

        // ================================
        // Insert/Update basic details (CURRENT YEAR)
        // ================================
        $remarks = is_string($request->input('remarks')) ? trim($request->input('remarks')) : null;

        foreach ($request->input('coursecode', []) as $course) {
            $minorityFlag   = $request->input('minority', 'N');
            $minorityStatus = $minorityFlag === 'Y' ? $request->input('minority_status', null) : 'N';

            $data = [
                'Govt'            => $request->input('Govt_'.$course, 0),
                'KTU'             => $request->input('KTU_'.$course, 0),
                'AICTE'           => $request->input('AICTE_'.$course, 0),
                'BCI'             => $request->input('BCI_'.$course, 0),
                'PKTU'            => $request->input('PKTU_'.$course, 0),
                'TRUST'           => $request->input('TRUST_'.$course, 0),
                'MGMTC'           => $request->input('MGMTC_'.$course, 0),
                'MGM'             => $request->input('MGM_'.$course, 0),
                'MNGCE'           => $request->input('MNGCE_'.$course, 0),
                'TOTAL'           => $request->input('TOTAL_'.$course, 0),
                'Minority'        => $minorityFlag,
                'Minority_status' => $minorityStatus,
                'University'      => $request->input('university', 'N'),
                'auto'            => $request->input('auto', 0),
                'cgender'         => $request->input('cgender', ''),
                'Fees'            => $request->input('Fees_'.$course, 0),
                'Lfee'            => $request->input('Lfee_'.$course, 0),
                'Hfee'            => $request->input('Hfee_'.$course, 0),
                'Regulated_Fees'  => $request->input('Regulated_Fees_'.$course, 0),
                'ICAR'            => $request->input('ICAR_'.$course, 0),
                'remarks'         => $remarks,
                'EmployeeID'      => $EmployeeCd,
                'UpdateTime'      => now(),
                'Year'            => $currentYear, // ALWAYS CURRENT YEAR
                'Newcourse'       => "N",
                'NMC'             => $request->input("NMC_$course")??0,
                'AIQ'             => $request->input("AIQ_$course")??0,
                'ICAR'            => $request->input("ICAR_$course")??0,
                'Nri_seat'        => $request->input("Nri_seat_$course")??0,
                'gen_fees'        => $request->input("gen_fees_$course")??0,
                'Nri_fees'        => $request->input("Nri_fees_$course")??0,
                'Nri_min'         => $request->input("Nri_min_$course")??0,
                'minority_seat'   => $request->input("minority_seat_$course")??0,
                'Status'          => $request->input("Status_$course") ?? 'N',
                'other_fees'      => $request->input("other_fees_$course")??0,
                'EWS'             => $request->input("EWS_$course")??0,
                'KUHS'            => $request->input("KUHS_$course") ?? 0,
                'CollegeCode'     => $CollegeCode,
                'CourseCode'      => $course
            ];

            DB::table('basicdetails')->updateOrInsert(
                ['Year' => $currentYear, 'CollegeCode' => $CollegeCode, 'CourseCode' => $course],
                $data
            );
        }

        // Update college gender if changed
        if ($updatedGender = $request->input('cgender', null)) {
            DB::table('collegedetails')
                ->where('CollegeCode', $CollegeCode)
                ->update(['CollegeGender' => $updatedGender]);
        }

        // ================================
        // Minority table & uploads
        // ================================
        $minorityFlag   = $request->input('minority', 'N');

        $minorityData = [
            'CollegeCode'    => $CollegeCode,
            'MinorityStatus' => $minorityFlag,
            'UpdateTime'     => now(),
        ];

        $fileCols = ['Minority','Gender','Approval','Other','University','Government','Upload'];
        if ($existingMinority) {
            foreach ($fileCols as $col) {
                $minorityData[$col] = $existingMinority->{$col} ?? '';
            }
        } else {
            foreach ($fileCols as $col) {
                $minorityData[$col] = '';
            }
        }

        $uploadMap = [
            'minoritydocs' => 'Minority',
            'genderdocs'   => 'Gender',
            'approvaldocs' => 'Approval',
            'otherdocs'    => 'Other',
            'unidocs'      => 'University',
            'govtdocs'     => 'Government',
        ];

        foreach ($uploadMap as $input => $col) {
            if ($request->hasFile($input)) {
                $file = $request->file($input);
                $filename = time().'_'.$file->getClientOriginalName();
                $path = $file->storeAs('minority_docs', $filename, 'public');
                $minorityData[$col] = $path;
            }
        }

        // DB::table('minority')->updateOrInsert(
        //     ['CollegeCode' => $CollegeCode],
        //     $minorityData
        // );
        $minorityData['Year'] = $currentYear;

DB::table('basic_documents')->updateOrInsert(
    [
        'CollegeCode' => $CollegeCode,
        'Year'        => $currentYear,
    ],
    $minorityData
);


        return redirect()->back()->with('success', 'Details and uploaded documents saved for current year!');
    }

    // ================================
    // GET Section - Display Form
    // ================================
    $latestYearData = DB::table('basicdetails')
        ->where('CollegeCode', $CollegeCode)
        ->orderByDesc('Year')
        ->first();

    $displayYear = $latestYearData ? $latestYearData->Year : $currentYear; // view latest available

    $row_coldetails = DB::table('collegedetails')->where('CollegeCode', $CollegeCode)->first();
    $row_remarks    = DB::table('basicdetails')->where('CollegeCode', $CollegeCode)->where('Year', $displayYear)->first();
    $qry_courses    = DB::table('coursedetails')->where('CollegeCode', $CollegeCode)->pluck('CourseCode');

    $basicDetails = DB::table('basicdetails')
        ->where('CollegeCode', $CollegeCode)
        ->where('Year', $displayYear)
        ->get()
        ->keyBy('CourseCode');

    $uniStatus       = $basicDetails->first()->University ?? '';
    $gender          = trim($row_coldetails->CollegeGender ?? '');
    $minStatus       = $basicDetails->first()->Minority ?? '';
    $minority_status = $basicDetails->first()->Minority_status ?? '';

    // $minoritydocs = DB::table('minority')->where('CollegeCode', $CollegeCode)->first();
    $minoritydocs = DB::table('basic_documents')
    ->where('CollegeCode', $CollegeCode)
    ->where('Year', $displayYear)
    ->first();


    $genUpload = $minUpload = $aprUpload = $othUpload = $uniUpload = $govUpload = 0;
    if ($minoritydocs) {
        $genUpload = !empty($minoritydocs->Gender) ? 1 : 0;
        $minUpload = !empty($minoritydocs->Minority) ? 1 : 0;
        $aprUpload = !empty($minoritydocs->Approval) ? 1 : 0;
        $othUpload = !empty($minoritydocs->Other) ? 1 : 0;
        $uniUpload = !empty($minoritydocs->University) ? 1 : 0;
        $govUpload = !empty($minoritydocs->Government) ? 1 : 0;
    }

    $minorityReligions = DB::table('minority_religions')->pluck('name');

    $genderCode = strtoupper($gender);
    $genderDesc = DB::table('college_genders')
        ->where('code', $genderCode)
        ->value('description');

    $genderOptions = DB::table('college_genders')->get();

    $basicDetailsRecord = DB::table('basicdetails')
    ->where('CollegeCode', $CollegeCode)
    ->where('Year', $displayYear)
    ->first();

$isFinalised = ($basicDetailsRecord->Status ?? '') === 'Y';

    return view('CAP.basic_details_2', compact(
        'CollegeCode', 'CollegeGroup', 'CollegeType',
        'row_coldetails', 'row_remarks', 'qry_courses',
        'basicDetails', 'getCourseName',
        'gender', 'genUpload', 'minUpload', 'uniStatus','genderCode','genderOptions','genderDesc',
        'aprUpload', 'othUpload', 'uniUpload', 'govUpload',
        'minoritydocs', 'minStatus', 'minority_status','minorityReligions',
        'displayYear', 'currentYear','isFinalised'
    ));}




public function viewDoc(Request $request, $type)
{
    $collegeCode = $request->query('CollegeCode');

    // Get the document path from the DB
    // $record = DB::table('minority')->where('CollegeCode', $collegeCode)->first();
    $year = $request->query('Year', date('Y'));

$record = DB::table('basic_documents')
    ->where('CollegeCode', $collegeCode)
    ->where('Year', $year)
    ->first();


    if (!$record) {
        abort(404, 'Document not found');
    }

    // Map type to DB column
    $colMap = [
        'minority' => 'Minority',
        'gender'   => 'Gender',
        'approval' => 'Approval',
        'other'    => 'Other',
        'university' => 'University',
        'government' => 'Government',
    ];

    if (!isset($colMap[$type])) {
        abort(400, 'Invalid document type');
    }

    $filePath = $record->{$colMap[$type]};

    if (!$filePath || !Storage::disk('public')->exists($filePath)) {
        abort(404, 'File not found');
    }

    // Return file as response
    return response()->file(storage_path('app/public/' . $filePath));
}




public function printUploadBasicDetails()
{
    $CollegeCode = session('CollegeCode');

    // Safeguard in case session is missing
    if (!$CollegeCode) {
        return redirect()->route('login')->with('error', 'Session expired. Please log in again.');
    }

    // Fetch the basicdetails record
    $currentYear = now()->year;

    $basicDetails = DB::table('basicdetails')
        ->where('CollegeCode', $CollegeCode)
        ->where('Year', $currentYear)  // ✅ only check current year
        ->first();
    
    // Default value to prevent undefined variable error
    $isFinalised = false;
    
    if ($basicDetails && isset($basicDetails->status)) {
        $isFinalised = ($basicDetails->status === 'Y');
    }

    // Return the print upload view
    return view('CAP.printUploadBasicDetails', compact('basicDetails', 'isFinalised','currentYear'));
}

public function uploadAndVerify(Request $request)
{
    $CollegeCode = session('CollegeCode');

    if (!$CollegeCode) {
        return redirect()->route('login')->with('error', 'Session expired. Please log in again.');
    }

    // ✅ Validation
    $request->validate([
        'signed_report' => 'required|file|mimes:pdf|max:300', // max 300KB
        'declaration'   => 'accepted',
    ], [
        'signed_report.required' => 'Please upload the signed Basic Details Report.',
        'signed_report.mimes'    => 'Only PDF files are allowed.',
        'signed_report.max'      => 'The file size must be below 300 KB.',
        'declaration.accepted'   => 'You must confirm that the details are true.',
    ]);

    // ✅ Upload the PDF file
    $file = $request->file('signed_report');
    $filename = 'BasicDetails_' . $CollegeCode . '_' . time() . '.pdf';
    $path = $file->storeAs('basicdetails_reports', $filename, 'public');

    // ✅ Insert or update in "documents" table
    DB::table('documents')->updateOrInsert(
        ['CollegeCode' => $CollegeCode],
        [
            'Doc1'            => $path,
            'Doc1_Flag'       => 'Y',
            'Doc1_UpdateTime' => now(),
        ]
    );

    // // ✅ Update basicdetails table status
    // DB::table('basicdetails')
    //     ->where('CollegeCode', $CollegeCode)
    //     ->update(['status' => 'Y']);
    $currentYear = date('Y');
    DB::table('basicdetails')
        ->where('CollegeCode', $CollegeCode)
        ->where('Year', $currentYear)
        ->update(['status' => 'Y']);

    return redirect()->route('print_upload_basicdetails')
        ->with('success', 'Signed Basic Details Report uploaded and verified successfully!');
}

public function printBasicDetails()
{
    $CollegeCode = session('CollegeCode');
    $CollegeGroup = session('CollegeGroup');
    $CollegeType = session('CollegeType');

    // Check session
    if (!$CollegeCode) {
        return redirect()->route('login')->with('error', 'Session expired. Please log in again.');
    }

    $currentYear = date('Y');

    // Try to get current year details first
    $details = DB::table('basicdetails')
        ->where('CollegeCode', $CollegeCode)
        ->where('Year', $currentYear)
        ->get();

    // If no records for current year, fetch latest available year
    if ($details->isEmpty()) {
        $latestRecord = DB::table('basicdetails')
            ->where('CollegeCode', $CollegeCode)
            ->orderByDesc('Year')
            ->first();

        if ($latestRecord) {
            $currentYear = $latestRecord->Year; // update year for display
            $details = DB::table('basicdetails')
                ->where('CollegeCode', $CollegeCode)
                ->where('Year', $currentYear)
                ->get();
        }
    }

    // Fetch college details
    $college = DB::table('collegedetails')
        ->where('CollegeCode', $CollegeCode)
        ->first();

    $row_coldetails = $college; // optional alias for Blade

    return view('CAP.print_basicdetails_pdf', compact(
        'details',
        'college',
        'currentYear',
        'CollegeGroup',
        'row_coldetails','CollegeType','CollegeCode'
    ));
}


////////////////law



// public function basicdetails_law(Request $request)
// {
//     $CollegeCode  = session('CollegeCode');
//     $CollegeGroup = session('CollegeGroup');
//     $CollegeType  = session('CollegeType');
//     $year         = date('Y');
//     $EmployeeCd   = Auth::user()->EmployeeCd ?? null;
//     $currentYear = date('Y');
//     // Helper - get course name
//     $getCourseName = function ($CourseCode) use ($CollegeGroup) {
//         $row = DB::table('coursemaster')
//             ->where('CourseCode', $CourseCode)
//             ->where('CounselGroup', $CollegeGroup)
//             ->first();
//         return $row->CourseDesc ?? '';
//     };

//     // ================================
//     // POST REQUEST
//     // ================================
//     if ($request->isMethod('post')) {

//         $action = $request->input('action');

//         // Finalise → skip update
//         if ($action === 'finalise') {
//             return redirect()->route('print_upload_basicdetails')
//                              ->with('success', 'Finalised successfully!');
//         }

//         // Load existing minority docs
//         $existingMinority = DB::table('minority')
//             ->where('CollegeCode', $CollegeCode)
//             ->first();

//         $currentGender = DB::table('collegedetails')
//             ->where('CollegeCode', $CollegeCode)
//             ->value('CollegeType');

//         // ================================
//         // VALIDATION
//         // ================================
//         $rules = [
//             'coursecode' => 'required|array|min:1',
//             'university' => 'required|in:Y,N',
//             'cgender'    => 'required|in:M,W',
//             'minority'   => 'required|in:Y,N',
//             'remarks'    => 'nullable|string|max:1000',
//         ];

//         if ($request->input('minority') === 'Y' && empty($existingMinority->Minority ?? null)) {
//             $rules['minoritydocs'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:2048';
//         }
//         if (empty($existingMinority->Gender ?? null)) {
//             $rules['genderdocs'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:2048';
//         }
        
//         if ($request->input('university') === 'Y') {
//             // Check if the university document is uploaded or already exists in DB
//             $existingMinority = DB::table('minority')->where('CollegeCode', $CollegeCode)->first();
//             if (!$request->hasFile('unidocs') && empty($existingMinority->University ?? null)) {
//                 $rules['unidocs'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:2048';
//             }
//         }
        
//         if (empty($existingMinority->Other ?? null)) {
//             $rules['otherdocs'] = 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048';
//         }

//         $request->validate($rules);

//         // ================================
//         // UPDATE BASIC DETAILS (PER COURSE)
//         // ================================
//         foreach ($request->input('coursecode', []) as $course) {
//             $minorityFlag   = $request->input('minority', 'N');
//             $minorityStatus = $minorityFlag === 'Y' ? $request->input('minority_status', null) : 'N';

//             $data = [
//                 'Govt'        => $request->input("Govt_$course", 0),
//                 'KTU'         => $request->input("KTU_$course", 0),
//                 'AICTE'       => $request->input("AICTE_$course", 0),
//                 'BCI'         => $request->input("BCI_$course", 0),
//                 'TRUST'       => $request->input("TRUST_$course", 0),
//                 'MGMTC'       => $request->input("MGMTC_$course", 0),
//                 'TOTAL'       => $request->input("TOTAL_$course", 0),
//                 'EWS'         => $request->input("EWS_$course", 0),
//                 'Fees'        => $request->input("Fees_$course", 0),
//                 'Lfee'        => $request->input("Lfee_$course", 0),
//                 'other_fees'  => $request->input("other_fees_$course", 0),

//                 'Minority'        => $request->minority,
//                 'Minority_status' => ($request->minority === 'Y') ? ($request->minority_status ?? 'N') : 'N',
//                 'University'      => $request->university,
//                 'cgender'         => $request->cgender,

//                 'remarks'     => trim($request->remarks) ?: 'Verified',
//                 'EmployeeID'  => $EmployeeCd,
//                 'UpdateTime'  => now(),
//                  'Status'         => $request->input("Status_$course") ?? 'N',
//                 //
//                 'Year' => $currentYear,
//                 'Newcourse'=>"N",
//                 'NMC'=> $request->input("NMC_$course")??0,
//                 'AIQ'=> $request->input("AIQ_$course")??0,
//                 'ICAR'=> $request->input("ICAR_$course")??0,
//                 'Nri_seat'=> $request->input("Nri_seat_$course")??0,
//                 'gen_fees'=> $request->input("gen_fees_$course")??0,
//                 'Nri_fees'=> $request->input("Nri_fees_$course")??0,
//                 'Nri_min'=> $request->input("Nri_min_$course")??0,
//                 'minority_seat'=> $request->input("minority_seat_$course")??0,
                
//                 'other_fees'=> $request->input("other_fees_$course")??0,
//                 'EWS'=> $request->input("EWS_$course")??0,

//                 'CollegeCode' => $CollegeCode,
//             'CourseCode'     => $course
//             ];

//             $exists = DB::table('basicdetails')
//                 ->where('Year', $year)
//                 ->where('CollegeCode', $CollegeCode)
//                 ->where('CourseCode', $course)
//                 ->exists();

//             if ($exists) {
//                 DB::table('basicdetails')
//                     //->where('Year', $year)
//                     ->where('CollegeCode', $CollegeCode)
//                     ->where('CourseCode', $course)
//                     ->update($data);
//             } else {
//                 $data['Year'] = $year;
//                 $data['CollegeCode'] = $CollegeCode;
//                 $data['CourseCode'] = $course;
//                 DB::table('basicdetails')->insert($data);
//             }
//         }
//         $updatedGender = $request->input('cgender', null);
// if ($updatedGender) {
//     DB::table('collegedetails')
//         ->where('CollegeCode', $CollegeCode)
//         ->update(['CollegeGender' => $updatedGender]);
// }


//         // ================================
//         // Minority Table & File Uploads (only if Minority = Y)
//         // ================================
//         $minorityFlag = $request->input('minority', 'N');
//         $minorityStatus = $request->input('minority_status', null);

//     //     if ($minorityFlag === 'Y') {
           
//                 $minorityData = [
//                     'CollegeCode'    => $CollegeCode,
//                     'MinorityStatus' => $minorityFlag,
//                     'UpdateTime'     => now(),
//                     'Minority'       => $minorityData['Minority'] ?? '', // always set
//                     'Gender'         => $minorityData['Gender'] ?? '',   // always set
//                     'Approval'       => $minorityData['Approval'] ?? '',
//                     'Other'          => $minorityData['Other'] ?? '',
//                     'University'     => $minorityData['University'] ?? '',
//                     'Government'     => $minorityData['Government'] ?? '',
//                     'Upload'     => $minorityData['Upload'] ?? '',
//                 ];
                
            

//             $fileCols = ['Minority','Gender','Approval','Other','University','Government','Upload'];
//             if ($existingMinority) {
//                 foreach ($fileCols as $col) {
//                     if (!empty($existingMinority->{$col})) {
//                         $minorityData[$col] = $existingMinority->{$col};
//                     }
//                 }
//             }

//             $uploadMap = [
//                 'minoritydocs' => 'Minority',
//                 'genderdocs'   => 'Gender',
//                 'approvaldocs' => 'Approval',
//                 'otherdocs'    => 'Other',
//                 'unidocs'      => 'University',
//                 'govtdocs'     => 'Government',
//             ];

//             foreach ($uploadMap as $input => $col) {
//                 if ($request->hasFile($input)) {
//                     $file = $request->file($input);
//                     $filename = time().'_'.$file->getClientOriginalName();
//                     $path = $file->storeAs('minority_docs', $filename, 'public');
//                     $minorityData[$col] = $path;
//                 }
//             }

//             DB::table('minority')->updateOrInsert(
//                 ['CollegeCode' => $CollegeCode],
//                 $minorityData
//             );
//         }
//         else {
//             // If minority = N or empty, delete existing record(deleting it from minority table-no permission)
//            // DB::table('minority')->where('CollegeCode', $CollegeCode)->delete();
//         }


//         return redirect()->back()->with('success', 'Details and uploaded documents saved successfully!');
//     }


//    // ================================
//    // GET REQUEST
//     // ================================
//     $row_coldetails = DB::table('collegedetails')->where('CollegeCode', $CollegeCode)->first();
//     $row_remarks = DB::table('basicdetails')
//     ->where('CollegeCode', $CollegeCode)
//     ->where('Year', $year)
//     ->first();

//     $qry_courses    = DB::table('coursedetails')->where('CollegeCode', $CollegeCode)->pluck('CourseCode');

//     $basicDetails   = DB::table('basicdetails')
//                         ->where('CollegeCode', $CollegeCode)
//                         ->where('Year', $year)
//                         ->get()
//                         ->keyBy('CourseCode');

//     // Correct gender source
//     $gender          = $row_coldetails->CollegeType ?? '';
//     $uniStatus       = $basicDetails->first()->University ?? '';
//     $minStatus       = $basicDetails->first()->Minority ?? '';
//     $minority_status = $basicDetails->first()->Minority_status ?? '';

//     $minoritydocs = DB::table('minority')->where('CollegeCode', $CollegeCode)->first();

//     // Upload flags
//     $genUpload = !empty($minoritydocs->Gender ?? '') ? 1 : 0;
//     $minUpload = !empty($minoritydocs->Minority ?? '') ? 1 : 0;
//     $aprUpload = !empty($minoritydocs->Approval ?? '') ? 1 : 0;
//     $othUpload = !empty($minoritydocs->Other ?? '') ? 1 : 0;
//     $uniUpload = !empty($minoritydocs->University ?? '') ? 1 : 0;
//     $govUpload = !empty($minoritydocs->Government ?? '') ? 1 : 0;
//     $minorityReligions = DB::table('minority_religions')->pluck('name');

//     $gender = trim($row_coldetails->CollegeGender ?? '');
// $genderCode = strtoupper($gender);
// $genderDesc = DB::table('college_genders')
//     ->where('code', $genderCode)
//     ->value('description');
//     $coursedetails = DB::table('coursedetails')
//     ->whereIn('CourseCode', $qry_courses)
//     ->get();
//     $genderOptions = DB::table('college_genders')->get();
//     return view('CAP.basicdetails_law', compact(
//         'CollegeCode','CollegeGroup','CollegeType',
//         'row_coldetails','row_remarks','qry_courses',
//         'basicDetails','getCourseName',
//         'gender','genUpload','minUpload','uniStatus',
//         'aprUpload','othUpload','uniUpload','govUpload',
//         'minoritydocs','minStatus','minority_status','genderCode','genderOptions','genderDesc','minorityReligions','coursedetails'
//     ));
// }


// public function basicdetails_law(Request $request)
// {
//     $CollegeCode  = session('CollegeCode');
//     $CollegeGroup = session('CollegeGroup');
//     $CollegeType  = session('CollegeType');
//     $currentYear  = date('Y');
//     $EmployeeCd   = Auth::user()->EmployeeCd ?? null;

//     if (!$CollegeCode) {
//         abort(403, 'College code not found in session.');
//     }

//     // -------------------------
//     // HELPER: Course name
//     // -------------------------
//     $getCourseName = function ($CourseCode) use ($CollegeGroup) {
//         return DB::table('coursemaster')
//             ->where('CourseCode', $CourseCode)
//             ->where('CounselGroup', $CollegeGroup)
//             ->value('CourseDesc') ?? '';
//     };

//     // -------------------------
//     // POST REQUEST
//     // -------------------------
//     if ($request->isMethod('post')) {

//         if ($request->input('action') === 'finalise') {
//             return redirect()
//                 ->route('print_upload_basicdetails')
//                 ->with('success', 'Finalised successfully!');
//         }

//         // $existingMinority = DB::table('minority')
//         //     ->where('CollegeCode', $CollegeCode)
//         //     ->first();
//         $existingMinority = DB::table('basic_documents')
//     ->where('CollegeCode', $CollegeCode)
//     ->where('Year', $currentYear)
//     ->first();


//         // -------------------------
//         // VALIDATION
//         // -------------------------
//         $rules = [
//             'coursecode' => 'required|array|min:1',
//             'university' => 'required|in:Y,N',
//             'cgender'    => 'required|in:M,W',
//             'minority'   => 'required|in:Y,N',
//             'remarks'    => 'nullable|string|max:1000',
//         ];

//         $messages = [
//             'minoritydocs.required' => 'Please upload Minority document.',
//             'genderdocs.required'   => 'Please upload Gender document.',
//             'unidocs.required'      => 'Please upload University document.',
//             'minoritydocs.mimes'    => 'Minority document must be a PDF, JPG, or PNG file.',
//             'genderdocs.mimes'      => 'Gender document must be a PDF, JPG, or PNG file.',
//             'unidocs.mimes'         => 'University document must be a PDF, JPG, or PNG file.',
//             'minoritydocs.max'      => 'Minority document size must not exceed 2 MB.',
//             'genderdocs.max'        => 'Gender document size must not exceed 2 MB.',
//             'unidocs.max'           => 'University document size must not exceed 2 MB.',
//         ];

//         if ($request->minority === 'Y' && empty($existingMinority->Minority ?? null)) {
//             $rules['minoritydocs'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:2048';
//         }

//         if (empty($existingMinority->Gender ?? null)) {
//             $rules['genderdocs'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:2048';
//         }

//         if ($request->university === 'Y' && empty($existingMinority->University ?? null)) {
//             $rules['unidocs'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:2048';
//         }

//         $request->validate($rules,$messages);

//         // -------------------------
//         // YEAR ROLLOVER LOGIC
//         // -------------------------
//         $existsCurrentYear = DB::table('basicdetails')
//             ->where('CollegeCode', $CollegeCode)
//             ->where('Year', $currentYear)
//             ->exists();

//         if (!$existsCurrentYear) {
//             // Get nearest previous year
//             $previousYear = DB::table('basicdetails')
//                 ->where('CollegeCode', $CollegeCode)
//                 ->max('Year');

//             if ($previousYear) {
//                 $previousRows = DB::table('basicdetails')
//                     ->where('CollegeCode', $CollegeCode)
//                     ->where('Year', $previousYear)
//                     ->get();

//                 foreach ($previousRows as $row) {
//                     $newRow = (array) $row;
//                     unset($newRow['id']); // remove primary key if exists
//                     $newRow['Year'] = $currentYear;
//                     $newRow['UpdateTime'] = now();
//                     $newRow['EmployeeID'] = $EmployeeCd;
//                     DB::table('basicdetails')->insert($newRow);
//                 }
//             }
//         }

//         // -------------------------
//         // SAVE CURRENT YEAR DATA
//         // -------------------------
//         foreach ($request->coursecode as $course) {
//             $data = [
//                 'Govt'        => $request->input("Govt_$course", 0),
//                 'KTU'         => $request->input("KTU_$course", 0),
//                 'AICTE'       => $request->input("AICTE_$course", 0),
//                 'BCI'         => $request->input("BCI_$course", 0),
//                 'TRUST'       => $request->input("TRUST_$course", 0),
//                 'MGMTC'       => $request->input("MGMTC_$course", 0),
//                 'TOTAL'       => $request->input("TOTAL_$course", 0),
//                 'EWS'         => $request->input("EWS_$course", 0),
//                 'Fees'        => $request->input("Fees_$course", 0),
//                 'Lfee'        => $request->input("Lfee_$course", 0),
//                 'other_fees'  => $request->input("other_fees_$course", 0),
//                 'Minority'        => $request->minority,
//                 'Minority_status' => $request->minority === 'Y'
//                                     ? ($request->minority_status ?? 'N')
//                                     : 'N',
//                 'University' => $request->university,
//                 'cgender'    => $request->cgender,
//                 'remarks'    => trim($request->remarks) ?: 'Verified',
//                 'EmployeeID' => $EmployeeCd,
//                 'UpdateTime' => now(),
//                 'Status'     => $request->input("Status_$course", 'N'),
//                 'Year'       => $currentYear,
//                 'Newcourse'  => 'N',
//                 'NMC'        => $request->input("NMC_$course", 0),
//                 'AIQ'        => $request->input("AIQ_$course", 0),
//                 'ICAR'       => $request->input("ICAR_$course", 0),
//                 'Nri_seat'   => $request->input("Nri_seat_$course", 0),
//                 'gen_fees'   => $request->input("gen_fees_$course", 0),
//                 'Nri_fees'   => $request->input("Nri_fees_$course", 0),
//                 'Nri_min'    => $request->input("Nri_min_$course", 0),
//                 'minority_seat'=> $request->input("minority_seat_$course", 0),
//                 'CollegeCode'=> $CollegeCode,
//                 'CourseCode' => $course,
//             ];

//             DB::table('basicdetails')->updateOrInsert(
//                 [
//                     'Year'        => $currentYear,
//                     'CollegeCode' => $CollegeCode,
//                     'CourseCode'  => $course,
//                 ],
//                 $data
//             );
//         }

//         // -------------------------
//         // UPDATE COLLEGE GENDER
//         // -------------------------
//         if ($request->input('cgender')) {
//             DB::table('collegedetails')
//                 ->where('CollegeCode', $CollegeCode)
//                 ->update(['CollegeGender' => $request->cgender]);
//         }

//         // -------------------------
//         // MINORITY TABLE & UPLOAD
//         // -------------------------
        
//             $minorityData = [
//                 'CollegeCode'    => $CollegeCode,
//                 'Year'           => $currentYear,
//                 'MinorityStatus' => $request->minority,   // Y or N
//                 'UpdateTime'     => now(),
//             ];

//             // $existingCols = ['Minority','Gender','Approval','Other','University','Government','Upload'];
//             // if ($existingMinority) {
//             //     foreach ($existingCols as $col) {
//             //         if (!empty($existingMinority->$col)) {
//             //             $minorityData[$col] = $existingMinority->$col;
//             //         }
//             //     }
//             // }
//             $existingCols = ['Minority','Gender','Approval','Other','University','Government','Upload'];

// if ($existingMinority) {
//     foreach ($existingCols as $col) {
//         if (!empty($existingMinority->$col)) {
//             $minorityData[$col] = $existingMinority->$col;
//         }
//     }
// }


//             $uploadMap = [
//                 'minoritydocs' => 'Minority',
//                 'genderdocs'   => 'Gender',
//                 'approvaldocs' => 'Approval',
//                 'otherdocs'    => 'Other',
//                 'unidocs'      => 'University',
//                 'govtdocs'     => 'Government',
//             ];

//             foreach ($uploadMap as $input => $col) {
//                 if ($request->hasFile($input)) {
//                     $file = $request->file($input);
//                     $filename = time().'_'.$file->getClientOriginalName();
//                     $minorityData[$col] = $file->storeAs('minority_docs', $filename, 'public');
//                 }
//             }

//             // DB::table('minority')->updateOrInsert(
//             //     ['CollegeCode' => $CollegeCode],
//             //     $minorityData
//             // );
//             $minorityData['Year'] = $currentYear;

// DB::table('basic_documents')->updateOrInsert(
//     [
//         'CollegeCode' => $CollegeCode,
//         'Year'        => $currentYear,
//     ],
//     $minorityData
// );

       

//         return redirect()->back()->with('success', 'Details saved for year '.$currentYear);
//     }

//     // -------------------------
//     // GET REQUEST
//     // -------------------------
//     $row_coldetails = DB::table('collegedetails')
//         ->where('CollegeCode', $CollegeCode)
//         ->first();

//     // Find nearest year for this college
//     $sourceYear = DB::table('basicdetails')
//         ->where('CollegeCode', $CollegeCode)
//         ->max('Year');

//     $displayYear = $sourceYear ?: $currentYear;

//     $basicDetails = DB::table('basicdetails')
//         ->where('CollegeCode', $CollegeCode)
//         ->where('Year', $displayYear)
//         ->get()
//         ->keyBy('CourseCode');

//     $qry_courses = DB::table('coursedetails')
//         ->where('CollegeCode', $CollegeCode)
//         ->pluck('CourseCode');

//     $coursedetails = DB::table('coursedetails')
//         ->whereIn('CourseCode', $qry_courses)
//         ->get();

//     $firstBasic = $basicDetails->first();
//     $uniStatus = $firstBasic->University ?? '';
//     $minStatus = $firstBasic->Minority ?? '';
//     $minority_status = $firstBasic->Minority_status ?? '';

//     // $minoritydocs = DB::table('minority')
//     //     ->where('CollegeCode', $CollegeCode)
//     //     ->first();
//     $minoritydocs = DB::table('basic_documents')
//     ->where('CollegeCode', $CollegeCode)
//     ->where('Year', $displayYear)
//     ->first();


//     $gender = trim($row_coldetails->CollegeGender ?? '');
//     $genderCode = strtoupper($gender);
//     $genderDesc = DB::table('college_genders')
//         ->where('code', $genderCode)
//         ->value('description');

//     $genderOptions = DB::table('college_genders')->get();
//     $minorityReligions = DB::table('minority_religions')->pluck('name');

//     return view('CAP.basicdetails_law', compact(
//         'CollegeCode','CollegeGroup','CollegeType',
//         'row_coldetails','qry_courses','coursedetails',
//         'basicDetails','getCourseName',
//         'uniStatus','minStatus','minority_status',
//         'minoritydocs','gender','genderCode','genderDesc',
//         'genderOptions','minorityReligions'
//     ));
// }

// public function basicdetails_law(Request $request)
// {
//     $CollegeCode  = session('CollegeCode');
//     $CollegeGroup = session('CollegeGroup');
//     $CollegeType  = session('CollegeType');
//     $currentYear  = date('Y');
//     $EmployeeCd   = Auth::user()->EmployeeCd ?? null;

//     if (!$CollegeCode) {
//         abort(403, 'College code not found in session.');
//     }

//     // -------------------------
//     // HELPER: Course name
//     // -------------------------
//     $getCourseName = function ($CourseCode) use ($CollegeGroup) {
//         return DB::table('coursemaster')
//             ->where('CourseCode', $CourseCode)
//             ->where('CounselGroup', $CollegeGroup)
//             ->value('CourseDesc') ?? '';
//     };

//     // -------------------------
//     // POST REQUEST
//     // -------------------------
//     if ($request->isMethod('post')) {

//         if ($request->input('action') === 'finalise') {
//             return redirect()
//                 ->route('print_upload_basicdetails')
//                 ->with('success', 'Finalised successfully!');
//         }

//         // Fetch existing minority/document record
//         $existingMinority = DB::table('basic_documents')
//             ->where('CollegeCode', $CollegeCode)
//             ->where('Year', $currentYear)
//             ->first();

//         // -------------------------
//         // VALIDATION
//         // -------------------------
//         $rules = [
//             'coursecode' => 'required|array|min:1',
//             'university' => 'required|in:Y,N',
//             'cgender'    => 'required|in:M,W',
//             'minority'   => 'required|in:Y,N',
//             'remarks'    => 'nullable|string|max:1000',
//         ];

//         $messages = [
//             'minoritydocs.required' => 'Please upload Minority document.',
//             'genderdocs.required'   => 'Please upload Gender document.',
//             'unidocs.required'      => 'Please upload University document.',
//             'minoritydocs.mimes'    => 'Minority document must be a PDF, JPG, or PNG file.',
//             'genderdocs.mimes'      => 'Gender document must be a PDF, JPG, or PNG file.',
//             'unidocs.mimes'         => 'University document must be a PDF, JPG, or PNG file.',
//             'minoritydocs.max'      => 'Minority document size must not exceed 2 MB.',
//             'genderdocs.max'        => 'Gender document size must not exceed 2 MB.',
//             'unidocs.max'           => 'University document size must not exceed 2 MB.',
//         ];

//         if ($request->minority === 'Y' && empty($existingMinority->Minority ?? null)) {
//             $rules['minoritydocs'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:2048';
//         }

//         if (empty($existingMinority->Gender ?? null)) {
//             $rules['genderdocs'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:2048';
//         }

//         if ($request->university === 'Y' && empty($existingMinority->University ?? null)) {
//             $rules['unidocs'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:2048';
//         }

//         $request->validate($rules, $messages);

//         // -------------------------
//         // YEAR ROLLOVER LOGIC
//         // -------------------------
//         $existsCurrentYear = DB::table('basicdetails')
//             ->where('CollegeCode', $CollegeCode)
//             ->where('Year', $currentYear)
//             ->exists();

//         if (!$existsCurrentYear) {
//             $previousYear = DB::table('basicdetails')
//                 ->where('CollegeCode', $CollegeCode)
//                 ->max('Year');

//             if ($previousYear) {
//                 $previousRows = DB::table('basicdetails')
//                     ->where('CollegeCode', $CollegeCode)
//                     ->where('Year', $previousYear)
//                     ->get();

//                 foreach ($previousRows as $row) {
//                     $newRow = (array) $row;
//                     unset($newRow['id']);
//                     $newRow['Year'] = $currentYear;
//                     $newRow['UpdateTime'] = now();
//                     $newRow['EmployeeID'] = $EmployeeCd;
//                     DB::table('basicdetails')->insert($newRow);
//                 }
//             }
//         }

//         // -------------------------
//         // SAVE CURRENT YEAR DATA FOR EACH COURSE
//         // -------------------------
//         foreach ($request->coursecode as $course) {
//             $data = [
//                 'Govt'        => $request->input("Govt_$course", 0),
//                 'KTU'         => $request->input("KTU_$course", 0),
//                 'AICTE'       => $request->input("AICTE_$course", 0),
//                 'BCI'         => $request->input("BCI_$course", 0),
//                 'TRUST'       => $request->input("TRUST_$course", 0),
//                 'MGMTC'       => $request->input("MGMTC_$course", 0),
//                 'TOTAL'       => $request->input("TOTAL_$course", 0),
//                 'EWS'         => $request->input("EWS_$course", 0),
//                 'Fees'        => $request->input("Fees_$course", 0),
//                 'Lfee'        => $request->input("Lfee_$course", 0),
//                 'other_fees'  => $request->input("other_fees_$course", 0),
//                 'Minority'        => $request->minority,
//                 'Minority_status' => $request->minority === 'Y'
//                                     ? ($request->minority_status ?? 'N')
//                                     : 'N',
//                 'University' => $request->university,
//                 'cgender'    => $request->cgender,
//                 'remarks'    => trim($request->remarks) ?: 'Verified',
//                 'EmployeeID' => $EmployeeCd,
//                 'UpdateTime' => now(),
//                 'Status'     => $request->input("Status_$course", 'N'),
//                 'Year'       => $currentYear,
//                 'Newcourse'  => 'N',
//                 'NMC'        => $request->input("NMC_$course", 0),
//                 'AIQ'        => $request->input("AIQ_$course", 0),
//                 'ICAR'       => $request->input("ICAR_$course", 0),
//                 'Nri_seat'   => $request->input("Nri_seat_$course", 0),
//                 'gen_fees'   => $request->input("gen_fees_$course", 0),
//                 'Nri_fees'   => $request->input("Nri_fees_$course", 0),
//                 'Nri_min'    => $request->input("Nri_min_$course", 0),
//                 'minority_seat'=> $request->input("minority_seat_$course", 0),
//                 'CollegeCode'=> $CollegeCode,
//                 'CourseCode' => $course,
//             ];

//             DB::table('basicdetails')->updateOrInsert(
//                 [
//                     'Year'        => $currentYear,
//                     'CollegeCode' => $CollegeCode,
//                     'CourseCode'  => $course,
//                 ],
//                 $data
//             );
//         }

//         // -------------------------
//         // UPDATE COLLEGE GENDER
//         // -------------------------
//         if ($request->input('cgender')) {
//             DB::table('collegedetails')
//                 ->where('CollegeCode', $CollegeCode)
//                 ->update(['CollegeGender' => $request->cgender]);
//         }

//         // -------------------------
//         // MINORITY DOCUMENTS
//         // -------------------------
//         $minorityData = [
//             'CollegeCode'    => $CollegeCode,
//             'Year'           => $currentYear,
//             'MinorityStatus' => $request->minority,
//             'UpdateTime'     => now(),
//         ];

//         $existingCols = ['Minority','Gender','Approval','Other','University','Government','Upload'];
//         if ($existingMinority) {
//             foreach ($existingCols as $col) {
//                 if (!empty($existingMinority->$col)) {
//                     $minorityData[$col] = $existingMinority->$col;
//                 }
//             }
//         }

//         $uploadMap = [
//             'minoritydocs' => 'Minority',
//             'genderdocs'   => 'Gender',
//             'approvaldocs' => 'Approval',
//             'otherdocs'    => 'Other',
//             'unidocs'      => 'University',
//             'govtdocs'     => 'Government',
//         ];

//         foreach ($uploadMap as $input => $col) {
//             if ($request->hasFile($input)) {
//                 $file = $request->file($input);
//                 $filename = time().'_'.$file->getClientOriginalName();
//                 $minorityData[$col] = $file->storeAs('minority_docs', $filename, 'public');
//             }
//         }

//         DB::table('basic_documents')->updateOrInsert(
//             [
//                 'CollegeCode' => $CollegeCode,
//                 'Year'        => $currentYear,
//             ],
//             $minorityData
//         );

//         return redirect()->back()->with('success', 'Details saved for year '.$currentYear);
//     }

//     // -------------------------
//     // GET REQUEST
//     // -------------------------
//     $row_coldetails = DB::table('collegedetails')
//         ->where('CollegeCode', $CollegeCode)
//         ->first();

//     $sourceYear = DB::table('basicdetails')
//         ->where('CollegeCode', $CollegeCode)
//         ->max('Year');

//     $displayYear = $sourceYear ?: $currentYear;

//     $basicDetails = DB::table('basicdetails')
//         ->where('CollegeCode', $CollegeCode)
//         ->where('Year', $displayYear)
//         ->get()
//         ->keyBy('CourseCode');

//     $qry_courses = DB::table('coursedetails')
//         ->where('CollegeCode', $CollegeCode)
//         ->pluck('CourseCode');

//     $coursedetails = DB::table('coursedetails')
//         ->whereIn('CourseCode', $qry_courses)
//         ->get();

//     $firstBasic = $basicDetails->first();
//     $uniStatus = $firstBasic->University ?? '';
//     $minStatus = $firstBasic->Minority ?? '';
//     $minority_status = $firstBasic->Minority_status ?? '';

//     $minoritydocs = DB::table('basic_documents')
//         ->where('CollegeCode', $CollegeCode)
//         ->where('Year', $displayYear)
//         ->first();

//     $gender = trim($row_coldetails->CollegeGender ?? '');
//     $genderCode = strtoupper($gender);
//     $genderDesc = DB::table('college_genders')
//         ->where('code', $genderCode)
//         ->value('description');

//     $genderOptions = DB::table('college_genders')->get();
//     $minorityReligions = DB::table('minority_religions')->pluck('name');

//     return view('CAP.basicdetails_law', compact(
//         'CollegeCode','CollegeGroup','CollegeType',
//         'row_coldetails','qry_courses','coursedetails',
//         'basicDetails','getCourseName',
//         'uniStatus','minStatus','minority_status',
//         'minoritydocs','gender','genderCode','genderDesc',
//         'genderOptions','minorityReligions'
//     ));
// }
public function basicdetails_law(Request $request)
    {
        // $CollegeCode  = session('CollegeCode');
        // $CollegeGroup = session('CollegeGroup');
        // $CollegeType  = session('CollegeType');
        // $currentYear  = date('Y');
        // $EmployeeCd   = Auth::user()->EmployeeCd ?? null;

        // if (!$CollegeCode) {
        //     abort(403, 'College code not found in session.');
        // }

        // // -------------------------
        // // HELPER: Course name
        // // -------------------------
        // $getCourseName = function ($CourseCode) use ($CollegeGroup) {
        //     return DB::table('coursemaster')
        //         ->where('CourseCode', $CourseCode)
        //         ->where('CounselGroup', $CollegeGroup)
        //         ->value('CourseDesc') ?? '';
        // };

        // // -------------------------
        // // POST REQUEST
        // // -------------------------
        // if ($request->isMethod('post')) {

        //     if ($request->input('action') === 'finalise') {
        //         return redirect()
        //             ->route('print_upload_basicdetails')
        //             ->with('success', 'Finalised successfully!');
        //     }

        //     // Fetch existing minority/document record for current year
        //     $existingMinority = DB::table('basic_documents')
        //         ->where('CollegeCode', $CollegeCode)
        //         ->where('Year', $currentYear)
        //         ->first();

        //     // -------------------------
        //     // VALIDATION
        //     // -------------------------
        //     $rules = [
        //         'coursecode' => 'required|array|min:1',
        //         'university' => 'required|in:Y,N',
        //         'cgender'    => 'required|in:M,W',
        //         'minority'   => 'required|in:Y,N',
        //         'remarks'    => 'nullable|string|max:1000',
        //     ];

        //     $messages = [
        //         'minoritydocs.required' => 'Please upload Minority document.',
        //         'genderdocs.required'   => 'Please upload Gender document.',
        //         'unidocs.required'      => 'Please upload University document.',
        //         'minoritydocs.mimes'    => 'Minority document must be a PDF, JPG, or PNG file.',
        //         'genderdocs.mimes'      => 'Gender document must be a PDF, JPG, or PNG file.',
        //         'unidocs.mimes'         => 'University document must be a PDF, JPG, or PNG file.',
        //         'minoritydocs.max'      => 'Minority document size must not exceed 2 MB.',
        //         'genderdocs.max'        => 'Gender document size must not exceed 2 MB.',
        //         'unidocs.max'           => 'University document size must not exceed 2 MB.',
        //     ];

        //     if ($request->minority === 'Y' && empty($existingMinority->Minority ?? null)) {
        //         $rules['minoritydocs'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:2048';
        //     }

        //     if (empty($existingMinority->Gender ?? null)) {
        //         $rules['genderdocs'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:2048';
        //     }

        //     if ($request->university === 'Y' && empty($existingMinority->University ?? null)) {
        //         $rules['unidocs'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:2048';
        //     }

        //     $request->validate($rules, $messages);

        //     // -------------------------
        //     // YEAR ROLLOVER LOGIC
        //     // -------------------------
        //     $existsCurrentYear = DB::table('basicdetails')
        //         ->where('CollegeCode', $CollegeCode)
        //         ->where('Year', $currentYear)
        //         ->exists();

        //     if (!$existsCurrentYear) {
        //         $previousYear = DB::table('basicdetails')
        //             ->where('CollegeCode', $CollegeCode)
        //             ->max('Year');

        //         if ($previousYear) {
        //             $previousRows = DB::table('basicdetails')
        //                 ->where('CollegeCode', $CollegeCode)
        //                 ->where('Year', $previousYear)
        //                 ->get();

        //             foreach ($previousRows as $row) {
        //                 $newRow = (array) $row;
        //                 unset($newRow['id']);
        //                 $newRow['Year'] = $currentYear;
        //                 $newRow['UpdateTime'] = now();
        //                 $newRow['EmployeeID'] = $EmployeeCd;
        //                 DB::table('basicdetails')->insert($newRow);
        //             }
        //         }
        //     }

        //     // -------------------------
        //     // SAVE CURRENT YEAR DATA FOR EACH COURSE
        //     // -------------------------
        //     foreach ($request->coursecode as $course) {
        //         $data = [
        //             'Govt'        => $request->input("Govt_$course", 0),
        //             'KTU'         => $request->input("KTU_$course", 0),
        //             'AICTE'       => $request->input("AICTE_$course", 0),
        //             'BCI'         => $request->input("BCI_$course", 0),
        //             'TRUST'       => $request->input("TRUST_$course", 0),
        //             'MGMTC'       => $request->input("MGMTC_$course", 0),
        //             'TOTAL'       => $request->input("TOTAL_$course", 0),
        //             'EWS'         => $request->input("EWS_$course", 0),
        //             'Fees'        => $request->input("Fees_$course", 0),
        //             'Lfee'        => $request->input("Lfee_$course", 0),
        //             'other_fees'  => $request->input("other_fees_$course", 0),
        //             'Minority'        => $request->minority,
        //             'Minority_status' => $request->minority === 'Y'
        //                                 ? ($request->minority_status ?? 'N')
        //                                 : 'N',
        //             'University' => $request->university,
        //             'cgender'    => $request->cgender,
        //             'remarks'    => trim($request->remarks) ?: 'Verified',
        //             'EmployeeID' => $EmployeeCd,
        //             'UpdateTime' => now(),
        //             'Status'     => $request->input("Status_$course", 'N'),
        //             'Year'       => $currentYear,
        //             'Newcourse'  => 'N',
        //             'NMC'        => $request->input("NMC_$course", 0),
        //             'AIQ'        => $request->input("AIQ_$course", 0),
        //             'ICAR'       => $request->input("ICAR_$course", 0),
        //             'Nri_seat'   => $request->input("Nri_seat_$course", 0),
        //             'gen_fees'   => $request->input("gen_fees_$course", 0),
        //             'Nri_fees'   => $request->input("Nri_fees_$course", 0),
        //             'Nri_min'    => $request->input("Nri_min_$course", 0),
        //             'minority_seat'=> $request->input("minority_seat_$course", 0),
        //             'CollegeCode'=> $CollegeCode,
        //             'CourseCode' => $course,
        //         ];

        //         DB::table('basicdetails')->updateOrInsert(
        //             [
        //                 'Year'        => $currentYear,
        //                 'CollegeCode' => $CollegeCode,
        //                 'CourseCode'  => $course,
        //             ],
        //             $data
        //         );
        //     }

        //     // -------------------------
        //     // UPDATE COLLEGE GENDER
        //     // -------------------------
        //     if ($request->input('cgender')) {
        //         DB::table('collegedetails')
        //             ->where('CollegeCode', $CollegeCode)
        //             ->update(['CollegeGender' => $request->cgender]);
        //     }

        //     // -------------------------
        //     // MINORITY DOCUMENTS
        //     // -------------------------
        //     $minorityData = [
        //         'CollegeCode'    => $CollegeCode,
        //         'Year'           => $currentYear,
        //         'MinorityStatus' => $request->minority,
        //         'UpdateTime'     => now(),
        //         'Minority'       => $existingMinority->Minority ?? '',
        //         'Gender'         => $existingMinority->Gender ?? '',
        //         'Approval'       => $existingMinority->Approval ?? '',
        //         'Other'          => $existingMinority->Other ?? '',
        //         'University'     => $existingMinority->University ?? '',
        //         'Government'     => $existingMinority->Government ?? '',
        //         'Upload'         => $existingMinority->Upload ?? '',
        //     ];

        //     // Map uploaded files
        //     $uploadMap = [
        //         'minoritydocs' => 'Minority',
        //         'genderdocs'   => 'Gender',
        //         'approvaldocs' => 'Approval',
        //         'otherdocs'    => 'Other',
        //         'unidocs'      => 'University',
        //         'govtdocs'     => 'Government',
        //         'uploaddocs'   => 'Upload', // general Upload
        //     ];

        //     foreach ($uploadMap as $input => $col) {
        //         if ($request->hasFile($input)) {
        //             $file = $request->file($input);
        //             $filename = time().'_'.$file->getClientOriginalName();
        //             $minorityData[$col] = $file->storeAs('minority_docs', $filename, 'public');
        //         }
        //     }

        //     DB::table('basic_documents')->updateOrInsert(
        //         [
        //             'CollegeCode' => $CollegeCode,
        //             'Year'        => $currentYear,
        //         ],
        //         $minorityData
        //     );

        //     return redirect()->back()->with('success', 'Details saved for year '.$currentYear);
        // }

        // // -------------------------
        // // GET REQUEST
        // // -------------------------
        // $row_coldetails = DB::table('collegedetails')
        //     ->where('CollegeCode', $CollegeCode)
        //     ->first();

        // $sourceYear = DB::table('basicdetails')
        //     ->where('CollegeCode', $CollegeCode)
        //     ->max('Year');

        // $displayYear = $sourceYear ?: $currentYear;

        // $basicDetails = DB::table('basicdetails')
        //     ->where('CollegeCode', $CollegeCode)
        //     ->where('Year', $displayYear)
        //     ->get()
        //     ->keyBy('CourseCode');

        // $qry_courses = DB::table('coursedetails')
        //     ->where('CollegeCode', $CollegeCode)
        //     ->pluck('CourseCode');

        // $coursedetails = DB::table('coursedetails')
        //     ->whereIn('CourseCode', $qry_courses)
        //     ->get();

        // $firstBasic = $basicDetails->first();
        // $uniStatus = $firstBasic->University ?? '';
        // $minStatus = $firstBasic->Minority ?? '';
        // $minority_status = $firstBasic->Minority_status ?? '';

        // // -------------------------
        // // Fetch documents
        // // -------------------------
        // $minoritydocs = DB::table('basic_documents')
        //     ->where('CollegeCode', $CollegeCode)
        //     ->where('Year', $displayYear)
        //     ->first();

        // // -------------------------
        // // Remarks
        // // -------------------------
        // $row_remarks = DB::table('basicdetails')
        //     ->where('CollegeCode', $CollegeCode)
        //     ->where('Year', $displayYear)
        //     ->first();
        // $Remarks = $row_remarks->remarks ?? '';

        // // -------------------------
        // // Minority Religions
        // // -------------------------
        // $minorityReligions = DB::table('minority_religions')->pluck('name');

        // // -------------------------
        // // College Gender Info
        // // -------------------------
        // $gender = trim($row_coldetails->CollegeGender ?? '');
        // $genderCode = strtoupper($gender);
        // $genderDesc = DB::table('college_genders')
        //     ->where('code', $genderCode)
        //     ->value('description');
        // $genderOptions = DB::table('college_genders')->get();

        // // -------------------------
        // // FINALIZED FLAG
        // // -------------------------
        // $basicDetailsRecord = DB::table('basicdetails')
        //     ->where('CollegeCode', $CollegeCode)
        //     ->where('Year', $displayYear)
        //     ->first();
        // $isFinalised = ($basicDetailsRecord->Status ?? '') === 'Y';

        // return view('CAP.basicdetails_law', compact(
        //     'CollegeCode','CollegeGroup','CollegeType',
        //     'row_coldetails','qry_courses','coursedetails',
        //     'basicDetails','getCourseName',
        //     'uniStatus','minStatus','minority_status',
        //     'minoritydocs','gender','genderCode','genderDesc',
        //     'genderOptions','minorityReligions',
        //     'isFinalised',
        //     'displayYear',
        //     'Remarks',
        //     'row_remarks'
        // ));
        $CollegeCode  = session('CollegeCode');
        $CollegeGroup = session('CollegeGroup');
        $CollegeType  = session('CollegeType');
        $EmployeeCd   = Auth::user()->EmployeeCd ?? null;
        $currentYear  = date('Y');
    
        // ================================
        // Helper function to get course name
        // ================================
        $getCourseName = function ($CourseCode) use ($CollegeGroup) {
            $res = DB::table('coursemaster')
                ->select('CourseDesc')
                ->where('CourseCode', $CourseCode)
                ->where('CounselGroup', $CollegeGroup)
                ->first();
            return $res ? $res->CourseDesc : '';
        };
    
        // ================================
        // POST Request Handling
        // ================================
        if ($request->isMethod('post')) {
    
            $action = $request->input('action'); // 'save' or 'finalise'
    
            if ($action === 'finalise') {
                return redirect()->route('print_upload_basicdetails')
                                 ->with('success', 'Finalised successfully!');
            }
    
            $year = $currentYear;
    
            // Fetch existing documents for validations
            $existingMinority = DB::table('basic_documents')
                ->where('CollegeCode', $CollegeCode)
                ->where('Year', $currentYear)
                ->first();
    
            // ================================
            // Validation rules
            // ================================
            $rules = [
                'coursecode' => 'required|array|min:1',
                'university' => 'required|in:Y,N',
                'cgender'    => 'required|in:M,W',
                'minority'   => 'required|in:Y,N',
                'remarks'    => 'nullable|string|max:1000',
            ];
    
            $messages = [
                'minoritydocs.required' => 'Please upload Minority document.',
                'genderdocs.required'   => 'Please upload Gender document.',
                'unidocs.required'      => 'Please upload University document.',
                'minoritydocs.mimes'    => 'Minority document must be a PDF, JPG, or PNG file.',
                'genderdocs.mimes'      => 'Gender document must be a PDF, JPG, or PNG file.',
                'unidocs.mimes'         => 'University document must be a PDF, JPG, or PNG file.',
                'minoritydocs.max'      => 'Minority document size must not exceed 2 MB.',
                'genderdocs.max'        => 'Gender document size must not exceed 2 MB.',
                'unidocs.max'           => 'University document size must not exceed 2 MB.',
            ];
    
            if ($request->input('minority') === 'Y' && empty($existingMinority->Minority ?? null)) {
                $rules['minoritydocs'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:2048';
            }
            if (empty($existingMinority->Gender ?? null)) {
                $rules['genderdocs'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:2048';
            }
            if ($request->input('university') === 'Y' && empty($existingMinority->University ?? null)) {
                $rules['unidocs'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:2048';
            }
            if (empty($existingMinority->Other ?? null)) {
                $rules['otherdocs'] = 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048';
            }
    
            $request->validate($rules, $messages);
    
            // ================================
            // Insert/Update basic details (CURRENT YEAR)
            // ================================
            $remarks = is_string($request->input('remarks')) ? trim($request->input('remarks')) : null;
    
            foreach ($request->input('coursecode', []) as $course) {
                $minorityFlag   = $request->input('minority', 'N');
                $minorityStatus = $minorityFlag === 'Y' ? $request->input('minority_status', null) : 'N';
    
                // ======= FULL $data block =======
                $data = [
                    'Govt'            => $request->input('Govt_'.$course, 0),
                    'KTU'             => $request->input('KTU_'.$course, 0),
                    'AICTE'           => $request->input('AICTE_'.$course, 0),
                    'BCI'             => $request->input('BCI_'.$course, 0),
                    'PKTU'            => $request->input('PKTU_'.$course, 0),
                    'TRUST'           => $request->input('TRUST_'.$course, 0),
                    'MGMTC'           => $request->input('MGMTC_'.$course, 0),
                    'MGM'             => $request->input('MGM_'.$course, 0),
                    'MNGCE'           => $request->input('MNGCE_'.$course, 0),
                    'TOTAL'           => $request->input('TOTAL_'.$course, 0),
                    'Minority'        => $minorityFlag,
                    'Minority_status' => $minorityStatus,
                    'University'      => $request->input('university', 'N'),
                    'auto'            => $request->input('auto', 0),
                    'cgender'         => $request->input('cgender', ''),
                    'Fees'            => $request->input('Fees_'.$course, 0),
                    'Lfee'            => $request->input('Lfee_'.$course, 0),
                    'Hfee'            => $request->input('Hfee_'.$course, 0),
                    'Regulated_Fees'  => $request->input('Regulated_Fees_'.$course, 0),
                    'ICAR'            => $request->input('ICAR_'.$course, 0),
                    'remarks'         => $remarks,
                    'EmployeeID'      => $EmployeeCd,
                    'UpdateTime'      => now(),
                    'Year'            => $currentYear, // ALWAYS CURRENT YEAR
                    'Newcourse'       => "N",
                    'NMC'             => $request->input("NMC_$course") ?? 0,
                    'AIQ'             => $request->input("AIQ_$course") ?? 0,
                    'ICAR'            => $request->input("ICAR_$course") ?? 0,
                    'Nri_seat'        => $request->input("Nri_seat_$course") ?? 0,
                    'gen_fees'        => $request->input("gen_fees_$course") ?? 0,
                    'Nri_fees'        => $request->input("Nri_fees_$course") ?? 0,
                    'Nri_min'         => $request->input("Nri_min_$course") ?? 0,
                    'minority_seat'   => $request->input("minority_seat_$course") ?? 0,
                    'Status'          => $request->input("Status_$course") ?? 'N',
                    'other_fees'      => $request->input("other_fees_$course") ?? 0,
                    'EWS'             => $request->input("EWS_$course") ?? 0,
                    'KUHS'            => $request->input("KUHS_$course") ?? 0,
                    'CollegeCode'     => $CollegeCode,
                    'CourseCode'      => $course
                ];
    
                DB::table('basicdetails')->updateOrInsert(
                    ['Year' => $currentYear, 'CollegeCode' => $CollegeCode, 'CourseCode' => $course],
                    $data
                );
            }
    
            // Update college gender if changed
            if ($updatedGender = $request->input('cgender', null)) {
                DB::table('collegedetails')
                    ->where('CollegeCode', $CollegeCode)
                    ->update(['CollegeGender' => $updatedGender]);
            }
    
            // ================================
            // Minority table & uploads
            // ================================
            $minorityFlag   = $request->input('minority', 'N');
    
            $minorityData = [
                'CollegeCode'    => $CollegeCode,
                'MinorityStatus' => $minorityFlag,
                'UpdateTime'     => now(),
            ];
    
            $fileCols = ['Minority','Gender','Approval','Other','University','Government','Upload'];
            if ($existingMinority) {
                foreach ($fileCols as $col) {
                    $minorityData[$col] = $existingMinority->{$col} ?? '';
                }
            } else {
                foreach ($fileCols as $col) {
                    $minorityData[$col] = '';
                }
            }
    
            $uploadMap = [
                'minoritydocs' => 'Minority',
                'genderdocs'   => 'Gender',
                'approvaldocs' => 'Approval',
                'otherdocs'    => 'Other',
                'unidocs'      => 'University',
                'govtdocs'     => 'Government',
            ];
    
            foreach ($uploadMap as $input => $col) {
                if ($request->hasFile($input)) {
                    $file = $request->file($input);
                    $filename = time().'_'.$file->getClientOriginalName();
                    $path = $file->storeAs('minority_docs', $filename, 'public');
                    $minorityData[$col] = $path;
                }
            }
    
            $minorityData['Year'] = $currentYear;
    
            DB::table('basic_documents')->updateOrInsert(
                ['CollegeCode' => $CollegeCode, 'Year' => $currentYear],
                $minorityData
            );
    
            return redirect()->back()->with('success', 'Details and uploaded documents saved for current year!');
        }
    

    // ================================
    // GET Section - Display Form
    // ================================
    $latestYearData = DB::table('basicdetails')
        ->where('CollegeCode', $CollegeCode)
        ->orderByDesc('Year')
        ->first();

    $displayYear = $latestYearData ? $latestYearData->Year : $currentYear;

    $row_coldetails = DB::table('collegedetails')->where('CollegeCode', $CollegeCode)->first();
    $row_remarks    = DB::table('basicdetails')->where('CollegeCode', $CollegeCode)->where('Year', $displayYear)->first();
    $qry_courses    = DB::table('coursedetails')->where('CollegeCode', $CollegeCode)->pluck('CourseCode');

    $basicDetails = DB::table('basicdetails')
        ->where('CollegeCode', $CollegeCode)
        ->where('Year', $displayYear)
        ->get()
        ->keyBy('CourseCode');

    $uniStatus       = $basicDetails->first()->University ?? '';
    $gender          = trim($row_coldetails->CollegeGender ?? '');
    $minStatus       = $basicDetails->first()->Minority ?? '';
    $minority_status = $basicDetails->first()->Minority_status ?? '';

    $minoritydocs = DB::table('basic_documents')
        ->where('CollegeCode', $CollegeCode)
        ->where('Year', $displayYear)
        ->first();

    $genUpload = $minUpload = $aprUpload = $othUpload = $uniUpload = $govUpload = 0;
    if ($minoritydocs) {
        $genUpload = !empty($minoritydocs->Gender) ? 1 : 0;
        $minUpload = !empty($minoritydocs->Minority) ? 1 : 0;
        $aprUpload = !empty($minoritydocs->Approval) ? 1 : 0;
        $othUpload = !empty($minoritydocs->Other) ? 1 : 0;
        $uniUpload = !empty($minoritydocs->University) ? 1 : 0;
        $govUpload = !empty($minoritydocs->Government) ? 1 : 0;
    }

    $minorityReligions = DB::table('minority_religions')->pluck('name');

    $genderCode = strtoupper($gender);
    $genderDesc = DB::table('college_genders')
        ->where('code', $genderCode)
        ->value('description');

    $genderOptions = DB::table('college_genders')->get();

    $basicDetailsRecord = DB::table('basicdetails')
        ->where('CollegeCode', $CollegeCode)
        ->where('Year', $displayYear)
        ->first();

    $isFinalised = ($basicDetailsRecord->Status ?? '') === 'Y';

    return view('CAP.basicdetails_law', compact(
        'CollegeCode', 'CollegeGroup', 'CollegeType',
        'row_coldetails', 'row_remarks', 'qry_courses',
        'basicDetails', 'getCourseName',
        'gender', 'genUpload', 'minUpload', 'uniStatus','genderCode','genderOptions','genderDesc',
        'aprUpload', 'othUpload', 'uniUpload', 'govUpload',
        'minoritydocs', 'minStatus', 'minority_status','minorityReligions',
        'displayYear', 'currentYear','isFinalised'
    ));}
/////////////medical

public function basicdetails_m(Request $request)
{
    $CollegeCode  = session('CollegeCode');
    $CollegeGroup = session('CollegeGroup');
    $CollegeType  = session('CollegeType');
    $EmployeeCd   = Auth::user()->EmployeeCd ?? null;
    $currentYear  = date('Y');

    // ================================
    // Helper function to get course name
    // ================================
    $getCourseName = function ($CourseCode) use ($CollegeGroup) {
        $res = DB::table('coursemaster')
            ->select('CourseDesc')
            ->where('CourseCode', $CourseCode)
            ->where('CounselGroup', $CollegeGroup)
            ->first();
        return $res ? $res->CourseDesc : '';
    };

    // ================================
    // POST Request Handling
    // ================================
    if ($request->isMethod('post')) {

        $action = $request->input('action'); // 'save' or 'finalise'

        if ($action === 'finalise') {
            return redirect()->route('print_upload_basicdetails')
                             ->with('success', 'Finalised successfully!');
        }

        $year = $currentYear;

        // Fetch existing documents for validations
        $existingMinority = DB::table('basic_documents')
            ->where('CollegeCode', $CollegeCode)
            ->where('Year', $currentYear)
            ->first();

        // ================================
        // Validation rules
        // ================================
        $rules = [
            'coursecode' => 'required|array|min:1',
            'university' => 'required|in:Y,N',
            'cgender'    => 'required|in:M,W',
            'minority'   => 'required|in:Y,N',
            'remarks'    => 'nullable|string|max:1000',
        ];

        $messages = [
            'minoritydocs.required' => 'Please upload Minority document.',
            'genderdocs.required'   => 'Please upload Gender document.',
            'unidocs.required'      => 'Please upload University document.',
            'minoritydocs.mimes'    => 'Minority document must be a PDF, JPG, or PNG file.',
            'genderdocs.mimes'      => 'Gender document must be a PDF, JPG, or PNG file.',
            'unidocs.mimes'         => 'University document must be a PDF, JPG, or PNG file.',
            'minoritydocs.max'      => 'Minority document size must not exceed 2 MB.',
            'genderdocs.max'        => 'Gender document size must not exceed 2 MB.',
            'unidocs.max'           => 'University document size must not exceed 2 MB.',
        ];

        if ($request->input('minority') === 'Y' && empty($existingMinority->Minority ?? null)) {
            $rules['minoritydocs'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:2048';
        }
        if (empty($existingMinority->Gender ?? null)) {
            $rules['genderdocs'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:2048';
        }
        if ($request->input('university') === 'Y' && empty($existingMinority->University ?? null)) {
            $rules['unidocs'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:2048';
        }
        if (empty($existingMinority->Other ?? null)) {
            $rules['otherdocs'] = 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048';
        }

        $request->validate($rules, $messages);

        // ================================
        // Insert/Update basic details (CURRENT YEAR)
        // ================================
        $remarks = is_string($request->input('remarks')) ? trim($request->input('remarks')) : null;

        foreach ($request->input('coursecode', []) as $course) {
            $minorityFlag   = $request->input('minority', 'N');
            $minorityStatus = $minorityFlag === 'Y' ? $request->input('minority_status', null) : 'N';

            // ======= FULL $data block =======
            $data = [
                'Govt'            => $request->input('Govt_'.$course, 0),
                'KTU'             => $request->input('KTU_'.$course, 0),
                'AICTE'           => $request->input('AICTE_'.$course, 0),
                'BCI'             => $request->input('BCI_'.$course, 0),
                'PKTU'            => $request->input('PKTU_'.$course, 0),
                'TRUST'           => $request->input('TRUST_'.$course, 0),
                'MGMTC'           => $request->input('MGMTC_'.$course, 0),
                'MGM'             => $request->input('MGM_'.$course, 0),
                'MNGCE'           => $request->input('MNGCE_'.$course, 0),
                'TOTAL'           => $request->input('TOTAL_'.$course, 0),
                'Minority'        => $minorityFlag,
                'Minority_status' => $minorityStatus,
                'University'      => $request->input('university', 'N'),
                'auto'            => $request->input('auto', 0),
                'cgender'         => $request->input('cgender', ''),
                'Fees'            => $request->input('Fees_'.$course, 0),
                'Lfee'            => $request->input('Lfee_'.$course, 0),
                'Hfee'            => $request->input('Hfee_'.$course, 0),
                'Regulated_Fees'  => $request->input('Regulated_Fees_'.$course, 0),
                'ICAR'            => $request->input('ICAR_'.$course, 0),
                'remarks'         => $remarks,
                'EmployeeID'      => $EmployeeCd,
                'UpdateTime'      => now(),
                'Year'            => $currentYear, // ALWAYS CURRENT YEAR
                'Newcourse'       => "N",
                'NMC'             => $request->input("NMC_$course") ?? 0,
                'AIQ'             => $request->input("AIQ_$course") ?? 0,
                'ICAR'            => $request->input("ICAR_$course") ?? 0,
                'Nri_seat'        => $request->input("Nri_seat_$course") ?? 0,
                'gen_fees'        => $request->input("gen_fees_$course") ?? 0,
                'Nri_fees'        => $request->input("Nri_fees_$course") ?? 0,
                'Nri_min'         => $request->input("Nri_min_$course") ?? 0,
                'minority_seat'   => $request->input("minority_seat_$course") ?? 0,
                'Status'          => $request->input("Status_$course") ?? 'N',
                'other_fees'      => $request->input("other_fees_$course") ?? 0,
                'EWS'             => $request->input("EWS_$course") ?? 0,
                'KUHS'            => $request->input("KUHS_$course") ?? 0,
                'CollegeCode'     => $CollegeCode,
                'CourseCode'      => $course
            ];

            DB::table('basicdetails')->updateOrInsert(
                ['Year' => $currentYear, 'CollegeCode' => $CollegeCode, 'CourseCode' => $course],
                $data
            );
        }

        // Update college gender if changed
        if ($updatedGender = $request->input('cgender', null)) {
            DB::table('collegedetails')
                ->where('CollegeCode', $CollegeCode)
                ->update(['CollegeGender' => $updatedGender]);
        }

        // ================================
        // Minority table & uploads
        // ================================
        $minorityFlag   = $request->input('minority', 'N');

        $minorityData = [
            'CollegeCode'    => $CollegeCode,
            'MinorityStatus' => $minorityFlag,
            'UpdateTime'     => now(),
        ];

        $fileCols = ['Minority','Gender','Approval','Other','University','Government','Upload'];
        if ($existingMinority) {
            foreach ($fileCols as $col) {
                $minorityData[$col] = $existingMinority->{$col} ?? '';
            }
        } else {
            foreach ($fileCols as $col) {
                $minorityData[$col] = '';
            }
        }

        $uploadMap = [
            'minoritydocs' => 'Minority',
            'genderdocs'   => 'Gender',
            'approvaldocs' => 'Approval',
            'otherdocs'    => 'Other',
            'unidocs'      => 'University',
            'govtdocs'     => 'Government',
        ];

        foreach ($uploadMap as $input => $col) {
            if ($request->hasFile($input)) {
                $file = $request->file($input);
                $filename = time().'_'.$file->getClientOriginalName();
                $path = $file->storeAs('minority_docs', $filename, 'public');
                $minorityData[$col] = $path;
            }
        }

        $minorityData['Year'] = $currentYear;

        DB::table('basic_documents')->updateOrInsert(
            ['CollegeCode' => $CollegeCode, 'Year' => $currentYear],
            $minorityData
        );

        return redirect()->back()->with('success', 'Details and uploaded documents saved for current year!');
    }


// ================================
// GET Section - Display Form
// ================================
$latestYearData = DB::table('basicdetails')
    ->where('CollegeCode', $CollegeCode)
    ->orderByDesc('Year')
    ->first();

$displayYear = $latestYearData ? $latestYearData->Year : $currentYear;

$row_coldetails = DB::table('collegedetails')->where('CollegeCode', $CollegeCode)->first();
$row_remarks    = DB::table('basicdetails')->where('CollegeCode', $CollegeCode)->where('Year', $displayYear)->first();
$qry_courses    = DB::table('coursedetails')->where('CollegeCode', $CollegeCode)->pluck('CourseCode');

$basicDetails = DB::table('basicdetails')
    ->where('CollegeCode', $CollegeCode)
    ->where('Year', $displayYear)
    ->get()
    ->keyBy('CourseCode');

$uniStatus       = $basicDetails->first()->University ?? '';
$gender          = trim($row_coldetails->CollegeGender ?? '');
$minStatus       = $basicDetails->first()->Minority ?? '';
$minority_status = $basicDetails->first()->Minority_status ?? '';

$minoritydocs = DB::table('basic_documents')
    ->where('CollegeCode', $CollegeCode)
    ->where('Year', $displayYear)
    ->first();

$genUpload = $minUpload = $aprUpload = $othUpload = $uniUpload = $govUpload = 0;
if ($minoritydocs) {
    $genUpload = !empty($minoritydocs->Gender) ? 1 : 0;
    $minUpload = !empty($minoritydocs->Minority) ? 1 : 0;
    $aprUpload = !empty($minoritydocs->Approval) ? 1 : 0;
    $othUpload = !empty($minoritydocs->Other) ? 1 : 0;
    $uniUpload = !empty($minoritydocs->University) ? 1 : 0;
    $govUpload = !empty($minoritydocs->Government) ? 1 : 0;
}

$minorityReligions = DB::table('minority_religions')->pluck('name');

$genderCode = strtoupper($gender);
$genderDesc = DB::table('college_genders')
    ->where('code', $genderCode)
    ->value('description');

$genderOptions = DB::table('college_genders')->get();

$basicDetailsRecord = DB::table('basicdetails')
    ->where('CollegeCode', $CollegeCode)
    ->where('Year', $displayYear)
    ->first();

$isFinalised = ($basicDetailsRecord->Status ?? '') === 'Y';
$coursedetails = DB::table('coursedetails')
    ->where('CollegeCode', $CollegeCode)
    ->get(); // get full course objects

return view('CAP.basicdetails_m', compact(
    'CollegeCode', 'CollegeGroup', 'CollegeType',
    'row_coldetails', 'row_remarks', 'qry_courses',
    'basicDetails', 'getCourseName',
    'gender', 'genUpload', 'minUpload', 'uniStatus','genderCode','genderOptions','genderDesc',
    'aprUpload', 'othUpload', 'uniUpload', 'govUpload',
    'minoritydocs', 'minStatus', 'minority_status','minorityReligions',
    'displayYear', 'currentYear','isFinalised','coursedetails'
));}

//pharmacy

public function basicdetails_bp(Request $request)
{
    $CollegeCode  = session('CollegeCode');
    $CollegeGroup = session('CollegeGroup');
    $CollegeType  = session('CollegeType');
    $EmployeeCd   = Auth::user()->EmployeeCd ?? null;
    $currentYear  = date('Y');

    // ================================
    // Helper function to get course name
    // ================================
    $getCourseName = function ($CourseCode) use ($CollegeGroup) {
        $res = DB::table('coursemaster')
            ->select('CourseDesc')
            ->where('CourseCode', $CourseCode)
            ->where('CounselGroup', $CollegeGroup)
            ->first();
        return $res ? $res->CourseDesc : '';
    };

    // ================================
    // POST Request Handling
    // ================================
    if ($request->isMethod('post')) {

        $action = $request->input('action'); // 'save' or 'finalise'

        if ($action === 'finalise') {
            return redirect()->route('print_upload_basicdetails')
                             ->with('success', 'Finalised successfully!');
        }

        $year = $currentYear;

        // Fetch existing documents for validations
        $existingMinority = DB::table('basic_documents')
            ->where('CollegeCode', $CollegeCode)
            ->where('Year', $currentYear)
            ->first();

        // ================================
        // Validation rules
        // ================================
        $rules = [
            'coursecode' => 'required|array|min:1',
            'university' => 'required|in:Y,N',
            'cgender'    => 'required|in:M,W',
            'minority'   => 'required|in:Y,N',
            'remarks'    => 'nullable|string|max:1000',
        ];

        $messages = [
            'minoritydocs.required' => 'Please upload Minority document.',
            'genderdocs.required'   => 'Please upload Gender document.',
            'unidocs.required'      => 'Please upload University document.',
            'minoritydocs.mimes'    => 'Minority document must be a PDF, JPG, or PNG file.',
            'genderdocs.mimes'      => 'Gender document must be a PDF, JPG, or PNG file.',
            'unidocs.mimes'         => 'University document must be a PDF, JPG, or PNG file.',
            'minoritydocs.max'      => 'Minority document size must not exceed 2 MB.',
            'genderdocs.max'        => 'Gender document size must not exceed 2 MB.',
            'unidocs.max'           => 'University document size must not exceed 2 MB.',
        ];

        if ($request->input('minority') === 'Y' && empty($existingMinority->Minority ?? null)) {
            $rules['minoritydocs'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:2048';
        }
        if (empty($existingMinority->Gender ?? null)) {
            $rules['genderdocs'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:2048';
        }
        if ($request->input('university') === 'Y' && empty($existingMinority->University ?? null)) {
            $rules['unidocs'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:2048';
        }
        if (empty($existingMinority->Other ?? null)) {
            $rules['otherdocs'] = 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048';
        }

        $request->validate($rules, $messages);

        // ================================
        // Insert/Update basic details (CURRENT YEAR)
        // ================================
        $remarks = is_string($request->input('remarks')) ? trim($request->input('remarks')) : null;

        foreach ($request->input('coursecode', []) as $course) {
            $minorityFlag   = $request->input('minority', 'N');
            $minorityStatus = $minorityFlag === 'Y' ? $request->input('minority_status', null) : 'N';

            // ======= FULL $data block =======
            $data = [
                'Govt'            => $request->input('Govt_'.$course, 0),
                'KTU'             => $request->input('KTU_'.$course, 0),
                'AICTE'           => $request->input('AICTE_'.$course, 0),
                'BCI'             => $request->input('BCI_'.$course, 0),
                'PKTU'            => $request->input('PKTU_'.$course, 0),
                'TRUST'           => $request->input('TRUST_'.$course, 0),
                'MGMTC'           => $request->input('MGMTC_'.$course, 0),
                'MGM'             => $request->input('MGM_'.$course, 0),
                'MNGCE'           => $request->input('MNGCE_'.$course, 0),
                'TOTAL'           => $request->input('TOTAL_'.$course, 0),
                'Minority'        => $minorityFlag,
                'Minority_status' => $minorityStatus,
                'University'      => $request->input('university', 'N'),
                'auto'            => $request->input('auto', 0),
                'cgender'         => $request->input('cgender', ''),
                'Fees'            => $request->input('Fees_'.$course, 0),
                'Lfee'            => $request->input('Lfee_'.$course, 0),
                'Hfee'            => $request->input('Hfee_'.$course, 0),
                'Regulated_Fees'  => $request->input('Regulated_Fees_'.$course, 0),
                'ICAR'            => $request->input('ICAR_'.$course, 0),
                'remarks'         => $remarks,
                'EmployeeID'      => $EmployeeCd,
                'UpdateTime'      => now(),
                'Year'            => $currentYear, // ALWAYS CURRENT YEAR
                'Newcourse'       => "N",
                'NMC'             => $request->input("NMC_$course") ?? 0,
                'AIQ'             => $request->input("AIQ_$course") ?? 0,
                'ICAR'            => $request->input("ICAR_$course") ?? 0,
                'Nri_seat'        => $request->input("Nri_seat_$course") ?? 0,
                'gen_fees'        => $request->input("gen_fees_$course") ?? 0,
                'Nri_fees'        => $request->input("Nri_fees_$course") ?? 0,
                'Nri_min'         => $request->input("Nri_min_$course") ?? 0,
                'minority_seat'   => $request->input("minority_seat_$course") ?? 0,
                'Status'          => $request->input("Status_$course") ?? 'N',
                'other_fees'      => $request->input("other_fees_$course") ?? 0,
                'EWS'             => $request->input("EWS_$course") ?? 0,
                'KUHS'            => $request->input("KUHS_$course") ?? 0,
                'CollegeCode'     => $CollegeCode,
                'CourseCode'      => $course
            ];

            DB::table('basicdetails')->updateOrInsert(
                ['Year' => $currentYear, 'CollegeCode' => $CollegeCode, 'CourseCode' => $course],
                $data
            );
        }

        // Update college gender if changed
        if ($updatedGender = $request->input('cgender', null)) {
            DB::table('collegedetails')
                ->where('CollegeCode', $CollegeCode)
                ->update(['CollegeGender' => $updatedGender]);
        }

        // ================================
        // Minority table & uploads
        // ================================
        $minorityFlag   = $request->input('minority', 'N');

        $minorityData = [
            'CollegeCode'    => $CollegeCode,
            'MinorityStatus' => $minorityFlag,
            'UpdateTime'     => now(),
        ];

        $fileCols = ['Minority','Gender','Approval','Other','University','Government','Upload'];
        if ($existingMinority) {
            foreach ($fileCols as $col) {
                $minorityData[$col] = $existingMinority->{$col} ?? '';
            }
        } else {
            foreach ($fileCols as $col) {
                $minorityData[$col] = '';
            }
        }

        $uploadMap = [
            'minoritydocs' => 'Minority',
            'genderdocs'   => 'Gender',
            'approvaldocs' => 'Approval',
            'otherdocs'    => 'Other',
            'unidocs'      => 'University',
            'govtdocs'     => 'Government',
        ];

        foreach ($uploadMap as $input => $col) {
            if ($request->hasFile($input)) {
                $file = $request->file($input);
                $filename = time().'_'.$file->getClientOriginalName();
                $path = $file->storeAs('minority_docs', $filename, 'public');
                $minorityData[$col] = $path;
            }
        }

        $minorityData['Year'] = $currentYear;

        DB::table('basic_documents')->updateOrInsert(
            ['CollegeCode' => $CollegeCode, 'Year' => $currentYear],
            $minorityData
        );

        return redirect()->back()->with('success', 'Details and uploaded documents saved for current year!');
    }


// ================================
// GET Section - Display Form
// ================================
$latestYearData = DB::table('basicdetails')
    ->where('CollegeCode', $CollegeCode)
    ->orderByDesc('Year')
    ->first();

$displayYear = $latestYearData ? $latestYearData->Year : $currentYear;

$row_coldetails = DB::table('collegedetails')->where('CollegeCode', $CollegeCode)->first();
$row_remarks    = DB::table('basicdetails')->where('CollegeCode', $CollegeCode)->where('Year', $displayYear)->first();
$qry_courses    = DB::table('coursedetails')->where('CollegeCode', $CollegeCode)->pluck('CourseCode');

$basicDetails = DB::table('basicdetails')
    ->where('CollegeCode', $CollegeCode)
    ->where('Year', $displayYear)
    ->get()
    ->keyBy('CourseCode');

$uniStatus       = $basicDetails->first()->University ?? '';
$gender          = trim($row_coldetails->CollegeGender ?? '');
$minStatus       = $basicDetails->first()->Minority ?? '';
$minority_status = $basicDetails->first()->Minority_status ?? '';

$minoritydocs = DB::table('basic_documents')
    ->where('CollegeCode', $CollegeCode)
    ->where('Year', $displayYear)
    ->first();

$genUpload = $minUpload = $aprUpload = $othUpload = $uniUpload = $govUpload = 0;
if ($minoritydocs) {
    $genUpload = !empty($minoritydocs->Gender) ? 1 : 0;
    $minUpload = !empty($minoritydocs->Minority) ? 1 : 0;
    $aprUpload = !empty($minoritydocs->Approval) ? 1 : 0;
    $othUpload = !empty($minoritydocs->Other) ? 1 : 0;
    $uniUpload = !empty($minoritydocs->University) ? 1 : 0;
    $govUpload = !empty($minoritydocs->Government) ? 1 : 0;
}

$minorityReligions = DB::table('minority_religions')->pluck('name');

$genderCode = strtoupper($gender);
$genderDesc = DB::table('college_genders')
    ->where('code', $genderCode)
    ->value('description');

$genderOptions = DB::table('college_genders')->get();

$basicDetailsRecord = DB::table('basicdetails')
    ->where('CollegeCode', $CollegeCode)
    ->where('Year', $displayYear)
    ->first();

$isFinalised = ($basicDetailsRecord->Status ?? '') === 'Y';
$coursedetails = DB::table('coursedetails')
    ->where('CollegeCode', $CollegeCode)
    ->get(); // get full course objects

return view('CAP.basicdetails_bp', compact(
    'CollegeCode', 'CollegeGroup', 'CollegeType',
    'row_coldetails', 'row_remarks', 'qry_courses',
    'basicDetails', 'getCourseName',
    'gender', 'genUpload', 'minUpload', 'uniStatus','genderCode','genderOptions','genderDesc',
    'aprUpload', 'othUpload', 'uniUpload', 'govUpload',
    'minoritydocs', 'minStatus', 'minority_status','minorityReligions',
    'displayYear', 'currentYear','isFinalised','coursedetails'
));}


///////////////////////////////////
public function feedetails(Request $request)
{
    $CollegeCode  = session('CollegeCode');
    $CollegeGroup = session('CollegeGroup');

    /* ---------------------------------
     * Redirect checks (Finalize / Docs)
     * --------------------------------- */
    $courseDetails = DB::table('coursedetails')
        ->where('CollegeCode', $CollegeCode)
        ->first();

    if ($courseDetails && $courseDetails->Finalize === 'Y') {
        return redirect()->route('fee_details_completed');
    }

    $documents = DB::table('documents')
        ->where('CollegeCode', $CollegeCode)
        ->first();

    if ($documents && $documents->Doc1_Flag === 'Y') {
        return redirect()->route('show_fee_details');
    }

    $kcma_colleges = [
        'AIK','AJC','BJK','CCE','CMA','JEC','LMC','MBT',
        'MCE','RET','SHR','SJC','VJC','VML','MGP'
    ];

    $isKCMA = in_array($CollegeCode, $kcma_colleges);

    /* ---------------------------------
     * POST : Save Fee Details
     * --------------------------------- */
    if ($request->isMethod('post')) {

        $courses = DB::table('coursedetails')
            ->where('CollegeCode', $CollegeCode)
            ->pluck('CourseCode');

        DB::beginTransaction();

        try {

            foreach ($courses as $CourseCode) {

                /* ---------- INPUTS ---------- */
                $Govt_Tuition_LIG = (int) $request->input("Govt_Tuition_LIG_$CourseCode", 0);
                $Govt_Tuition     = (int) $request->input("Govt_Tuition_$CourseCode", 0);
                $Govt_Special     = (int) $request->input("Govt_Special_$CourseCode", 0);
                $Govt_Scholarship = (int) $request->input("Govt_Scholarship_$CourseCode", 0);
                $Govt_Deposit     = (int) $request->input("Govt_Deposit_$CourseCode", 0);

                $Mang_Tuition     = (int) $request->input("Mang_Tuition_$CourseCode", 0);
                $Mang_Special     = (int) $request->input("Mang_Special_$CourseCode", 0);
                $Mang_Deposit     = (int) $request->input("Mang_Deposit_$CourseCode", 0);

                $NRI_Tuition      = (int) $request->input("NRI_Tuition_$CourseCode", 0);
                $NRI_Special      = (int) $request->input("NRI_Special_$CourseCode", 0);
                $NRI_Deposit      = (int) $request->input("NRI_Deposit_$CourseCode", 0);

                /* ---------- VALIDATIONS ---------- */

                // Tuition rules
                if ($Govt_Tuition <= 0) {
                    return back()->withErrors('Tuition Fee must be greater than 0')->withInput();
                }

                if ($Govt_Tuition > 200500) {
                    return back()->withErrors('Tuition Fee should not exceed 200500')->withInput();
                }

                // LIG rules (Non-KCMA only)
                if (!$isKCMA && $Govt_Tuition_LIG <= 0) {
                    return back()->withErrors('LIG Tuition must be greater than 0')->withInput();
                }

                if (!$isKCMA && $Govt_Tuition_LIG > 200500) {
                    return back()->withErrors('LIG Tuition should not exceed 200500')->withInput();
                }

                // Special fee
                if ($Govt_Special > 200000) {
                    return back()->withErrors('Special Fee should not exceed 200000')->withInput();
                }

                // Scholarship
                if ($Govt_Scholarship > ($Govt_Tuition + $Govt_Special)) {
                    return back()->withErrors(
                        'Scholarship should not exceed Tuition + Special'
                    )->withInput();
                }

                // Deposit rules
                if ($isKCMA && $Govt_Deposit > 200000) {
                    return back()->withErrors(
                        'Deposit Fee should not exceed 200000'
                    )->withInput();
                }

                if (!$isKCMA && $Govt_Deposit != 0) {
                    return back()->withErrors(
                        'Deposit Fee must be zero for this college'
                    )->withInput();
                }

                /* ---------- ACTUAL FEE ---------- */
                $Actual_Fee = ($Govt_Tuition + $Govt_Special) - $Govt_Scholarship;

                if ($Actual_Fee < 0) {
                    $Actual_Fee = 0;
                }

                if ($Actual_Fee < $Govt_Tuition_LIG) {
                    return back()->withErrors(
                        'Actual Fee must be greater than or equal to LIG Tuition'
                    )->withInput();
                }

                /* ---------- UPDATE ---------- */
                DB::table('coursedetails')
                    ->where('CollegeCode', $CollegeCode)
                    ->where('CourseCode', $CourseCode)
                    ->update([
                        'Govt_Tuition_LIG' => $Govt_Tuition_LIG,
                        'Govt_Tuition'     => $Govt_Tuition,
                        'Govt_Special'     => $Govt_Special,
                        'Govt_Scholarship' => $Govt_Scholarship,
                        'Govt_Deposit'     => $Govt_Deposit,
                        'Actual_Fee'       => $Actual_Fee,

                        'Mang_Tuition'     => $Mang_Tuition,
                        'Mang_Special'     => $Mang_Special,
                        'Mang_Deposit'     => $Mang_Deposit,

                        'NRI_Tuition'      => $NRI_Tuition,
                        'NRI_Special'      => $NRI_Special,
                        'NRI_Deposit'      => $NRI_Deposit,

                        'UpdateTime'       => now(),
                    ]);
            }

            DB::commit();

            return redirect()
    ->route('show_fee_details')
    ->with('success', 'Fee details updated successfully');


        } catch (\Exception $e) {

            DB::rollBack();

            return back()->withErrors(
                'Something went wrong while saving fee details'
            )->withInput();
        }
    }

    /* ---------------------------------
     * GET : Display page
     * --------------------------------- */
    $courses = DB::table('coursedetails')
        ->where('CollegeCode', $CollegeCode)
        ->get();

    return view('CAP.fee_details', compact(
        'courses',
        'CollegeGroup',
        'CollegeCode',
        'kcma_colleges'
    ));
}

public function show_fee_details(Request $request)
{
    $CollegeCode  = Session::get('CollegeCode');
    $CollegeGroup = Session::get('CollegeGroup');

    Session::put('doc_id', 1);

    /* ---------- Handle Form Actions ---------- */
    if ($request->has('Edit')) {
        return redirect()->route('fee_details')
            ->with('success', 'You can now edit the fee details.');
    }

    if ($request->has('Finalize')) {
        DB::table('coursedetails')
            ->where('CollegeCode', $CollegeCode)
            ->update(['Finalize' => 'Y']);

        return redirect()->route('fee_details_completed')
            ->with('success', 'Fee details verified & finalized successfully.');
    }

    /* ---------- Data Queries ---------- */
    $courses = DB::table('coursedetails')
        ->where('CollegeCode', $CollegeCode)
        ->get();

    $upload = DB::table('fee_documents')
        ->where('CollegeCode', $CollegeCode)
        ->first();

    $kcma_colleges = [
        'AIK','AJC','BJK','CCE','CMA','JEC','LMC','MBT',
        'MCE','RET','SHR','SJC','VJC','VML','MGP'
    ];

    return view('CAP.show_fee_details', compact(
        'courses',
        'CollegeCode',
        'CollegeGroup',
        'kcma_colleges',
        'upload'
    ));
}


    /* ---------- Helper ---------- */
    public static function getCourseName($CourseCode, $CollegeGroup)
    {
        $course = DB::table('coursemaster')
            ->where('CourseCode', $CourseCode)
            ->where('CounselGroup', $CollegeGroup)
            ->first();

        return $course ? $course->CourseDesc : '';
    }


    public function feeDetailsCompleted(Request $request)
{
    $CollegeCode  = session('CollegeCode');
    $CollegeGroup = session('CollegeGroup');

    if (!$CollegeCode) {
        return redirect()->route('login');
    }

    $course = DB::table('coursedetails')
        ->where('CollegeCode', $CollegeCode)
        ->first();

    $document = DB::table('fee_documents')
        ->where('CollegeCode', $CollegeCode)
        ->first();  // <-- pass this to view

    $isFullySubmitted = (
        $course &&
        $course->Finalize === 'Y' &&
        $document &&
        $document->Doc1_Flag === 'Y'
    );

    return view('CAP.fee_details_completed', compact(
        'CollegeCode',
        'CollegeGroup',
        'course',
        'document',      // <-- pass it here
        'isFullySubmitted'
    ));
}



    public function viewFeeDetails()
    {
        $collegeCode = session('CollegeCode');

        if (!$collegeCode) {
            return redirect('/');
        }

        // Fetch college details
        $college = DB::table('collegedetails')
            ->where('CollegeCode', $collegeCode)
            ->first();

        // Update print flag
        DB::table('coursedetails')
            ->where('CollegeCode', $collegeCode)
            ->update(['Print' => 'Y']);

        // Fetch courses with CourseDesc
        $courses = DB::table('coursedetails as a')
            ->join('coursemaster as b', 'a.CourseCode', '=', 'b.CourseCode')
            ->where('b.CourseType', 'UG')
            ->where('a.CollegeCode', $collegeCode)
            ->select('a.*', 'b.CourseDesc')
            ->get();

        return view('CAP.fee-detailspdf', compact('college', 'courses', 'collegeCode'));
    }





    public function docsUpload(Request $request)
    {
        return view('CAP.docs_upload', [
            'a'     => $request->query('a'),
            'error' => $request->query('error'),
        ]);
    }

    /* ------------------------------
     * Store & Preview (Base64 in DB)
     * ------------------------------ */
    public function storeFeeDocument(Request $request)
    {
        $collegeCode = session('CollegeCode');
        $updatetime  = now();

        if (!$request->hasFile('image')) {
            return redirect()->route('docs_upload', ['error' => 2]);
        }

        $file = $request->file('image');

        // Size check (350 KB)
        if ($file->getSize() > 350000) {
            return redirect()->route('docs_upload', ['error' => 4]);
        }

        // Convert to base64
        $content = base64_encode(file_get_contents($file->getRealPath()));

        if (!$content) {
            return redirect()->route('docs_upload', ['error' => 2]);
        }

        // Insert / Update
        $exists = DB::table('fee_documents')
            ->where('CollegeCode', $collegeCode)
            ->exists();

        if ($exists) {
            DB::table('fee_documents')
                ->where('CollegeCode', $collegeCode)
                ->update([
                    'Doc1'            => $content,
                    'Doc1_UpdateTime' => $updatetime,
                ]);
        } else {
            DB::table('fee_documents')->insert([
                'CollegeCode'     => $collegeCode,
                'Doc1'            => $content,
                'Doc1_Flag'       => '',
                'Doc1_UpdateTime' => $updatetime,
            ]);
        }

        return redirect()->route('docs_upload', ['a' => 1]);
    }

    /* ------------------------------
     * Finalize Upload
     * ------------------------------ */
    public function finalizeFeeDocument(Request $request)
    {
        if (!$request->has('declaration')) {
            return redirect()->route('docs_upload');
        }

        $collegeCode = session('CollegeCode');

        DB::table('fee_documents')
            ->where('CollegeCode', $collegeCode)
            ->update(['Doc1_Flag' => 'Y']);

        return redirect()->route('show_fee_details');
    }

    /* ------------------------------
     * View PDF from DB
     * ------------------------------ */
    public function docsView()
    {
        $collegeCode = session('CollegeCode');

        $doc = DB::table('fee_documents')
            ->where('CollegeCode', $collegeCode)
            ->first();

        if (!$doc || !$doc->Doc1) {
            abort(404);
        }

        return response(base64_decode($doc->Doc1), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="FeeDetails.pdf"');
    }
}