<?php

namespace App\Console\Commands;

use App\Models\City;
use App\Models\Community;
use App\Models\Education;
use App\Models\IncomeRange;
use App\Models\MaritalStatus;
use App\Models\MotherTongue;
use App\Models\Occupation;
use App\Models\Religion;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ResetTenProfilesCommand extends Command
{
    protected $signature = 'matrimony:reset-ten-profiles';
    protected $description = 'Remove all existing member profiles and seed exactly 10 profiles (5 female, 5 male) across all marital status categories';

    public function handle(): int
    {
        $this->info('Starting member profiles reset...');

        $roleId = Role::where('name', 'user')->value('id');
        if (! $roleId) {
            $this->error('Member role "user" not found.');
            return Command::FAILURE;
        }

        // Ensure "Married" exists in marital_statuses
        $statusesMap = [
            'Never Married' => 'Never Married',
            'Married' => 'Married',
            'Divorced' => 'Divorced',
            'Widowed' => 'Widowed',
            'Awaiting Divorce' => 'Awaiting Divorce',
        ];

        foreach ($statusesMap as $slugName => $name) {
            MaritalStatus::updateOrCreate(
                ['slug' => Str::slug($slugName)],
                ['name' => $name, 'is_active' => true]
            );
        }

        $maritalStatuses = MaritalStatus::pluck('id', 'name')->all();

        // 1. Permanently delete existing member profiles (including soft-deleted)
        $memberUsers = User::withTrashed()->where('role_id', $roleId)->get();
        $this->info('Found ' . $memberUsers->count() . ' member users to permanently remove.');

        DB::transaction(function () use ($memberUsers) {
            foreach ($memberUsers as $u) {
                DB::table('user_profiles')->where('user_id', $u->id)->delete();
                DB::table('user_preferences')->where('user_id', $u->id)->delete();
                DB::table('privacy_settings')->where('user_id', $u->id)->delete();
                DB::table('profile_photos')->where('user_id', $u->id)->delete();
                $u->forceDelete();
            }
        });

        $this->info('Existing member profiles removed. Now creating 10 balanced profiles...');

        // Preload lookups
        $tamilId = MotherTongue::where('name', 'like', '%Tamil%')->value('id') ?? MotherTongue::first()->id;
        $teluguId = MotherTongue::where('name', 'like', '%Telugu%')->value('id') ?? $tamilId;

        $hinduId = Religion::where('name', 'Hindu')->value('id') ?? Religion::first()->id;

        $brahminId = Community::where('name', 'Brahmin')->where('religion_id', $hinduId)->value('id') ?? Community::first()->id;
        $mudaliarId = Community::where('name', 'Mudaliar')->where('religion_id', $hinduId)->value('id') ?? $brahminId;
        $chettiarId = Community::where('name', 'Chettiar')->where('religion_id', $hinduId)->value('id') ?? $brahminId;
        $nadarId = Community::where('name', 'Nadar')->where('religion_id', $hinduId)->value('id') ?? $brahminId;
        $gounderId = Community::where('name', 'Gounder')->where('religion_id', $hinduId)->value('id') ?? $brahminId;
        $reddyId = Community::where('name', 'Reddy')->where('religion_id', $hinduId)->value('id') ?? $brahminId;
        $naiduId = Community::where('name', 'Naidu')->where('religion_id', $hinduId)->value('id') ?? $brahminId;
        $vanniyarId = Community::where('name', 'Vanniyar')->where('religion_id', $hinduId)->value('id') ?? $brahminId;

        $educations = Education::pluck('id')->all();
        $occupations = Occupation::pluck('id')->all();
        $incomes = IncomeRange::pluck('id')->all();

        $defaultCity = City::first();
        $cityId = $defaultCity?->id ?? 1;
        $stateId = $defaultCity?->state_id ?? 1;
        $countryId = 1;

        $password = Hash::make('Password@123');
        $now = now();
        $year = $now->format('Y');

        $profilesData = [
            // 5 FEMALE PROFILES
            [
                'n' => 1,
                'name' => 'Priya Sundaram',
                'gender' => 'female',
                'email' => 'priya.s@matrimony.test',
                'mobile' => '+919876543210',
                'dob' => $now->copy()->subYears(25)->subMonths(3)->toDateString(),
                'marital_status' => 'Never Married',
                'height_cm' => 162,
                'weight_kg' => 54,
                'community_id' => $brahminId,
                'mother_tongue_id' => $tamilId,
                'diet' => 'vegetarian',
                'education_detail' => 'B.Tech - Computer Science',
                'job_type' => 'private',
                'about_me' => 'Warm, ambitious software professional who enjoys classical music, reading, and weekend treks.',
                'family_status' => 'upper_middle',
            ],
            [
                'n' => 2,
                'name' => 'Ananya Ramachandran',
                'gender' => 'female',
                'email' => 'ananya.r@matrimony.test',
                'mobile' => '+919876543211',
                'dob' => $now->copy()->subYears(28)->subMonths(5)->toDateString(),
                'marital_status' => 'Married',
                'height_cm' => 165,
                'weight_kg' => 58,
                'community_id' => $mudaliarId,
                'mother_tongue_id' => $tamilId,
                'diet' => 'eggetarian',
                'education_detail' => 'MBA - Finance',
                'job_type' => 'private',
                'about_me' => 'Analytical mindset with a passion for creative arts and family gatherings.',
                'family_status' => 'middle_class',
            ],
            [
                'n' => 3,
                'name' => 'Divya Murugan',
                'gender' => 'female',
                'email' => 'divya.m@matrimony.test',
                'mobile' => '+919876543212',
                'dob' => $now->copy()->subYears(31)->subMonths(7)->toDateString(),
                'marital_status' => 'Divorced',
                'height_cm' => 160,
                'weight_kg' => 55,
                'community_id' => $chettiarId,
                'mother_tongue_id' => $tamilId,
                'diet' => 'non-vegetarian',
                'education_detail' => 'MBBS, MD - General Medicine',
                'job_type' => 'private',
                'about_me' => 'Dedicated healthcare professional looking for an understanding, mature partner. Believes in mutual respect and open communication.',
                'family_status' => 'upper_middle',
            ],
            [
                'n' => 4,
                'name' => 'Meenakshi Krishnan',
                'gender' => 'female',
                'email' => 'meenakshi.k@matrimony.test',
                'mobile' => '+919876543213',
                'dob' => $now->copy()->subYears(33)->subMonths(2)->toDateString(),
                'marital_status' => 'Widowed',
                'height_cm' => 158,
                'weight_kg' => 52,
                'community_id' => $nadarId,
                'mother_tongue_id' => $tamilId,
                'diet' => 'vegetarian',
                'education_detail' => 'M.Sc, B.Ed - Mathematics',
                'job_type' => 'govt',
                'about_me' => 'Compassionate high school educator who values family bonding, simple living, and cultural values.',
                'family_status' => 'middle_class',
            ],
            [
                'n' => 5,
                'name' => 'Kavitha Natarajan',
                'gender' => 'female',
                'email' => 'kavitha.n@matrimony.test',
                'mobile' => '+919876543214',
                'dob' => $now->copy()->subYears(29)->subMonths(9)->toDateString(),
                'marital_status' => 'Awaiting Divorce',
                'height_cm' => 163,
                'weight_kg' => 56,
                'community_id' => $gounderId,
                'mother_tongue_id' => $tamilId,
                'diet' => 'vegetarian',
                'education_detail' => 'B.Des - UI/UX Product Design',
                'job_type' => 'self_employed',
                'about_me' => 'Creative soul working in digital design. Looking for a fresh, positive chapter with a supportive partner.',
                'family_status' => 'middle_class',
            ],

            // 5 MALE PROFILES
            [
                'n' => 6,
                'name' => 'Arjun Reddy',
                'gender' => 'male',
                'email' => 'arjun.r@matrimony.test',
                'mobile' => '+919876543215',
                'dob' => $now->copy()->subYears(28)->subMonths(4)->toDateString(),
                'marital_status' => 'Never Married',
                'height_cm' => 178,
                'weight_kg' => 74,
                'community_id' => $reddyId,
                'mother_tongue_id' => $teluguId,
                'diet' => 'non-vegetarian',
                'education_detail' => 'M.S. - Data Science & AI',
                'job_type' => 'private',
                'about_me' => 'Technology enthusiast and fitness buff. Enjoys road trips, photography, and good conversations.',
                'family_status' => 'upper_middle',
            ],
            [
                'n' => 7,
                'name' => 'Karthik Venkat',
                'gender' => 'male',
                'email' => 'karthik.v@matrimony.test',
                'mobile' => '+919876543216',
                'dob' => $now->copy()->subYears(32)->subMonths(8)->toDateString(),
                'marital_status' => 'Married',
                'height_cm' => 175,
                'weight_kg' => 70,
                'community_id' => $brahminId,
                'mother_tongue_id' => $tamilId,
                'diet' => 'vegetarian',
                'education_detail' => 'B.E., MBA - Product Strategy',
                'job_type' => 'private',
                'about_me' => 'Product strategist in fintech. Passionate about innovation, culinary experiments, and books.',
                'family_status' => 'rich',
            ],
            [
                'n' => 8,
                'name' => 'Rajesh Palaniswamy',
                'gender' => 'male',
                'email' => 'rajesh.p@matrimony.test',
                'mobile' => '+919876543217',
                'dob' => $now->copy()->subYears(34)->subMonths(11)->toDateString(),
                'marital_status' => 'Divorced',
                'height_cm' => 180,
                'weight_kg' => 76,
                'community_id' => $naiduId,
                'mother_tongue_id' => $tamilId,
                'diet' => 'eggetarian',
                'education_detail' => 'B.Arch - Architecture & Urban Planning',
                'job_type' => 'business',
                'about_me' => 'Independent entrepreneur and architect. Grounded, family-oriented, seeking an understanding companion.',
                'family_status' => 'upper_middle',
            ],
            [
                'n' => 9,
                'name' => 'Suresh Balaji',
                'gender' => 'male',
                'email' => 'suresh.b@matrimony.test',
                'mobile' => '+919876543218',
                'dob' => $now->copy()->subYears(36)->subMonths(6)->toDateString(),
                'marital_status' => 'Widowed',
                'height_cm' => 172,
                'weight_kg' => 71,
                'community_id' => $mudaliarId,
                'mother_tongue_id' => $tamilId,
                'diet' => 'non-vegetarian',
                'education_detail' => 'M.Tech - Mechanical Engineering',
                'job_type' => 'private',
                'about_me' => 'Patient, family-centered engineering manager who enjoys music and nature walks. Ready for a new beginning.',
                'family_status' => 'middle_class',
            ],
            [
                'n' => 10,
                'name' => 'Vignesh Mohan',
                'gender' => 'male',
                'email' => 'vignesh.m@matrimony.test',
                'mobile' => '+919876543219',
                'dob' => $now->copy()->subYears(30)->subMonths(1)->toDateString(),
                'marital_status' => 'Awaiting Divorce',
                'height_cm' => 174,
                'weight_kg' => 68,
                'community_id' => $vanniyarId,
                'mother_tongue_id' => $tamilId,
                'diet' => 'vegetarian',
                'education_detail' => 'Chartered Accountant (FCA)',
                'job_type' => 'private',
                'about_me' => 'Analytical, calm, and grounded audit consultant. Believes in transparency, personal growth, and companionship.',
                'family_status' => 'upper_middle',
            ],
        ];

        DB::transaction(function () use ($profilesData, $roleId, $password, $now, $year, $maritalStatuses, $hinduId, $educations, $occupations, $incomes, $cityId, $stateId, $countryId) {
            foreach ($profilesData as $data) {
                $user = User::create([
                    'profile_code' => \App\Support\ProfileCode::generate(),
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'mobile' => $data['mobile'],
                    'password' => $password,
                    'gender' => $data['gender'],
                    'date_of_birth' => $data['dob'],
                    'role_id' => $roleId,
                    'status' => 'active',
                    'email_verified_at' => $now,
                    'mobile_verified_at' => $now,
                    'last_active_at' => $now,
                    'registration_source' => 'web',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $maritalStatusId = $maritalStatuses[$data['marital_status']] ?? array_values($maritalStatuses)[0];
                $educationId = $educations[$data['n'] % count($educations)] ?? null;
                $occupationId = $occupations[$data['n'] % count($occupations)] ?? null;
                $incomeId = $incomes[$data['n'] % count($incomes)] ?? null;

                DB::table('user_profiles')->insert([
                    'user_id' => $user->id,
                    'profile_created_by' => 'self',
                    'height_cm' => $data['height_cm'],
                    'weight_kg' => $data['weight_kg'],
                    'marital_status_id' => $maritalStatusId,
                    'physical_status' => 'normal',
                    'religion_id' => $hinduId,
                    'community_id' => $data['community_id'],
                    'mother_tongue_id' => $data['mother_tongue_id'],
                    'education_id' => $educationId,
                    'education_detail' => $data['education_detail'],
                    'occupation_id' => $occupationId,
                    'income_range_id' => $incomeId,
                    'job_type' => $data['job_type'],
                    'country_id' => $countryId,
                    'state_id' => $stateId,
                    'city_id' => $cityId,
                    'family_type' => 'nuclear',
                    'family_status' => $data['family_status'],
                    'family_values' => 'moderate',
                    'diet' => $data['diet'],
                    'smoking' => 'no',
                    'drinking' => 'no',
                    'about_me' => $data['about_me'],
                    'expectations' => 'Looking for a kind, understanding partner with shared family values.',
                    'completion_percentage' => 100,
                    'approval_status' => 'approved',
                    'is_verified' => true,
                    'verified_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                // Opposite gender preferences for matches
                $partnerGender = $data['gender'] === 'female' ? 'male' : 'female';
                DB::table('user_preferences')->insert([
                    'user_id' => $user->id,
                    'age_min' => 21,
                    'age_max' => 45,
                    'height_min_cm' => 150,
                    'height_max_cm' => 195,
                    'religion_ids' => json_encode([$hinduId]),
                    'mother_tongue_ids' => json_encode([$data['mother_tongue_id']]),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $privacyDefaults = config('matrimony.privacy.defaults', [
                    'photo_visibility' => 'all_members',
                    'phone_visibility' => 'connections_only',
                    'email_visibility' => 'connections_only',
                    'profile_visibility' => 'visible',
                    'horoscope_visibility' => 'connections_only',
                ]);

                DB::table('privacy_settings')->insert($privacyDefaults + [
                    'user_id' => $user->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });

        $this->info('Successfully seeded 10 profiles (5 female, 5 male) across all marital status categories.');
        $this->info('All profiles password: Password@123');

        return Command::SUCCESS;
    }
}
