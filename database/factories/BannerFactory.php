<?php

namespace Database\Factories;

use App\Models\Banner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Banner>
 */
class BannerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ten_banner' => fake()->sentence(4),
            'hinh_anh' => 'banners/banner.jpg',
            'lien_ket' => fake()->optional()->url(),
            'thu_tu' => fake()->numberBetween(0, 99),
            'trang_thai' => true,
        ];
    }
}
