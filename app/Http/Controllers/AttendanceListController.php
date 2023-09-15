<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AttendanceList;
use Illuminate\Support\Facades\DB;

class AttendanceListController extends Controller {

  private $statuses = ['absent', 'excuse', 'sick', 'present'];

  public function set(Request $req){

    $req->validate([
      'id' => 'required|exists:users',
      'status' => 'required|in:'.implode(',', $this->statuses),
      'date' => 'required|date_format:Y-m-d'
    ]);

    $date    = $req->input('date');
    $id      = $req->input('id');
    $status  = $req->input('status');
    $implode = 'implode';

    $updated = DB::table('attendance_lists')
    ->whereRaw("created_at BETWEEN '{$date}' AND '{$date} 23:59:59'")
    ->update([
      'data' => DB::raw(
        "REGEXP_REPLACE(
          CONCAT(
            '{$id},',
            UNIX_TIMESTAMP(NOW()), ',',
            '{$status};',
            REGEXP_REPLACE(
              data,
              '{$id},[0-9]+,[^;]+;?',
              ''
            )
          ),
          ';$',
          ''
        )"
      )
    ]);

    if(!$updated) DB::table('attendance_lists')
    ->whereRaw(
      "not exists(
        SELECT id
        FROM attendance_lists
        WHERE created_at BETWEEN '{$date}' AND '{$date} 23:59:59'
      )"
    )->insert([

      'data' => DB::raw(
        "CONCAT(
          '{$id},',
          UNIX_TIMESTAMP(NOW()), ',',
          '{$status}'
        )"
      ),

      'created_at' => DB::raw(
        "'$date'"
      ),

      'updated_at' => DB::raw(
        "'$date'"
      )

    ]);

  }

  public function get($date){

    $data = DB::table('attendance_lists')
    ->whereRaw("created_at BETWEEN '{$date}' AND '{$date} 23:59:59'")
    ->get()->toArray();

    if(isset($data[0]))
      return $data[0];

    return response()->noContent();

  }


}

