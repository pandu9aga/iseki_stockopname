<?php

namespace App\Http\Controllers;

use App\Models\BaseData;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use PhpOffice\PhpSpreadsheet\IOFactory;

class BaseDataController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = BaseData::query();
            $isSaiful = auth('admin')->user()->name === 'saiful';
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) use ($isSaiful) {
                    if (!$isSaiful) return '';
                    return '<button class="btn btn-warning btn-sm editBaseData" data-id="' . $row->Id_Base_Data . '" data-code_part="' . $row->Code_Part . '" data-name_part="' . $row->Name_Part . '" data-code_rack="' . $row->Code_Rack . '" data-area="' . $row->Area . '" data-location="' . $row->Location . '">Edit</button>
                            <form action="' . route('admin.base-data.destroy', $row->Id_Base_Data) . '" method="POST" style="display:inline">
                                ' . csrf_field() . '
                                ' . method_field('DELETE') . '
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm(\'Are you sure?\')">Delete</button>
                            </form>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('admin.base_data.index');
    }

    public function store(Request $request)
    {
        if (auth('admin')->user()->name !== 'saiful') {
            return redirect()->route('admin.base-data.index')->with('error', 'Unauthorized');
        }
        $request->validate([
            'Code_Part'  => 'required',
            'Name_Part'  => 'required',
            'Code_Rack'  => 'required',
            'Location'   => 'required',
        ]);

        try {
            BaseData::create($request->only(['Code_Part', 'Name_Part', 'Code_Rack', 'Area', 'Location']));
            return redirect()->route('admin.base-data.index')->with('success', 'Base Data created successfully');
        } catch (\Exception $e) {
            return redirect()->route('admin.base-data.index')->with('error', 'Failed to create: ' . $e->getMessage());
        }
    }

    public function update(Request $request, BaseData $base_datum)
    {
        if (auth('admin')->user()->name !== 'saiful') {
            return redirect()->route('admin.base-data.index')->with('error', 'Unauthorized');
        }
        $request->validate([
            'Code_Part'  => 'required',
            'Name_Part'  => 'required',
            'Code_Rack'  => 'required',
            'Location'   => 'required',
        ]);

        try {
            $base_datum->update($request->only(['Code_Part', 'Name_Part', 'Code_Rack', 'Area', 'Location']));
            return redirect()->route('admin.base-data.index')->with('success', 'Base Data updated successfully');
        } catch (\Exception $e) {
            return redirect()->route('admin.base-data.index')->with('error', 'Failed to update: ' . $e->getMessage());
        }
    }

    public function destroy(BaseData $base_datum)
    {
        if (auth('admin')->user()->name !== 'saiful') {
            return redirect()->route('admin.base-data.index')->with('error', 'Unauthorized');
        }
        $base_datum->delete();
        return redirect()->route('admin.base-data.index')->with('success', 'Base Data deleted successfully');
    }

    public function import(Request $request)
    {
        if (auth('admin')->user()->name !== 'saiful') {
            return redirect()->route('admin.base-data.index')->with('error', 'Unauthorized');
        }
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);

        try {
            $file = $request->file('file');
            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            $imported = 0;
            foreach ($rows as $index => $row) {
                if ($index == 0) continue;

                $location  = trim($row[1] ?? '');
                $codeRack  = trim($row[2] ?? '');
                $codePart  = trim($row[3] ?? '');
                $namePart  = trim($row[4] ?? '');
                $area      = trim($row[5] ?? '');

                if (empty($codePart) || empty($namePart) || empty($codeRack) || empty($location)) continue;

                BaseData::create([
                    'Code_Part' => $codePart,
                    'Name_Part' => $namePart,
                    'Code_Rack' => $codeRack,
                    'Area'      => $area,
                    'Location'  => $location,
                ]);
                $imported++;
            }

            return redirect()->route('admin.base-data.index')->with('success', "$imported records imported successfully");
        } catch (\Exception $e) {
            return redirect()->route('admin.base-data.index')->with('error', 'Import failed: ' . $e->getMessage());
        }
    }
}
