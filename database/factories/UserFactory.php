<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            // 'email_verified_at' => now(),
            'password' => '$argon2id$v=19$m=1024,t=2,p=2$bHp4RzVNL2Q5LjMudW5GMg$BezO6KSPgiFFeR7Aj4rd21RG7ujU3EZi87J/CT8orrw', // password = '12345'
            // 'remember_token' => Str::random(10),
        ];
    }
}