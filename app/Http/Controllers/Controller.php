<?php

namespace App\Http\Controllers;

use App\Models\BaseData;
use App\Models\Record;

abstract class Controller
{
    protected function getGroupedData()
    {
        $baseDataItems = BaseData::all();
        $records = Record::with('member')->orderBy('Time_Record')->get();
        $dualRecords = Record::where('Is_Dual_Check', 1)->get();

        $dualGroups = [];
        foreach ($dualRecords as $dr) {
            $key = implode('|', [$dr->Code_Part, $dr->Name_Part, $dr->Code_Rack, $dr->Area, $dr->Location]);
            $dualGroups[$key] = ($dualGroups[$key] ?? 0) + 1;
        }

        $recordGroups = [];
        foreach ($records as $r) {
            $key = implode('|', [$r->Code_Part, $r->Name_Part, $r->Code_Rack, $r->Area, $r->Location]);
            $recordGroups[$key][] = $r;
        }

        $rows = [];
        $usedKeys = [];

        foreach ($baseDataItems as $bd) {
            $key = implode('|', [$bd->Code_Part, $bd->Name_Part, $bd->Code_Rack, $bd->Area, $bd->Location]);
            $usedKeys[$key] = true;

            $matchingRecords = $recordGroups[$key] ?? [];
            $recordCount = count($matchingRecords);
            $recordA = $matchingRecords[0] ?? null;
            $recordB = $matchingRecords[1] ?? null;
            $memberA = $recordA ? ($recordA->member ? $recordA->member->nama : ($recordA->NIK ?? '-')) : '-';
            $memberB = $recordB ? ($recordB->member ? $recordB->member->nama : ($recordB->NIK ?? '-')) : '-';

            $dualCount = $dualGroups[$key] ?? 0;

            $rows[] = [
                'Code_Rack' => $bd->Code_Rack,
                'Location' => $bd->Location,
                'Code_Part' => $bd->Code_Part,
                'Name_Part' => $bd->Name_Part,
                'Area' => $bd->Area,
                'Time_A' => $recordA ? $recordA->Time_Record : '',
                'Time_B' => $recordB ? $recordB->Time_Record : '',
                'Member_A' => $memberA,
                'Member_B' => $memberB,
                'Count_A' => $recordA ? $recordA->Count_Record : '',
                'Count_B' => $recordB ? $recordB->Count_Record : '',
                'scan_count' => $recordCount,
                'Id_Record_A' => $recordA ? $recordA->Id_Record : null,
                'Id_Record_B' => $recordB ? $recordB->Id_Record : null,
                'Dual_Count' => $dualCount,
            ];
        }

        foreach ($recordGroups as $key => $matchingRecords) {
            if (!isset($usedKeys[$key])) {
                $recordCount = count($matchingRecords);
                $recordA = $matchingRecords[0] ?? null;
                $recordB = $matchingRecords[1] ?? null;
                $memberA = $recordA ? ($recordA->member ? $recordA->member->nama : ($recordA->NIK ?? '-')) : '-';
                $memberB = $recordB ? ($recordB->member ? $recordB->member->nama : ($recordB->NIK ?? '-')) : '-';

                $dualCount = $dualGroups[$key] ?? 0;

                $rows[] = [
                    'Code_Rack' => $recordA->Code_Rack,
                    'Location' => $recordA->Location,
                    'Code_Part' => $recordA->Code_Part,
                    'Name_Part' => $recordA->Name_Part,
                    'Area' => $recordA->Area,
                    'Time_A' => $recordA ? $recordA->Time_Record : '',
                    'Time_B' => $recordB ? $recordB->Time_Record : '',
                    'Member_A' => $memberA,
                    'Member_B' => $memberB,
                    'Count_A' => $recordA ? $recordA->Count_Record : '',
                    'Count_B' => $recordB ? $recordB->Count_Record : '',
                    'scan_count' => $recordCount,
                    'Id_Record_A' => $recordA ? $recordA->Id_Record : null,
                    'Id_Record_B' => $recordB ? $recordB->Id_Record : null,
                    'Dual_Count' => $dualCount,
                ];
            }
        }

        usort($rows, function ($a, $b) {
            $hasTimeA = !empty($a['Time_A']);
            $hasTimeB = !empty($b['Time_A']);
            if ($hasTimeA && !$hasTimeB) return -1;
            if (!$hasTimeA && $hasTimeB) return 1;
            if ($hasTimeA && $hasTimeB) {
                return strtotime($b['Time_A']) - strtotime($a['Time_A']);
            }
            $cmp = strcmp($a['Code_Rack'], $b['Code_Rack']);
            if ($cmp === 0) $cmp = strcmp($a['Code_Part'], $b['Code_Part']);
            if ($cmp === 0) $cmp = strcmp($a['Location'], $b['Location']);
            return $cmp;
        });

        return $rows;
    }
}
