<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
class ReportsController extends Controller
{
    public function index()
    {
        $collegeCode = session('CollegeCode');

        if (!$collegeCode) {
            return redirect('/');
        }

        // Fee Transfer dropdowns
        $feeTransferExams = DB::table('fee_transfer')->distinct()->pluck('ExamName');
        $feeTransferYears = DB::table('fee_transfer')->distinct()->pluck('Year');

        // LIG Verification dropdowns
        $ligYears = DB::table('lig_verification')->where('Status', 'Y')->distinct()->pluck('Year');
        $ligVerificationYears = DB::table('lig_verification')->where('Status', 'V')->distinct()->pluck('Year');

        // AICTE Verification dropdowns
        $aicteYears = DB::table('AICTE_veri')->where('Status', 'Y')->distinct()->pluck('Year');
        $aicteVerificationYears = DB::table('AICTE_veri')->where('Status', 'V')->distinct()->pluck('Year');


        $feeConcessionYears = DB::table('fee_concession')
    ->distinct()
    ->orderBy('Year', 'asc')
    ->pluck('Year');

    

        return view('reports.reports', compact(
            'feeTransferExams',
            'feeTransferYears',
            'ligYears',
            'ligVerificationYears',
            'aicteYears',
            'aicteVerificationYears','feeConcessionYears'
        ));
    }



    //////////////////////fee tansfer
    
    public function feeTransfer(Request $request)
{
    $collegeCode = session('CollegeCode');
    if (!$collegeCode) return redirect('/');

    $exam = $request->input('exam'); 
    $year = $request->input('year'); 

    // Determine PG courses
    $pgCourses = ['PGM','MDS','PGA'];
    $isPG = in_array($exam, $pgCourses);

    $query = DB::table('fee_transfer as a')
        ->join('coursemaster as b', 'a.Course', '=', 'b.CourseCode')
        ->select(
            'a.RollNo',
            'a.Name',
            'a.Course',
            'b.CourseDesc',
            DB::raw('SUM(a.Amount) as Amount'),
            DB::raw('GROUP_CONCAT(a.Remarks SEPARATOR ", ") as Remarks'),
            DB::raw('MAX(a.Year) as Year')
        )
        ->where('a.College', $collegeCode);

    if ($exam) {
        $query->where('a.ExamName', $exam);
    }

    if ($year) {
        $query->where('a.Year', $year);
    }

    if ($isPG) {
        $query->where('b.CourseType', 'PG'); // filter by CourseType in coursemaster
    }

    $rows = $query->groupBy('a.RollNo','a.Name','a.Course','b.CourseDesc')
        ->orderBy('a.Course','asc')
        ->orderByDesc('Amount')
        ->orderBy('a.RollNo','asc')
        ->get();

    $collegeName = DB::table('collegedetails')
        ->where('CollegeCode', $collegeCode)
        ->value('CollegeDesc');

    return view('reports.feetransfer', [
        'rows' => $rows,
        'collegeCode' => $collegeCode,
        'collegeName' => $collegeName,
        'year' => $year,
        'exam' => $exam,
        'date' => date('d/m/Y')
    ]);
}


    /////////////////////
    /**
     * Generate Fee Transfer report
     */
    public function feeConcession(Request $request)
{
    $collegeCode = session('CollegeCode');
    $collegeGroup = session('CollegeGroup');

    if (!$collegeCode) {
        return redirect('/');
    }

    $year = $request->input('year');       // Year from form
    $rptType = $request->input('rptType'); // 'F' or 'C'

    // Get College Name
    $collegeName = DB::table('collegedetails')
        ->where('CollegeCode', $collegeCode)
        ->value('CollegeDesc');

    // Get report title + date
    $rptRow = DB::table('rpt_values')
        ->where('Year', $year)
        ->where('CollegeGroup', 'LIKE', "%$collegeGroup%")
        ->where('ReportType', $rptType)
        ->first();

    $rptDate = $rptRow->Date ?? date('d/m/Y');
    $rptTitle = $rptRow->Title ?? '';

    // Build query
    $query = DB::table('fee_concession as a')
        ->join('coursemaster as b', 'a.Course', '=', 'b.CourseCode')
        ->select('a.*', 'b.CourseDesc')
        ->where('a.College', $collegeCode)
        ->where('a.Year', $year)
        ->where('b.CounselGroup', $collegeGroup); // <-- Filter on coursemaster

    // Apply FeeType filter based on report type
    if ($rptType === 'F') {
        $query->where('a.FeeType', '<>', 'C');
    } elseif ($rptType === 'C') {
        $query->where('a.FeeType', '=', 'C');
    }

    // Ordering
    if ($rptType === 'F') {
        $query->orderBy('a.Course', 'ASC')
              ->orderBy('a.FeeType', 'DESC')
              ->orderByRaw("CASE a.FeeType WHEN 'L' THEN a.Income WHEN 'S' THEN a.Rank ELSE 1 END ASC");
    } else {
        $query->orderBy('a.Income', 'ASC');
    }

    $rows = $query->get();
    $recordCount = $rows->count();

    return view('reports.feeconcession', [
        'rows' => $rows,
        'collegeName' => $collegeName,
        'collegeCode' => $collegeCode,
        'rptDate' => $rptDate,
        'year' => $year,
        'rptType' => $rptType,
        'rptTitle' => $rptTitle,
        'recordCount' => $recordCount
    ]);
}



    /**
     * Generate LIG Verification report
     */
   
    public function ligForm()
    {
        return view('reports.lig-form');
    }

    public function generateLigReport(Request $request)
    {
       $collegeCode = session('CollegeCode');
    $collegeGroup = session('CollegeGroup');
    $year = $request->input('year');

    if (!$collegeCode) {
        return redirect('/');
    }

    // Fetch the report title for LIG report
    $reportTitleRow = DB::table('rpt_values')
        ->where('Year', $year)
        ->where('CollegeGroup', 'like', "%$collegeGroup%")
        ->where('ReportType', 'L') // LIG report type
        ->first();

    $reportTitle = $reportTitleRow ? $reportTitleRow->Title : 'Lower Income Group Report';

    // Fetch LIG data
    $ligRows = DB::table('lig_verification as a')
        ->join('coursemaster as b', 'a.Course', '=', 'b.CourseCode')
        ->select('a.*', 'b.CourseDesc')
        ->where('a.College', $collegeCode)
        ->where('a.Year', $year)
        ->where('b.CounselGroup', $collegeGroup)
        ->orderBy('a.Course')
        ->orderBy('a.slno')
        ->get();

    $collegeName = DB::table('collegedetails')
        ->where('CollegeCode', $collegeCode)
        ->value('CollegeDesc');

    return view('reports.lig-print', [
        'rows' => $ligRows,
        'collegeCode' => $collegeCode,
        'collegeName' => $collegeName,
        'year' => $year,
        'reportTitle' => $reportTitle,
        'date' => date('d/m/Y'),
    ]);
    }


    /**
     * Generate AICTE Verification report
     */
    public function aicteVerification(Request $request)
{
    $collegeCode = session('CollegeCode');
    $collegeGroup = session('CollegeGroup');   // Needed for title generation

    if (!$collegeCode) {
        return redirect('/');
    }

    // FIX: Read the correct form input name
    $year = $request->input('Year_AICTE_veri');

    // 🔥 FETCH REPORT TITLE (same style as your example)
    $reportTitleRow = DB::table('rpt_values')
        ->where('Year', $year)
        ->where('CollegeGroup', 'like', "%$collegeGroup%")
        ->where('ReportType', 'A') // <-- AICTE ReportType (YOU CONFIRM THIS)
        ->first();

    $reportTitle = $reportTitleRow->Title ?? 'AICTE Verification Report';
    $reportDate  = $reportTitleRow->Date  ?? date('d/m/Y');

    // MAIN DATA FETCH
    $rows = DB::table('AICTE_veri as a')
        ->join('coursemaster as b', 'a.Course', '=', 'b.CourseCode')
        ->select(
            'a.RollNo', 'a.Rank', 'a.Name', 'a.Course', 'b.CourseDesc',
            'a.Category', 'a.Income', 'a.FeeType', 'a.apprintake',
            'a.admitted', 'a.Selected'
        )
        ->where('a.College', $collegeCode)
        ->where('a.Year', $year)         // ALWAYS filter by selected year
        ->where('a.CollegeGroup', 'E')
        ->orderBy('a.Course', 'ASC')
        ->orderBy('a.Rank', 'ASC')
        ->get();

    $collegeName = DB::table('collegedetails')
        ->where('CollegeCode', $collegeCode)
        ->value('CollegeDesc');

    return view('reports.aicte', [
        'rows' => $rows,
        'collegeCode' => $collegeCode,
        'collegeName' => $collegeName,
        'year' => $year,
        'reportTitle' => $reportTitle,
        'reportDate' => $reportDate
    ]);
}



}