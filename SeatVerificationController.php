<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class SeatVerificationController extends Controller
{
   // $CollegeCode = $request->input('CollegeCode');
   public function index(Request $request)
   {
       $CollegeCode = session('CollegeCode');
       $EmployeeCd  = session('EmployeeCd');
       $tk          = $request->input('tk');

       // Fetch seat verification data
       $seatData = DB::table('seat_verification')
           ->select(
               'CourseCode', 
               'CEE_Seat', 
               'Total_Seat', 
               'CEE_det', 
               DB::raw("IFNULL(Non_CEE_seat, '-') AS Non_CEE_seat"), 
               'Non_CEE_seat_det', 
               'Total_det', 
               'Verified'
           )
           ->where('CollegeCode', $CollegeCode)
           ->whereNotNull('Verified')
           ->get();

       $FW = 0;
       $CollegeGroup = '';
       $Verified = null;

       foreach ($seatData as $row) {
           if ($row->CourseCode === 'MD') $CollegeGroup = 'M';
           if (strpos($row->CEE_det ?? '', 'FW') !== false) $FW = 1;
           if (!$Verified) $Verified = $row->Verified; // first row's verified status
       }

       // Decide message based on verification status
       $msg = '';
       if ($Verified === 'Y') {
           $msg = "The above seat details have been verified by the college authority and declared correct.";
       } elseif ($Verified === 'N') {
           $msg = "You have declared that the seat details are NOT correct.";
       }

       return view('seat_allot', compact('seatData', 'CollegeCode', 'FW', 'Verified', 'msg', 'tk', 'CollegeGroup'));
   }

   /**
    * Update seat verification status.
    */
  public function update(Request $request)
{
    $CollegeCode = $request->input('CollegeCode');
    $EmployeeCd  = session('EmployeeCd');
    $tk          = $request->input('tk');

   
    if (empty($EmployeeCd)) {
        return back()->with('msg', 'Error: Employee code missing from session. Please log in again.');
    }

   
    if ($request->has('sub1')) {
        // Verified & Found Correct
        DB::table('seat_verification')
            ->where('CollegeCode', $CollegeCode)
            ->update([
                'Verified'   => 'Y',
                'UpdateTime' => now(),
                'EmployeeCd' => $EmployeeCd,
            ]);

    } elseif ($request->has('sub2')) {
        // Verified & Found NOT Correct
        DB::table('seat_verification')
            ->where('CollegeCode', $CollegeCode)
            ->update([
                'Verified'   => 'N',
                'remarks'    => $request->input('remarks'),
                'UpdateTime' => now(),
                'EmployeeCd' => $EmployeeCd,
            ]);
    }

    return redirect()->route('seat_allot', [
        'tk' => $tk,
        'CollegeCode' => $CollegeCode
    ])->with('msg', 'Seat verification updated successfully.');
}

   /**
    * Helper method to fetch course name.
    */
   public static function getCourseName($CourseCode, $CollegeGroup)
   {
       return DB::table('coursemaster')
           ->where('CourseCode', $CourseCode)
           ->where('CounselGroup', $CollegeGroup)
           ->value('CourseDesc');
   }
   
   
   
   
   public function vacancy_index(Request $request)
    {
        $CollegeCode = $request->CollegeCode ?? auth()->user()->CollegeCode;
        $tk = $request->tk ?? '';

        // Fetch seat / vacancy data
        $seatData = DB::table('vacancy_verification')
            ->where('CollegeCode', $CollegeCode)
            ->where('Verified', '!=', '')
            ->orderBy('coursedesc', 'DESC')
            ->get();

        // Fetch verification status
        $Verified = DB::table('vacancy_verification')
            ->where('CollegeCode', $CollegeCode)
            ->value('Verified');

        // Message logic (same as old PHP)
        $msg = "";
        if ($Verified == 'Y') {
            $msg = "The above vacancy details have been verified by the college authority and declared correct.";
        } elseif ($Verified == 'N') {
            $msg = "You have declared that the vacancy details are NOT correct. 
                    You must inform immediately to ceekinfo@cee.kerala.gov.in 
                    with subject: Vacancy details - Correction - reg.";
        }

        return view('seat_verification', compact('seatData', 'CollegeCode', 'Verified', 'msg', 'tk'));
    }

    public function vacancy_update(Request $request)
    {
        $CollegeCode = $request->CollegeCode;
        $verify = $request->verify;
        $remarks = $request->remarks ?? '';

        if ($verify == 'N' && empty($remarks)) {
            return back()->with('error', 'Please provide remarks if details are NOT correct.');
        }

        DB::table('vacancy_verification')
            ->where('CollegeCode', $CollegeCode)
            ->update([
                'Verified' => $verify,
                'remarks'  => $remarks,
                'UpdateTime' => now(),
                'EmployeeCd' => auth()->user()->EmployeeCode ?? 0
            ]);

        return redirect()->route('seat_verification', [
            'CollegeCode' => $CollegeCode,
            'tk' => $request->tk,
        ]);
    }

   
}