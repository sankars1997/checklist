<?php

namespace App\Http\Controllers;
use App\Models\Employee;
use App\Models\Msg;
use Illuminate\Support\Facades\Hash;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

use Illuminate\Support\Facades\Schema;
class viewcontroller extends Controller
{

    public function showHomeView()
    {
        $exams = DB::table('defvalues')->select('ExamName')->distinct()->get();
        return view('view.homeview', compact('exams'));
    }

    public function examdetails($examName){
      

        
        $defValue = DB::table('defvalues')->where('ExamName', $examName)->first();

        if (!$defValue) {
            return "No configuration found for exam: $examName";
        }
    
        // Step 2: Define dynamic DB connection
        Config::set('database.connections.dynamic_exam', [
            'driver' => 'mysql',
            'host' => $defValue->HostName,
            'port' => 3306,
            'database' => $defValue->ApplicationDB,
            'username' => env('DB_USERNAME', 'po-2'),  // fallback to default
            'password' => env('DB_PASSWORD', 'Sankar@123#'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ]);
    
        try {
            // Step 3: Connect and fetch tables
            $dbName = $defValue->ApplicationDB;
            $tables = DB::connection('dynamic_exam')->select("SHOW TABLES FROM `$dbName`");
            $key = "Tables_in_$dbName";
    
            $tableNames = array_map(fn($t) => $t->$key, $tables);
    
            $tableData = [];
    
            foreach ($tableNames as $table) {
                $columns = Schema::connection('dynamic_exam')->getColumnListing($table);
                $rows = DB::connection('dynamic_exam')->table($table)->get();

    
                $tableData[$table] = [
                    'columns' => $columns,
                    'rows' => $rows,
                ];
            }
    
            // Step 4: Return to your view
            return view('view.examdetails', compact('tableData', 'examName'));
    
        } catch (\Exception $e) {
            return "Connection failed: " . $e->getMessage();
        }
    }

}