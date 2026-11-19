<?php

namespace Database\Seeders;

use App\Enums\EventStatus;
use App\Enums\HourLogStatus;
use App\Enums\OrganizationStatus;
use App\Enums\RegistrationStatus;
use App\Enums\Role;
use App\Models\BugReport;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\HourLog;
use App\Models\Organization;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

/**
 * Demo data: 1 admin, 3 organizers (approved organizations), 20 volunteers,
 * 15 events, 40 posts, likes, comments, follows, hour logs, a few reports.
 * Every account's password is "password".
 */
class DemoSeeder extends Seeder
{
    private string $password;

    public function run(): void
    {
        mt_srand(2026);          // same demo data on every fresh seed
        fake()->seed(2026);
        $this->password = Hash::make('password');

        $admin = $this->user('VolunteerHub Admin', 'admin', 'admin@volunteerhub.test', Role::Admin, 'Platform administrator.');

        $orgs = $this->organizers();
        $volunteers = $this->volunteers();
        $events = $this->events($orgs);

        $this->registrationsAndHours($events, $volunteers, $admin);
        $posts = $this->posts($volunteers, $orgs, $events);
        $this->likesAndComments($posts, $volunteers);
        $this->follows($volunteers, $orgs);
        $this->reports($posts, $volunteers);
    }

    private function user(string $name, string $username, string $email, Role $role, ?string $bio = null): User
    {
        return User::create([
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'password' => $this->password,
            'role' => $role,
            'bio' => $bio,
            'email_verified_at' => now(),
        ]);
    }

    /** @return Collection<int, Organization> */
    private function organizers(): Collection
    {
        $data = [
            ['Green Mekong', 'greenmekong', 'Keeping the Mekong and Tonle Sap clean, one riverside at a time.'],
            ['Read Cambodia', 'readcambodia', 'Books and reading clubs for rural schools across Cambodia.'],
            ['Health Friends', 'healthfriends', 'Free health checks and first-aid training for village communities.'],
        ];

        return collect($data)->map(function ($o) {
            [$name, $username, $desc] = $o;
            $user = $this->user($name, $username, "{$username}@volunteerhub.test", Role::Organizer, $desc);

            return Organization::create([
                'user_id' => $user->id,
                'name' => $name,
                'description' => $desc,
                'verified' => true,
                'status' => OrganizationStatus::Approved,
            ]);
        });
    }

    /** @return Collection<int, User> */
    private function volunteers(): Collection
    {
        $names = [
            ['Sokha Chan', 'sokha', 'IT student who loves tree planting and river cleanups. Happy to help with photos or tech support.'],
            ['Dara Kim', 'darakim', 'Weekend volunteer, weekday engineer.'],
            ['Vanna Ros', 'vannaros', 'Love meeting new people at cleanups.'],
            ['Maly Sok', 'malysok', 'Nursing student in Phnom Penh.'],
            ['Bopha Lim', 'bophalim', 'English teacher. I help with reading clubs.'],
            ['Piseth Heng', 'pisethheng', null],
            ['Sreyneang Chea', 'sreyneang', 'ចូលចិត្តជួយសហគមន៍ 💚'],
            ['Rithy Nuon', 'rithynuon', 'Photographer from Siem Reap.'],
            ['Channary Pov', 'channary', null],
            ['Kosal Meas', 'kosalmeas', 'Medical volunteer.'],
            ['Sophea Ouk', 'sopheaouk', 'University student at RUPP.'],
            ['Visal Tep', 'visaltep', null],
            ['Rachana Keo', 'rachanakeo', 'Cooking for community kitchens on Sundays.'],
            ['Vibol Seng', 'vibolseng', null],
            ['Sreymom Phan', 'sreymom', 'Love kids and books.'],
            ['Chenda Long', 'chendalong', null],
            ['Makara Yim', 'makarayim', 'Cyclist and tree hugger from Battambang.'],
            ['Theary Mao', 'thearymao', null],
            ['Samnang Hor', 'samnanghor', 'Sen Sok neighbourhood volunteer.'],
            ['Leakhena Sim', 'leakhena', 'អ្នកស្ម័គ្រចិត្តនៅភ្នំពេញ'],
        ];

        $volunteers = collect($names)->map(fn ($v) => $this->user($v[0], $v[1], "{$v[1]}@volunteerhub.test", Role::Volunteer, $v[2]));

        // A few profile details so profiles don't look empty
        $cities = ['Phnom Penh', 'Phnom Penh', 'Phnom Penh', 'Siem Reap', 'Battambang', 'Kampot'];
        $skills = [['Photography', 'Teaching', 'Computers'], ['Logistics', 'Driving'], ['Cooking'], ['First aid'], ['English', 'Reading with kids'], ['Gardening']];
        $volunteers->each(fn ($u, $i) => $u->update([
            'city' => $cities[$i % count($cities)],
            'skills' => $skills[$i % count($skills)],
            'school' => $i === 0 ? 'Royal University of Phnom Penh' : null,
        ]));

        return $volunteers;
    }

    /** Approximate map pins for the demo locations (latitude, longitude). */
    public const COORDINATES = [
        'Riverside, Phnom Penh' => [11.5712, 104.9284], // Sisowath Quay
        'Toul Kork, Phnom Penh' => [11.5795, 104.8976],
        'Chroy Changvar, Phnom Penh' => [11.5939, 104.9283],
        'Puok, Siem Reap' => [13.4425, 103.7472],
        'Daun Penh, Phnom Penh' => [11.5733, 104.9206],
        'Russey Keo, Phnom Penh' => [11.6200, 104.8990],
        'Kep Beach, Kep' => [10.4836, 104.3165],
        'Sen Sok, Phnom Penh' => [11.5989, 104.8664],
        'Siem Reap City, Siem Reap' => [13.3633, 103.8564],
        'Battambang City, Battambang' => [13.0957, 103.2022],
        'Kampot Town, Kampot' => [10.6104, 104.1815],
    ];

    /** @return Collection<int, Event> */
    private function events(Collection $orgs): Collection
    {
        $cat = Category::pluck('id', 'slug');
        [$green, $read, $health] = $orgs->all();

        // [org, category, title, location, days from today, start hour, hours, capacity, status]
        $rows = [
            // upcoming
            [$green, 'environment', 'River Cleanup Day', 'Riverside, Phnom Penh', 5, 7, 4, 50, EventStatus::Open],
            [$read, 'education', 'Book Drive for Schools', 'Toul Kork, Phnom Penh', 6, 8, 4, 6, EventStatus::Open],   // will be full
            [$green, 'environment', 'Tree Planting Morning', 'Chroy Changvar, Phnom Penh', 12, 6, 4, 60, EventStatus::Open],
            [$health, 'health', 'Free Health Check Day', 'Puok, Siem Reap', 3, 8, 7, 25, EventStatus::Open],
            [$health, 'community', 'Community Kitchen', 'Daun Penh, Phnom Penh', 9, 10, 4, 15, EventStatus::Open],
            [$green, 'environment', 'Campus Recycling Drive', 'Russey Keo, Phnom Penh', 25, 13, 4, 40, EventStatus::Open],
            [$green, 'environment', 'Beach Cleanup Weekend', 'Kep Beach, Kep', 20, 7, 5, 30, EventStatus::Open],
            [$read, 'education', 'Weekend Reading Club', 'Sen Sok, Phnom Penh', 1, 9, 3, 20, EventStatus::Open], // tomorrow: reminder demo
            [$health, 'health', 'First Aid Training', 'Siem Reap City, Siem Reap', 15, 8, 5, 30, EventStatus::Open],
            [$read, 'education', 'English Corner for Kids', 'Battambang City, Battambang', 30, 14, 2, 25, EventStatus::Pending], // waits for admin
            [$green, 'community', 'Pagoda Garden Clean-up', 'Kampot Town, Kampot', 35, 7, 3, 20, EventStatus::Pending],
            // past (for hour logs)
            [$green, 'environment', 'Mekong Riverside Cleanup', 'Riverside, Phnom Penh', -9, 7, 4, 40, EventStatus::Closed],
            [$read, 'education', 'Library Painting Day', 'Toul Kork, Phnom Penh', -30, 8, 5, 20, EventStatus::Closed],
            [$health, 'community', 'Rice Distribution', 'Sen Sok, Phnom Penh', -60, 7, 6, 30, EventStatus::Closed],
            [$green, 'environment', 'Mangrove Planting', 'Kep Beach, Kep', -100, 6, 8, 30, EventStatus::Closed],
        ];

        $descriptions = [
            'environment' => 'Join us to protect Cambodia\'s nature. We meet at the main gate, split into small teams and work together. Gloves, bags and water are provided. Wear comfortable shoes and bring a hat.',
            'education' => 'Help children and young students enjoy learning. Sort books, read stories and play learning games with them. No experience needed — just patience and a smile.',
            'health' => 'Support nurses and doctors at a free community health event. Volunteers help with registration, queues and simple first-aid tasks.',
            'community' => 'Give back to the neighbourhood. We cook, clean and share food and supplies with families who need it.',
        ];

        return collect($rows)->map(function ($r) use ($cat, $descriptions) {
            [$org, $slug, $title, $location, $days, $hour, $hours, $capacity, $status] = $r;
            $start = now()->startOfDay()->addDays($days)->setTime($hour, 0);

            return Event::create([
                'organization_id' => $org->id,
                'category_id' => $cat[$slug],
                'title' => $title,
                'description' => $descriptions[$slug],
                'location' => $location,
                'latitude' => self::COORDINATES[$location][0] ?? null,
                'longitude' => self::COORDINATES[$location][1] ?? null,
                'starts_at' => $start,
                'ends_at' => $start->copy()->addHours($hours),
                'hours' => $hours,
                'capacity' => $capacity,
                'status' => $status,
            ]);
        });
    }

    private function registrationsAndHours(Collection $events, Collection $volunteers, User $admin): void
    {
        $sokha = $volunteers->first();

        foreach ($events as $event) {
            if ($event->status === EventStatus::Pending) {
                continue;
            }

            $past = $event->starts_at->isPast();
            $count = match (true) {
                $event->title === 'Book Drive for Schools' => $event->capacity,     // fills it up
                $past => mt_rand(8, 14),
                default => mt_rand(3, min(12, $event->capacity - 1)),
            };

            // Sokha joins most events so the demo account has data.
            $people = $volunteers->slice(1)->shuffle()->take($count - 1)->prepend($sokha)->take($count);

            foreach ($people as $i => $volunteer) {
                if (! $past) {
                    EventRegistration::create(['event_id' => $event->id, 'user_id' => $volunteer->id]);

                    continue;
                }

                $attended = $volunteer->is($sokha) || mt_rand(1, 10) <= 8;
                $reg = EventRegistration::create([
                    'event_id' => $event->id,
                    'user_id' => $volunteer->id,
                    'status' => $attended ? RegistrationStatus::Attended : RegistrationStatus::Absent,
                ]);

                if ($attended) {
                    // The most recent past event is still waiting for admin review.
                    $recent = $event->starts_at->gt(now()->subDays(14));
                    HourLog::create([
                        'event_registration_id' => $reg->id,
                        'user_id' => $volunteer->id,
                        'event_id' => $event->id,
                        'hours' => $event->hours,
                        'status' => $recent ? HourLogStatus::Pending : HourLogStatus::Approved,
                        'reviewed_by' => $recent ? null : $admin->id,
                        'reviewed_at' => $recent ? null : $event->ends_at->copy()->addDays(2),
                        'created_at' => $event->ends_at,
                        'updated_at' => $event->ends_at,
                    ]);
                }
            }

            $event->refreshCapacityStatus();
        }

        // Saved events for the demo user
        $sokha->savedEvents()->attach($events->where('status', EventStatus::Open)->random(2)->pluck('id'));
    }

    /** @return Collection<int, Post> */
    private function posts(Collection $volunteers, Collection $orgs, Collection $events): Collection
    {
        $orgUsers = $orgs->map->user;
        $published = $events->whereIn('status', [EventStatus::Open, EventStatus::Full, EventStatus::Closed])->values();

        $volunteerTexts = [
            'My first time planting trees and it was so much fun. Who is coming next week? #TreePlanting #PhnomPenh',
            'We collected so much trash along the river today. Tired but happy! #RiverCleanup',
            'Reading stories with the kids in Toul Kork made my weekend. #Education #BookDrive',
            'Thank you to everyone who came to the community kitchen. We served 200 meals! #Community',
            'ស្ម័គ្រចិត្តថ្ងៃនេះពិតជាសប្បាយណាស់! #ស្ម័គ្រចិត្ត #PhnomPenh',
            'Sunrise at the Mekong before the cleanup started. Worth waking up at 5am. #RiverCleanup #VolunteerLife',
            'Learned basic first aid today. Everyone should take this training. #Health',
            'Proud to reach 25 volunteer hours this year! #VolunteerLife',
            'Bring a hat and water if you join the beach cleanup in Kep, it gets hot. #BeachCleanup #Kep',
            'Met so many kind people at the pagoda today. #Community #SiemReap',
            'អរគុណអ្នកទាំងអស់គ្នាដែលបានចូលរួមដាំដើមឈើ 🌱 #ដាំដើមឈើ #TreePlanting',
            'Who else is going to the reading club this weekend in Sen Sok? #Education #SenSok',
            'Sorting books for rural schools — 500 books packed! #BookDrive',
            'Volunteering is the best way to meet friends in a new city. #VolunteerLife #PhnomPenh',
        ];

        $orgTexts = [
            'Thank you to the volunteers who joined last week! Together we collected 120 bags of trash. #RiverCleanup',
            'We still need 5 more helpers to pack books for rural schools this Sunday. Thank you, everyone!',
            'Registration is open! Join our next event and bring a friend. #Volunteer',
            'Free health checks reached 300 villagers in Puok. Huge thanks to our volunteers! #Health #SiemReap',
            'New event posted — spots are limited, sign up early. #Community',
        ];

        $posts = collect();

        for ($i = 0; $i < 40; $i++) {
            $byOrg = $i % 4 === 0;
            $author = $byOrg ? $orgUsers->random() : ($i < 6 ? $volunteers->first() : $volunteers->random());
            $body = $byOrg ? $orgTexts[array_rand($orgTexts)] : $volunteerTexts[array_rand($volunteerTexts)];
            $event = mt_rand(1, 10) <= 4 ? $published->random() : null;
            $createdAt = now()->subMinutes(mt_rand(10, 60 * 24 * 30));

            $post = Post::create([
                'user_id' => $author->id,
                'event_id' => $event?->id,
                'body' => $body,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
            $post->syncHashtags();
            $posts->push($post);
        }

        // One hidden post so moderation can be tested
        $posts->last()->update(['status' => 'hidden']);

        return $posts;
    }

    private function likesAndComments(Collection $posts, Collection $volunteers): void
    {
        $comments = [
            'Great job everyone!', 'See you next time 💚', 'I am in!', 'So inspiring.',
            'How can I join the next one?', 'អស្ចារ្យណាស់!', 'Thank you for organizing!', 'Count me in for next week.',
        ];

        foreach ($posts as $post) {
            $post->likers()->attach(
                $volunteers->random(mt_rand(0, 12))->pluck('id')->reject(fn ($id) => $id === $post->user_id)
            );

            foreach ($volunteers->random(mt_rand(0, 4)) as $commenter) {
                Comment::create([
                    'post_id' => $post->id,
                    'user_id' => $commenter->id,
                    'body' => $comments[array_rand($comments)],
                    'created_at' => $post->created_at->copy()->addMinutes(mt_rand(5, 600)),
                ]);
            }
        }
    }

    private function follows(Collection $volunteers, Collection $orgs): void
    {
        $orgUserIds = $orgs->map->user_id;

        foreach ($volunteers as $v) {
            $ids = $volunteers->where('id', '!=', $v->id)->random(mt_rand(2, 8))->pluck('id')
                ->merge($orgUserIds->random(mt_rand(1, 3)))
                ->unique();
            $v->following()->attach($ids);
        }
    }

    private function reports(Collection $posts, Collection $volunteers): void
    {
        Report::create([
            'reporter_id' => $volunteers[2]->id,
            'reportable_type' => 'post',
            'reportable_id' => $posts[7]->id,
            'reason' => 'Spam / advertising',
        ]);
        Report::create([
            'reporter_id' => $volunteers[4]->id,
            'reportable_type' => 'user',
            'reportable_id' => $volunteers[13]->id,
            'reason' => 'Rude messages in comments',
        ]);

        BugReport::create([
            'user_id' => $volunteers[0]->id,
            'message' => 'The certificate download button does nothing on my phone.',
            'page_url' => '/u/sokha',
        ]);
        BugReport::create([
            'user_id' => $volunteers[5]->id,
            'message' => 'Khmer text in comments shows as boxes on Safari.',
            'page_url' => '/',
        ]);
    }
}
