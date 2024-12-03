<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ReportTypeSeeder extends Seeder
{
    public function run(): void
    {
        \App\Models\ReportType::insert([
            ['name' => 'Spam'],
            ['name' => 'Nội dung không phù hợp'],
            ['name' => 'Quấy rối'],
            ['name' => 'Vi phạm bản quyền'],
            ['name' => 'Khác'],
        ]);
    }
}
