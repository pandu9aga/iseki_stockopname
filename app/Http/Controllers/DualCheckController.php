<?php

namespace App\Http\Controllers;

use App\Models\Record;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DualCheckController extends Controller
{
    public function index()
    {
        $records = Record::with('member')
            ->where('Is_Dual_Check', 1)
            ->orderBy('Time_Record')
            ->get();

        $groups = [];
        foreach ($records as $r) {
            $key = implode('|', [$r->Code_Part, $r->Name_Part, $r->Code_Rack, $r->Area, $r->Location]);
            $groups[$key][] = $r;
        }

        $rows = [];
        foreach ($groups as $key => $recs) {
            $first = $recs[0];
            $recordIds = [];
            $times = [];
            $members = [];
            $counts = [];
            foreach ($recs as $r) {
                $recordIds[] = $r->Id_Record;
                $times[] = $r->Time_Record;
                $members[] = $r->member ? $r->member->nama : ($r->NIK ?? '-');
                $counts[] = $r->Count_Record;
            }
            $rows[] = [
                'Code_Rack' => $first->Code_Rack,
                'Location' => $first->Location,
                'Code_Part' => $first->Code_Part,
                'Name_Part' => $first->Name_Part,
                'Area' => $first->Area,
                'record_ids' => $recordIds,
                'times' => $times,
                'members' => $members,
                'counts' => $counts,
                'total_records' => count($recs),
            ];
        }

        usort($rows, function ($a, $b) {
            $cmp = strcmp($a['Code_Rack'], $b['Code_Rack']);
            if ($cmp === 0) $cmp = strcmp($a['Code_Part'], $b['Code_Part']);
            if ($cmp === 0) $cmp = strcmp($a['Location'], $b['Location']);
            return $cmp;
        });

        return view('member.dual_check_dashboard', compact('rows'));
    }

    public function create()
    {
        return view('member.dual_check_create');
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
            'Is_Dual_Check' => 1,
        ];

        if ($request->filled('No_Sequence')) {
            $data['No_Sequence'] = $request->No_Sequence;
        }

        Record::create($data);

        return redirect()->route('dual-check.create')->with([
            'recorded' => true,
            'Code_Rack' => $request->Code_Rack,
            'Area' => $request->Area,
            'Code_Part' => $request->Code_Part,
            'Location' => $request->Location,
            'Name_Part' => $request->Name_Part,
            'Count_Record' => $request->Count_Record ?? '',
        ]);
    }

    public function adminIndex()
    {
        $records = Record::with('member')
            ->where('Is_Dual_Check', 1)
            ->orderBy('Time_Record')
            ->get();

        $groups = [];
        foreach ($records as $r) {
            $key = implode('|', [$r->Code_Part, $r->Name_Part, $r->Code_Rack, $r->Area, $r->Location]);
            $groups[$key][] = $r;
        }

        $isSaiful = Auth::guard('admin')->check() && Auth::guard('admin')->user()->name === 'saiful';

        $rows = [];
        foreach ($groups as $key => $recs) {
            $recordIds = [];
            $times = [];
            $members = [];
            $counts = [];
            $actions = [];
            foreach ($recs as $r) {
                $recordIds[] = $r->Id_Record;
                $times[] = $r->Time_Record;
                $members[] = $r->member ? $r->member->nama : ($r->NIK ?? '-');
                $counts[] = $r->Count_Record;
                $actions[] = $isSaiful
                    ? '<button type="button" class="btn btn-danger btn-sm delete-record" data-id="'.$r->Id_Record.'"><i class="fas fa-trash"></i></button>'
                    : '';
            }
            $rows[] = [
                'Code_Rack' => $recs[0]->Code_Rack,
                'Location' => $recs[0]->Location,
                'Code_Part' => $recs[0]->Code_Part,
                'Name_Part' => $recs[0]->Name_Part,
                'Area' => $recs[0]->Area,
                'record_ids' => $recordIds,
                'times' => $times,
                'members' => $members,
                'counts' => $counts,
                'actions' => $actions,
                'total_records' => count($recs),
            ];
        }

        usort($rows, function ($a, $b) {
            $cmp = strcmp($a['Code_Rack'], $b['Code_Rack']);
            if ($cmp === 0) $cmp = strcmp($a['Code_Part'], $b['Code_Part']);
            if ($cmp === 0) $cmp = strcmp($a['Location'], $b['Location']);
            return $cmp;
        });

        return view('admin.dual_check', compact('rows'));
    }
}
