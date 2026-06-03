<?php

namespace App\Http\Controllers;

use App\Models\BaseData;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;

class MissingController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = BaseData::whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('records')
                    ->whereColumn('records.Code_Rack', 'base_datas.Code_Rack')
                    ->whereColumn('records.Location', 'base_datas.Location');
            });

            return DataTables::of($data)
                ->addIndexColumn()
                ->make(true);
        }
        $totalBaseData = BaseData::count();
        $totalMissing = BaseData::whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('records')
                ->whereColumn('records.Code_Rack', 'base_datas.Code_Rack')
                ->whereColumn('records.Location', 'base_datas.Location');
        })->count();
        $totalRecorded = $totalBaseData - $totalMissing;

        return view('admin.missing.index', compact('totalMissing', 'totalRecorded'));
    }
}
