<?php

namespace Database\Seeders;

use App\Models\Caste;
use App\Models\Community;
use App\Models\Education;
use App\Models\Gothra;
use App\Models\IncomeRange;
use App\Models\LifestyleOption;
use App\Models\MaritalStatus;
use App\Models\MotherTongue;
use App\Models\Occupation;
use App\Models\Religion;
use App\Models\SubCommunity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Real data, not lorem ipsum.
 *
 * Dropdowns are the product for the first ten minutes of a user's life here —
 * a Tamil speaker who cannot find "Tamil" in the mother-tongue list does not
 * file a bug, they leave. Everything below is a genuine value from the Indian
 * matrimonial market, and all of it is admin-editable afterwards.
 *
 * updateOrCreate on the slug throughout, so re-running the seeder against a
 * live database is safe and never duplicates a row someone has already
 * referenced from a profile.
 */
class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->religionsAndCommunities();
        $this->motherTongues();
        $this->educations();
        $this->occupations();
        $this->incomeRanges();
        $this->maritalStatuses();
        $this->lifestyleOptions();
        $this->castesAndGothras();
    }

    protected function religionsAndCommunities(): void
    {
        // Communities are the ones users actually filter on. Sub-communities
        // are populated only where the distinction genuinely drives a match.
        $tree = [
            'Hindu' => [
                'Brahmin' => ['Iyer', 'Iyengar', 'Smartha', 'Kanyakubja', 'Deshastha', 'Kokanastha', 'Gaur', 'Maithil'],
                'Kshatriya' => ['Rajput', 'Thakur', 'Maratha'],
                'Vaishya' => ['Agarwal', 'Bania', 'Gupta', 'Khandelwal', 'Maheshwari', 'Oswal'],
                'Nadar' => [],
                'Mudaliar' => ['Arcot', 'Saiva', 'Sengunthar'],
                'Chettiar' => ['Nattukottai', 'Devanga', 'Vaniya'],
                'Gounder' => ['Kongu Vellalar', 'Vettuva'],
                'Vanniyar' => [],
                'Reddy' => ['Kapu', 'Munnuru'],
                'Kamma' => [],
                'Naidu' => ['Balija', 'Gavara', 'Kamma Naidu'],
                'Yadav' => [],
                'Kurmi' => [],
                'Jat' => [],
                'Lingayat' => ['Banajiga', 'Panchamsali'],
                'Vokkaliga' => [],
                'Nair' => [],
                'Ezhava' => [],
                'Menon' => [],
                'Patel' => ['Leva Patil', 'Kadva Patil'],
                'Kayastha' => [],
                'Arora' => [],
                'Khatri' => [],
                'Saini' => [],
                'Scheduled Caste' => [],
                'Scheduled Tribe' => [],
                'Caste No Bar' => [],
            ],
            'Muslim' => [
                'Sunni' => ['Hanafi', 'Shafi', 'Maliki', 'Hanbali'],
                'Shia' => ['Ithna Ashari', 'Bohra', 'Ismaili'],
                'Ansari' => [],
                'Dawoodi Bohra' => [],
                'Memon' => [],
                'Mapila' => [],
                'Rowther' => [],
                'Sheikh' => [],
                'Syed' => [],
                'Pathan' => [],
                'Mughal' => [],
                'Others' => [],
            ],
            'Christian' => [
                'Roman Catholic' => [],
                'Protestant' => [],
                'Syrian Catholic' => [],
                'Syro Malabar' => [],
                'Jacobite' => [],
                'Marthoma' => [],
                'Orthodox' => [],
                'Pentecostal' => [],
                'CSI' => [],
                'Born Again' => [],
                'Latin Catholic' => [],
                'Others' => [],
            ],
            'Sikh' => [
                'Jat Sikh' => [],
                'Ramgarhia' => [],
                'Khatri Sikh' => [],
                'Arora Sikh' => [],
                'Ramdasia' => [],
                'Ahluwalia' => [],
                'Others' => [],
            ],
            'Jain' => [
                'Digambar' => [],
                'Shwetambar' => [],
                'Vania' => [],
                'Others' => [],
            ],
            'Buddhist' => ['Mahayana' => [], 'Theravada' => [], 'Navayana' => [], 'Others' => []],
            'Parsi' => ['Zoroastrian' => []],
            'Jewish' => ['Bene Israel' => [], 'Cochin Jews' => []],
            'Inter-Religion' => ['Open to all' => []],
            'No Religion' => ['Atheist' => [], 'Spiritual' => []],
        ];

        $religionOrder = 0;

        foreach ($tree as $religionName => $communities) {
            $religion = Religion::updateOrCreate(
                ['slug' => Str::slug($religionName)],
                ['name' => $religionName, 'sort_order' => $religionOrder++, 'is_active' => true],
            );

            $communityOrder = 0;

            foreach ($communities as $communityName => $subCommunities) {
                $community = Community::updateOrCreate(
                    ['slug' => Str::slug($religionName.'-'.$communityName)],
                    [
                        'name' => $communityName,
                        'religion_id' => $religion->id,
                        'sort_order' => $communityOrder++,
                        'is_active' => true,
                    ],
                );

                foreach ($subCommunities as $index => $subName) {
                    SubCommunity::updateOrCreate(
                        ['slug' => Str::slug($religionName.'-'.$communityName.'-'.$subName)],
                        [
                            'name' => $subName,
                            'community_id' => $community->id,
                            'sort_order' => $index,
                            'is_active' => true,
                        ],
                    );
                }
            }
        }
    }

    protected function motherTongues(): void
    {
        // The 22 scheduled languages plus the regional tongues that appear on
        // real profiles. Ordered by speaker count, because an alphabetical list
        // buries Hindi and Tamil below Assamese.
        $tongues = [
            'Hindi', 'Bengali', 'Marathi', 'Telugu', 'Tamil', 'Gujarati', 'Urdu', 'Kannada',
            'Odia', 'Malayalam', 'Punjabi', 'Assamese', 'Maithili', 'Bhojpuri', 'Santali',
            'Kashmiri', 'Nepali', 'Sindhi', 'Konkani', 'Dogri', 'Manipuri', 'Bodo', 'Sanskrit',
            'Tulu', 'Kodava', 'Rajasthani', 'Haryanvi', 'Chhattisgarhi', 'Magahi', 'Awadhi',
            'Marwari', 'Kutchi', 'Garhwali', 'Kumaoni', 'Khasi', 'Mizo', 'Ladakhi', 'Tibetan',
            'English', 'Other',
        ];

        foreach ($tongues as $index => $name) {
            MotherTongue::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'sort_order' => $index, 'is_active' => true],
            );
        }
    }

    protected function educations(): void
    {
        // `category` is what EducationCriterion ranks on, so every row must
        // carry one that maps to a tier.
        $educations = [
            'school' => ['High School', 'Higher Secondary'],
            'diploma' => ['Diploma', 'Polytechnic', 'ITI'],
            'graduate' => [
                'B.A', 'B.Com', 'B.Sc', 'B.B.A', 'B.C.A', 'B.E / B.Tech', 'B.Arch',
                'MBBS', 'B.D.S', 'B.A.M.S', 'B.H.M.S', 'B.Pharm', 'B.Ed', 'LLB',
                'B.Des', 'B.F.A', 'B.P.T', 'B.Sc Nursing', 'Bachelor of Hotel Management',
            ],
            'post_graduate' => [
                'M.A', 'M.Com', 'M.Sc', 'M.B.A', 'M.C.A', 'M.E / M.Tech', 'M.Arch',
                'M.D', 'M.S (Medicine)', 'M.D.S', 'M.Pharm', 'M.Ed', 'LLM',
                'M.Des', 'M.F.A', 'M.P.T', 'C.A', 'C.S', 'I.C.W.A',
            ],
            'doctorate' => ['Ph.D', 'D.Sc', 'D.Litt', 'M.Phil', 'Post Doctoral'],
        ];

        $order = 0;

        foreach ($educations as $category => $names) {
            foreach ($names as $name) {
                Education::updateOrCreate(
                    ['slug' => Str::slug($name)],
                    ['name' => $name, 'category' => $category, 'sort_order' => $order++, 'is_active' => true],
                );
            }
        }
    }

    protected function occupations(): void
    {
        $occupations = [
            'IT & Software' => ['Software Engineer', 'Software Architect', 'Data Scientist', 'DevOps Engineer', 'QA Engineer', 'UI/UX Designer', 'Product Manager', 'IT Consultant', 'Cyber Security Analyst'],
            'Medical' => ['Doctor', 'Surgeon', 'Dentist', 'Nurse', 'Physiotherapist', 'Pharmacist', 'Veterinarian', 'Medical Technician', 'Ayurvedic Practitioner'],
            'Engineering' => ['Civil Engineer', 'Mechanical Engineer', 'Electrical Engineer', 'Electronics Engineer', 'Chemical Engineer', 'Aerospace Engineer'],
            'Finance' => ['Chartered Accountant', 'Company Secretary', 'Investment Banker', 'Financial Analyst', 'Auditor', 'Accountant', 'Actuary', 'Insurance Professional'],
            'Government' => ['IAS / IPS / IFS', 'Civil Services', 'Defence Services', 'Police Services', 'Railway Employee', 'Bank Officer (PSU)', 'Government Teacher', 'Clerk'],
            'Education' => ['Teacher', 'Professor', 'Lecturer', 'Research Scholar', 'Principal', 'Education Consultant'],
            'Legal' => ['Lawyer', 'Advocate', 'Legal Consultant', 'Judge', 'Company Legal Counsel'],
            'Business' => ['Business Owner', 'Entrepreneur', 'Trader', 'Contractor', 'Real Estate Professional', 'Agriculturist'],
            'Corporate' => ['Marketing Professional', 'Sales Professional', 'HR Professional', 'Operations Manager', 'Business Analyst', 'Supply Chain Professional', 'Customer Support'],
            'Creative' => ['Architect', 'Interior Designer', 'Graphic Designer', 'Journalist', 'Writer', 'Photographer', 'Film Professional', 'Artist', 'Musician'],
            'Service' => ['Airline Professional', 'Pilot', 'Cabin Crew', 'Hotel Management', 'Chef', 'Merchant Navy', 'Fitness Trainer'],
            'Other' => ['Student', 'Not Working', 'Retired', 'Homemaker', 'Other'],
        ];

        $order = 0;

        foreach ($occupations as $category => $names) {
            foreach ($names as $name) {
                Occupation::updateOrCreate(
                    ['slug' => Str::slug($name)],
                    ['name' => $name, 'category' => $category, 'sort_order' => $order++, 'is_active' => true],
                );
            }
        }
    }

    protected function incomeRanges(): void
    {
        // Rupees per year. A null ceiling means "and above" — IncomeRange
        // handles that case rather than storing a sentinel like 999999999.
        $ranges = [
            ['No Income', 0, 0],
            ['Below ₹1 Lakh', 0, 100_000],
            ['₹1 - 2 Lakh', 100_000, 200_000],
            ['₹2 - 3 Lakh', 200_000, 300_000],
            ['₹3 - 5 Lakh', 300_000, 500_000],
            ['₹5 - 7.5 Lakh', 500_000, 750_000],
            ['₹7.5 - 10 Lakh', 750_000, 1_000_000],
            ['₹10 - 15 Lakh', 1_000_000, 1_500_000],
            ['₹15 - 20 Lakh', 1_500_000, 2_000_000],
            ['₹20 - 30 Lakh', 2_000_000, 3_000_000],
            ['₹30 - 50 Lakh', 3_000_000, 5_000_000],
            ['₹50 - 75 Lakh', 5_000_000, 7_500_000],
            ['₹75 Lakh - 1 Crore', 7_500_000, 10_000_000],
            ['Above ₹1 Crore', 10_000_000, null],
        ];

        foreach ($ranges as $index => [$name, $min, $max]) {
            IncomeRange::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'min_amount' => $min,
                    'max_amount' => $max,
                    'currency' => 'INR',
                    'sort_order' => $index,
                    'is_active' => true,
                ],
            );
        }
    }

    protected function maritalStatuses(): void
    {
        $statuses = ['Never Married', 'Married', 'Divorced', 'Widowed', 'Awaiting Divorce', 'Annulled'];

        foreach ($statuses as $index => $name) {
            MaritalStatus::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'sort_order' => $index, 'is_active' => true],
            );
        }
    }

    protected function lifestyleOptions(): void
    {
        $options = [
            'diet' => ['Vegetarian', 'Non Vegetarian', 'Eggetarian', 'Vegan', 'Jain Vegetarian', 'Occasionally Non-Veg'],
            'smoking' => ['No', 'Occasionally', 'Yes'],
            'drinking' => ['No', 'Occasionally', 'Yes'],
            'body_type' => ['Slim', 'Athletic', 'Average', 'Heavy'],
            'complexion' => ['Very Fair', 'Fair', 'Wheatish', 'Wheatish Brown', 'Dark'],
        ];

        foreach ($options as $type => $names) {
            foreach ($names as $index => $name) {
                LifestyleOption::updateOrCreate(
                    ['type' => $type, 'slug' => Str::slug($name)],
                    ['name' => $name, 'sort_order' => $index, 'is_active' => true],
                );
            }
        }
    }

    /**
     * Caste and gothra stay free-text on the profile (UserProfile::caste /
     * ::gothra), so this is a starting list the admin panel builds on, not a
     * closed taxonomy — a member from a caste or gothra missing here still
     * needs somewhere to go, which is why "Others" closes both lists.
     */
    protected function castesAndGothras(): void
    {
        $castes = [
            'Iyer', 'Iyengar', 'Smartha', 'Nadar', 'Mudaliar', 'Chettiar', 'Gounder',
            'Vanniyar', 'Reddy', 'Kamma', 'Naidu', 'Yadav', 'Kurmi', 'Jat', 'Lingayat',
            'Vokkaliga', 'Nair', 'Ezhava', 'Menon', 'Patel', 'Kayastha', 'Arora', 'Khatri',
            'Saini', 'Rajput', 'Thakur', 'Maratha', 'Agarwal', 'Bania', 'Gupta',
            'Scheduled Caste', 'Scheduled Tribe', 'Caste No Bar', 'Others',
        ];

        foreach ($castes as $index => $name) {
            Caste::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'sort_order' => $index, 'is_active' => true],
            );
        }

        // The saptarishi gotras and the handful of others that show up most
        // often on real profiles.
        $gothras = [
            'Bharadwaja', 'Kashyapa', 'Vashishta', 'Vishwamitra', 'Atri', 'Jamadagni',
            'Gautama', 'Agastya', 'Kaundinya', 'Shandilya', 'Parashara', 'Kaushika',
            'Vatsa', 'Harita', 'Upamanyu', 'Garga', 'Others',
        ];

        foreach ($gothras as $index => $name) {
            Gothra::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'sort_order' => $index, 'is_active' => true],
            );
        }
    }
}
