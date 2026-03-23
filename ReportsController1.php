<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use PDF;

class ReportsController1 extends Controller
{
    public function index(Request $request)
    {
        $tk = $request->query('tk');

        // Session values (same as $_SESSION)
        $CollegeCode  = Session::get('CollegeCode');
        $CollegeGroup = Session::get('CollegeGroup');
        $CollegeType  = Session::get('CollegeType');
		
		 $db = $this->getDynamicDB();

        // Get max allot number
        $alno = $db->selectOne("SELECT MAX(AllotNo) AS AllotNo FROM allotvalues");
        $alno_new = $alno->AllotNo ?? 0;

        // Get allot values
        $allotvalues = $db->selectOne(
            "SELECT * FROM allotvalues WHERE AllotNo = ?",
            [$alno_new]
        );

        // Date list for modal
        $dates = $db->select(
            "SELECT DISTINCT ad.AdmnDate AS date
             FROM allotmentdetails al
             JOIN admndetails ad ON al.RollNo = ad.RollNo
             WHERE ad.AllotNo = ?
               AND MID(al.Allot,5,3) = ?
               AND ad.AdmnDate <> '0000-00-00'",
            [$alno_new, $CollegeCode]
        );

        return view('reports1.index', compact(
            'tk',
            'CollegeGroup',
            'CollegeType',
            'alno_new',
            'allotvalues',
            'dates'
        ));
    }

    public function datewise(Request $request)
    {
        $request->validate([
            'datewise' => 'required'
        ]);

        return redirect()->away(
            url('../pdfpages/admnlist_datewise.php') .
            '?tk=' . request('tk')
        );
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
	
	
	
	
	
	public function admission_generate(Request $request)
    {
        $tk  = $request->query('tk');
        $tks = Session::get('tks');

        $CollegeCode  = Session::get('CollegeCode');
        $CollegeGroup = Session::get('CollegeGroup');
        $LLBCourse    = Session::get('LLBCourse');

        if (!$CollegeCode || $tk !== $tks) {
            return redirect('/index');
        }
		
		 $db = $this->getDynamicDB();


        // College name
        $college = $db->selectOne(
            "SELECT CollegeDesc FROM collegemaster WHERE CollegeCode = ?",
            [$CollegeCode]
        );

        // Rank logic
        $Allot = 'Curr_Admn';

        if ($CollegeGroup == 'L') {
            $rankQry = 'LRank AS Rank';
        } elseif ($CollegeGroup == 'M' && $LLBCourse == 'PM') {
            $rankQry = 'PRank AS Rank';
        } elseif ($CollegeGroup == 'D') {
            $rankQry = 'DRank AS Rank';
        } elseif ($CollegeGroup == 'E' && $LLBCourse == 'LE') {
            $rankQry = 'ERank AS Rank';
        } else {
            $rankQry = "
                IF(mid(Curr_Admn,3,2)='BA',ARank,
                IF(mid($Allot,1,1)='E',ERank,
                IF(mid($Allot,1,1)='M',MRank,
                IF(mid($Allot,1,1)='R',RRank,
                IF(mid($Allot,1,1)='B',BRank,0))))) AS Rank
            ";
        }

        $students = $db->select("
            SELECT
                a.RollNo,
                a.Name,
                b.Clg_Admn_No,
                $rankQry,
                c.CourseDesc,
                IF(MID(Curr_Admn,10,2)='SY','Stray Vacancy',Curr_Admn_No) AS AllotPhase
            FROM candidates a
            JOIN allotmentdetails b ON a.ApplNo = b.ApplNo
            JOIN coursemaster c ON MID(Curr_Admn,3,2) = c.CourseCode
            WHERE MID(Curr_Admn,5,3) = ?
            ORDER BY c.CourseCode, AllotPhase, Rank
        ", [$CollegeCode]);

        return view('reports1.admission-list', [
            'college'  => $college->CollegeDesc,
            'students' => $students,
            'date'     => now()->format('d/m/Y')
        ]);
    }
	
	
	
	
	
	
	public function allotment_index(Request $request)
    {
        $tk  = $request->tk;
        $tks = Session::get('tks');
        $collegeCode  = Session::get('CollegeCode');

        if (!$collegeCode || $tk !== $tks) {
            return redirect('/');
        }

        $collegeGroup = Session::get('CollegeGroup');
        $llbCourse    = Session::get('LLBCourse');
		 $db = $this->getDynamicDB();

        /* College name */
        $college = $db->table('collegemaster')
            ->where('CollegeCode', $collegeCode)
            ->value('CollegeDesc');

        /* Allotment No */
        $allotNo = $db->table('allotvalues')
            ->where('AllotGroup', 'like', "%$collegeGroup%")
            ->max('AllotNo');

        $allot = 'Allot_' . $allotNo;

        /* Allotment date & provisional flag */
        $allotData = $db->table('allotvalues')
            ->where('AllotNo', $allotNo)
            ->first();

        $allotDate = $allotData->DOA ?? '';
        $isProvisional = $allotData->AllotProv ?? 'N';

        /* Rank logic */
        $rankSql = match (true) {
            $collegeGroup === 'L' => 'LRank',
            $collegeGroup === 'M' && $llbCourse === 'PM' => 'PRank',
            $collegeGroup === 'D' => 'DRank',
            $collegeGroup === 'B' => 'BRank',
            default => 'ARank'
        };

        /* Main query */
        $students = $db->table('candidates as a')
            ->join('allotmentdetails as b', 'a.ApplNo', '=', 'b.ApplNo')
            ->join('coursemaster as c', DB::raw("MID($allot,3,2)"), '=', 'c.CourseCode')
            ->selectRaw("
                a.RollNo,
                a.Name,
                a.Gender,
                a.MobileNo,
                a.Category,
                $rankSql as Rank,
                MID($allot,3,2) as CourseCode,
                c.CourseDesc,
                MID($allot,7,2) as SeatCategory
            ")
            ->whereRaw("MID(Allot,5,3) = ?", [$collegeCode])
            ->orderBy('CourseCode')
            ->orderBy('Rank')
            ->get()
            ->groupBy('CourseCode');

        return view('reports1.allotment-list', compact(
            'college',
            'students',
            'allotNo',
            'allotDate',
            'isProvisional'
        ));
    }
	
	
	
	
	
	public function newlyalloted_index(Request $request)
    {
        $tk  = $request->tk;
        $tks = Session::get('tks');
        $collegeCode = Session::get('CollegeCode');

        if (!$collegeCode || $tk !== $tks) {
            return redirect('/');
        }

        $collegeGroup = Session::get('CollegeGroup');
        $llbCourse    = Session::get('LLBCourse');
		$db = $this->getDynamicDB();

        /* College name */
        $college = $db->table('collegemaster')
            ->where('CollegeCode', $collegeCode)
            ->value('CollegeDesc');

        /* Current & Previous allotment */
        $allotNo = $db->table('allotvalues')->max('AllotNo');
        $allotColumn = 'Allot_' . $allotNo;

        /* Allotment date */
        $allotDate = $db->table('allotvalues')
            ->where('AllotNo', $allotNo)
            ->value('DOA');

        /* Rank logic */
        if ($collegeGroup === 'L') {
            $rankSql = 'LRank';
        } elseif ($collegeGroup === 'M' && $llbCourse === 'MD') {
            $rankSql = 'DRank';
        } elseif ($collegeGroup === 'M' && $llbCourse === 'PA') {
            $rankSql = "IF(MID($allotColumn,1,1)='M',ARank,
                        IF(MID($allotColumn,1,1)='D',DRank,0))";
        } elseif ($collegeGroup === 'M' && $llbCourse === 'MH') {
            $rankSql = "IF(SQRank>0,SQRank,IF(HRank>0,HRank,0))";
        } elseif ($collegeGroup === 'P') {
            $rankSql = 'PRank';
        } elseif ($collegeGroup === 'D') {
            $rankSql = 'DRank';
        } elseif ($collegeGroup === 'B') {
            $rankSql = 'BRank';
        } elseif ($collegeGroup === 'N' && $llbCourse === 'UN') {
            $rankSql = "IF(MID($allotColumn,1,1)='X',XRank,
                        IF(MID($allotColumn,1,1)='Y',YRank,
                        IF(MID($allotColumn,1,1)='Z',ZRank,0)))";
        } elseif ($collegeGroup === 'N' && $llbCourse === 'PN') {
            $rankSql = 'NRank';
        } elseif ($collegeGroup === 'E' && $llbCourse === 'LE') {
            $rankSql = 'ERank';
        } else {
            $rankSql = "IF(MID($allotColumn,3,2)='BA',ARank,
                        IF(MID($allotColumn,1,1)='E',ERank,
                        IF(MID($allotColumn,1,1)='M',MRank,
                        IF(MID($allotColumn,1,1)='R',RRank,
                        IF(MID($allotColumn,1,1)='B',BRank,0)))))";
        }

        /* Main query */
        $rows = $db->table('allotmentdetails as a')
            ->join('candidates as c', 'a.ApplNo', '=', 'c.ApplNo')
            ->join('coursemaster as cm', DB::raw("MID(a.$allotColumn,3,2)"), '=', 'cm.CourseCode')
            ->selectRaw("
                a.RollNo,
                a.$allotColumn as Allot,
                $rankSql as Rank,
                cm.CourseCode,
                cm.CourseDesc,
                c.Name,
                c.Gender,
                c.MobileNo,
                c.Category,
                c.Special1,
                c.Special2,
                c.Special3
            ")
            ->whereRaw("MID(a.$allotColumn,5,3) = ?", [$collegeCode])
            ->whereRaw("MID(a.Prev_Admn,5,3) != ?", [$collegeCode])
            ->orderBy('cm.CourseCode')
            ->orderBy('Rank')
            ->orderBy('a.RollNo')
            ->get()
            ->groupBy('CourseCode');

        return view('reports1.newlyalloted-list', compact(
            'college',
            'rows',
            'allotNo',
            'allotDate'
        ));
    }
	
	
	
	
	
	
	public function coursechange_index(Request $request)
    {
        $tk  = $request->tk;
        $tks = Session::get('tks');
        $collegeCode = Session::get('CollegeCode');

        if (!$collegeCode || $tk !== $tks) {
            return redirect('/');
        }

        $collegeGroup = Session::get('CollegeGroup');
		$db = $this->getDynamicDB();

        /* College name */
        $college = $db->table('collegemaster')
            ->where('CollegeCode', $collegeCode)
            ->value('CollegeDesc');

        /* Allotment numbers */
        $allotNo   = $db->table('allotvalues')->max('AllotNo');
        $prevAllot = $allotNo - 1;

        $allotCol = "Allot_$allotNo";
        $prevCol  = "Allot_$prevAllot";

        /* SQL based on CollegeGroup */
        if ($collegeGroup === 'P') {

            $rows = $db->table('allotmentdetails as a')
                ->join('candidates as c', 'a.ApplNo', '=', 'c.ApplNo')
                ->join('coursemaster as cm', DB::raw("MID(a.$allotCol,3,2)"), '=', 'cm.CourseCode')
                ->join('admndetails as ad', 'ad.RollNo', '=', 'a.RollNo')
                ->selectRaw("
                    a.RollNo,
                    ad.AdmnNo,
                    c.Name,
                    c.MobileNo,
                    c.Category,
                    c.Special1,
                    a.$allotCol as Allot,
                    MID(a.$prevCol,3,2) as ocourse,
                    MID(a.$allotCol,3,2) as ncourse
                ")
                ->whereRaw("MID(ad.AC_Employee,1,3) = ?", [$collegeCode])
                ->whereRaw("MID(a.$allotCol,5,3) = ?", [$collegeCode])
                ->whereRaw("MID(a.$prevCol,5,3) = ?", [$collegeCode])
                ->whereRaw("MID(a.$prevCol,3,2) != ''")
                ->whereRaw("MID(a.$allotCol,3,2) != ''")
                ->whereRaw("MID(a.$prevCol,3,2) != MID(a.$allotCol,3,2)")
                ->groupBy('a.RollNo')
                ->orderByRaw("MID(a.$prevCol,3,2), ad.AdmnNo")
                ->get();

        } elseif ($collegeGroup === 'D') {

            $rows = $db->table('allotmentdetails as a')
                ->join('candidates as c', 'a.ApplNo', '=', 'c.ApplNo')
                ->join('coursemaster as cm', DB::raw("MID(a.$allotCol,3,2)"), '=', 'cm.CourseCode')
                ->join('admndetails as ad', 'ad.ApplNo', '=', 'a.ApplNo')
                ->selectRaw("
                    a.RollNo,
                    ad.AdmnNo,
                    c.Name,
                    c.MobileNo,
                    c.Category,
                    c.Special1,
                    a.$allotCol as Allot,
                    MID(a.$prevCol,3,2) as ocourse,
                    MID(a.$allotCol,3,2) as ncourse
                ")
                ->whereRaw("MID(ad.AC_Employee,1,3) = ?", [$collegeCode])
                ->whereRaw("MID(a.$allotCol,5,3) = ?", [$collegeCode])
                ->whereRaw("MID(a.$prevCol,5,3) = ?", [$collegeCode])
                ->whereRaw("MID(a.$prevCol,3,2) != ''")
                ->whereRaw("MID(a.$allotCol,3,2) != ''")
                ->whereRaw("MID(a.$prevCol,3,2) != MID(a.$allotCol,3,2)")
                ->groupBy('a.RollNo')
                ->orderByRaw("MID(a.$prevCol,3,2), ad.AdmnNo")
                ->get();

        } else {

            $rows = $db->table('allotmentdetails as a')
                ->join('candidates as c', 'a.ApplNo', '=', 'c.ApplNo')
                ->join('coursemaster as cm', DB::raw("MID(a.$allotCol,3,2)"), '=', 'cm.CourseCode')
                ->join('admndetails as ad', 'ad.RollNo', '=', 'a.RollNo')
                ->selectRaw("
                    a.RollNo,
                    ad.AdmnNo,
                    c.Name,
                    c.MobileNo,
                    c.Category,
                    c.Special1,
                    c.Special2,
                    c.Special3,
                    a.$allotCol as Allot,
                    MID(a.Prev_Admn,3,2) as ocourse,
                    MID(a.$allotCol,3,2) as ncourse
                ")
                ->whereRaw("MID(ad.AC_Employee,1,3) = ?", [$collegeCode])
                ->whereRaw("MID(a.$allotCol,5,3) = ?", [$collegeCode])
                ->whereRaw("MID(a.Prev_Admn,5,3) = ?", [$collegeCode])
                ->whereRaw("MID(a.Prev_Admn,3,2) != ''")
                ->whereRaw("MID(a.$allotCol,3,2) != ''")
                ->whereRaw("MID(a.Prev_Admn,3,2) != MID(a.$allotCol,3,2)")
                ->orderByRaw("MID(a.Prev_Admn,3,2), ad.AdmnNo")
                ->get();
        }

        return view('reports1.coursechange', compact(
            'college',
            'rows',
            'allotNo'
        ));
    }
	
	
	
	public function payment_index(Request $request)
    {
        $tk  = $request->tk;
        $tks = Session::get('tks');
        $collegeCode = Session::get('CollegeCode');

        if (!$collegeCode || $tk !== $tks) {
            return redirect('/');
        }

        $collegeCode = Session::get('CollegeCode');
        if (!$collegeCode) {
            return redirect('/');
        }
		$db = $this->getDynamicDB();

        $collegeGroup = Session::get('CollegeGroup');
        $llbCourse    = Session::get('LLBCourse');

        /* College name */
        $college = $db->table('collegemaster')
            ->where('CollegeCode', $collegeCode)
            ->value('CollegeDesc');

        /* Allotment */
        $allotNo = $db->table('allotvalues')->max('AllotNo');
        $allotCol = "Allot_$allotNo";
        $payCol   = "PayStatus_$allotNo";

        /* Rank logic */
        if ($collegeGroup === 'L') {
            $rankSql = 'LRank';
        } elseif ($collegeGroup === 'P') {
            $rankSql = 'PRank';
        } elseif ($collegeGroup === 'D') {
            $rankSql = 'DRank';
        } elseif ($collegeGroup === 'A') {
            $rankSql = "IF(SQRank>0,SQRank,IF(ARank>0,ARank,0))";
        } elseif ($collegeGroup === 'M' && $llbCourse === 'PA') {
            $rankSql = "IF(SQRank>0,SQRank,IF(ARank>0,ARank,0))";
        } elseif ($collegeGroup === 'M' && $llbCourse === 'MH') {
            $rankSql = "IF(SQRank>0,SQRank,IF(HRank>0,HRank,0))";
        } elseif ($collegeGroup === 'M' && $llbCourse === 'PM') {
            $rankSql = 'PRank';
        } elseif ($collegeGroup === 'N' && $llbCourse === 'PN') {
            $rankSql = 'NRank';
        } elseif ($collegeGroup === 'N' && $llbCourse === 'UN') {
            $rankSql = "IF(MID($allotCol,1,1)='X',XRank,
                         IF(MID($allotCol,1,1)='Y',YRank,
                         IF(MID($allotCol,1,1)='Z',ZRank,0)))";
        } elseif ($collegeGroup === 'B') {
            $rankSql = 'BRank';
        } elseif ($collegeGroup === 'E' && $llbCourse === 'LE') {
            $rankSql = 'ERank';
        } else {
            $rankSql = "IF(MID($allotCol,3,2)='BA',ARank,
                         IF(MID($allotCol,1,1)='E',ERank,
                         IF(MID($allotCol,1,1)='M',MRank,
                         IF(MID($allotCol,1,1)='R',RRank,
                         IF(MID($allotCol,1,1)='B',BRank,0)))))";
        }

        /* Query */
        $rows = $db->table('candidates as a')
            ->join('allotmentdetails as b', 'a.ApplNo', '=', 'b.ApplNo')
            ->join('coursemaster as c', $db->raw("MID(b.$allotCol,3,2)"), '=', 'c.CourseCode')
            ->selectRaw("
                a.RollNo,
                a.Name,
                a.MobileNo,
                c.CourseDesc,
                $rankSql as Rank
            ")
            ->whereRaw("MID(b.$allotCol,5,3) = ?", [$collegeCode])
            ->whereRaw("b.$payCol = 'Y'")
            ->orderBy('c.CourseCode')
            ->orderBy('a.RollNo')
            ->get();

        return view('reports1.payment-list', compact(
            'college',
            'rows',
            'allotNo'
        ));
    }




public function tc_index()
    {
        $collegeCode  = Session::get('CollegeCode');
        $collegeGroup = Session::get('CollegeGroup');
        $llbCourse    = Session::get('LLBCourse');

        if (!$collegeCode) {
            return redirect('/');
        }
         $db = $this->getDynamicDB();
        /* College name */
        $college = $db->table('collegemaster')
            ->where('CollegeCode', $collegeCode)
            ->value('CollegeDesc');

        /* Allotment */
        $allotNo = $db->table('allotvalues')->max('AllotNo');

        /* Main list */
        $rows = $db->table('tcissued as t')
            ->join('allotmentdetails as al', 't.RollNo', '=', 'al.RollNo')
            ->join('coursemaster as c', $db->raw('MID(t.Allot,3,2)'), '=', 'c.CourseCode')
            ->selectRaw("
                t.RollNo,
                t.Allot,
                t.AllotNo,
                al.Clg_Admn_No,
                c.CourseDesc,
                IF(t.Date='0000-00-00', DATE(t.UpdatTime), t.Date) as IssuedDate
            ")
            ->whereRaw("MID(t.Allot,5,3) = ?", [$collegeCode])
            ->orderBy('c.CourseDesc')
            ->orderBy('t.AllotNo')
            ->get();

        /* Attach candidate + rank */
        foreach ($rows as $r) {
            $cand = $db->table('candidates')
                ->where('RollNo', $r->RollNo)
                ->first();

            $r->Name = $cand->Name ?? '';

            $grp = substr($r->Allot, 0, 1);
            $crs = substr($r->Allot, 2, 2);

            /* Rank resolution */
            if ($grp === 'M') {
                $r->Rank = ($crs === 'BA') ? ($cand->ARank ?? '') : ($cand->MRank ?? '');
            } elseif ($grp === 'E') {
                $r->Rank = $cand->ERank ?? '';
            } elseif ($grp === 'R') {
                $r->Rank = $cand->RRank ?? '';
            } elseif ($grp === 'X') {
                $r->Rank = $cand->XRank ?? '';
            } elseif ($grp === 'Y') {
                $r->Rank = $cand->YRank ?? '';
            } elseif ($grp === 'Z') {
                $r->Rank = $cand->ZRank ?? '';
            } elseif ($collegeGroup === 'D') {
                $r->Rank = $cand->DRank ?? '';
            } elseif ($collegeGroup === 'P') {
                $r->Rank = $cand->PRank ?? '';
            } elseif ($collegeGroup === 'L') {
                $r->Rank = $cand->LRank ?? '';
            } elseif ($collegeGroup === 'B') {
                $r->Rank = $cand->BRank ?? '';
            } else {
                $r->Rank = '';
            }
        }

        return view('reports1.tc-issued', compact(
            'college',
            'rows',
            'allotNo'
        ));
    }
}
