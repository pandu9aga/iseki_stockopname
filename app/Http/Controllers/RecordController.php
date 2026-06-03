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
            $rows = $this->getGroupedData();
            return response()->json(['data' => array_values($rows)]);
        }
        $rows = $this->getGroupedData();
        return view('member.dashboard', compact('rows'));
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
            'No_Sequence' => 'nullable',
            'Area' => 'required',
            'No_Card' => 'required',
            'Location' => 'required',
            'Count_Record' => 'required|numeric',
            'photos.*' => 'image|mimes:jpeg,png,jpg,gif|max:20480'
        ]);

        $existingCount = Record::where('Code_Part', $request->Code_Part)
            ->where('Name_Part', $request->Name_Part)
            ->where('Code_Rack', $request->Code_Rack)
            ->where('Area', $request->Area)
            ->where('Location', $request->Location)
            ->count();

        if ($existingCount >= 2) {
            return redirect()->back()->with('error', 'QR ini sudah di-record 2 kali, tidak bisa direcord lagi');
        }

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

                $mime = $photo->getMimeType();
                $srcPath = $photo->getRealPath();

                if ($mime === 'image/png') {
                    $src = imagecreatefrompng($srcPath);
                } elseif ($mime === 'image/gif') {
                    $src = imagecreatefromgif($srcPath);
                } else {
                    $src = imagecreatefromjpeg($srcPath);
                }

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

        $data = [
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
        ];

        if ($request->filled('No_Sequence')) {
            $data['No_Sequence'] = $request->No_Sequence;
        }

        Record::create($data);

        return redirect()->route('record.create')->with([
            'recorded' => true,
            'Code_Rack' => $request->Code_Rack,
            'Area' => $request->Area,
            'Code_Part' => $request->Code_Part,
            'Location' => $request->Location,
            'Name_Part' => $request->Name_Part,
            'Count_Record' => $request->Count_Record ?? '',
        ]);
    }

    public function adminIndex(Request $request)
    {
        $rows = $this->getGroupedData();
        $isSaiful = Auth::guard('admin')->check() && Auth::guard('admin')->user()->name === 'saiful';
        foreach ($rows as &$row) {
            $btn = '';
            if ($isSaiful) {
                if ($row['Id_Record_A']) {
                    $btn .= '<button type="button" class="btn btn-danger btn-sm delete-record mx-1" data-id="'.$row['Id_Record_A'].'" title="Delete A"><i class="fas fa-trash"></i></button>';
                }
                if ($row['Id_Record_B']) {
                    $btn .= '<button type="button" class="btn btn-danger btn-sm delete-record mx-1" data-id="'.$row['Id_Record_B'].'" title="Delete B"><i class="fas fa-trash"></i></button>';
                }
            }
            $row['action'] = $btn;
        }
        if ($request->ajax()) {
            return response()->json(['data' => array_values($rows)]);
        }
        return view('admin.dashboard', compact('rows'));
    }

    public function show(Record $record)
    {
        $record->load('member');
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
            'member_name' => $record->member ? $record->member->nama : '-',
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

        $records = Record::with('member')->get();
        if ($start_date && $end_date) {
            $records = Record::with('member')
                ->whereBetween('Time_Record', [$start_date . ' 00:00:00', $end_date . ' 23:59:59'])
                ->get();
        }

        $grouped = [];
        foreach ($records as $r) {
            $key = implode('|', [$r->Code_Part, $r->Name_Part, $r->Code_Rack, $r->Area, $r->Location]);
            $grouped[$key][] = $r;
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'No');
        $sheet->setCellValue('B1', 'Rack');
        $sheet->setCellValue('C1', 'Location');
        $sheet->setCellValue('D1', 'Code Part');
        $sheet->setCellValue('E1', 'Name Part');
        $sheet->setCellValue('F1', 'Time');
        $sheet->setCellValue('G1', 'Count A');
        $sheet->setCellValue('H1', 'Count B');

        $row = 2;
        $i = 1;
        foreach ($grouped as $key => $recs) {
            if (count($recs) < 2) continue;

            usort($recs, fn($a, $b) => strtotime($a->Time_Record) - strtotime($b->Time_Record));
            $recA = $recs[0];
            $recB = $recs[1];

            $sheet->setCellValue('A' . $row, $i++);
            $sheet->setCellValue('B' . $row, $recA->Code_Rack);
            $sheet->setCellValue('C' . $row, $recA->Location);
            $sheet->setCellValue('D' . $row, $recA->Code_Part);
            $sheet->setCellValue('E' . $row, $recA->Name_Part);
            $sheet->setCellValue('F' . $row, $recA->Time_Record);
            $sheet->setCellValue('G' . $row, $recA->Count_Record);
            $sheet->setCellValue('H' . $row, $recB->Count_Record);
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
