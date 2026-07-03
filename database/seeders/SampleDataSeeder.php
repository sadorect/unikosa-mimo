<?php

namespace Database\Seeders;

use App\Models\AlumniJob;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BusinessListing;
use App\Models\Campaign;
use App\Models\Chapter;
use App\Models\Due;
use App\Models\Event;
use App\Models\ForumGroup;
use App\Models\ForumPost;
use App\Models\ForumReply;
use App\Models\GalleryAlbum;
use App\Models\Set;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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

        $this->seedEvents($admin);
        $this->seedForum($admin, $member);
        $this->seedBlog($admin, $member);
        $this->seedJobs($member);
        $this->seedBusinessListings($member);
        $this->seedCampaigns($admin);
        $this->seedDues();
        $this->seedGallery($admin);
        $this->seedCelebrations();
    }

    /**
     * Give a few demo members birthdays/anniversaries in the next couple of weeks
     * (with the birthday privacy flag on) so the Celebrations widgets show data.
     */
    protected function seedCelebrations(): void
    {
        $offsets = [
            'john@example.com' => 1,
            'adebayo@example.com' => 3,
            'chioma@example.com' => 6,
            'emeka@example.com' => 9,
            'funke@example.com' => 12,
        ];

        foreach ($offsets as $email => $offset) {
            $user = User::where('email', $email)->first();
            if (! $user) {
                continue;
            }

            // Birthday: same month/day, some years ago; anniversary a bit further out.
            $user->date_of_birth = now()->addDays($offset)->subYears(30)->format('Y-m-d');
            $user->wedding_anniversary = now()->addDays($offset + 5)->subYears(5)->format('Y-m-d');
            $user->save();

            $user->profilePrivacy()->updateOrCreate([], ['show_dob' => true]);
        }
    }

    protected function seedEvents(User $admin): void
    {
        $events = [
            ['title' => 'Annual Alumni Homecoming', 'location' => 'Lagos Continental Hotel', 'is_virtual' => false, 'start_at' => now()->addMonth(), 'chapter_id' => 1, 'capacity' => 300],
            ['title' => 'UK Chapter Networking Night', 'location' => 'London', 'is_virtual' => false, 'start_at' => now()->addWeeks(3), 'chapter_id' => 2, 'capacity' => 80],
            ['title' => 'Virtual Career Panel: Tech in 2026', 'location' => null, 'is_virtual' => true, 'livestream_url' => 'https://meet.example.com/career-panel', 'start_at' => now()->addWeeks(2), 'chapter_id' => null, 'capacity' => 500],
            ['title' => 'US Chapter Summer Picnic', 'location' => 'Central Park, New York', 'is_virtual' => false, 'start_at' => now()->addMonths(2), 'chapter_id' => 3, 'capacity' => 150],
            ['title' => 'Founders Day Gala 2025', 'location' => 'Eko Hotel, Lagos', 'is_virtual' => false, 'start_at' => now()->subMonths(3), 'chapter_id' => 1, 'capacity' => 400],
        ];

        foreach ($events as $event) {
            Event::firstOrCreate(['title' => $event['title']], [
                'slug' => Str::slug($event['title']),
                'description' => "Join fellow alumni for the {$event['title']}. More details to follow.",
                'location' => $event['location'],
                'is_virtual' => $event['is_virtual'],
                'livestream_url' => $event['livestream_url'] ?? null,
                'start_at' => $event['start_at'],
                'chapter_id' => $event['chapter_id'],
                'created_by' => $admin->id,
                'capacity' => $event['capacity'],
            ]);
        }
    }

    protected function seedForum(User $admin, User $member): void
    {
        $groups = [
            ['name' => 'General Discussion', 'type' => 'interest', 'set_id' => null, 'chapter_id' => null],
            ['name' => 'Set of 2010 Reunion Planning', 'type' => 'set', 'set_id' => 3, 'chapter_id' => null],
            ['name' => 'Nigeria Chapter Talk', 'type' => 'chapter', 'set_id' => null, 'chapter_id' => 1],
        ];

        $groupModels = [];
        foreach ($groups as $group) {
            $groupModels[] = ForumGroup::firstOrCreate(['name' => $group['name']], [
                'slug' => Str::slug($group['name']),
                'description' => "Discussion space for {$group['name']}.",
                'type' => $group['type'],
                'set_id' => $group['set_id'],
                'chapter_id' => $group['chapter_id'],
                'created_by' => $admin->id,
                'is_moderated' => true,
            ]);
        }

        $posts = [
            ['title' => 'Welcome to the new alumni platform!', 'body' => 'Excited to see everyone reconnecting here. Introduce yourself below!', 'group' => 0, 'author' => $admin],
            ['title' => 'Any recommendations for the reunion venue?', 'body' => 'Looking at a few options in Lagos for the Set of 2010 reunion. Thoughts?', 'group' => 1, 'author' => $member],
            ['title' => 'Nigeria chapter meetup this weekend', 'body' => 'A few of us are meeting up in Lagos this Saturday, all welcome.', 'group' => 2, 'author' => $admin],
        ];

        foreach ($posts as $post) {
            $forumPost = ForumPost::firstOrCreate(['title' => $post['title']], [
                'forum_group_id' => $groupModels[$post['group']]->id,
                'user_id' => $post['author']->id,
                'body' => $post['body'],
                'status' => 'approved',
            ]);

            if ($forumPost->replies()->count() === 0) {
                ForumReply::create([
                    'forum_post_id' => $forumPost->id,
                    'user_id' => $post['author']->is($admin) ? $member->id : $admin->id,
                    'body' => 'Great to see this discussion started!',
                ]);
            }
        }
    }

    protected function seedBlog(User $admin, User $member): void
    {
        $categories = ['Alumni Spotlight', 'Announcements', 'Career Tips'];
        $categoryModels = [];
        foreach ($categories as $name) {
            $categoryModels[$name] = BlogCategory::firstOrCreate(['name' => $name], ['slug' => Str::slug($name)]);
        }

        $posts = [
            ['title' => 'Meet the Alumni Powering Africa\'s Tech Scene', 'category' => 'Alumni Spotlight', 'author' => $admin],
            ['title' => 'Platform Launch: Welcome to the New Alumni Hub', 'category' => 'Announcements', 'author' => $admin],
            ['title' => '5 Networking Tips for Career Growth', 'category' => 'Career Tips', 'author' => $member],
            ['title' => 'Dues Season is Here: What Your Contribution Supports', 'category' => 'Announcements', 'author' => $admin],
        ];

        foreach ($posts as $post) {
            $blogPost = BlogPost::firstOrCreate(['title' => $post['title']], [
                'user_id' => $post['author']->id,
                'slug' => Str::slug($post['title']),
                'excerpt' => Str::limit("An update from the UNIKOSA alumni community about {$post['title']}.", 120),
                'body' => "<p>{$post['title']} — full story coming soon. This is placeholder content generated to preview the blog module.</p>",
                'status' => 'published',
                'published_at' => now()->subDays(rand(1, 60)),
            ]);

            $category = $categoryModels[$post['category']];
            if (!$blogPost->categories()->where('blog_category_id', $category->id)->exists()) {
                $blogPost->categories()->attach($category->id);
            }
        }
    }

    protected function seedJobs(User $member): void
    {
        $jobs = [
            ['title' => 'Backend Engineer', 'company' => 'Flutterwave', 'type' => 'full_time', 'location' => 'Lagos, Nigeria', 'is_remote' => false],
            ['title' => 'Product Designer (Remote)', 'company' => 'Kuda Bank', 'type' => 'remote', 'location' => null, 'is_remote' => true],
            ['title' => 'Marketing Intern', 'company' => 'Jumia', 'type' => 'internship', 'location' => 'Lagos, Nigeria', 'is_remote' => false],
            ['title' => 'Freelance Legal Consultant', 'company' => null, 'type' => 'contract', 'location' => 'London, UK', 'is_remote' => false],
            ['title' => 'Data Analyst', 'company' => 'Paystack', 'type' => 'full_time', 'location' => 'Lagos, Nigeria', 'is_remote' => false],
        ];

        foreach ($jobs as $job) {
            AlumniJob::firstOrCreate(['title' => $job['title'], 'company' => $job['company']], [
                'user_id' => $member->id,
                'description' => "We're hiring a {$job['title']}. Reach out for more details.",
                'type' => $job['type'],
                'location' => $job['location'],
                'is_remote' => $job['is_remote'],
                'contact_email' => 'jobs@example.com',
                'status' => 'approved',
            ]);
        }
    }

    protected function seedBusinessListings(User $member): void
    {
        $businesses = [
            ['name' => 'Ogundimu Medical Clinic', 'category' => 'Healthcare'],
            ['name' => 'Nwosu & Co. Legal Practice', 'category' => 'Legal Services'],
            ['name' => 'Bakare Consulting', 'category' => 'Business Consulting'],
            ['name' => 'Adeyemi Creative Studio', 'category' => 'Design & Branding'],
        ];

        foreach ($businesses as $business) {
            BusinessListing::firstOrCreate(['name' => $business['name']], [
                'user_id' => $member->id,
                'slug' => Str::slug($business['name']),
                'description' => "{$business['name']} is an alumni-owned business in the {$business['category']} space.",
                'category' => $business['category'],
                'contact_email' => 'contact@example.com',
                'status' => 'approved',
            ]);
        }
    }

    protected function seedCampaigns(User $admin): void
    {
        $campaigns = [
            ['title' => 'Library Renovation Fund', 'target_amount' => 500000000, 'raised_amount' => 128000000],
            ['title' => 'Scholarship Endowment 2026', 'target_amount' => 1000000000, 'raised_amount' => 342000000],
        ];

        foreach ($campaigns as $campaign) {
            Campaign::firstOrCreate(['title' => $campaign['title']], [
                'slug' => Str::slug($campaign['title']),
                'description' => "Help us reach our goal for the {$campaign['title']}.",
                'target_amount' => $campaign['target_amount'],
                'currency' => 'NGN',
                'raised_amount' => $campaign['raised_amount'],
                'start_date' => now()->subMonth(),
                'end_date' => now()->addMonths(6),
                'is_active' => true,
                'created_by' => $admin->id,
            ]);
        }
    }

    protected function seedDues(): void
    {
        $dues = [
            ['name' => 'Annual Membership Dues 2026', 'amount' => 500000, 'frequency' => 'annual', 'set_id' => null, 'chapter_id' => null],
            ['name' => 'Set of 2010 Reunion Levy', 'amount' => 2500000, 'frequency' => 'one_time', 'set_id' => 3, 'chapter_id' => null],
            ['name' => 'Nigeria Chapter Monthly Dues', 'amount' => 200000, 'frequency' => 'monthly', 'set_id' => null, 'chapter_id' => 1],
        ];

        foreach ($dues as $due) {
            Due::firstOrCreate(['name' => $due['name']], [
                'description' => "{$due['name']} — contributions support alumni programs and events.",
                'amount' => $due['amount'],
                'currency' => 'NGN',
                'frequency' => $due['frequency'],
                'set_id' => $due['set_id'],
                'chapter_id' => $due['chapter_id'],
            ]);
        }
    }

    protected function seedGallery(User $admin): void
    {
        $albums = [
            ['title' => 'Founders Day Gala 2025', 'description' => 'Highlights from the 2025 Founders Day Gala.'],
            ['title' => 'UK Chapter Meetups', 'description' => 'Photos from various UK chapter gatherings.'],
        ];

        foreach ($albums as $album) {
            GalleryAlbum::firstOrCreate(['title' => $album['title']], [
                'slug' => Str::slug($album['title']),
                'description' => $album['description'],
                'user_id' => $admin->id,
            ]);
        }
    }
}
