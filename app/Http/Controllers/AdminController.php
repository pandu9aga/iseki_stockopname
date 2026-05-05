<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Admin::query();
            return DataTables::of($data)
                ->addColumn('action', function($row){
                    return '<button class="btn btn-warning btn-sm editAdmin" data-id="'.$row->id.'" data-name="'.$row->name.'">Edit</button>
                            <form action="'.route('admin.users.destroy', $row->id).'" method="POST" style="display:inline">
                                '.csrf_field().'
                                '.method_field('DELETE').'
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm(\'Are you sure?\')">Delete</button>
                            </form>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('admin.users.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:admins',
            'password' => 'required|min:6',
        ]);

        Admin::create([
            'name' => $request->name,
            'password' => $request->password,
        ]);

        return redirect()->route('admin.users.index')->with('success', 'Admin created successfully');
    }

    public function update(Request $request, Admin $user)
    {
        $request->validate([
            'name' => 'required|unique:admins,name,'.$user->id,
        ]);

        $data = [
            'name' => $request->name,
        ];

        if ($request->password) {
            $data['password'] = $request->password;
        }

        $user->update($data);

        return redirect()->route('admin.users.index')->with('success', 'Admin updated successfully');
    }

    public function destroy(Admin $user)
    {
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'Admin deleted successfully');
    }
}
