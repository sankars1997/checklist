<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;


use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MinorityController extends Controller
{
    public function status(Request $request)
    {
        $CollegeCode = $request->CollegeCode;

        DB::table('minority')->updateOrInsert(
            ['CollegeCode' => $CollegeCode],
            [
                'MinorityStatus' => $request->minority,
                'Upload' => $request->minority == 'Y' ? 'N' : null,
                'UpdateTime' => now()
            ]
        );

        return redirect()->route('minority.index');
    }

    // Upload PDF to database as BLOB
    public function upload(Request $request)
    {
        $CollegeCode = $request->CollegeCode;

        $request->validate([
            'image' => 'required|mimes:pdf|max:2048'
        ]);

        $file = $request->file('image');
        $fileContents = file_get_contents($file->getRealPath());

        DB::table('minority')->updateOrInsert(
            ['CollegeCode' => $CollegeCode],
            [
                'Minority' => $fileContents,
                'Upload' => 'N',
                'UpdateTime' => now()
            ]
        );

        return redirect()->route('minority.index', ['a' => 1]);
    }

    // Serve PDF for preview
    public function preview($CollegeCode)
    {
        $record = DB::table('minority')->where('CollegeCode', $CollegeCode)->first();

        if (!$record || !$record->Minority) {
            abort(404, 'File not found.');
        }

        return response($record->Minority)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="minority_'.$CollegeCode.'.pdf"');
    }

    // Finalize uploaded PDF
    public function finalize(Request $request)
    {
        $CollegeCode = $request->CollegeCode;

        DB::table('minority')->where('CollegeCode', $CollegeCode)->update([
            'Upload' => 'Y'
        ]);

        return redirect()->route('minority.index')->with('msg', 'Finalized successfully.');
    }

    // Show the main page
    public function index(Request $request)
    {
        $CollegeCode = $request->CollegeCode ?? session('CollegeCode');
        $tk = $request->tk ?? '';

        $minority = DB::table('minority')->where('CollegeCode', $CollegeCode)->first();
        $error_msg = session('error_msg') ?? null;

        return view('minority.index', compact('CollegeCode', 'tk', 'minority', 'error_msg'));
    }
}