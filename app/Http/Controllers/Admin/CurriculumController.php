<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\User;
use App\Models\Schoolyr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use Illuminate\Support\Facades\DB;

class CurriculumController extends Controller
{

    /**
     * POST /admin/settings/admins
     */
    public function store(Request $request)
    {

        $schoolyr   = $request->schoolyr_id;
        $grade      = $request->grade_id;
        $adviser    = $request->adviser_id;
        $items      = $request->itemlist['data'];

        DB::beginTransaction();



        try {
            
            if(isset($request->id)) {
                    $id = $request->id;
                    DB::update(
                        "UPDATE curriculum SET schoolyr_id = ? , grade_id = ? , adviser_id =? WHERE id = ?",
                         [$schoolyr, $grade, $adviser, $id]
                    );


                     foreach ($items as $row) {
                        $subjectId = $row['subject_id'];
                        if(isset($row["id"])) {
                            DB::update(
                                "UPDATE curriculum_child SET subject_id = ? , deleted = ?   WHERE id = ?",
                                [$subjectId, $row["deleted"], $row["id"]]
                            );
                        } else {
                             DB::insert(
                                "INSERT INTO curriculum_child (curriculum_id, subject_id)
                                VALUES (?, ?)",
                                [$id, $subjectId]
                            );
                        }
                        
                    }

            } else {

                // -------------------------------------
                // 1. INSERT HEADER
                // -------------------------------------
                DB::insert(
                    "INSERT INTO curriculum (schoolyr_id, grade_id, adviser_id)
                    VALUES (?, ?, ? )",
                    [$schoolyr, $grade, $adviser]
                );

                // GET last inserted header id
                $headerId = DB::getPdo()->lastInsertId();

                // -------------------------------------
                // 2. INSERT CHILD ROWS
                // -------------------------------------
                foreach ($items as $row) {
                    $subjectId = $row['subject_id'];

                    DB::insert(
                        "INSERT INTO curriculum_child (curriculum_id, subject_id)
                        VALUES (?, ?)",
                        [$headerId, $subjectId]
                    );
                }

            }


            DB::commit();

            return back()->with('success', 'Curriculum saved.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    
}
