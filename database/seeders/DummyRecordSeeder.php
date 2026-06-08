<?php

namespace Database\Seeders;

use App\Models\BaseData;
use App\Models\Member;
use App\Models\Record;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DummyRecordSeeder extends Seeder
{
    public function run(): void
    {
        $baseItems = BaseData::where('Area', '42-43')->inRandomOrder()->take(60)->get();
        $members = Member::all();
        $now = Carbon::now();

        $bar = $this->command->getOutput()->createProgressBar(120);
        $bar->start();

        foreach ($baseItems as $item) {
            $memberA = $members->random();
            $memberB = $members->random();
            $countValue = rand(10, 500);

            Record::create([
                'Code_Part' => $item->Code_Part,
                'Name_Part' => $item->Name_Part,
                'Code_Rack' => $item->Code_Rack,
                'Area' => $item->Area,
                'No_Card' => 'CARD-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
                'Location' => $item->Location,
                'NIK' => $memberA->nik,
                'Time_Record' => (clone $now)->subHours(2),
                'Count_Record' => $countValue,
                'Photo_Record' => '[]',
            ]);
            $bar->advance();

            Record::create([
                'Code_Part' => $item->Code_Part,
                'Name_Part' => $item->Name_Part,
                'Code_Rack' => $item->Code_Rack,
                'Area' => $item->Area,
                'No_Card' => 'CARD-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
                'Location' => $item->Location,
                'NIK' => $memberB->nik,
                'Time_Record' => clone $now,
                'Count_Record' => $countValue,
                'Photo_Record' => '[]',
            ]);
            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info('Created 120 dummy records (60 OK pairs) from BaseData');
    }
}
