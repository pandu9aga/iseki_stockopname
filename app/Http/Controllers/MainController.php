<?php

namespace App\Http\Controllers;

use App\Models\BaseData;
use App\Models\Record;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class MainController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $rows = $this->getGroupedData();
            // Guest: replace member names with '-'
            foreach ($rows as &$row) {
                $row['Member_A'] = '-';
                $row['Member_B'] = '-';
            }
            return response()->json(['data' => array_values($rows)]);
        }
        $rows = $this->getGroupedData();
        foreach ($rows as &$row) {
            $row['Member_A'] = '-';
            $row['Member_B'] = '-';
        }
        return view('dashboard', compact('rows'));
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
            return \Yajra\DataTables\Facades\DataTables::of($data)
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
        $areas = BaseData::distinct()->orderBy('Area')->pluck('Area');
        return view('admin.dashboard_no_count', compact('areas'));
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
        $area = $request->area;

        $start = \Carbon\Carbon::parse($start_date);
        $end = \Carbon\Carbon::parse($end_date);

        if ($start->format('FY') === $end->format('FY')) {
            $dateRangeText = $start->format('j') . ' - ' . $end->format('j F Y');
        } else {
            $dateRangeText = $start->format('j F') . ' - ' . $end->format('j F Y');
        }

        $records = Record::with('member')
            ->whereBetween('Time_Record', [$start_date . ' 00:00:00', $end_date . ' 23:59:59'])
            ->where('Area', $area)
            ->get();

        $grouped = [];
        foreach ($records as $r) {
            $key = implode('|', [$r->Code_Part, $r->Name_Part, $r->Code_Rack, $r->Area, $r->Location]);
            $grouped[$key][] = $r;
        }

        $validItems = [];
        foreach ($grouped as $key => $recs) {
            if (count($recs) < 2) continue;
            usort($recs, fn($a, $b) => strtotime($a->Time_Record) - strtotime($b->Time_Record));
            $countA = $recs[0]->Count_Record;
            $countB = $recs[1]->Count_Record;
            if ($countA != $countB) continue;
            $validItems[] = [
                'Code_Part' => $recs[0]->Code_Part,
                'Name_Part' => $recs[0]->Name_Part,
                'Area' => $recs[0]->Area,
                'Location' => $recs[0]->Location,
                'Code_Rack' => $recs[0]->Code_Rack,
                'Count' => $countA,
            ];
        }

        usort($validItems, fn($a, $b) => strcmp($a['Location'] . $a['Code_Rack'], $b['Location'] . $b['Code_Rack']));

        $templatePath = public_path('assets/template.xlsx');
        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Stock Opname');
        $sheet->setCellValue('D2', 'AREA ' . $area);
        $sheet->setCellValue('G2', $dateRangeText);
        $sheet->setCellValue('A50', '');
        $sheet->setCellValue('D50', '');

        $perPage = 40;
        $chunks = array_chunk($validItems, $perPage);
        $totalPages = count($chunks);

        $borderStyle = ['borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]];
        $currentRow = 9;
        $pageNo = 1;
        $globalNo = 1;

        foreach ($chunks as $pageData) {
            if ($pageNo > 1) {
                $currentRow += 3;
                $hdr = 'A' . $currentRow . ':H' . $currentRow;
                foreach (range('A', 'H') as $i => $col) {
                    $sheet->setCellValue($col . $currentRow, $sheet->getCell($col . '8')->getValue());
                }
                $sheet->getStyle($hdr)->applyFromArray([
                    'font' => ['bold' => true, 'size' => 9],
                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD9D9D9']],
                    'alignment' => ['horizontal' => 'center', 'vertical' => 'bottom'],
                    'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
                ]);
                $currentRow++;
            }

            $dataStartRow = $currentRow;
            foreach ($pageData as $item) {
                $sheet->setCellValueExplicit('A' . $currentRow, $globalNo, DataType::TYPE_NUMERIC);
                $sheet->setCellValueExplicit('B' . $currentRow, $item['Code_Part'], DataType::TYPE_STRING);
                $sheet->setCellValueExplicit('C' . $currentRow, $item['Name_Part'], DataType::TYPE_STRING);
                $sheet->setCellValueExplicit('D' . $currentRow, $item['Area'], DataType::TYPE_STRING);
                $sheet->setCellValueExplicit('E' . $currentRow, $item['Location'], DataType::TYPE_STRING);
                $sheet->setCellValueExplicit('F' . $currentRow, $item['Code_Rack'], DataType::TYPE_STRING);
                $sheet->setCellValueExplicit('G' . $currentRow, $item['Count'], DataType::TYPE_NUMERIC);
                $sheet->setCellValueExplicit('H' . $currentRow, 'PCS', DataType::TYPE_STRING);
                $currentRow++;
                $globalNo++;
            }
            $dataEndRow = $currentRow - 1;

            if ($dataEndRow >= $dataStartRow) {
                $range = 'A' . $dataStartRow . ':H' . $dataEndRow;
                $sheet->getStyle($range)->applyFromArray($borderStyle);
                $sheet->getStyle('A' . $dataStartRow . ':A' . $dataEndRow)->getAlignment()->setHorizontal('center');
                $sheet->getStyle('B' . $dataStartRow . ':C' . $dataEndRow)->getAlignment()->setHorizontal('left');
                $sheet->getStyle('D' . $dataStartRow . ':H' . $dataEndRow)->getAlignment()->setHorizontal('center');
            }

            $sheet->setCellValue('A' . $currentRow, 'Page : ' . $pageNo);
            $sheet->setCellValue('D' . $currentRow, now()->format('n/j/Y g:i A'));
            $pageNo++;
        }

        if ($totalPages === 0) {
            $sheet->setCellValue('A50', 'Page : 1');
            $sheet->setCellValue('D50', now()->format('n/j/Y g:i A'));
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'Stockopname Actual Cheklist - ' . $dateRangeText . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        $writer->save('php://output');
        exit;
    }

}
