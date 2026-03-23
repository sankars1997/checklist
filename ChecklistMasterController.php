<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

class ChecklistMasterController extends Controller
{

public function index()
{
    return view('checklist.master');
}

/* MAIN */

public function getMain()
{
    return response()->json(
        DB::table('checklist_main')->get()
    );
}

public function addMain(Request $r)
{
    DB::table('checklist_main')->insert([
        'main_heading'=>$r->name,
        'status'=>'Y'
    ]);

    return response()->json(['success'=>true]);
}

public function updateMain(Request $r)
{
    DB::table('checklist_main')
    ->where('id',$r->id)
    ->update(['main_heading'=>$r->name]);

    return response()->json(['success'=>true]);
}

public function deleteMain(Request $r)
{
    DB::table('checklist_main')
    ->where('id',$r->id)
    ->delete();

    return response()->json(['success'=>true]);
}

public function toggleMain(Request $r)
{
    $item = DB::table('checklist_main')->where('id',$r->id)->first();

    $status = $item->status=='Y'?'N':'Y';

    DB::table('checklist_main')
    ->where('id',$r->id)
    ->update(['status'=>$status]);

    return response()->json(['success'=>true]);
}

/* SECTIONS */

public function getSections($main_id)
{
    return response()->json(
        DB::table('checklist_sections')
        ->where('main_id',$main_id)
        ->get()
    );
}

public function addSection(Request $r)
{
    DB::table('checklist_sections')->insert([
        'main_id'=>$r->main_id,
        'section_name'=>$r->name,
        'status'=>'Y'
    ]);

    return response()->json(['success'=>true]);
}

public function updateSection(Request $r)
{
    DB::table('checklist_sections')
    ->where('id',$r->id)
    ->update([
        'section_name'=>$r->name
    ]);

    return response()->json(['success'=>true]);
}

public function deleteSection(Request $r)
{
    DB::table('checklist_sections')
    ->where('id',$r->id)
    ->delete();

    return response()->json(['success'=>true]);
}

public function toggleSection(Request $r)
{
    $item = DB::table('checklist_sections')
    ->where('id',$r->id)
    ->first();

    $status = $item->status=='Y'?'N':'Y';

    DB::table('checklist_sections')
    ->where('id',$r->id)
    ->update(['status'=>$status]);

    return response()->json(['success'=>true]);
}

/* SUB ITEMS */

public function getSubitems($section_id)
{
    return response()->json(
        DB::table('checklist_subitems')
        ->where('section_id',$section_id)
        ->get()
    );
}

public function addSubitem(Request $r)
{
    DB::table('checklist_subitems')->insert([
        'section_id'=>$r->section_id,
        'subitem'=>$r->name,
        'description'=>$r->description,
        'status'=>'Y'
    ]);

    return response()->json(['success'=>true]);
}

public function updateSubitem(Request $r)
{
    DB::table('checklist_subitems')
    ->where('id',$r->id)
    ->update([
        'subitem'=>$r->name,
        'description'=>$r->description
    ]);

    return response()->json(['success'=>true]);
}

public function deleteSubitem(Request $r)
{
    DB::table('checklist_subitems')
    ->where('id',$r->id)
    ->delete();

    return response()->json(['success'=>true]);
}

public function toggleSubitem(Request $r)
{
    $item = DB::table('checklist_subitems')
    ->where('id',$r->id)
    ->first();

    $status = $item->status=='Y'?'N':'Y';

    DB::table('checklist_subitems')
    ->where('id',$r->id)
    ->update(['status'=>$status]);

    return response()->json(['success'=>true]);
}

}