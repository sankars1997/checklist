<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamController extends Controller
{
    public function index()
    {
        $exams = DB::table('exams')->get();
        return view('checklist.addexam', compact('exams'));
    }

    public function store(Request $request)
    {
        DB::table('exams')->insert([
            'exam_name' => $request->exam_name,
            'year'      => $request->year,
            'status'    => $request->status, // 'Y' or 'N'
            'created_at'=> now(),
            'updated_at'=> now()
        ]);

        return redirect()->back();
    }

    public function update(Request $request, $id)
    {
        DB::table('exams')->where('examid', $id)->update([
            'exam_name' => $request->exam_name,
            'year'      => $request->year,
            'status'    => $request->status, // 'Y' or 'N'
            'updated_at'=> now()
        ]);

        return redirect()->back();
    }

    public function toggle($id)
    {
        $exam = DB::table('exams')->where('examid', $id)->first();
        if ($exam) {
            $newStatus = $exam->status == 'Y' ? 'N' : 'Y';
            DB::table('exams')->where('examid', $id)
                ->update(['status'=>$newStatus, 'updated_at'=>now()]);
        }
        return redirect()->back();
    }
}