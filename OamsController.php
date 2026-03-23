<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use App\Helpers\AdmissionHelper;
use Carbon\Carbon;


class OamsController extends Controller
{
    // Show exam selection page
	


   public function show()
    {
        // Ensure session is alive
        if (!session()->has('CollegeCode')) {
            return redirect()->route('login')->with('error', 'Session expired. Please login.');
        }

        $CollegeCode = session('CollegeCode');

        $row = DB::select("SELECT LCourse FROM collegedetails WHERE CollegeCode = ?", [$CollegeCode]);
        $LCourse = $row ? str_split($row[0]->LCourse, 2) : [];
        $commaList = "'" . implode("','", $LCourse) . "'";
        
        $courses = DB::select("SELECT * FROM degreelevel WHERE CourseType IN ($commaList) AND Active = 'Y'");

        return view('select_exam', compact('courses'));
    }

    /* ---------------- STORE SELECTED EXAM ---------------- */
    public function store(Request $request)
    {
        // Protect session integrity
        if (!session()->has('CollegeCode') || !session()->has('EmpType')) {
            return redirect()->route('login')->with('error', 'Session expired. Please login.');
        }

        if (!$request->exam_name) {
            return back()->with('error', 'Please select a course');
        }

        $CollegeCode = session('CollegeCode');
        $EmpType     = session('EmpType');

        // Save selected exam
        $LCourseSession = DB::select("SELECT LCourse FROM collegedetails WHERE CollegeCode = ?", [$CollegeCode]);
        $LCourseArray = $LCourseSession ? str_split($LCourseSession[0]->LCourse, 2) : [];

        // Persist session forever until logout
        session()->put('LLBCourse', $request->exam_name);
        session()->put('LCourse', $LCourseArray);
        session()->save();   // ?? This line prevents auto-logout

        $course = $request->exam_name;

        if ($EmpType === 'P' || $EmpType === 'A') {
            if (in_array($course, ['BL','MP','MM','MD','PM','L5','L3','LM','PN','PA','MH'])) {
                return redirect()->route('admin.home');
            }
            return redirect()->route('admin.home');
        }

        return redirect()->route('normal.user');
    }

    /* ---------------- ADMIN HOME ---------------- */
    public function home()
{
    if (!session()->has('EmployeeCd')) {
        return redirect()->route('login')->with('error', 'Session expired.');
    }

    $EmployeeCd   = session('EmployeeCd');
    $CollegeCode  = session('CollegeCode');
    $CollegeGroup = session('CollegeGroup');

    //  Get course-based dynamic DB
    $db = $this->getDynamicDB();

    //  Fetch employee permissions from dynamic DB
    $employee = $db->table('collegemaster')
        //->where('EmployeeCd', $EmployeeCd)
        ->where('CollegeCode', $CollegeCode)
        ->first();

    if (!$employee) {
        return redirect()->route('login')
            ->with('error', 'Employee not mapped to this course/college.');
    }

    $permissions = [
        'TC_Enable'      => $employee->tc ?? 'N',
        'Admn_Enable'    => $employee->admission_admin ?? 'N',
        'AdmnApproved'   => $employee->AdmnApproved ?? 'N',
        'Report_Enable'  => $employee->report ?? 'N',
        'TC_Issued'      => $employee->tc ?? 'N',
        'TC_tobe_Issued' => $employee->tc_tobe_issued ?? 'N',
        'UserMng_Enable' => $employee->user_management ?? 'N',
        'Status_Enable'  => $employee->status ?? 'N',
        'StrayAdmn'      => $employee->stray ?? 'N',
    ];

  
    /*$msg = $db->table('msg')
        ->where('Status', 'Y')
        ->where('MType', 'O')
        ->value('Msg');*/

    return view('oams.home', compact('permissions'));
}

    public function homeTest()
    {
        return view('admin.home_test');
    }

    public function normalUser()
    {
        return view('normal_user');
    }
	
	
	
	 public function dashboard()
    {
        return view('oams.dashboard');
    }

    /* =======================
       ADD USER FORM
    ========================== */
public function addUserForm()
{
    // Get all EmployeeCd values
    $codes = DB::table('employee')->pluck('EmployeeCd');

    $maxNumeric = 0;

    foreach ($codes as $code) {
        // Extract digits from the string
        preg_match_all('/\d+/', $code, $matches);
        if (!empty($matches[0])) {
            $num = (int) end($matches[0]); // take the last number found
            if ($num > $maxNumeric) $maxNumeric = $num;
        }
    }

    $newCode = $maxNumeric + 1; // increment

    return view('oams.add_user', compact('newCode'));
}

    /* =======================
       ADD USER SUBMIT
    ========================== */
    public function addUserSubmit(Request $request)
    {
        $CollegeCode = session('CollegeCode');
        $CollegeGroup = session('CollegeGroup');
        $CollegeType = session('CollegeType');

        $validated = $request->validate([
            'employeecd' => 'required',
            'employeename' => 'required',
            'employeedesig' => 'required',
            'employeepasswd' => 'required|min:8',
        ]);

        $roles = "";
        if ($request->AV) $roles .= "AV";
        if ($request->DV) $roles .= "DV";
        if ($request->FE) $roles .= "FE";
        if ($request->AC) $roles .= "AC";
        $TC = $request->TC ? "Y" : "";

        $Admn_Enable = $roles != "" ? "Y" : "";

        // Check exists
        $exists = DB::table('employee')
            ->where('EmployeeCd', $request->employeecd)
            ->where('CollegeCode', $CollegeCode)
            ->exists();

        if ($exists) {
            return back()->with('error', 'User already exists.');
        }

        DB::table('employee')->insert([
            'CollegeGroup' => $CollegeGroup,
            'CollegeType' => $CollegeType,
            'CollegeCode' => $CollegeCode,
            'EmployeeCd'  => $request->employeecd,
            'Name'        => strtoupper($request->employeename),
            'Desig'       => strtoupper($request->employeedesig),
            'Password'    => hash('sha256', $request->employeepasswd),
            'EmpRole'     => $roles,
            'EmpLogged'   => '0',
            'EmpType'     => 'N',
            'Active'      => 'Y',
            'TC_Issued'   => $TC,
            'Report_Enable' => '',
            'Admn_Enable' => $Admn_Enable,
			'StrayAdmn' =>'Y'
        ]);

        return back()->with('success', 'New User Added');
    }


    /* =======================
       EDIT USER LIST
    ========================== */
    public function editUserForm()
    {
        $CollegeCode = session('CollegeCode');

        $users = DB::table('employee')
            ->select('EmployeeCd', 'Name', 'Desig', 'Active', 'EmpRole')
            ->where('CollegeCode', $CollegeCode)
            ->where('EmpType', '<>', 'A')
            ->get();

        return view('oams.edit_user', ['users' => $users]);
    }

    /* =======================
       UPDATE USER STATUS (AJAX)
    ========================== */
    public function toggleUserStatus(Request $request)
    {
        DB::table('employee')
            ->where('EmployeeCd', $request->empcode)
            ->update(['Active' => $request->status]);

        return "OK";
    }


    /* =======================
       ADD PRINCIPAL FORM
    ========================== */
    public function addPrincipalForm()
    {
        $CollegeCode = session('CollegeCode');
        $empCode = $CollegeCode . "PRINCIPAL";

        return view('oams.add_principal', ['empCode' => $empCode]);
    }

    /* =======================
       ADD PRINCIPAL SUBMIT
    ========================== */
    public function addPrincipalSubmit(Request $request)
    {    
	
	dd($request);
        $CollegeCode = session('CollegeCode');
        $CollegeGroup = session('CollegeGroup');
        $CollegeType = session('CollegeType');

        $validated = $request->validate([
            'employeecd' => 'required',
            'employeename' => 'required',
            'employepasswd' => 'required|min:8'
        ]);

        $exists = DB::table('employee')
            ->where('EmployeeCd', $request->employeecd)
            ->where('CollegeCode', $CollegeCode)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Principal already exists.');
        }

        DB::table('employee')->insert([
            'CollegeGroup' => $CollegeGroup,
            'CollegeType' => $CollegeType,
            'CollegeCode' => $CollegeCode,
            'EmployeeCd'  => $request->employeecd,
            'Name'        => strtoupper($request->employeename),
            'Desig'       => strtoupper($request->employeedesig),
            'Password'    => hash('sha256', $request->employeepasswd),
            'EmpRole'     => "AVDVFEAC",
            'EmpLogged'   => '0',
            'EmpType'     => 'P',
            'Active'      => 'Y',
            'TC_Issued'   => 'Y',
            'Report_Enable' => 'Y',
            'Status_Enable' => 'Y'
        ]);

        return back()->with('success', 'Principal Added');
    }


public function change_index()
    {
        $CollegeCode = Auth::user()->CollegeCode;

        $users = DB::table('employee')
            ->where('CollegeCode', $CollegeCode)
            ->where('EmpType', '!=', 'A')
            ->get();

        return view('users.index', compact('users'));
    }



    // Show change role page
    public function editRole($empcode)
    {
        $CollegeCode = Auth::user()->CollegeCode;

        $user = DB::table('employee')
            ->where('EmployeeCd', $empcode)
            ->where('CollegeCode', $CollegeCode)
            ->first();

        if (!$user) {
            abort(404);
        }

        
        $roles = $user->EmpRole ? str_split($user->EmpRole, 2) : [];

        return view('oams.change_role', compact('user', 'roles'));
    }



    // Update roles
    public function updateRole(Request $request, $empcode)
    {
        $CollegeCode = Auth::user()->CollegeCode;

        $user = DB::table('employee')
                ->where('EmployeeCd', $empcode)
                ->where('CollegeCode', $CollegeCode)
                ->first();

        if (!$user) {
            return back()->with('error', 'User not found');
        }

        // Construct role string like old PHP
        $role = '';
        if ($request->AV) $role .= 'AV';
        if ($request->DV) $role .= 'DV';
        if ($request->FE) $role .= 'FE';
        if ($request->AC) $role .= 'AC';

        $tc = $request->TC === "Y" ? "Y" : "";

        // Update using Query Builder
        DB::table('employee')
            ->where('EmployeeCd', $empcode)
            ->where('CollegeCode', $CollegeCode)
            ->update([
                'EmpRole' => $role,
                'TC_Issued' => $tc
            ]);

        return back()->with('success', 'User Role Modified Successfully!');
    }


    // Toggle active / inactive (AJAX)
    public function toggleStatus(Request $request)
    {
        DB::table('employee')
            ->where('EmployeeCd', $request->empcode)
            ->update(['Active' => $request->status]);

        return response()->json(['success' => true]);
    }
     public function showResetForm($empcode)
    {
        $CollegeCode = session('CollegeCode');

        $employee = DB::table('employee')
            ->where('EmployeeCd', $empcode)
            ->where('CollegeCode', $CollegeCode)
            ->first();

        if (!$employee) {
            abort(404);
        }

        return view('oams.resetpassword', compact('employee'));
    }

    public function resetPassword(Request $request, $empcode)
    {
        $request->validate([
            'employeepasswd' => 'required|string|min:6',
        ]);

        $CollegeCode = session('CollegeCode');

        $encryptedPassword = hash('sha256', $request->employeepasswd);

        $updated = DB::table('employee')
            ->where('EmployeeCd', $empcode)
            ->where('CollegeCode', $CollegeCode)
            ->update([
                'Password' => $encryptedPassword,
                'EmpLogged' => 0
            ]);

        if ($updated) {
            return back()->with('success', 'Password Reset Successfully!');
        }

        return back()->with('error', 'Failed to reset password.');
    }

    public function oamsHome()
    {
        return redirect()->route('employee.oamsHome'); // Your OAMS home route
    }
	
	
	
   private function getDynamicDB()
{
    $CollegeGroup = Session::get('CollegeGroup');
    $LLBCourse    = Session::get('LLBCourse');

    $query = DB::connection('mysql')
        ->table('defvalues')
        ->where('ExamStatus', 'Y');

    if ($CollegeGroup === 'L' || $CollegeGroup === 'B') {

        $query->where('CType', $LLBCourse);

    } else {

        $map = [
            'MM'=>'M','PM'=>'M','DM'=>'M','DD'=>'M','PN'=>'N',
            'MH'=>'M','PA'=>'M','MD'=>'M','LE'=>'E','DE'=>'D','AR'=>'R'
        ];

        if (isset($map[$LLBCourse])) {

            Session::put('CollegeGroup', $map[$LLBCourse]);

            $query->where('CType', $LLBCourse);

        } else {

            $query->where('CollegeGroup', 'LIKE', "%{$CollegeGroup}%");
        }
    }

    $row = $query->first();

    if (!$row) {
        dd([
            'error'        => 'NO ROW FOUND IN defvalues',
            'CollegeGroup' => $CollegeGroup,
            'LLBCourse'    => $LLBCourse,
            'sql'          => $query->toSql(),
            'bindings'     => $query->getBindings(),
        ]);
    }

    // Reset old dynamic connection
    DB::purge('dynamic');

    config([
        'database.connections.dynamic' => [
            'driver'    => 'mysql',
            'host'      => $row->HostName,
            'database'  => $row->ApplicationDB,
            'username'  => env('DYNAMIC_DB_USER', 'dba@cee.k'),
            'password'  => env('DYNAMIC_DB_PASS', 'e_treme#ICEworld'),
            'charset'   => 'utf8',
            'collation' => 'utf8_general_ci',
            'strict'    => false,
        ],
    ]);

    DB::reconnect('dynamic');

    return DB::connection('dynamic');
}

    // ---------------------
    // GET ALLOT NO
    // ---------------------
    private function getMaxAllotNo()
    {
	//dd(config('database.connections.dynamic'));
        $db = $this->getDynamicDB();

        $res = $db->select("SELECT MAX(AllotNo) AS A FROM allotvalues");
        return $res[0]->A ?? 0;
    }

    // ---------------------
    // COUNT ADMISSION STATUS
    // ---------------------
    private function admitStatus($val, $CollegeCode, $alno)
    {
        $db = $this->getDynamicDB();

        $res = $db->select("
            SELECT COUNT(*) AS C FROM admndetails
            WHERE AllotNo = ? AND Admn_Status = ? AND MID(AV_Employee,1,3) = ?
        ", [$alno, $val, $CollegeCode]);

        return $res[0]->C ?? 0;
    }
	
	
	public function status_index()
{
    $CollegeCode = Session::get('CollegeCode');
    $db          = $this->getDynamicDB();

    $alno = $this->getMaxAllotNo();
    $allotCol   = "Allot_{$alno}";
    $payCol     = "PayStatus_{$alno}";
    $joinCol    = "JoinStatus_{$alno}";

    // Check if the allotment column exists
    if (!$this->columnExists($db, 'allotmentdetails', $allotCol)) {
        // If no allotment column exists, return no allotment
        return view('status', [
            'alno' => $alno,
            'rows' => [],
            'message' => 'No allotment for this college',
            'admitCounts' => []
        ]);
    }

    // Main query
    $sql = "
    SELECT 
        a.course,
        a.allotted,
        b.nonpay,
        c.paid,
        d.nonjoin,
        f.tcissue,
        e.admitted
    FROM (
        SELECT MID({$allotCol},3,2) AS course, COUNT(*) AS allotted
        FROM allotmentdetails
        WHERE MID({$allotCol},5,3) = ?
        GROUP BY MID({$allotCol},3,2)
    ) a
    LEFT JOIN (
        SELECT MID({$allotCol},3,2) AS course, COUNT(*) AS nonpay
        FROM allotmentdetails
        WHERE {$payCol} = 'N' AND MID({$allotCol},5,3) = ?
        GROUP BY MID({$allotCol},3,2)
    ) b ON b.course = a.course
    LEFT JOIN (
        SELECT MID({$allotCol},3,2) AS course, COUNT(*) AS paid
        FROM allotmentdetails
        WHERE {$payCol} = 'Y' AND MID({$allotCol},5,3) = ?
        GROUP BY MID({$allotCol},3,2)
    ) c ON c.course = a.course
    LEFT JOIN (
        SELECT MID({$allotCol},3,2) AS course, COUNT(*) AS nonjoin
        FROM allotmentdetails
        WHERE {$joinCol} = 'N' AND MID({$allotCol},5,3) = ?
        GROUP BY MID({$allotCol},3,2)
    ) d ON d.course = a.course
    LEFT JOIN (
        SELECT MID({$allotCol},3,2) AS course, COUNT(*) AS tcissue
        FROM allotmentdetails
        WHERE {$joinCol} = 'TC' AND MID({$allotCol},5,3) = ?
        GROUP BY MID({$allotCol},3,2)
    ) f ON f.course = a.course
    LEFT JOIN (
        SELECT MID({$allotCol},3,2) AS course, COUNT(*) AS admitted
        FROM allotmentdetails
        WHERE {$joinCol} = 'Y' AND MID({$allotCol},5,3) = ?
        GROUP BY MID({$allotCol},3,2)
    ) e ON e.course = a.course
    ";

    $rows = $db->select($sql, array_fill(0, 6, $CollegeCode));

    return view('oams.status', [
        'alno' => $alno,
        'rows' => $rows,
        'admitCounts' => [
            'AV' => $this->admitStatus('AV', $CollegeCode, $alno),
            'DV' => $this->admitStatus('DV', $CollegeCode, $alno),
            'FE' => $this->admitStatus('FE', $CollegeCode, $alno),
            'AC' => $this->admitStatus('AC', $CollegeCode, $alno),
        ]
    ]);
}

// Utility function to check if a column exists
private function columnExists($db, $table, $column)
{
    $result = $db->select("SHOW COLUMNS FROM {$table} LIKE '{$column}'");
    return !empty($result);
}



   public function tc_index()
    {
        return view('oams.tcissued');
    }
	
	

    public function checkRoll(Request $request)
     {
    $request->validate([
        'RollNo' => 'required|numeric'
    ]);

    $collegeCode = session('CollegeCode');
    $db = $this->getDynamicDB();

    $valid = $db->table('allotmentdetails')
        ->where('RollNo', $request->RollNo)
        ->where('TC_ToBe_Issued', '!=', 'Y')
        ->whereRaw("MID(Curr_Admn,5,3) = ?", [$collegeCode])
        ->exists();

    if (!$valid) {
        return redirect()
            ->route('tc.issue')
            ->with('error', 'Invalid Roll Number');
    }

    session(['SRollNo' => $request->RollNo]);

    return redirect()
        ->route('tc.issueForm')
        ->with('success', 'Valid Roll Number');
}


    /* ===============================
       STEP 2: Issue TC Form
    =============================== */
    public function issueForm()
    {
        $rollNo = session('SRollNo');
        $collegeCode = session('CollegeCode');
		$db = $this->getDynamicDB();


        $data = $db->table('allotmentdetails')
            ->where('RollNo', $rollNo)
            ->whereRaw("MID(Curr_Admn,5,3) = ?", [$collegeCode])
            ->first();

        if (!$data) {
            return redirect('/tc-issued')->with('error', 'Session expired');
        }

        return view('oams.tc_issue_form', compact('data'));
    }

    /* ===============================
       STEP 3: Confirm Issue
    =============================== */
    public function confirmIssue(Request $request)
{
    $request->validate([
        'txtDate' => 'required|date_format:d/m/Y',
        'remarks' => 'nullable|string|max:255'
    ]);

    $rollNo = session('SRollNo');
    $employeeCd = session('EmployeeCd');
    $ip = $request->ip();

    if (!$rollNo) {
        return redirect('/tc-issued')->with('error', 'Session expired or Roll Number missing');
    }

    $date = Carbon::createFromFormat('d/m/Y', $request->txtDate)->format('Y-m-d');

    DB::beginTransaction();

    $db = $this->getDynamicDB(); // make sure this returns valid connection

    try {
        // Get next allot number
        $tcIssuedNo = ($db->table('tcissued')->max('AllotNo') ?? 0) + 1;

        $admn = $db->table('allotmentdetails')->where('RollNo', $rollNo)->first();

        if (!$admn) {
            throw new \Exception("Admission details not found for RollNo: $rollNo");
        }

        $db->table('tcissued')->insert([
            'AllotNo' => $tcIssuedNo,
            'RollNo' => $rollNo,
            'Date' => $date,
            'Allot' => $admn->Curr_Admn,
            'Remarks' => $request->remarks,
            'EmployeeCd' => $employeeCd,
            'UpdatTime' => now(),
            'IPAddress' => $ip
        ]);

        // Update dynamic column safely
        $column = 'JoinStatus_' . $tcIssuedNo;
        $db->table('allotmentdetails')
            ->where('RollNo', $rollNo)
            ->update([
                'Curr_Admn' => '',
                'Curr_Admn_No' => 0,
                $column => 'TC'
            ]);

        DB::commit();

        return redirect('/tc-issue')->with('success', 'TC Issued Successfully');

    } catch (\Exception $e) {
        DB::rollBack();
        dd($e->getMessage(), $e->getFile(), $e->getLine()); // <-- Debug error
        return back()->with('error', 'Failed to issue TC');
    }
}





public function tfs_index(Request $request)
{
    $tk = $request->get('tk');

    $empType     = Session::get('EmpType');
    $collegeCode = Session::get('collegecode');

    if ($empType === 'N') {
        return redirect("main.php?tk={$tk}");
    }

    // Dynamic connection name
    $connection = $this->getDynamicDB();

    $currAllotNo = $this->getMaxAllotNo();
    $prevAllotNo = $this->getPrevAllotNo();

    $sql = "
        SELECT  
            a.RollNo,
            b.Name,
            a.Clg_Admn_No,
            MID(a.Prev_Admn,3,2) AS Course_prev,
            MID(a.Allot_{$currAllotNo},3,2) AS Course_new,
            MID(a.Allot_{$currAllotNo},5,3) AS College_new,
            a.TC_ToBe_Issued
        FROM allotmentdetails a
        JOIN candidates b ON a.RollNo = b.RollNo
        WHERE a.TC_ToBe_Issued IN ('Y','P','A')
          AND MID(a.Prev_Admn,5,3) = ?
    ";

    //$students = DB::connection($connection)->select($sql, [$collegeCode]);<br />
//$students = DB::connection($connection)->select($sql, [$collegeCode]);
	$students = $connection->select($sql, [$collegeCode]);

    return view('oams.tfs', compact('students', 'tk'));
}
    

    private function getPrevAllotNo()
    {
        $db = $this->getDynamicDB();

        $row= $db->select("SELECT MAX(AllotNo) AS A FROM allotvalues");
		//$row = DB::selectOne("SELECT MAX(AllotNo)-1 AS prevno FROM allot_master");
        return $row->prevno ?? 0;
    }
	
	
	
	
	
	public function stray_index()
    {
        $collegeCode = Session::get('CollegeCode');
		$db = $this->getDynamicDB();

        $seats = $db->select("
            SELECT CourseCode, Category, BalanceSeat
            FROM seatcategory
            WHERE CollegeCode = ? AND BalanceSeat > 0
        ", [$collegeCode]);

        return view('oams.stray', compact('seats'));
    }

    /* ===============================
       CHECK ROLL NUMBER
    =============================== */
    public function checkRollNo(Request $request)
{
    $rollno = trim($request->input('rollNo')); 
    $db = $this->getDynamicDB();

    $data = $db->select("
        SELECT b.applno, a.rollno, b.JoinStray AS admitted
        FROM stray_allot a
        JOIN allotmentdetails b ON a.rollno = b.rollno
        WHERE a.rollno = ?
    ", [$rollno]);

    if (empty($data)) {
        return redirect()->back()->with('error', 'Invalid Roll Number');
    }

    Session::put('SRollNo', $data[0]->rollno);
    Session::put('ApplNo', $data[0]->applno);

    if ($data[0]->admitted === 'Y') {
        return redirect()->back()->with('error', 'Candidate already admitted');
    }

    return redirect()->route('stray.admission.form');
}


    /* ===============================
       STRAY ADMISSION FILL PAGE
    =============================== */
    public function fill()
    {
        $collegeCode = Session::get('CollegeCode');
        $rollno      = Session::get('SRollNo');
		 $db = $this->getDynamicDB();

        $courses = $db->select("
        SELECT CourseCode, Category
        FROM seatcategory
        WHERE CollegeCode = ?
          AND BalanceSeat > 0
        ORDER BY CourseCode, Category
    ", [$collegeCode]);

        return view('oams.strayfill', compact('courses', 'rollno'));
    }

    /* ===============================
       SAVE STRAY ADMISSION
    =============================== */
    public function stray_store(Request $request)
{
    $rollno   = Session::get('SRollNo');
    $course   = $request->course;
    $category = $request->category;
    $college  = Session::get('CollegeCode');
    $collegeGroup = Session::get('CollegeGroup');
    $collegeType  = Session::get('CollegeType');

    $db = $this->getDynamicDB();

    if (!$course || !$category) {
        return redirect()->back()->with('error', 'Select Course & Category');
    }

    $seat = $db->select("
        SELECT * FROM seatcategory
        WHERE CollegeCode=? AND CourseCode=? AND Category=? AND BalanceSeat>0
    ", [$college, $course, $category]);

    if (count($seat) == 0) {
        return redirect()->back()->with('error', 'No vacant seat available');
    }

    // Generate Stray string
    $admn = $collegeGroup . $collegeType . $course . $college . $category . $category;

    // Update allotmentdetails
    $db->update("
        UPDATE allotmentdetails
        SET Stray=?, JoinStray='Y', StrayRound='R1'
        WHERE RollNo=?
    ", [$admn, $rollno]);

    // Update seatcategory
    $db->update("
        UPDATE seatcategory
        SET BalanceSeat = BalanceSeat - 1
        WHERE CollegeCode=? AND CourseCode=? AND Category=?
    ", [$college, $course, $category]);

    return redirect('/stray-admission')
        ->with('success', "Admission completed for Roll No $rollno");
}
    /* ===============================
       AJAX CATEGORY FETCH
    =============================== */
  public function getCategory(Request $request)
{
    $collegeCode = Session::get('CollegeCode');
    $course = $request->course;

    $db = $this->getDynamicDB();

    // Fetch distinct categories for the selected course
    $categories = $db->select("
        SELECT DISTINCT Category
        FROM seatcategory
        WHERE CollegeCode = ?
          AND CourseCode = ?
          AND BalanceSeat > 0
        ORDER BY Category
    ", [$collegeCode, $course]);

    // Convert to a simple array of strings
    $categoryArray = array_map(fn($c) => $c->Category, $categories);

    return response()->json($categoryArray);
}


}