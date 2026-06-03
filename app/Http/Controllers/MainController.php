<?php

namespace App\Http\Controllers;

use App\Models\Record;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class MainController extends Controller
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
        return view('dashboard');
    }

    public function create()
    {
        return view('record_create');
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
            'photos' => 'required',
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
            'Area' => $request->Area,
            'No_Card' => $request->No_Card,
            'Location' => $request->Location,
            'Time_Record' => now(),
            'Photo_Record' => json_encode($photoPaths),
        ]);

        return redirect()->route('page.record.create')->with([
            'recorded' => true,
            'Code_Rack' => $request->Code_Rack,
            'Area' => $request->Area,
            'Code_Part' => $request->Code_Part,
            'Location' => $request->Location,
            'Name_Part' => $request->Name_Part,
            'Count_Record' => $request->Count_Record ?? '',
        ]);
    }

    public function dashboardNoCount(Request $request)
    {
        if ($request->ajax()) {
            $data = Record::orderBy('Time_Record', 'desc');
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('photos', function($row) {
                    return '<button type="button" class="btn btn-primary btn-sm view-record" data-id="'.$row->Id_Record.'"><i class="fas fa-eye"></i></button>';
                })
                ->addColumn('action', function($row) {
                    if (Auth::guard('admin')->check() && Auth::guard('admin')->user()->name === 'saiful') {
                        return '<button type="button" class="btn btn-danger btn-sm delete-record" data-id="'.$row->Id_Record.'"><i class="fas fa-trash"></i></button>';
                    }
                    return '';
                })
                ->rawColumns(['photos', 'action'])
                ->make(true);
        }
        return view('admin.dashboard_no_count');
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
        $sheet->setCellValue('A1', 'No');
        $sheet->setCellValue('B1', 'Rack');
        $sheet->setCellValue('C1', 'Count');
        $sheet->setCellValue('D1', 'Time');
        $sheet->setCellValue('E1', 'Member');
        $sheet->setCellValue('F1', 'Name Part');
        $sheet->setCellValue('G1', 'Code Part');
        $sheet->setCellValue('H1', 'Area');
        $sheet->setCellValue('I1', 'Location');

        $row = 2;
        foreach ($records as $i => $record) {
            $sheet->setCellValue('A' . $row, $i + 1);
            $sheet->setCellValue('B' . $row, $record->Code_Rack);
            $sheet->setCellValue('C' . $row, $record->Count_Record);
            $sheet->setCellValue('D' . $row, $record->Time_Record);
            $sheet->setCellValue('E' . $row, $record->member ? $record->member->nama : '-');
            $sheet->setCellValue('F' . $row, $record->Name_Part);
            $sheet->setCellValue('G' . $row, $record->Code_Part);
            $sheet->setCellValue('H' . $row, $record->Area);
            $sheet->setCellValue('I' . $row, $record->Location);
            $row++;
        }

        $lastRow = $row - 1;
        $lastCol = $sheet->getHighestColumn();
        $styleArray = [
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
        ];
        $sheet->getStyle("A1:{$lastCol}{$lastRow}")->applyFromArray($styleArray);
        $sheet->getStyle("A1:{$lastCol}1")->getFont()->setBold(true);
        $sheet->getStyle("A1:{$lastCol}1")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFF4CCCC');
        $sheet->setAutoFilter("B1:{$lastCol}{$lastRow}");

        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'records_' . now()->format('YmdHis') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        $writer->save('php://output');
        exit;
    }
}
