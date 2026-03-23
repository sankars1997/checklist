<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        if ($request->isMethod('post')) {

            if ($request->hidden_id == "") {
                // INSERT
                DB::table('employee')->insert([
                    'CollegeGroup' => $request->CollegeGroup,
                    'CollegeType' => $request->CollegeType,
                    'CollegeCode' => $request->CollegeCode,
                    'EmployeeCd' => $request->EmployeeCd,
                    'Name' => $request->Name,
                    'Desig' => $request->Desig,
                    'Password' => bcrypt($request->Password),
                    'Active' => $request->Active,
                    'EmpType' => $request->EmpType,
                    'EmpRole' => $request->EmpRole,
                    'masked' => $request->masked,
                    'EmpLogged' => 0,
                ]);
            } else {
                // UPDATE
                DB::table('employee')
                    ->where('EmployeeCd', $request->hidden_id)
                    ->update([
                        'CollegeGroup' => $request->CollegeGroup,
                        'CollegeType' => $request->CollegeType,
                        'CollegeCode' => $request->CollegeCode,
                        'Name' => $request->Name,
                        'Desig' => $request->Desig,
                        'Active' => $request->Active,
                        'EmpType' => $request->EmpType,
                        'EmpRole' => $request->EmpRole,
                        'masked' => $request->masked,
                    ]);
            }

            return redirect('/employees'); // refresh page
        }

        $employees = DB::table('employee')->get();

        return view('checklist.employees', compact('employees'));
    }
}