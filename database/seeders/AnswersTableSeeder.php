<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AnswersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('answers')->insert([
            ['text' => 'Bằng 2', 'question_id' => 1],
            ['text' => 'Bằng 3', 'question_id' => 2],
            ['text' => 'Bằng 4', 'question_id' => 3],
        ]);
    }
}
