<?php

namespace App\Console\Commands;

use App\Models\Spin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateSpinsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-spins-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cập nhật lượt quay cho người dùng qua ngày mới';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Lấy tất cả người dùng
        $users = DB::table('users')->get();

        foreach ($users as $user) {
            // Cập nhật hoặc tạo bản ghi Spin cho ngày mới
            Spin::updateOrCreate(
                ['user_id' => $user->id, 'spin_date' => today()],
                ['spins_left' => 2] // Đặt lại số lượt quay cho ngày mới
            );
        }

        $this->info('Lượt quay đã được cập nhật cho tất cả người dùng.');
    }
}
