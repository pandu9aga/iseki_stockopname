<?php

namespace App\Http\Controllers;

use App\Models\Record;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class RecordController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Record::query()->orderBy('Time_Record', 'desc');
            return DataTables::of($data)
                ->addColumn('photos', function($row) {
                    return '<button type="button" class="btn btn-primary btn-sm view-record" data-id="'.$row->Id_Record.'"><i class="fas fa-eye"></i></button>';
                })
                ->rawColumns(['photos'])
                ->make(true);
        }
        return view('member.dashboard');
    }

    public function create()
    {
        return view('member.record_create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'Code_Part' => 'required',
            'Name_Part' => 'required',
            'Code_Rack' => 'required',
            'Area' => 'required',
            'No_Card' => 'required',
            'Location' => 'required',
            'Count_Record' => 'required|integer',
            'photos.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $photoPaths = [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $path = $photo->store('records', 'public');
                $photoPaths[] = $path;
            }
        }

        Record::create([
            'Code_Part' => $request->Code_Part,
            'Name_Part' => $request->Name_Part,
            'Code_Rack' => $request->Code_Rack,
            'Area' => $request->Area,
            'No_Card' => $request->No_Card,
            'Location' => $request->Location,
            'NIK' => Auth::guard('member')->user()->nik,
            'Time_Record' => now(),
            'Count_Record' => $request->Count_Record,
            'Photo_Record' => json_encode($photoPaths),
        ]);

        return redirect()->route('dashboard')->with('success', 'Record saved successfully');
    }

    public function adminIndex(Request $request)
    {
        if ($request->ajax()) {
            $data = Record::query()->orderBy('Time_Record', 'desc');
            return DataTables::of($data)
                ->addColumn('photos', function($row) {
                    return '<button type="button" class="btn btn-primary btn-sm view-record" data-id="'.$row->Id_Record.'"><i class="fas fa-eye"></i></button>';
                })
                ->rawColumns(['photos'])
                ->make(true);
        }
        return view('admin.dashboard');
    }

    public function show(Record $record)
    {
        return response()->json([
            'id' => $record->Id_Record,
            'code' => $record->Code_Part,
            'name' => $record->Name_Part,
            'rack' => $record->Code_Rack,
            'area' => $record->Area,
            'no_card' => $record->No_Card,
            'location' => $record->Location,
            'nik' => $record->NIK,
            'time' => $record->Time_Record,
            'count' => $record->Count_Record,
            'photos' => json_decode($record->Photo_Record)
        ]);
    }

    public function export(Request $request)
    {
        $start_date = $request->start_date;
        $end_date = $request->end_date;

        $query = Record::query();
        if ($start_date && $end_date) {
            $query->whereBetween('Time_Record', [$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
        }

        $records = $query->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Code Part');
        $sheet->setCellValue('C1', 'Name Part');
        $sheet->setCellValue('D1', 'Rack');
        $sheet->setCellValue('E1', 'Area');
        $sheet->setCellValue('F1', 'No Card');
        $sheet->setCellValue('G1', 'Location');
        $sheet->setCellValue('H1', 'NIK');
        $sheet->setCellValue('I1', 'Time');
        $sheet->setCellValue('J1', 'Count');

        $row = 2;
        foreach ($records as $record) {
            $sheet->setCellValue('A' . $row, $record->Id_Record);
            $sheet->setCellValue('B' . $row, $record->Code_Part);
            $sheet->setCellValue('C' . $row, $record->Name_Part);
            $sheet->setCellValue('D' . $row, $record->Code_Rack);
            $sheet->setCellValue('E' . $row, $record->Area);
            $sheet->setCellValue('F' . $row, $record->No_Card);
            $sheet->setCellValue('G' . $row, $record->Location);
            $sheet->setCellValue('H' . $row, $record->NIK);
            $sheet->setCellValue('I' . $row, $record->Time_Record);
            $sheet->setCellValue('J' . $row, $record->Count_Record);
            $row++;
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'records_' . now()->format('YmdHis') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        $writer->save('php://output');
        exit;
    }
}
