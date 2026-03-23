<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;



namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    // Show upload page
    public function index(Request $request)
    {
        $CollegeCode = $request->query('college', 'ENG001');

        // Fetch uploaded files list
        $uploads = DB::table('uploads')
            ->where('CollegeCode', $CollegeCode)
            ->orderByDesc('created_at')
            ->get();

        return view('upload', compact('CollegeCode', 'uploads'));
    }

    // Handle file upload
    public function store(Request $request)
    {
        $request->validate([
            'CollegeCode' => 'required',
            'file' => 'required|file|mimes:pdf,doc,docx,xls,xlsx,jpg,png|max:5120',
        ]);

        $CollegeCode = $request->input('CollegeCode');
        $EmployeeCd = auth()->id() ?? 'system';

        // Store in storage/app/public/uploads/{college}/filename.ext
        $path = $request->file('file')->store("uploads/$CollegeCode", 'public');

        DB::table('uploads')->insert([
            'CollegeCode' => $CollegeCode,
            'filename' => basename($path),
            'filepath' => $path,
            'EmployeeID' => $EmployeeCd,
            'created_at' => now(),
        ]);

        return back()->with('success', 'File uploaded successfully!');
    }

    // Download file
    public function download($id)
    {
        $file = DB::table('uploads')->find($id);
        if (!$file) {
            abort(404);
        }

        return Storage::disk('public')->download($file->filepath);
    }
}
