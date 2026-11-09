<?php

namespace App\Http\Controllers;

use App\Models\Area;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class AreaController extends Controller
{
    /**
     * Display a listing of areas (DataTables for AJAX, Blade view for normal request).
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Area::query();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    return '<button class="btn btn-warning btn-sm editArea"
                                data-id="' . $row->id . '"
                                data-name="' . e($row->area_name) . '"
                                data-letter="' . e($row->area_letter) . '">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <form action="' . route('admin.areas.destroy', $row->id) . '" method="POST" style="display:inline">
                                ' . csrf_field() . method_field('DELETE') . '
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm(\'Yakin ingin menghapus area ini?\')">
                                    <i class="fas fa-trash"></i> Hapus
                                </button>
                            </form>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('admin.areas.index');
    }

    /**
     * Store a newly created area in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'area_name'   => 'required|string|max:255',
            'area_letter' => 'required|string|max:255',
        ]);

        try {
            Area::create([
                'area_name'   => $request->area_name,
                'area_letter' => $request->area_letter,
            ]);

            return redirect()->route('admin.areas.index')->with('success', 'Area berhasil ditambahkan.');
        } catch (\Exception $e) {
            return redirect()->route('admin.areas.index')->with('error', 'Gagal menambahkan area: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified area in storage.
     */
    public function update(Request $request, Area $area)
    {
        $request->validate([
            'area_name'   => 'required|string|max:255',
            'area_letter' => 'required|string|max:255',
        ]);

        try {
            $area->update([
                'area_name'   => $request->area_name,
                'area_letter' => $request->area_letter,
            ]);

            return redirect()->route('admin.areas.index')->with('success', 'Area berhasil diperbarui.');
        } catch (\Exception $e) {
            return redirect()->route('admin.areas.index')->with('error', 'Gagal memperbarui area: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified area from storage.
     */
    public function destroy(Area $area)
    {
        try {
            $area->delete();
            return redirect()->route('admin.areas.index')->with('success', 'Area berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->route('admin.areas.index')->with('error', 'Gagal menghapus area: ' . $e->getMessage());
        }
    }
}

