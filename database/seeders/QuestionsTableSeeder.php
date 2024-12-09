<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuestionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('questions')->insert([
            ['text' => '1 + 1 = ?', 'answer_id' => null],
            ['text' => '2 + 2 = ?', 'answer_id' => null],
            ['text' => 'Vậy còn 3 + 3 = ?', 'answer_id' => 1],
        ]);
    }
}
