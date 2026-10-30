<?php

namespace App\Http\Controllers;

use App\Models\Auditor;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class AuditorController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Auditor::query();
            return DataTables::of($data)
                ->addColumn('action', function($row){
                    return '<button class="btn btn-warning btn-sm editAuditor" data-id="'.$row->id.'" data-name="'.$row->name.'">Edit</button>
                            <form action="'.route('admin.auditors.destroy', $row->id).'" method="POST" style="display:inline">
                                '.csrf_field().'
                                '.method_field('DELETE').'
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm(\'Are you sure?\')">Delete</button>
                            </form>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('admin.auditors.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:auditors',
            'password' => 'required',
        ]);

        try {
            Auditor::create([
                'name' => $request->name,
                'password' => $request->password,
            ]);
            return redirect()->route('admin.auditors.index')->with('success', 'Auditor created successfully');
        } catch (\Exception $e) {
            return redirect()->route('admin.auditors.index')->with('error', 'Failed to create auditor: ' . $e->getMessage());
        }
    }

    public function update(Request $request, Auditor $auditor)
    {
        $request->validate([
            'name' => 'required|unique:auditors,name,'.$auditor->id,
        ]);

        $data = [
            'name' => $request->name,
        ];

        if ($request->password) {
            $data['password'] = $request->password;
        }

        try {
            $auditor->update($data);
            return redirect()->route('admin.auditors.index')->with('success', 'Auditor updated successfully');
        } catch (\Exception $e) {
            return redirect()->route('admin.auditors.index')->with('error', 'Failed to update auditor: ' . $e->getMessage());
        }
    }

    public function destroy(Auditor $auditor)
    {
        $auditor->delete();
        return redirect()->route('admin.auditors.index')->with('success', 'Auditor deleted successfully');
    }
}

