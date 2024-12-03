<?php

namespace Database\Factories;

use App\Models\ChatRoom;
use App\Models\Report;
use App\Models\ReportType;
use App\Models\User;
use App\Models\Post;
use App\Models\Comment;
use App\Models\Message;
use App\Models\Story;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReportFactory extends Factory
{
    protected $model = Report::class;

    public function definition()
    {
        // Random chọn loại model và ID
        $reportable = $this->getRandomReportable();

        return [
            'report_type_id' => ReportType::query()->inRandomOrder()->value('id'), // Lấy ID ngẫu nhiên từ bảng report_types
            'reportable_id' => $reportable['id'], // ID của model được chọn
            'reportable_type' => $reportable['type'], // Tên class của model được chọn
            'content' => $this->faker->sentence(),
            'status' => $this->faker->randomElement(['pending', 'approved', 'declined']),
            'user_id' => User::query()->inRandomOrder()->value('id'), // Lấy ID ngẫu nhiên từ bảng users
        ];
    }

    private function getRandomReportable()
    {
        $models = [
            User::class,
            Post::class,
            // Comment::class,
            // Story::class,
            // ChatRoom::class,
            // Message::class,
        ];

        $model = $this->faker->randomElement($models); // Chọn ngẫu nhiên 1 model
        $instance = app($model);

        return [
            'type' => $model,
            'id' => $instance->query()->inRandomOrder()->value('id'), // Lấy ID ngẫu nhiên từ bảng tương ứng
        ];
    }
}
