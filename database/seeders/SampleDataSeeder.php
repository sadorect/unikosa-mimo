<?php

namespace Database\Seeders;

use App\Models\Chapter;
use App\Models\Set;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $sets = [
            ['name' => 'Set of 2000', 'year' => 2000],
            ['name' => 'Set of 2005', 'year' => 2005],
            ['name' => 'Set of 2010', 'year' => 2010],
            ['name' => 'Set of 2015', 'year' => 2015],
            ['name' => 'Set of 2020', 'year' => 2020],
        ];

        foreach ($sets as $set) {
            Set::firstOrCreate(['year' => $set['year']], $set);
        }

        $chapters = [
            ['name' => 'Nigeria Chapter', 'country' => 'Nigeria', 'city' => 'Lagos'],
            ['name' => 'UK Chapter', 'country' => 'United Kingdom', 'city' => 'London'],
            ['name' => 'US Chapter', 'country' => 'United States', 'city' => 'New York'],
            ['name' => 'Canada Chapter', 'country' => 'Canada', 'city' => 'Toronto'],
        ];

        foreach ($chapters as $chapter) {
            Chapter::firstOrCreate(['name' => $chapter['name']], $chapter);
        }

        $admin = User::firstOrCreate(['email' => 'admin@unikosa.org'], [
            'name' => 'Admin User',
            'password' => Hash::make('password'),
            'status' => 'approved',
            'graduating_set_id' => 1,
            'country' => 'Nigeria',
            'email_verified_at' => now(),
        ]);
        if (!$admin->hasRole('super_admin')) {
            $admin->assignRole('super_admin');
        }

        $member = User::firstOrCreate(['email' => 'john@example.com'], [
            'name' => 'John Doe',
            'password' => Hash::make('password'),
            'status' => 'approved',
            'graduating_set_id' => 2,
            'chapter_id' => 1,
            'profession' => 'Software Engineer',
            'country' => 'Nigeria',
            'city' => 'Lagos',
            'email_verified_at' => now(),
        ]);
        if (!$member->hasRole('member')) {
            $member->assignRole('member');
        }

        // One approved user per staff role so each access level can be previewed.
        // All use the password "password".
        $staff = [
            ['email' => 'setrep@unikosa.org',    'name' => 'Set Rep User',           'role' => 'set_representative', 'graduating_set_id' => 2, 'chapter_id' => 1],
            ['email' => 'chapter@unikosa.org',   'name' => 'Chapter Head User',      'role' => 'chapter_head',       'graduating_set_id' => 3, 'chapter_id' => 2],
            ['email' => 'moderator@unikosa.org', 'name' => 'Content Moderator User', 'role' => 'content_moderator',  'graduating_set_id' => 4, 'chapter_id' => 1],
            ['email' => 'finance@unikosa.org',   'name' => 'Finance Admin User',     'role' => 'finance_admin',      'graduating_set_id' => 5, 'chapter_id' => 1],
        ];

        foreach ($staff as $person) {
            $user = User::firstOrCreate(['email' => $person['email']], [
                'name' => $person['name'],
                'password' => Hash::make('password'),
                'status' => 'approved',
                'graduating_set_id' => $person['graduating_set_id'],
                'chapter_id' => $person['chapter_id'],
                'country' => 'Nigeria',
                'city' => 'Lagos',
                'email_verified_at' => now(),
            ]);
            if (!$user->hasRole($person['role'])) {
                $user->assignRole($person['role']);
            }
        }

        User::firstOrCreate(['email' => 'jane@example.com'], [
            'name' => 'Jane Smith',
            'password' => Hash::make('password'),
            'status' => 'pending',
            'graduating_set_id' => 3,
            'country' => 'United Kingdom',
            'city' => 'London',
        ]);

        $imported = [
            ['name' => 'Adebayo Ogundimu', 'email' => 'adebayo@example.com', 'graduating_set_id' => 1, 'chapter_id' => 1, 'profession' => 'Doctor', 'country' => 'Nigeria', 'city' => 'Lagos'],
            ['name' => 'Chioma Nwosu', 'email' => 'chioma@example.com', 'graduating_set_id' => 2, 'chapter_id' => 2, 'profession' => 'Lawyer', 'country' => 'United Kingdom', 'city' => 'London'],
            ['name' => 'Emeka Obi', 'email' => 'emeka@example.com', 'graduating_set_id' => 3, 'chapter_id' => 3, 'profession' => 'Engineer', 'country' => 'United States', 'city' => 'New York'],
            ['name' => 'Funke Adeyemi', 'email' => 'funke@example.com', 'graduating_set_id' => 4, 'chapter_id' => 4, 'profession' => 'Designer', 'country' => 'Canada', 'city' => 'Toronto'],
            ['name' => 'Tunde Bakare', 'email' => 'tunde@example.com', 'graduating_set_id' => 1, 'chapter_id' => 1, 'profession' => 'Teacher', 'country' => 'Nigeria', 'city' => 'Ibadan'],
        ];

        foreach ($imported as $importedMember) {
            User::firstOrCreate(['email' => $importedMember['email']], [
                ...$importedMember,
                'status' => 'approved',
                'imported' => true,
                'account_claimed' => false,
                'imported_at' => now()->subDays(rand(1, 30)),
                'password' => null,
            ]);
        }
    }
}
