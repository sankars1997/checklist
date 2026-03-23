<?php

namespace App\Http\Controllers;
use App\Models\Employee;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class logincontroller extends Controller
{
     



    public function login(){
        
        $captchaNumber = rand(1000, 9999);

        // Store it in session to validate later
        session(['captcha_number' => $captchaNumber]);

    
        return view('login', compact('captchaNumber'));

    }


    public function loginsave(Request $request)
{

    if ($request->captcha != session('captcha_number')) {
        session(['captcha_number' => rand(1000, 9999)]); // regenerate
    
        return back()->withErrors([
            'captcha' => 'Invalid captcha'
        ])->withInput($request->except('passwd'));
    }
    

    $request->validate([
        'EmployeeCd' => ['required', 'string', 'alpha_num', 'exists:employee,EmployeeCd'],
        'passwd' => ['required', 'string'],
    ]);

    $credentials = [
        'EmployeeCd' => $request->EmployeeCd,
        'password' => $request->passwd,
    ];

    if (Auth::attempt($credentials)) {
        $request->session()->regenerate();

        $employee = Auth::user();

        session([
            'CollegeCode' => $employee->CollegeCode,
            'Name' => $employee->Name,
            'EmpType' => $employee->EmpType,
            'CollegeGroup' => $employee->CollegeGroup,
            'CollegeType' => $employee->CollegeType,
            'EmpRole' => $employee->EmpRole,
            'masked' => $employee->masked,
        ]);

        // Log successful login
        DB::table('logtime')->insert([
            'EmployeeCd' => $employee->EmployeeCd,
            'LoginTime' => now(),
            'IpAddress' => $request->ip(),
            'LogStatus' => 'L',
            'Updatetime' => now(),
        ]);

        // Redirection based on EmpType
        if ($employee->EmpLogged == 0) {
            return redirect()->route('password.change');
        }


        if (($employee->EmpType === 'A' || $employee->EmpType === 'N' ) && $employee->EmpLogged != 0) {
            return redirect()->route('checklist.index');
        }

        if (( $employee->EmpType === 'E') && $employee->EmpLogged != 0) {
            return redirect()->route('homeview');
        }

        if ($employee->EmpType === 'P' && $employee->EmpLogged != 0) {
            return redirect()->route('home');
        }

        if ($employee->EmpType === 'C') {
            return redirect()->route('checklist.index');
        }

        if ($employee->EmpType === 'D') {
            return redirect()->route('dte.college.details');
        }

        return redirect()->route('home');
    }
    else
    {
        // This matches your legacy PHP: logging failed login attempt
        DB::table('logtime')->insert([
            'EmployeeCd' => $request->EmployeeCd,
            'LoginTime' => now(),
            'IpAddress' => $request->ip(),
            'LogStatus' => 'P', 
            'Updatetime' => now(),
        ]);

        // Redirect to login with `err=1` like your PHP logic
        //return redirect()->route('login', ['err' => 1]);
return back()->withErrors([
        'EmployeeCd' => 'The provided credentials do not match our records.',
    ]);
    }
}
public function logout(Request $request)
{
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login'); 

}

////logout
// app/Http/Controllers/AdminController.php

public function changePassword()
{
    // Show change password form
    return view('CAP.changepassword');
}

public function updatePassword(Request $request)
{
    $request->validate([
        'current_password' => ['required', 'string'],
        'new_password'     => ['required', 'string', 'min:8', 'confirmed'], // password_confirmation field must match
    ]);

    $user = Auth::user();

    // Check current password
    if (!Hash::check($request->current_password, $user->Password)) {
        return back()->withErrors(['current_password' => 'Current password does not match.']);
    }

    // Update password
    $user->password = Hash::make($request->new_password);
    $user->EmpLogged = 1; // Mark user as logged in at least once
    $user->save();

    return back()->with('success', 'Password changed successfully.');
}

}