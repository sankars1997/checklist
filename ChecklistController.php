<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ChecklistController extends Controller
{
    public function index()
    {
        $employee = DB::table('employee')
            ->where('EmployeeCd', Auth::user()->EmployeeCd)
            ->first();

            $mainHeadings = DB::table('checklist_main')
            ->where('status', 'Y')
            ->get();

        $employeeCd = Auth::user()->EmployeeCd;

        // Show exams only if:
        //  - exam not used by OTHER employees for the same year
        //  - OR used by the current employee
        $exams = DB::table('exams')
            ->whereNotIn('examid', function ($query) use ($employeeCd) {
                $query->select('exam_id')
                    ->from('checklist_response')
                    ->where('employee_cd', '!=', $employeeCd)
                    ->whereColumn('checklist_response.year', 'exams.year');
            })
            ->get();

        $statuses = DB::table('checklist_status_options')
            ->select('status_name', 'submit_status', 'bg_color', 'border_color')
            ->get();

        return view('checklist.index', compact(
            'employee', 'mainHeadings', 'exams', 'statuses'
        ));
    }

    public function getSections(Request $request)
    {
        $main_id = $request->main_id;
        $exam_id = $request->exam_id;
        $employee_cd = Auth::user()->EmployeeCd;

        if (!$main_id || !$exam_id) {
            return response()->json(['status' => 'error', 'message' => 'Main heading or exam missing']);
        }

        // Get exam year
        $examYear = DB::table('exams')
            ->where('examid', $exam_id)
            ->value('year');

            $sections = DB::table('checklist_sections')
            ->where('main_id', $main_id)
            ->where('status', 'Y')
            ->get();

        $responses = DB::table('checklist_response')
            ->where('employee_cd', $employee_cd)
            ->where('exam_id', $exam_id)
            ->where('main_id', $main_id)
            ->where('year', $examYear)
            ->get()
            ->keyBy(fn($r) => $r->section_id . '-' . $r->subitem_id);

        $statusOptions = DB::table('checklist_status_options')->get();

        $statusMapping = $statusOptions->pluck('submit_status', 'status_name')->toArray();

        $statusColors = [];
        foreach ($statusOptions as $s) {
            $statusColors[$s->status_name] = [
                'bg' => $s->bg_color ?? '#ffffff',
                'border' => $s->border_color ?? '#ced4da'
            ];
        }

        $data = [];
        foreach ($sections as $section) {
            $subitems = DB::table('checklist_subitems')
    ->where('section_id', $section->id)
    ->where('status', 'Y')
    ->get();
            $subitemData = [];
            foreach ($subitems as $subitem) {
                $existing = $responses->get($section->id . '-' . $subitem->id);
                $submit_status = $existing ? ($statusMapping[$existing->status] ?? 'N') : null;

                $subitemData[] = [
                    'id' => $subitem->id,
                    'subitem' => $subitem->subitem,
                    'description' => $subitem->description,
                    'status' => $existing->status ?? null,
                    'remarks' => $existing->remarks ?? null,
                    'submit_status' => $submit_status
                ];
            }

            $data[] = [
                'id' => $section->id,
                'section_name' => $section->section_name,
                'subitems' => $subitemData
            ];
        }

        $programmer_cd = DB::table('checklist_response')
            ->where('employee_cd', $employee_cd)
            ->where('exam_id', $exam_id)
            ->where('main_id', $main_id)
            ->where('year', $examYear)
            ->orderBy('created_at', 'desc')
            ->value('programmer_cd');

        return response()->json([
            'status' => 'success',
            'sections' => $data,
            'programmer_cd' => $programmer_cd,
            'status_colors' => $statusColors
        ]);
    }

    public function save(Request $request)
    {
        $employee_cd = Auth::user()->EmployeeCd;
        $Name = Auth::user()->Name ?? '';
        $exam_id = $request->exam_id;
        $main_id = $request->main_id;
        $programmer_cd = $request->programmer_cd ?? null;

        // Get exam year
        $examYear = DB::table('exams')
            ->where('examid', $exam_id)
            ->value('year');

        $existingResponses = DB::table('checklist_response')
            ->where('employee_cd', $employee_cd)
            ->where('exam_id', $exam_id)
            ->where('main_id', $main_id)
            ->where('year', $examYear)
            ->get()
            ->keyBy(fn($r) => $r->section_id . '-' . $r->subitem_id);

        $statusMapping = DB::table('checklist_status_options')
            ->pluck('submit_status', 'status_name')
            ->toArray();

        $insertData = [];
        foreach ($request->items as $item) {
            $sectionId = $item['section_id'] ?? null;
            $subitemId = $item['subitem_id'] ?? null;
            $status = $item['status'] ?? null;
            $remarks = $item['remarks'] ?? null;

            if (!$sectionId || !$subitemId) continue;

            $key = $sectionId . '-' . $subitemId;
            $submit_status = $status ? ($statusMapping[$status] ?? 'N') : 'N';

            if (isset($existingResponses[$key])) {
                if ($existingResponses[$key]->submit_status === 'Y') continue;

                DB::table('checklist_response')
                    ->where('id', $existingResponses[$key]->id)
                    ->update([
                        'status' => $status,
                        'remarks' => $remarks,
                        'submit_status' => $submit_status,
                        'year' => $examYear,
                        'updated_at' => now()
                    ]);
            } else {
                $insertData[] = [
                    'employee_cd' => $employee_cd,
                    'Name'=>$Name,
                    'exam_id' => $exam_id,
                    'main_id' => $main_id,
                    'section_id' => $sectionId,
                    'subitem_id' => $subitemId,
                    'programmer_cd' => $programmer_cd,
                    'status' => $status,
                    'remarks' => $remarks,
                    'submit_status' => $submit_status,
                    'year' => $examYear, // ✅ Add year here
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }
        }

        if (!empty($insertData)) {
            DB::table('checklist_response')->insert($insertData);
        }

        return redirect()->back()->with('success', 'Checklist submitted successfully.');
    }



    // Load dashboard page with exams only
    public function viewDashboard()
    {
        $exams = DB::table('exams')
        ->where('status', 'Y')      // only active exams
        ->orderBy('year', 'desc')
        ->get();
    
        $statuses = DB::table('checklist_status_options')
            ->select('status_name','submit_status','bg_color','border_color')
            ->get();
    
        return view('checklist.checklist_dashboard', compact('exams','statuses'));
    }

    // 2️⃣ Fetch Main Headings for a selected exam
    // public function fetchMainHeadings(Request $request)
    // {
    //     $examId = $request->exam_id;
    
    //     if (!$examId) {
    //         return response()->json(['status' => 'error']);
    //     }
    
    //     $employeeCd = Auth::user()->EmployeeCd;
    
    //     $mainHeadings = DB::table('checklist_main')->get();
    
    //     $data = [];
    
    //     foreach ($mainHeadings as $main) {
    
    //         $sectionIds = DB::table('checklist_sections') // MAKE SURE THIS IS CORRECT TABLE
    //             ->where('main_id', $main->id)
    //             ->pluck('id');
    
    //         $totalSubitems = DB::table('checklist_subitems')
    //             ->whereIn('section_id', $sectionIds)
    //             ->count();
    
    //         $completedSubitems = DB::table('checklist_response')
    //             ->where('employee_cd', $employeeCd)
    //             ->where('exam_id', $examId)
    //             ->whereIn('section_id', $sectionIds)
    //             ->where('status', 'OK')
    //             ->count();
    
    //         $percent = $totalSubitems
    //             ? round(($completedSubitems / $totalSubitems) * 100)
    //             : 0;
    
    //         $data[] = [
    //             'id' => $main->id,
    //             'main_heading' => $main->main_heading,
    //             'completion_percent' => $percent
    //         ];
    //     }
    
    //     return response()->json([
    //         'status' => 'success',
    //         'mainHeadings' => $data
    //     ]);
    // }
    public function fetchMainHeadings(Request $request)
{
    $examId = $request->exam_id;

    $examDetails = DB::table('checklist_response')
        ->where('exam_id', $examId)
        ->select('employee_cd','name','programmer_cd','exam_id','year')
        ->first();

    $employeeCd = $examDetails->employee_cd ?? null;
    $programmerCd = $examDetails->programmer_cd ?? null;
    $year = $examDetails->year ?? null;

    $mainHeadings = DB::table('checklist_main')->get();

    $result = [];

    foreach ($mainHeadings as $main) {

        $sectionIds = DB::table('checklist_sections')
            ->where('main_id',$main->id)
            ->pluck('id');

        $totalSections = $sectionIds->count();
        $okCount = 0;
        $responseExists = false;

        foreach ($sectionIds as $secId) {

            $subCount = DB::table('checklist_subitems')
                ->where('section_id',$secId)
                ->count();

            $responses = DB::table('checklist_response')
                ->where('exam_id',$examId)
                ->where('employee_cd',$employeeCd)
                ->where('programmer_cd',$programmerCd)
                ->where('year',$year)
                ->where('section_id',$secId)
                ->get();

            if($responses->count() > 0){
                $responseExists = true;
            }

            $okSubs = $responses->where('status','OK')->count();

            if($subCount > 0 && $okSubs == $subCount){
                $okCount++;
            }
        }

        $percent = $totalSections > 0 ? round(($okCount/$totalSections)*100) : 0;

        if(!$responseExists){
            $overall = 'Not Started';
        }
        elseif($percent == 100){
            $overall = 'OK';
        }
        else{
            $overall = 'Pending';
        }

        $result[] = [
            'id'=>$main->id,
            'main_heading'=>$main->main_heading,
            'completion_percent'=>$percent,
            'overall_status'=>$overall
        ];
    }

    return response()->json([
        'status'=>'success',
        'mainHeadings'=>$result,
        'examDetails'=>$examDetails
    ]);
}

    
public function fetchSections(Request $request)
{
    $mainId = $request->main_id;
    $examId = $request->exam_id;

    $header = DB::table('checklist_response')
        ->where('exam_id',$examId)
        ->select('employee_cd','programmer_cd','year')
        ->first();

    $employeeCd = $header->employee_cd ?? null;
    $programmerCd = $header->programmer_cd ?? null;
    $year = $header->year ?? null;

    $secs = DB::table('checklist_sections')
        ->where('main_id',$mainId)
        ->get();

    $result = [];

    foreach($secs as $sec){

        $totalSubitems = DB::table('checklist_subitems')
            ->where('section_id',$sec->id)
            ->count();

        $responses = DB::table('checklist_response')
            ->where('exam_id',$examId)
            ->where('employee_cd',$employeeCd)
            ->where('programmer_cd',$programmerCd)
            ->where('year',$year)
            ->where('section_id',$sec->id)
            ->get();

        $responseCount = $responses->count();
        $okCount = $responses->where('status','OK')->count();

        if($responseCount == 0){
            $sectionStatus = 'Not Completed';
        }
        elseif($totalSubitems > 0 && $okCount == $totalSubitems){
            $sectionStatus = 'Completed';
        }
        else{
            $sectionStatus = 'On Going';
        }

        $result[] = [
            'id'=>$sec->id,
            'section_name'=>$sec->section_name,
            'status'=>$sectionStatus
        ];
    }

    return response()->json([
        'status'=>'success',
        'sections'=>$result
    ]);
}


public function fetchSubitems(Request $request)
{
    $sectionId = $request->section_id;
    $examId = $request->exam_id;

    $header = DB::table('checklist_response')
        ->where('exam_id',$examId)
        ->select('employee_cd','programmer_cd','year')
        ->first();

    $employeeCd = $header->employee_cd ?? null;
    $programmerCd = $header->programmer_cd ?? null;
    $year = $header->year ?? null;

    $items = DB::table('checklist_subitems as si')
        ->leftJoin('checklist_response as cr', function($join) use ($examId,$employeeCd,$programmerCd,$year){

            $join->on('si.id','=','cr.subitem_id')
                ->where('cr.exam_id',$examId)
                ->where('cr.employee_cd',$employeeCd)
                ->where('cr.programmer_cd',$programmerCd)
                ->where('cr.year',$year);
        })
        ->where('si.section_id',$sectionId)
        ->select(
            'si.subitem',
            'si.description',
            DB::raw("COALESCE(cr.status,'Not Started') as status"),
            'cr.updated_at'
        )
        ->get();

    return response()->json([
        'status'=>'success',
        'subitems'=>$items
    ]);
}
}