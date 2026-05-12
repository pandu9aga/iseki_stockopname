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
            $data = Record::with('member')->orderBy('Time_Record', 'desc');
            return DataTables::of($data)
                ->addColumn('member_name', function($row) {
                    return $row->member ? $row->member->nama : '-';
                })
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
            'No_Sequence' => 'required',
            'Area' => 'required',
            'No_Card' => 'required',
            'Location' => 'required',
            'Count_Record' => 'required|numeric',
            'photos.*' => 'image|mimes:jpeg,png,jpg,gif|max:20480'
        ]);

        $photoPaths = [];
        if ($request->hasFile('photos')) {
            $folder = now()->format('m_Y');
            $uploadPath = public_path("uploads/{$folder}");
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            foreach ($request->file('photos') as $photo) {
                $filename = uniqid() . '.jpg';
                $destPath = "{$uploadPath}/{$filename}";

                // Compress using GD to stay under 1MB
                $mime = $photo->getMimeType();
                $srcPath = $photo->getRealPath();

                if ($mime === 'image/png') {
                    $src = imagecreatefrompng($srcPath);
                } elseif ($mime === 'image/gif') {
                    $src = imagecreatefromgif($srcPath);
                } else {
                    $src = imagecreatefromjpeg($srcPath);
                }

                // Try quality from 85 down until under 1MB
                $quality = 85;
                do {
                    ob_start();
                    imagejpeg($src, null, $quality);
                    $imageData = ob_get_clean();
                    $quality -= 5;
                } while (strlen($imageData) > 1048576 && $quality > 10);

                imagedestroy($src);
                file_put_contents($destPath, $imageData);

                $photoPaths[] = "uploads/{$folder}/{$filename}";
            }
        }

        Record::create([
            'Code_Part' => $request->Code_Part,
            'Name_Part' => $request->Name_Part,
            'Code_Rack' => $request->Code_Rack,
            'No_Sequence' => $request->No_Sequence,
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
            $data = Record::with('member')->orderBy('Time_Record', 'desc');
            return DataTables::of($data)
                ->addColumn('member_name', function($row) {
                    return $row->member ? $row->member->nama : '-';
                })
                ->addColumn('action', function($row) {
                    $btn = '<button type="button" class="btn btn-primary btn-sm view-record" data-id="'.$row->Id_Record.'"><i class="fas fa-eye"></i></button>';
                    
                    if (Auth::guard('admin')->check() && Auth::guard('admin')->user()->name === 'saiful') {
                        $btn .= ' <button type="button" class="btn btn-danger btn-sm delete-record" data-id="'.$row->Id_Record.'"><i class="fas fa-trash"></i></button>';
                    }
                    
                    return $btn;
                })
                ->rawColumns(['action'])
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
            'no_sequence' => $record->No_Sequence,
            'area' => $record->Area,
            'no_card' => $record->No_Card,
            'location' => $record->Location,
            'nik' => $record->NIK,
            'time' => $record->Time_Record,
            'count' => $record->Count_Record,
            'photos' => json_decode($record->Photo_Record)
        ]);
    }

    public function destroy(Record $record)
    {
        if (Auth::guard('admin')->user()->name !== 'saiful') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $photos = json_decode($record->Photo_Record);
        if ($photos) {
            foreach ($photos as $photo) {
                $path = public_path($photo);
                if (file_exists($path)) {
                    @unlink($path);
                }
            }
        }

        $record->delete();

        return response()->json(['message' => 'Record deleted successfully']);
    }

    public function export(Request $request)
    {
        $start_date = $request->start_date;
        $end_date = $request->end_date;

        $query = Record::query();
        if ($start_date && $end_date) {
            $query->whereBetween('Time_Record', [$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
        }

        $records = $query->with('member')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Rack');
        $sheet->setCellValue('B1', 'Count');
        $sheet->setCellValue('C1', 'Member Name');
        $sheet->setCellValue('D1', 'Time');
        $sheet->setCellValue('E1', 'Name Part');
        $sheet->setCellValue('F1', 'Code Part');
        $sheet->setCellValue('G1', 'Seq');
        $sheet->setCellValue('H1', 'Area');
        $sheet->setCellValue('I1', 'Location');

        $row = 2;
        foreach ($records as $record) {
            $sheet->setCellValue('A' . $row, $record->Code_Rack);
            $sheet->setCellValue('B' . $row, $record->Count_Record);
            $sheet->setCellValue('C' . $row, $record->member ? $record->member->nama : '-');
            $sheet->setCellValue('D' . $row, $record->Time_Record);
            $sheet->setCellValue('E' . $row, $record->Name_Part);
            $sheet->setCellValue('F' . $row, $record->Code_Part);
            $sheet->setCellValue('G' . $row, $record->No_Sequence);
            $sheet->setCellValue('H' . $row, $record->Area);
            $sheet->setCellValue('I' . $row, $record->Location);
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
