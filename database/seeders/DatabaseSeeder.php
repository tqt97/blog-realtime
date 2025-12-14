<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'TuanTQ',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('12341234'),
        ]);

        $author = User::firstOrCreate(
            ['email' => 'author@test.com'],
            ['name' => 'Author', 'password' => Hash::make('password')]
        );

        $userA = User::firstOrCreate(
            ['email' => 'usera@test.com'],
            ['name' => 'User A', 'password' => Hash::make('password')]
        );

        $userB = User::firstOrCreate(
            ['email' => 'userb@test.com'],
            ['name' => 'User B', 'password' => Hash::make('password')]
        );

        Post::firstOrCreate(
            ['slug' => 'realtime-demo'],
            [
                'user_id' => $author->id,
                'title' => $title = 'Realtime Demo Post',
                'slug' => Str::slug($title),
                'excerpt' => 'Hello realtime!',
                'content' => 'This is a realtime demo post.',
            ]
        );
    }
}
