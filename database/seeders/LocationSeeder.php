<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * India in full (36 states and union territories, with the cities that carry
 * real user volume), plus the NRI destinations that matter to this market —
 * a Gulf or US-based groom is a large and well-paying segment, and "not in the
 * dropdown" means "not registered".
 *
 * The complete world city list is an import job, not a seeder: admin has
 * POST /admin/locations/import for that. This gives a usable product on a
 * fresh database.
 */
class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $india = Country::updateOrCreate(
            ['slug' => 'india'],
            ['name' => 'India', 'iso2' => 'IN', 'iso3' => 'IND', 'phone_code' => '+91', 'sort_order' => 0, 'is_active' => true],
        );

        $this->seedIndia($india);
        $this->seedNriCountries();
    }

    protected function seedIndia(Country $india): void
    {
        $states = [
            'Tamil Nadu' => ['TN', ['Chennai', 'Coimbatore', 'Madurai', 'Tiruchirappalli', 'Salem', 'Tirunelveli', 'Erode', 'Vellore', 'Thoothukudi', 'Thanjavur', 'Dindigul', 'Tiruppur', 'Nagercoil', 'Kanchipuram', 'Cuddalore', 'Karur', 'Namakkal', 'Sivakasi', 'Hosur', 'Ooty']],
            'Karnataka' => ['KA', ['Bengaluru', 'Mysuru', 'Mangaluru', 'Hubballi', 'Belagavi', 'Davanagere', 'Ballari', 'Kalaburagi', 'Shivamogga', 'Tumakuru', 'Udupi', 'Dharwad']],
            'Maharashtra' => ['MH', ['Mumbai', 'Pune', 'Nagpur', 'Nashik', 'Thane', 'Aurangabad', 'Solapur', 'Kolhapur', 'Amravati', 'Navi Mumbai', 'Sangli', 'Jalgaon']],
            'Kerala' => ['KL', ['Thiruvananthapuram', 'Kochi', 'Kozhikode', 'Thrissur', 'Kollam', 'Kannur', 'Alappuzha', 'Palakkad', 'Kottayam', 'Malappuram']],
            'Telangana' => ['TG', ['Hyderabad', 'Warangal', 'Nizamabad', 'Karimnagar', 'Khammam', 'Secunderabad', 'Ramagundam']],
            'Andhra Pradesh' => ['AP', ['Visakhapatnam', 'Vijayawada', 'Guntur', 'Nellore', 'Tirupati', 'Kurnool', 'Rajahmundry', 'Kakinada', 'Anantapur', 'Kadapa']],
            'Delhi' => ['DL', ['New Delhi', 'North Delhi', 'South Delhi', 'East Delhi', 'West Delhi', 'Dwarka', 'Rohini']],
            'Uttar Pradesh' => ['UP', ['Lucknow', 'Kanpur', 'Ghaziabad', 'Agra', 'Varanasi', 'Meerut', 'Prayagraj', 'Noida', 'Bareilly', 'Aligarh', 'Moradabad', 'Gorakhpur']],
            'Gujarat' => ['GJ', ['Ahmedabad', 'Surat', 'Vadodara', 'Rajkot', 'Bhavnagar', 'Jamnagar', 'Gandhinagar', 'Junagadh', 'Anand']],
            'West Bengal' => ['WB', ['Kolkata', 'Howrah', 'Durgapur', 'Asansol', 'Siliguri', 'Bardhaman', 'Malda', 'Kharagpur']],
            'Rajasthan' => ['RJ', ['Jaipur', 'Jodhpur', 'Udaipur', 'Kota', 'Ajmer', 'Bikaner', 'Alwar', 'Bhilwara']],
            'Punjab' => ['PB', ['Ludhiana', 'Amritsar', 'Jalandhar', 'Patiala', 'Bathinda', 'Mohali', 'Pathankot']],
            'Haryana' => ['HR', ['Gurugram', 'Faridabad', 'Panipat', 'Ambala', 'Hisar', 'Karnal', 'Rohtak', 'Sonipat']],
            'Madhya Pradesh' => ['MP', ['Indore', 'Bhopal', 'Jabalpur', 'Gwalior', 'Ujjain', 'Sagar', 'Satna']],
            'Bihar' => ['BR', ['Patna', 'Gaya', 'Bhagalpur', 'Muzaffarpur', 'Darbhanga', 'Purnia']],
            'Odisha' => ['OD', ['Bhubaneswar', 'Cuttack', 'Rourkela', 'Berhampur', 'Sambalpur', 'Puri']],
            'Assam' => ['AS', ['Guwahati', 'Silchar', 'Dibrugarh', 'Jorhat', 'Nagaon', 'Tezpur']],
            'Jharkhand' => ['JH', ['Ranchi', 'Jamshedpur', 'Dhanbad', 'Bokaro', 'Deoghar']],
            'Chhattisgarh' => ['CG', ['Raipur', 'Bhilai', 'Bilaspur', 'Korba', 'Durg']],
            'Uttarakhand' => ['UK', ['Dehradun', 'Haridwar', 'Roorkee', 'Haldwani', 'Rishikesh', 'Nainital']],
            'Himachal Pradesh' => ['HP', ['Shimla', 'Solan', 'Dharamshala', 'Mandi', 'Kullu']],
            'Goa' => ['GA', ['Panaji', 'Margao', 'Vasco da Gama', 'Mapusa']],
            'Jammu and Kashmir' => ['JK', ['Srinagar', 'Jammu', 'Anantnag', 'Baramulla']],
            'Chandigarh' => ['CH', ['Chandigarh']],
            'Puducherry' => ['PY', ['Puducherry', 'Karaikal', 'Yanam', 'Mahe']],
            'Tripura' => ['TR', ['Agartala', 'Udaipur', 'Dharmanagar']],
            'Manipur' => ['MN', ['Imphal', 'Thoubal', 'Bishnupur']],
            'Meghalaya' => ['ML', ['Shillong', 'Tura', 'Jowai']],
            'Nagaland' => ['NL', ['Kohima', 'Dimapur', 'Mokokchung']],
            'Mizoram' => ['MZ', ['Aizawl', 'Lunglei', 'Champhai']],
            'Arunachal Pradesh' => ['AR', ['Itanagar', 'Naharlagun', 'Pasighat']],
            'Sikkim' => ['SK', ['Gangtok', 'Namchi', 'Gyalshing']],
            'Ladakh' => ['LA', ['Leh', 'Kargil']],
            'Andaman and Nicobar Islands' => ['AN', ['Port Blair']],
            'Dadra and Nagar Haveli and Daman and Diu' => ['DN', ['Daman', 'Silvassa', 'Diu']],
            'Lakshadweep' => ['LD', ['Kavaratti']],
        ];

        foreach ($states as $stateName => [$code, $cities]) {
            $state = State::updateOrCreate(
                ['country_id' => $india->id, 'slug' => Str::slug($stateName)],
                ['name' => $stateName, 'code' => $code, 'is_active' => true],
            );

            foreach ($cities as $cityName) {
                City::updateOrCreate(
                    ['state_id' => $state->id, 'slug' => Str::slug($cityName)],
                    ['name' => $cityName, 'is_active' => true],
                );
            }
        }
    }

    /**
     * The diaspora destinations that appear on real profiles. Each gets the
     * cities with meaningful Indian populations rather than a full gazetteer —
     * a 40,000-row global city table belongs in the import job.
     */
    protected function seedNriCountries(): void
    {
        $countries = [
            ['United States', 'US', 'USA', '+1', [
                'California' => ['San Francisco', 'San Jose', 'Los Angeles', 'San Diego', 'Fremont', 'Irvine'],
                'Texas' => ['Dallas', 'Houston', 'Austin', 'Plano', 'Irving'],
                'New Jersey' => ['Jersey City', 'Edison', 'Newark', 'Princeton'],
                'New York' => ['New York City', 'Buffalo', 'Albany'],
                'Illinois' => ['Chicago', 'Naperville', 'Schaumburg'],
                'Washington' => ['Seattle', 'Bellevue', 'Redmond'],
                'Georgia' => ['Atlanta', 'Alpharetta'],
                'Massachusetts' => ['Boston', 'Cambridge'],
            ]],
            ['United Arab Emirates', 'AE', 'ARE', '+971', [
                'Dubai' => ['Dubai', 'Deira', 'Jumeirah'],
                'Abu Dhabi' => ['Abu Dhabi', 'Al Ain'],
                'Sharjah' => ['Sharjah'],
                'Ajman' => ['Ajman'],
            ]],
            ['United Kingdom', 'GB', 'GBR', '+44', [
                'England' => ['London', 'Birmingham', 'Manchester', 'Leicester', 'Leeds', 'Coventry'],
                'Scotland' => ['Glasgow', 'Edinburgh'],
                'Wales' => ['Cardiff'],
                'Northern Ireland' => ['Belfast'],
            ]],
            ['Canada', 'CA', 'CAN', '+1', [
                'Ontario' => ['Toronto', 'Ottawa', 'Mississauga', 'Brampton', 'Waterloo'],
                'British Columbia' => ['Vancouver', 'Surrey', 'Burnaby'],
                'Alberta' => ['Calgary', 'Edmonton'],
                'Quebec' => ['Montreal'],
            ]],
            ['Australia', 'AU', 'AUS', '+61', [
                'New South Wales' => ['Sydney', 'Newcastle'],
                'Victoria' => ['Melbourne', 'Geelong'],
                'Queensland' => ['Brisbane', 'Gold Coast'],
                'Western Australia' => ['Perth'],
            ]],
            ['Singapore', 'SG', 'SGP', '+65', ['Singapore' => ['Singapore']]],
            ['Qatar', 'QA', 'QAT', '+974', ['Doha' => ['Doha', 'Al Rayyan']]],
            ['Saudi Arabia', 'SA', 'SAU', '+966', [
                'Riyadh' => ['Riyadh'],
                'Makkah' => ['Jeddah', 'Makkah'],
                'Eastern Province' => ['Dammam', 'Al Khobar'],
            ]],
            ['Kuwait', 'KW', 'KWT', '+965', ['Kuwait' => ['Kuwait City', 'Hawalli']]],
            ['Oman', 'OM', 'OMN', '+968', ['Muscat' => ['Muscat', 'Salalah']]],
            ['Malaysia', 'MY', 'MYS', '+60', ['Kuala Lumpur' => ['Kuala Lumpur'], 'Selangor' => ['Petaling Jaya', 'Shah Alam'], 'Penang' => ['George Town']]],
            ['New Zealand', 'NZ', 'NZL', '+64', ['Auckland' => ['Auckland'], 'Wellington' => ['Wellington'], 'Canterbury' => ['Christchurch']]],
            ['Germany', 'DE', 'DEU', '+49', ['Bavaria' => ['Munich'], 'Berlin' => ['Berlin'], 'Hesse' => ['Frankfurt']]],
            ['Ireland', 'IE', 'IRL', '+353', ['Leinster' => ['Dublin'], 'Munster' => ['Cork', 'Limerick']]],
            ['Sri Lanka', 'LK', 'LKA', '+94', ['Western' => ['Colombo', 'Negombo'], 'Northern' => ['Jaffna']]],
            ['Nepal', 'NP', 'NPL', '+977', ['Bagmati' => ['Kathmandu', 'Lalitpur']]],
        ];

        foreach ($countries as $index => [$name, $iso2, $iso3, $phone, $states]) {
            $country = Country::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'iso2' => $iso2, 'iso3' => $iso3, 'phone_code' => $phone, 'sort_order' => $index + 1, 'is_active' => true],
            );

            foreach ($states as $stateName => $cities) {
                $state = State::updateOrCreate(
                    ['country_id' => $country->id, 'slug' => Str::slug($stateName)],
                    ['name' => $stateName, 'is_active' => true],
                );

                foreach ($cities as $cityName) {
                    City::updateOrCreate(
                        ['state_id' => $state->id, 'slug' => Str::slug($cityName)],
                        ['name' => $cityName, 'is_active' => true],
                    );
                }
            }
        }
    }
}
