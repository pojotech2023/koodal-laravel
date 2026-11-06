<?php

namespace Database\Seeders;

use App\Models\KoodalFindItChallenge;
use App\Models\KoodalSyncQuestion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class KoodalGamesSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Remove old generic compatibility questions and previous test answers cleanly
        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('koodal_sync_answers')->truncate();
            DB::table('koodal_sync_sessions')->truncate();
            if (Schema::hasTable('koodal_sync_pair_histories')) {
                DB::table('koodal_sync_pair_histories')->truncate();
            }
            DB::table('koodal_sync_questions')->truncate();
            if (Schema::hasTable('game_rooms')) {
                DB::table('game_rooms')->where('game_type', 'sync')->delete();
            }
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        } else {
            DB::table('koodal_sync_answers')->delete();
            DB::table('koodal_sync_sessions')->delete();
            if (Schema::hasTable('koodal_sync_pair_histories')) {
                DB::table('koodal_sync_pair_histories')->delete();
            }
            DB::table('koodal_sync_questions')->delete();
            if (Schema::hasTable('game_rooms')) {
                DB::table('game_rooms')->where('game_type', 'sync')->delete();
            }
        }

        // 2. Seed fresh compatibility questions with real, engaging, specific options
        $questionsData = $this->getQuestionsData();

        foreach ($questionsData as $item) {
            KoodalSyncQuestion::create([
                'category' => $item['category'],
                'question_text_en' => $item['question_text_en'],
                'question_text_ta' => $item['question_text_ta'],
                'options' => $item['options'],
                'is_active' => true,
            ]);
        }

        // 3. Seed Find It Challenges
        KoodalFindItChallenge::updateOrCreate(
            ['title' => 'Madurai Meenakshi Temple Courtyard'],
            [
                'image_a' => '/images/games/find_it_1a.jpg',
                'image_b' => '/images/games/find_it_1b.jpg',
                'differences' => [
                    ['id' => 1, 'x' => 20, 'y' => 30, 'radius' => 10, 'name' => 'Missing Flower Garland'],
                    ['id' => 2, 'x' => 45, 'y' => 15, 'radius' => 10, 'name' => 'Clock Time Shift'],
                    ['id' => 3, 'x' => 70, 'y' => 60, 'radius' => 10, 'name' => 'Window Blind Removed'],
                    ['id' => 4, 'x' => 85, 'y' => 25, 'radius' => 10, 'name' => 'Bird on Tower'],
                    ['id' => 5, 'x' => 35, 'y' => 80, 'radius' => 10, 'name' => 'Extra Brass Lamp'],
                    ['id' => 6, 'x' => 12, 'y' => 65, 'radius' => 10, 'name' => 'Pillar Carving Detail'],
                    ['id' => 7, 'x' => 58, 'y' => 42, 'radius' => 10, 'name' => 'Umbrella Color Change'],
                    ['id' => 8, 'x' => 90, 'y' => 75, 'radius' => 10, 'name' => 'Missing Peacock Feather'],
                ],
                'is_active' => true,
            ]
        );

        KoodalFindItChallenge::updateOrCreate(
            ['title' => 'Traditional Tamil Marriage Hall'],
            [
                'image_a' => '/images/games/find_it_2a.jpg',
                'image_b' => '/images/games/find_it_2b.jpg',
                'differences' => [
                    ['id' => 1, 'x' => 15, 'y' => 25, 'radius' => 10, 'name' => 'Banana Leaf Missing Item'],
                    ['id' => 2, 'x' => 38, 'y' => 50, 'radius' => 10, 'name' => 'Silk Saree Pallu Motif'],
                    ['id' => 3, 'x' => 62, 'y' => 18, 'radius' => 10, 'name' => 'Nadaswaram Decor'],
                    ['id' => 4, 'x' => 80, 'y' => 70, 'radius' => 10, 'name' => 'Kolam Pattern Curve'],
                    ['id' => 5, 'x' => 28, 'y' => 85, 'radius' => 10, 'name' => 'Kumbam Water Vessel'],
                    ['id' => 6, 'x' => 50, 'y' => 35, 'radius' => 10, 'name' => 'Thamboolam Plate Coconut'],
                    ['id' => 7, 'x' => 72, 'y' => 45, 'radius' => 10, 'name' => 'Background Curtain Tie'],
                    ['id' => 8, 'x' => 92, 'y' => 20, 'radius' => 10, 'name' => 'Gold Chain Accent'],
                ],
                'is_active' => true,
            ]
        );
    }

    /**
     * Curated list of engaging, cultural, and realistic compatibility questions.
     * ZERO generic "High/Moderate/Occasional/Not Interested" options.
     * Every single question contains 4 meaningful, culturally relevant, specific choices.
     */
    private function getQuestionsData(): array
    {
        return [
            // ==========================================
            // 1. TRAVEL & DESTINATIONS (பயணம் & இடங்கள்)
            // ==========================================
            [
                'category' => 'Travel',
                'question_text_en' => 'Which is your favorite hill station to visit?',
                'question_text_ta' => 'உங்களுக்கு மிகவும் பிடித்த ஹில் ஸ்டேஷன் எது?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Ooty (Queen of Hills)', 'label_ta' => 'ஊட்டி 🌲'],
                    ['key' => 'B', 'label_en' => 'Kodaikanal (Princess of Hills)', 'label_ta' => 'கொடைக்கானல் 🌸'],
                    ['key' => 'C', 'label_en' => 'Munnar (Tea Garden Haven)', 'label_ta' => 'மூணார் 🍃'],
                    ['key' => 'D', 'label_en' => 'Yercaud (Jewel of the South)', 'label_ta' => 'ஏற்காடு ⛰️'],
                ],
            ],
            [
                'category' => 'Travel',
                'question_text_en' => 'Where would you love to live after marriage?',
                'question_text_ta' => 'திருமணத்திற்குப் பின் எங்கு வசிக்க விரும்புகிறீர்கள்?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Peaceful Native Town / Village Greenery', 'label_ta' => 'அமைதியான சொந்த ஊர் / இயற்கை சூழல் 🌴'],
                    ['key' => 'B', 'label_en' => 'Bustling Modern Metro City', 'label_ta' => 'நவீன பெருநகரம் / சிட்டி வாழ்க்கை 🏙️'],
                    ['key' => 'C', 'label_en' => 'Calm Suburban Town near City', 'label_ta' => 'அமைதியான புறநகர் பகுதி 🏡'],
                    ['key' => 'D', 'label_en' => 'Misty Hill Station / Nature Foothills', 'label_ta' => 'குளுமையான மலை பிரதேசம் ⛰️'],
                ],
            ],
            [
                'category' => 'Travel',
                'question_text_en' => 'Which coastal or beach destination do you like most?',
                'question_text_ta' => 'உங்களுக்கு மிகவும் பிடித்த கடற்கரை தலம் எது?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Pondicherry Beach Promenade', 'label_ta' => 'பாண்டிச்சேரி கடற்கரை 🏖️'],
                    ['key' => 'B', 'label_en' => 'Chennai Marina / Besant Nagar', 'label_ta' => 'சென்னை மெரினா / பெசன்ட் நகர் 🌊'],
                    ['key' => 'C', 'label_en' => 'Kanyakumari Sunset & Sunrise Point', 'label_ta' => 'கன்னியாகுமரி முக்கடல் சங்கமம் 🌅'],
                    ['key' => 'D', 'label_en' => 'Rameswaram Peaceful Coast', 'label_ta' => 'ராமேஸ்வரம் கடற்கரை 🛕'],
                ],
            ],
            [
                'category' => 'Travel',
                'question_text_en' => 'What is your dream honeymoon getaway?',
                'question_text_ta' => 'உங்கள் கனவு ஹனிமூன் சுற்றுலா எது?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Chilly Hill Resort & Misty Tea Gardens', 'label_ta' => 'குளுமையான மலை ரிசார்ட் & தேயிலைத் தோட்டம் 🏔️'],
                    ['key' => 'B', 'label_en' => 'Tropical Island & Private Beach Villa', 'label_ta' => 'அழகிய தீவு & கடற்கரை வில்லா 🏝️'],
                    ['key' => 'C', 'label_en' => 'International City & Shopping Tour', 'label_ta' => 'வெளிநாட்டு பெருநகர உலா & ஷாப்பிங் ✈️'],
                    ['key' => 'D', 'label_en' => 'Royal Heritage Palace & Nature Sanctuary', 'label_ta' => 'பாரம்பரிய அரண்மனை தங்குமிடம் 🏰'],
                ],
            ],
            [
                'category' => 'Travel',
                'question_text_en' => 'How do you prefer to travel with your partner?',
                'question_text_ta' => 'துணையுடன் எப்படி பயணிக்க விரும்புவீர்கள்?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Long Road Trip in Car with Good Music', 'label_ta' => 'பாடல்களுடன் கார் ரோடு ட்ரிப் 🚗'],
                    ['key' => 'B', 'label_en' => 'Scenic Window-Seat Train Journey', 'label_ta' => 'ரம்மியமான ரயில் பயணம் 🚂'],
                    ['key' => 'C', 'label_en' => 'Fast & Comfortable Flight Travel', 'label_ta' => 'விரைவான விமானப் பயணம் ✈️'],
                    ['key' => 'D', 'label_en' => 'Breezy & Thrilling Bike Ride', 'label_ta' => 'ஜாலியான பைக் ரைடு 🏍️'],
                ],
            ],
            [
                'category' => 'Travel',
                'question_text_en' => 'What is your ideal weekend outing?',
                'question_text_ta' => 'வார இறுதியில் உலா செல்ல பிடித்த இடம் எது?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Peaceful Temple & Spiritual Trip', 'label_ta' => 'அமைதியான கோவில் வழிபாடு 🛕'],
                    ['key' => 'B', 'label_en' => 'Shopping Mall & Theatre Movie Night', 'label_ta' => 'மால் ஷாப்பிங் & சினிமா 🛍️'],
                    ['key' => 'C', 'label_en' => 'Nature Trekking & Waterfalls', 'label_ta' => 'இயற்கை அருவி & ட்ரெக்கிங் 🌊'],
                    ['key' => 'D', 'label_en' => 'Relaxing at Home with Family & Movies', 'label_ta' => 'வீட்டில் குடும்பத்துடன் ஓய்வு 🏡'],
                ],
            ],
            [
                'category' => 'Travel',
                'question_text_en' => 'What is your travel packing style?',
                'question_text_ta' => 'பயணங்களுக்கு நீங்கள் பேக்கிங் செய்யும் விதம் எப்படி?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Light & Minimal Backpack only', 'label_ta' => 'எளிமையான குறைந்த பேக்கிங் 🎒'],
                    ['key' => 'B', 'label_en' => 'Well-Planned for every possible need', 'label_ta' => 'எல்லாவற்றுக்கும் முன்கூட்டியே திட்டம் 🧳'],
                    ['key' => 'C', 'label_en' => 'Last Minute Rush Packing', 'label_ta' => 'கடைசி நேர அவசர பேக்கிங் ⚡'],
                    ['key' => 'D', 'label_en' => 'Extra Trendy Outfits & Accessories', 'label_ta' => 'விதவிதமான உடைகள் & அலங்காரம் 👗'],
                ],
            ],
            [
                'category' => 'Travel',
                'question_text_en' => 'What do you like to do most during a vacation?',
                'question_text_ta' => 'சுற்றுலாவில் உங்களுக்கு என்ன செய்ய மிகவும் பிடிக்கும்?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Explore Famous Local Food Spots', 'label_ta' => 'உள்ளூர் சுவையான உணவுகளை ருசிப்பது 🍲'],
                    ['key' => 'B', 'label_en' => 'Relax in Cozy Resort with Zero Rush', 'label_ta' => 'அவசரமின்றி ரிசார்ட்டில் நிம்மதியான ஓய்வு 💆'],
                    ['key' => 'C', 'label_en' => 'Visit All Sightseeing & Historic Landmarks', 'label_ta' => 'அனைத்து முக்கிய இடங்களையும் காண்பது 📸'],
                    ['key' => 'D', 'label_en' => 'Adventure Rides & Water Sports', 'label_ta' => 'சாகச விளையாட்டுகள் & படகு சவாரி 🏄'],
                ],
            ],

            // ==========================================
            // 2. FOOD & DINING (உணவு & விருந்து)
            // ==========================================
            [
                'category' => 'Food',
                'question_text_en' => 'Which is your all-time favorite sweet?',
                'question_text_ta' => 'உங்களுக்கு மிகவும் பிடித்த இனிப்பு எது?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Hot Gulab Jamun', 'label_ta' => 'சூடான குலாப் ஜாமூன் 🍯'],
                    ['key' => 'B', 'label_en' => 'Srivilliputhur Paalkova', 'label_ta' => 'ஸ்ரீவில்லிபுத்தூர் பால்கோவா 🥛'],
                    ['key' => 'C', 'label_en' => 'Melt-in-Mouth Ghee Mysore Pak', 'label_ta' => 'நெய் மைசூர் பாக் 🧈'],
                    ['key' => 'D', 'label_en' => 'Tirunelveli Ghee Halwa', 'label_ta' => 'திருநெல்வேலி நெய் அல்வா 🥮'],
                ],
            ],
            [
                'category' => 'Food',
                'question_text_en' => 'What is your favorite South Indian breakfast?',
                'question_text_ta' => 'உங்களுக்கு மிகவும் பிடித்த தென்னிந்திய காலை உணவு எது?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Crispy Ghee Roast Dosa with Chutneys', 'label_ta' => 'மொறுமொறு நெய் ரோஸ்ட் தோசை 🥞'],
                    ['key' => 'B', 'label_en' => 'Hot Soft Idli with Medu Vada & Sambar', 'label_ta' => 'மல்லிகைப் பூ இட்லி, மெதுவடை & சாம்பார் 🥣'],
                    ['key' => 'C', 'label_en' => 'Fluffy Poori with Potato Masala', 'label_ta' => 'உப்பலான பூரி கிழங்கு மசாலா 🍞'],
                    ['key' => 'D', 'label_en' => 'Ghee Pongal with Crispy Vada', 'label_ta' => 'சுவையான நெய் பொங்கல் வடை 🍲'],
                ],
            ],
            [
                'category' => 'Food',
                'question_text_en' => 'Which Biryani variety do you relish most?',
                'question_text_ta' => 'உங்களுக்கு மிகவும் பிடித்த பிரியாணி வகை எது?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Dindigul Thalappakatti Seeraga Samba Biryani', 'label_ta' => 'திண்டுக்கல் சீரக சம்பா பிரியாணி 🍲'],
                    ['key' => 'B', 'label_en' => 'Authentic Ambur Dum Biryani', 'label_ta' => 'ஆம்பூர் தம் பிரியாணி 🍗'],
                    ['key' => 'C', 'label_en' => 'Spicy Chettinad Biryani', 'label_ta' => 'காரசாரமான செட்டிநாடு பிரியாணி 🌶️'],
                    ['key' => 'D', 'label_en' => 'Hyderabadi Fragrant Dum Biryani', 'label_ta' => 'ஹைதராபாதி நறுமண பிரியாணி 🍚'],
                ],
            ],
            [
                'category' => 'Food',
                'question_text_en' => 'What is your go-to beverage every day?',
                'question_text_ta' => 'தினமும் பருக விரும்பும் விருப்பமான பானம் எது?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'South Indian Filter Coffee', 'label_ta' => 'பாரம்பரிய ஃபில்டர் காபி ☕'],
                    ['key' => 'B', 'label_en' => 'Hot Elaichi / Ginger Masala Tea', 'label_ta' => 'மணமணக்கும் ஏலக்காய் / மசாலா டீ 🍵'],
                    ['key' => 'C', 'label_en' => 'Healthy Sukku Malli Herbal Coffee', 'label_ta' => 'ஆரோக்கியமான சுக்கு மல்லி காபி 🌿'],
                    ['key' => 'D', 'label_en' => 'Rich Hot Badam Milk', 'label_ta' => 'சுவையான பாதாம் பால் 🥛'],
                ],
            ],
            [
                'category' => 'Food',
                'question_text_en' => 'How much spiciness do you prefer in your meals?',
                'question_text_ta' => 'உணவில் உங்கள் காரத்தின் அளவு எப்படி இருக்க வேண்டும்?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Spicy & Fiery Chettinad Style', 'label_ta' => 'காரசாரமான செட்டிநாடு காரம் 🌶️🔥'],
                    ['key' => 'B', 'label_en' => 'Balanced Medium Spice', 'label_ta' => 'நடுத்தர மிதமான காரம் 🌶️'],
                    ['key' => 'C', 'label_en' => 'Mild & Gentle Spicing', 'label_ta' => 'குறைந்த காரம் & சாந்தமான சுவை 🥬'],
                    ['key' => 'D', 'label_en' => 'Slight Sweet & Tangy Touch', 'label_ta' => 'லேசான இனிப்பும் புளிப்பும் கலந்த சுவை 🍯'],
                ],
            ],
            [
                'category' => 'Food',
                'question_text_en' => 'What is your ideal Sunday afternoon lunch?',
                'question_text_ta' => 'ஞாயிற்றுக்கிழமை மதிய உணவு எப்படி அமைய வேண்டும்?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Grand Banana Leaf Non-Veg Feast', 'label_ta' => 'வாழை இலை அசைவ விருந்து 🍗'],
                    ['key' => 'B', 'label_en' => 'Traditional South Indian Veg Meals with Payasam', 'label_ta' => 'பாயசத்துடன் கூடிய சைவ விருந்து 🍃'],
                    ['key' => 'C', 'label_en' => 'Fresh Seafood Delicacies', 'label_ta' => 'சுவையான கடல் உணவு வகைகள் 🐟'],
                    ['key' => 'D', 'label_en' => 'Simple Homemade Comfort Food & Rasam', 'label_ta' => 'எளிய வீட்டு சாப்பாடு & ரசம் சாதம் 🥣'],
                ],
            ],
            [
                'category' => 'Food',
                'question_text_en' => 'What is your favorite evening snack with tea?',
                'question_text_ta' => 'மாலை நேரத்தில் டீயுடன் கொறிக்க மிகவும் பிடித்த ஸ்நாக்ஸ் எது?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Hot Medu Vada or Onion Bajji', 'label_ta' => 'சூடான உளுந்த வடை அல்லது வெங்காய பஜ்ஜி ☕'],
                    ['key' => 'B', 'label_en' => 'Spicy Pani Puri & Chaat Varieties', 'label_ta' => 'காரசார பானி பூரி & சாட் வகைகள் 🥙'],
                    ['key' => 'C', 'label_en' => 'Crispy Samosa & Mint Chutney', 'label_ta' => 'மொறுமொறு சமோசா & சட்னி 🥟'],
                    ['key' => 'D', 'label_en' => 'Steamed Sundal & Butter Sweet Corn', 'label_ta' => 'சுண்டல் & சூடான சோளம் 🌽'],
                ],
            ],
            [
                'category' => 'Food',
                'question_text_en' => 'How do you feel about cooking together with your partner?',
                'question_text_ta' => 'சமையல் பற்றிய உங்கள் பார்வை என்ன?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Love cooking together as a couple', 'label_ta' => 'இருவரும் சேர்ந்து மகிழ்ச்சியாக சமைப்பது 👩‍🍳👨‍🍳'],
                    ['key' => 'B', 'label_en' => 'I love experimenting with new recipes', 'label_ta' => 'புதுப்புது உணவுகளை சமைக்க பிடிக்கும் 🍳'],
                    ['key' => 'C', 'label_en' => 'Partner cooks, I will happily wash & clean up', 'label_ta' => 'துணை சமைக்க, நான் பாத்திரம் கழுவி உதவுவேன் 🧼'],
                    ['key' => 'D', 'label_en' => 'Prefer easy cooking or ordering tasty food', 'label_ta' => 'சுலபமாக சமைப்பது அல்லது ஆர்டர் செய்வது 🍕'],
                ],
            ],
            [
                'category' => 'Food',
                'question_text_en' => 'Which dessert or cold treat satisfies your sweet tooth?',
                'question_text_ta' => 'உங்களுக்கு மிகவும் பிடித்த குளிரூட்டப்பட்ட இனிப்பு எது?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Madurai Famous Jigarthanda', 'label_ta' => 'மதுரை ஸ்பெஷல் ஜிகர்தண்டா 🍧'],
                    ['key' => 'B', 'label_en' => 'Tender Coconut Natural Ice Cream', 'label_ta' => 'இளநீர் ஐஸ்கிரீம் 🥥'],
                    ['key' => 'C', 'label_en' => 'Royal Falooda with Dry Fruits', 'label_ta' => 'ராயல் ஃபலூடா 🍨'],
                    ['key' => 'D', 'label_en' => 'Rich Dark Chocolate Brownie', 'label_ta' => 'சாக்லேட் பிரவுனி & கேக் 🍫'],
                ],
            ],

            // ==========================================
            // 3. LIFESTYLE (வாழ்க்கை முறை)
            // ==========================================
            [
                'category' => 'Lifestyle',
                'question_text_en' => 'How do you love spending your Sunday mornings?',
                'question_text_ta' => 'ஞாயிறு காலை நேரத்தை எப்படி கழிக்க பிடிக்கும்?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Sleeping in peacefully with no alarm', 'label_ta' => 'அலாரம் இன்றி நிம்மதியாக தூங்குவது 🛌'],
                    ['key' => 'B', 'label_en' => 'Filter Coffee with Newspaper in quiet', 'label_ta' => 'ரிலாக்ஸாக ஃபில்டர் காபி & செய்தி வாசிப்பு 📰'],
                    ['key' => 'C', 'label_en' => 'Morning Walk / Jog in fresh cool air', 'label_ta' => 'சுத்தமான காற்றில் காலை நடைபயிற்சி 🏃‍♂️'],
                    ['key' => 'D', 'label_en' => 'Temple Visit for blessings and peace', 'label_ta' => 'மன அமைதிக்காக கோவில் வழிபாடு 🛕'],
                ],
            ],
            [
                'category' => 'Lifestyle',
                'question_text_en' => 'What is your favorite attire for weddings & festivals?',
                'question_text_ta' => 'திருமணம் & விசேஷங்களுக்கு உங்கள் உடை விருப்பம் எது?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Traditional Silk Saree / Silk Dhoti', 'label_ta' => 'பாரம்பரிய பட்டு சேலை / பட்டு வேட்டி 🥻'],
                    ['key' => 'B', 'label_en' => 'Elegant Modern Kurta or Formal Suit', 'label_ta' => 'நவீன குர்தா / நேர்த்தியான கோட் சூட் 👔'],
                    ['key' => 'C', 'label_en' => 'Indo-Western Fusion Wear', 'label_ta' => 'மாடர்ன் இந்தோ-வெஸ்டர்ன் உடை 👗'],
                    ['key' => 'D', 'label_en' => 'Simple & Comfortable Cotton Clothes', 'label_ta' => 'எளிமையான காட்டன் ஆடைகள் 👕'],
                ],
            ],
            [
                'category' => 'Lifestyle',
                'question_text_en' => 'What are your thoughts on having pets at home?',
                'question_text_ta' => 'வீட்டில் செல்லப் பிராணிகள் வளர்ப்பதில் உங்கள் விருப்பம் என்ன?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Dog Lover - Playful & faithful', 'label_ta' => 'அன்பான நாய் குட்டிகள் 🐶'],
                    ['key' => 'B', 'label_en' => 'Cat Lover - Calm & adorable', 'label_ta' => 'செல்ல பூனைகள் 🐱'],
                    ['key' => 'C', 'label_en' => 'Colorful Birds & Aquarium Fishes', 'label_ta' => 'வண்ணப் பறவைகள் & மீன்கள் 🦜🐠'],
                    ['key' => 'D', 'label_en' => 'Prefer a clean home without pets', 'label_ta' => 'செல்லப் பிராணிகள் வளர்க்க ஆர்வம் இல்லை 🚫'],
                ],
            ],
            [
                'category' => 'Lifestyle',
                'question_text_en' => 'How do you approach shopping?',
                'question_text_ta' => 'ஷாப்பிங் செல்லும்போது உங்கள் பழக்கம் எப்படி?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Planned list with budget tracking', 'label_ta' => 'திட்டமிட்ட பட்ஜெட் ஷாப்பிங் 📝'],
                    ['key' => 'B', 'label_en' => 'Buy whatever instantly captures my eye', 'label_ta' => 'பார்த்ததும் பிடித்ததை உடனே வாங்குவது 🛍️'],
                    ['key' => 'C', 'label_en' => 'Hunting online for best deals & discounts', 'label_ta' => 'ஆஃபர் & தள்ளுபடி பார்த்து வாங்குவது 🏷️'],
                    ['key' => 'D', 'label_en' => 'Quick minimal shopping only when needed', 'label_ta' => 'தேவைப்படும் போது மட்டும் குறைந்த ஷாப்பிங் ⏱️'],
                ],
            ],
            [
                'category' => 'Lifestyle',
                'question_text_en' => 'What is your favorite way to stay fit and active?',
                'question_text_ta' => 'உடலை ஆரோக்கியமாக வைக்க நீங்கள் விரும்பும் வழி எது?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Daily Morning / Evening Walk', 'label_ta' => 'நடைபயிற்சி 🚶'],
                    ['key' => 'B', 'label_en' => 'Gym Workouts & Weight Training', 'label_ta' => 'ஜிம் உடற்பயிற்சி 🏋️'],
                    ['key' => 'C', 'label_en' => 'Yoga, Pranayama & Meditation', 'label_ta' => 'யோகா & தியானம் 🧘'],
                    ['key' => 'D', 'label_en' => 'Outdoor Sports like Badminton or Cricket', 'label_ta' => 'விளையாட்டு (பேட்மிண்டன் / கிரிக்கெட்) 🏸'],
                ],
            ],
            [
                'category' => 'Lifestyle',
                'question_text_en' => 'How do you use your smartphone during leisure time?',
                'question_text_ta' => 'ஓய்வு நேரத்தில் மொபைல் போனை எப்படி பயன்படுத்துவீர்கள்?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Scrolling Instagram Reels & Comedy Memes', 'label_ta' => 'இன்ஸ்டாகிராம் ரீல்ஸ் & மீம்ஸ் பார்ப்பது 📱'],
                    ['key' => 'B', 'label_en' => 'WhatsApp chatting with close family & friends', 'label_ta' => 'குடும்பம் & நண்பர்களுடன் சாட் செய்வது 💬'],
                    ['key' => 'C', 'label_en' => 'Watching Informative YouTube Videos & News', 'label_ta' => 'பயனுள்ள தகவல்கள் & செய்திகள் காண்பது 🎥'],
                    ['key' => 'D', 'label_en' => 'Minimal phone use, enjoy real world moments', 'label_ta' => 'போனை அதிகம் தொடாமல் ரிலாக்ஸ் செய்வது 📵'],
                ],
            ],

            // ==========================================
            // 4. ENTERTAINMENT & MUSIC (பொழுதுபோக்கு & கலை)
            // ==========================================
            [
                'category' => 'Entertainment',
                'question_text_en' => 'Which movie genre do you enjoy watching most in theatres?',
                'question_text_ta' => 'தியேட்டரில் எந்த வகையான திரைப்படங்களை பார்க்க பிடிக்கும்?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Mass Action & Whistle-worthy Commercial Films', 'label_ta' => 'மாஸ் ஆக்சன் திரைப்படங்கள் 🎬'],
                    ['key' => 'B', 'label_en' => 'Romantic Comedy & Feel-Good Family Drama', 'label_ta' => 'காதல் & நகைச்சுவை ஃபீல்-குட் படங்கள் ❤️'],
                    ['key' => 'C', 'label_en' => 'Gripping Suspense Thrillers & Murder Mysteries', 'label_ta' => 'சஸ்பென்ஸ் திரில்லர் படங்கள் 🕵️'],
                    ['key' => 'D', 'label_en' => 'Historical Epics & True Life Stories', 'label_ta' => 'வரலாற்று காவியங்கள் & உண்மை கதைகள் 🏰'],
                ],
            ],
            [
                'category' => 'Entertainment',
                'question_text_en' => 'Whose musical genius touches your soul the most?',
                'question_text_ta' => 'யாருடைய இசை உங்கள் மனதை அதிகம் தொடுகிறது?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Isaignani Ilaiyaraaja Timeless Melodies', 'label_ta' => 'இளையராஜாவின் காலத்தால் அழியாத மெலடிகள் 🎻'],
                    ['key' => 'B', 'label_en' => 'AR Rahman Magical Harmonies & Global Sound', 'label_ta' => 'ஏ.ஆர்.ரஹ்மானின் மேஜிக்கல் இசை 🎼'],
                    ['key' => 'C', 'label_en' => 'Anirudh High-Energy Beats & Youth Anthems', 'label_ta' => 'அனிருத்தின் எனர்ஜி துள்ளல் பாடல்கள் 🥁'],
                    ['key' => 'D', 'label_en' => 'Harris Jayaraj Breezy & Romantic Hits', 'label_ta' => 'ஹாரிஸ் ஜெயராஜின் வசீகர காதல் பாடல்கள் 🎸'],
                ],
            ],
            [
                'category' => 'Entertainment',
                'question_text_en' => 'How do you prefer watching web series and movies at home?',
                'question_text_ta' => 'வீட்டில் வெப் சீரிஸ் அல்லது படங்கள் பார்க்கும் முறை எப்படி?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Binge the entire series in one thrilling night', 'label_ta' => 'ஒரே இரவில் முழு தொடரையும் முடிப்பது 🌙'],
                    ['key' => 'B', 'label_en' => 'One relaxing episode per day after dinner', 'label_ta' => 'தினமும் டின்னருக்குப் பின் 1 எபிசோட் 📺'],
                    ['key' => 'C', 'label_en' => 'Weekend Movie Nights with Popcorn & Snacks', 'label_ta' => 'வார இறுதியில் பாப்கார்னுடன் படம் பார்ப்பது 🍿'],
                    ['key' => 'D', 'label_en' => 'Watch only highly recommended top-rated content', 'label_ta' => 'டாப் ரேட்டிங் பெற்ற சிறந்த படைப்புகள் மட்டும் ⭐'],
                ],
            ],
            [
                'category' => 'Entertainment',
                'question_text_en' => 'Which live event would you love to attend with your partner?',
                'question_text_ta' => 'துணையுடன் எந்த நேரடி நிகழ்ச்சிக்கு செல்ல விரும்புவீர்கள்?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'High-Energy Live Musical Concert', 'label_ta' => 'பிரம்மாண்ட நேரடி இசை கச்சேரி 🎤'],
                    ['key' => 'B', 'label_en' => 'Stand-Up Comedy Show full of non-stop laughs', 'label_ta' => 'கலகலப்பான ஸ்டாண்ட்-அப் காமெடி ஷோ 😂'],
                    ['key' => 'C', 'label_en' => 'Serene Classical Carnatic Sabha Concert', 'label_ta' => 'அமைதியான சங்கீத சபா கச்சேரி 🪕'],
                    ['key' => 'D', 'label_en' => 'Live Cricket Match at Stadium', 'label_ta' => 'ஸ்டேடியத்தில் நேரடி கிரிக்கெட் போட்டி 🏏'],
                ],
            ],
            [
                'category' => 'Entertainment',
                'question_text_en' => 'Which indoor game would you enjoy playing together?',
                'question_text_ta' => 'வீட்டில் இருவரும் சேர்ந்து விளையாட பிடித்த உள்விளையாட்டு எது?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Classic Carrom Board Match', 'label_ta' => 'பாரம்பரிய கேரம் போர்டு 🎯'],
                    ['key' => 'B', 'label_en' => 'Brainy Chess Challenge', 'label_ta' => 'அறிவுத்திறன் செஸ் விளையாட்டு ♟️'],
                    ['key' => 'C', 'label_en' => 'Fun Board Games like Ludo or Monopoly', 'label_ta' => 'லூடோ / மோனோபலி போன்ற பலகை ஆட்டம் 🎲'],
                    ['key' => 'D', 'label_en' => 'Mobile / Console Multiplayer Video Games', 'label_ta' => 'மொபைல் / வீடியோ கேம்ஸ் 🎮'],
                ],
            ],

            // ==========================================
            // 5. DAILY HABITS (தினசரி பழக்கவழக்கங்கள்)
            // ==========================================
            [
                'category' => 'Daily habits',
                'question_text_en' => 'What is your usual morning wake-up time?',
                'question_text_ta' => 'வழக்கமாக காலையில் எத்தனை மணிக்கு கண் விழிப்பீர்கள்?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Early Bird (5:00 AM – 6:00 AM)', 'label_ta' => 'அதிகாலை 5:00 - 6:00 மணி 🌅'],
                    ['key' => 'B', 'label_en' => 'Standard Morning (6:30 AM – 7:30 AM)', 'label_ta' => 'காலை 6:30 - 7:30 மணி ☀️'],
                    ['key' => 'C', 'label_en' => 'Cozy Late Riser (8:00 AM or after)', 'label_ta' => 'பொறுமையாக 8:00 மணிக்கு மேல் 🛌'],
                    ['key' => 'D', 'label_en' => 'Flexible depending on work schedule', 'label_ta' => 'வேலை நேரத்தைப் பொறுத்து மாறும் ⏰'],
                ],
            ],
            [
                'category' => 'Daily habits',
                'question_text_en' => 'What time do you usually drift off to sleep?',
                'question_text_ta' => 'இரவில் பொதுவாக எத்தனை மணிக்கு தூங்கச் செல்வீர்கள்?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Early to Bed (Before 10:30 PM)', 'label_ta' => 'இரவு 10:30 மணிக்கு முன் 🌙'],
                    ['key' => 'B', 'label_en' => 'Moderate Time (11:00 PM – 11:30 PM)', 'label_ta' => 'இரவு 11:00 - 11:30 மணி 😴'],
                    ['key' => 'C', 'label_en' => 'Night Owl (After Midnight)', 'label_ta' => 'நள்ளிரவு 12 மணிக்கு மேல் 🦉'],
                    ['key' => 'D', 'label_en' => 'Depends on movies or sweet conversations', 'label_ta' => 'படம் அல்லது பேச்சுக்கு ஏற்ப மாறும் 🔄'],
                ],
            ],
            [
                'category' => 'Daily habits',
                'question_text_en' => 'What is the very first thing you like doing after waking up?',
                'question_text_ta' => 'காலையில் எழுந்தவுடன் முதலில் என்ன செய்ய பிடிக்கும்?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Peaceful sip of hot Coffee or Tea in silence', 'label_ta' => 'அமைதியாக சூடான காபி / டீ குடிப்பது ☕'],
                    ['key' => 'B', 'label_en' => 'Quick prayer and thanking for the new day', 'label_ta' => 'இறை வழிபாடு & நன்றி தெரிவிப்பது 🕯️'],
                    ['key' => 'C', 'label_en' => 'Glass of warm water and refreshing wash', 'label_ta' => 'வெந்நீர் குடித்து முகம் கழுவுவது 🥛'],
                    ['key' => 'D', 'label_en' => 'Checking essential messages & time on phone', 'label_ta' => 'மொபைலில் முக்கிய செய்திகள் பார்ப்பது 📱'],
                ],
            ],
            [
                'category' => 'Daily habits',
                'question_text_en' => 'What is your favorite habit right before falling asleep?',
                'question_text_ta' => 'இரவு தூங்குவதற்கு முன் உங்கள் வழக்கம் என்ன?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Heart-to-heart talk about the day highlights', 'label_ta' => 'அன்றைய நிகழ்வுகளை துணையுடன் பேசுவது 💬'],
                    ['key' => 'B', 'label_en' => 'Reading an inspiring book or devotional', 'label_ta' => 'புத்தகம் வாசிப்பது 📖'],
                    ['key' => 'C', 'label_en' => 'Listening to peaceful soft melodies', 'label_ta' => 'மெல்லிய அமைதியான பாடல்கள் கேட்பது 🎧'],
                    ['key' => 'D', 'label_en' => 'Watching funny short videos with lights dimmed', 'label_ta' => 'வேடிக்கையான வீடியோக்கள் பார்த்து சிரிப்பது 📱'],
                ],
            ],
            [
                'category' => 'Daily habits',
                'question_text_en' => 'How would you love to handle household chores together?',
                'question_text_ta' => 'வீட்டு வேலைகளை எப்படி பகிர்ந்து கொள்ள விரும்புகிறீர்கள்?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Teamwork – doing everything together joyfully', 'label_ta' => 'இருவரும் ஒன்றாக சேர்ந்து மகிழ்ச்சியுடன் செய்வது 🤝'],
                    ['key' => 'B', 'label_en' => 'Equal 50-50 division of specific chores', 'label_ta' => 'ஆளுக்குப் பாதியாக பணிகளை பிரித்துக் கொள்வது 🧹'],
                    ['key' => 'C', 'label_en' => 'One cooks delicious meals, the other cleans up', 'label_ta' => 'ஒருவர் சமைக்க மற்றவர் சுத்தம் செய்வது 🍳'],
                    ['key' => 'D', 'label_en' => 'Modern approach with smart appliances & helpers', 'label_ta' => 'வாஷிங் மெஷின் & ஆட்கள் உதவி பெறுவது 🏠'],
                ],
            ],
            [
                'category' => 'Daily habits',
                'question_text_en' => 'How punctual are you when attending events?',
                'question_text_ta' => 'ஒரு நிகழ்ச்சிக்கு புறப்படுவதில் உங்கள் பழக்கம் எப்படி?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Always 10–15 minutes early', 'label_ta' => 'எப்போதும் 10-15 நிமிடம் முன்கூட்டியே செல்வேன் ⌚'],
                    ['key' => 'B', 'label_en' => 'Exactly on the dot of the promised time', 'label_ta' => 'சரியான நேரத்திற்கு போய்விடுவேன் 🎯'],
                    ['key' => 'C', 'label_en' => 'Slightly relaxed (5–10 minutes delay)', 'label_ta' => 'சிறிது நிதானமாக 5-10 நிமிடம் தாமதம் ⏳'],
                    ['key' => 'D', 'label_en' => 'Last minute rush to make it in time', 'label_ta' => 'கடைசி நிமிடத்தில் பறந்து செல்வேன் 🏃'],
                ],
            ],

            // ==========================================
            // 6. PERSONALITY & EMOTIONS (ஆளுமை & குணம்)
            // ==========================================
            [
                'category' => 'Personality',
                'question_text_en' => 'When small disagreements happen, how do you handle them?',
                'question_text_ta' => 'கருத்து வேறுபாடுகள் வரும்போது எப்படி கையாளுவீர்கள்?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Discuss openly and calmly with a warm smile', 'label_ta' => 'புன்னகையுடன் வெளிப்படையாக பேசி தீர்ப்பேன் 🕊️'],
                    ['key' => 'B', 'label_en' => 'Take 30 minutes of quiet time to cool down first', 'label_ta' => 'சிறிது நேரம் அமைதியாக இருந்து பின் பேசுவேன் ⏳'],
                    ['key' => 'C', 'label_en' => 'Break the tension with humor or sweet words', 'label_ta' => 'நகைச்சுவை அல்லது அன்பான பேச்சால் சரிசெய்வேன் 😊'],
                    ['key' => 'D', 'label_en' => 'Give in warmly because relationship is bigger', 'label_ta' => 'உறவு தான் முக்கியம் என விட்டுக்கொடுப்பேன் ❤️'],
                ],
            ],
            [
                'category' => 'Personality',
                'question_text_en' => 'Which gesture makes you feel loved and appreciated most?',
                'question_text_ta' => 'அன்பை வெளிப்படுத்துவதில் உங்களுக்கு மிகவும் பிடித்த விதம் எது?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Words of Praise & Constant Encouragement', 'label_ta' => 'பாராட்டும் உற்சாகமூட்டும் அன்பான வார்த்தைகள் 💬'],
                    ['key' => 'B', 'label_en' => 'Undivided Quality Time & Deep Attention', 'label_ta' => 'முழு கவனத்துடன் ஒன்றாக நேரம் செலவிடுவது 💑'],
                    ['key' => 'C', 'label_en' => 'Thoughtful Surprise Gifts & Handwritten Notes', 'label_ta' => 'அன்பான சர்ப்ரைஸ் பரிசுகள் 🎁'],
                    ['key' => 'D', 'label_en' => 'Helpful Actions & Supporting in Daily Work', 'label_ta' => 'தேவைகளை அறிந்து உதவிகள் செய்வது 🤝'],
                ],
            ],
            [
                'category' => 'Personality',
                'question_text_en' => 'How do you make important decisions in life?',
                'question_text_ta' => 'முக்கியமான முடிவுகளை நீங்கள் எப்படி எடுப்பீர்கள்?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Discuss deeply with partner and decide mutually', 'label_ta' => 'துணையுடன் கலந்து பேசி ஒருமித்த முடிவு 🤝'],
                    ['key' => 'B', 'label_en' => 'Practical, logical analysis of pros and cons', 'label_ta' => 'சாதக பாதகங்களை தெளிவாக ஆராய்ந்து முடிவு 📊'],
                    ['key' => 'C', 'label_en' => 'Follow my inner heart & intuitive gut feeling', 'label_ta' => 'மனசாட்சி மற்றும் உள்ளுணர்வு சொல்வதை கேட்டு ❤️'],
                    ['key' => 'D', 'label_en' => 'Seek the guidance & blessings of experienced elders', 'label_ta' => 'பெரியவர்களின் ஆலோசனையைப் பெற்று 👴'],
                ],
            ],
            [
                'category' => 'Personality',
                'question_text_en' => 'What kind of humor tickles your funny bone the most?',
                'question_text_ta' => 'உங்களை மனம் விட்டு சிரிக்க வைக்கும் நகைச்சுவை எது?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Vadivelu & Goundamani timeless classic timing', 'label_ta' => 'வடிவேலு & கவுண்டமணி காமெடி கலாட்டா 😄'],
                    ['key' => 'B', 'label_en' => 'Cute witty sarcasm and sharp comebacks', 'label_ta' => 'அழகான நக்கல் & சமயோசித பேச்சு 😏'],
                    ['key' => 'C', 'label_en' => 'Wholesome family comedy movies', 'label_ta' => 'குடும்பத்தோடு ரசிக்கும் திரைப்பட நகைச்சுவை 🎬'],
                    ['key' => 'D', 'label_en' => 'Funny everyday relatable reels & memes', 'label_ta' => 'சமூக வலைத்தள ரீல்ஸ் & மீம்ஸ் 📱'],
                ],
            ],
            [
                'category' => 'Personality',
                'question_text_en' => 'How are you at large family or social functions?',
                'question_text_ta' => 'பெரிய விசேஷ கூட்டங்களில் உங்கள் சுபாவம் எப்படி?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Friendly and mingling warmly with everyone', 'label_ta' => 'அனைவரிடமும் கலகலப்பாக நலம் விசாரிப்பேன் 🌟'],
                    ['key' => 'B', 'label_en' => 'Cozy and relaxed in my close circle of favorites', 'label_ta' => 'நெருங்கிய சொந்தங்களிடம் மட்டும் பேசுவேன் ☕'],
                    ['key' => 'C', 'label_en' => 'Polite listener and smiling peaceful observer', 'label_ta' => 'அமைதியான புன்னகையோடு ரசித்துப் பார்ப்பேன் 🕊️'],
                    ['key' => 'D', 'label_en' => 'Prefer intimate small gatherings over huge crowds', 'label_ta' => 'கூட்டத்தை விட சிறிய சந்திப்புகளையே விரும்புவேன் 🏡'],
                ],
            ],

            // ==========================================
            // 7. FAMILY & CULTURE (குடும்பம் & உறவுகள்)
            // ==========================================
            [
                'category' => 'Family/life situations',
                'question_text_en' => 'How do you love celebrating major festivals like Pongal or Diwali?',
                'question_text_ta' => 'பொங்கல், தீபாவளி பண்டிகைகளை எப்படி கொண்டாட பிடிக்கும்?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Visiting native village with entire family', 'label_ta' => 'சொந்த ஊருக்கு சென்று ஒட்டுமொத்த குடும்பத்தோடு 🛕'],
                    ['key' => 'B', 'label_en' => 'Traditional home pooja, sweets & new clothes', 'label_ta' => 'வீட்டில் முறைப்படி பூஜை, இனிப்புகள் & புத்தாடை 🪔'],
                    ['key' => 'C', 'label_en' => 'Catching up with friends & visiting relatives', 'label_ta' => 'உறவினர்கள் & நண்பர்களை சந்தித்து மகிழ்வது 🥳'],
                    ['key' => 'D', 'label_en' => 'Quiet peaceful holiday relaxing at home', 'label_ta' => 'நெரிசல் இன்றி வீட்டில் அமைதியாக கொண்டாடுவது ☕'],
                ],
            ],
            [
                'category' => 'Family/life situations',
                'question_text_en' => 'What is your vision for bonding with your spouse family?',
                'question_text_ta' => 'வாழ்க்கைத் துணையின் குடும்பத்தோடு பழகும் விதம் பற்றிய உங்கள் பார்வை என்ன?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Treat like my own parents with genuine care', 'label_ta' => 'சொந்த பெற்றோரைப் போல பாசத்தோடு கவனிப்பேன் 💖'],
                    ['key' => 'B', 'label_en' => 'Friendly and open relationship with mutual respect', 'label_ta' => 'நண்பர்கள் போன்ற ஆரோக்கியமான மரியாதை 🤝'],
                    ['key' => 'C', 'label_en' => 'Supportive pillar whenever they need assistance', 'label_ta' => 'அவர்களுக்கு தேவையான போது உதவும் தூணாக இருப்பேன் 🛡️'],
                    ['key' => 'D', 'label_en' => 'Balanced harmony with healthy personal space', 'label_ta' => 'இரு குடும்பங்களுக்கும் சமமான முக்கியத்துவம் ⚖️'],
                ],
            ],
            [
                'category' => 'Family/life situations',
                'question_text_en' => 'How should major family choices be finalized?',
                'question_text_ta' => 'குடும்பத்தின் பெரிய முடிவுகள் எப்படி எடுக்கப்பட வேண்டும்?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Couple mutual consensus first, then family input', 'label_ta' => 'தம்பதியர் முதலில் முடிவெடுத்து பின் குடும்ப ஆலோசனை 💑'],
                    ['key' => 'B', 'label_en' => 'Open discussion including everyone perspective', 'label_ta' => 'அனைவரும் அமர்ந்து மனம் திறந்து பேசுவது 🗣️'],
                    ['key' => 'C', 'label_en' => 'Value the wisdom of experienced family elders', 'label_ta' => 'குடும்பப் பெரியவர்களின் அனுபவத்திற்கு முன்னுரிமை 👴'],
                    ['key' => 'D', 'label_en' => 'Balance personal dreams with family happiness', 'label_ta' => 'இருதரப்பு மகிழ்ச்சியையும் சமமாக பேணுவது ⚖️'],
                ],
            ],
            [
                'category' => 'Family/life situations',
                'question_text_en' => 'How often would you love visiting parents and native place?',
                'question_text_ta' => 'பெற்றோர் வீடு அல்லது சொந்த ஊருக்கு செல்லும் விருப்பம் எப்படி?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Regular weekend or bi-weekly visits warmly', 'label_ta' => 'மாதத்தில் இரண்டு முறை அல்லது அடிக்கடி 🚗'],
                    ['key' => 'B', 'label_en' => 'Monthly relaxed trip to spend quality days', 'label_ta' => 'மாதம் ஒருமுறை சென்று சில நாட்கள் தங்குவது 🗓️'],
                    ['key' => 'C', 'label_en' => 'Every festive occasion and family functions', 'label_ta' => 'அனைத்து பண்டிகைகள் & குடும்ப விசேஷங்களுக்கு 🎉'],
                    ['key' => 'D', 'label_en' => 'Warmly welcoming parents to our home often', 'label_ta' => 'பெற்றோரை நம் வீட்டிற்கு அடிக்கடி அழைத்து உபசரிப்பது 🏡'],
                ],
            ],

            // ==========================================
            // 8. MONEY PREFERENCES (பண நிர்வாகம் & நிதி)
            // ==========================================
            [
                'category' => 'Money preferences',
                'question_text_en' => 'What is your core philosophy about money and savings?',
                'question_text_ta' => 'பணத்தை கையாள்வதில் உங்கள் முக்கிய கொள்கை என்ன?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Save & invest first, then budget the remainder', 'label_ta' => 'முதலில் சேமிப்பு & முதலீடு, பின் செலவு 💰'],
                    ['key' => 'B', 'label_en' => 'Balanced 50-50 ratio of savings and life enjoyment', 'label_ta' => 'பாதி சேமிப்பு, பாதி வாழ்க்கையை கொண்டாடுவது ⚖️'],
                    ['key' => 'C', 'label_en' => 'Spend happily on good quality food & experiences', 'label_ta' => 'நல்ல உணவு & அனுபவங்களுக்கு தாராளமாக செலவிடுவது 🎉'],
                    ['key' => 'D', 'label_en' => 'Grow assets smartly in real estate and gold', 'label_ta' => 'தங்கம் மற்றும் சொத்துக்களில் புத்திசாலி முதலீடு 📈'],
                ],
            ],
            [
                'category' => 'Money preferences',
                'question_text_en' => 'What does your dream home look like?',
                'question_text_ta' => 'உங்கள் கனவு இல்லம் எப்படிப்பட்டதாக இருக்க வேண்டும்?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Independent House with a beautiful garden & trees', 'label_ta' => 'மரங்கள் & பூந்தோட்டத்துடன் கூடிய தனி வீடு 🏡'],
                    ['key' => 'B', 'label_en' => 'Modern Gated Community Apartment with amenities', 'label_ta' => 'நீச்சல் குளம் ஜிம் வசதியுடன் நவீன அபார்ட்மெண்ட் 🏢'],
                    ['key' => 'C', 'label_en' => 'Traditional Farmhouse with nature breeze', 'label_ta' => 'இயற்கை சூழலில் அமைதியான பண்ணை வீடு 🌾'],
                    ['key' => 'D', 'label_en' => 'Cozy suburban home close to city comforts', 'label_ta' => 'நகருக்கு அருகில் அமைதியான குடியிருப்பு இல்லம் 🏠'],
                ],
            ],
            [
                'category' => 'Money preferences',
                'question_text_en' => 'When planning big purchases, what is your preferred approach?',
                'question_text_ta' => 'பெரிய பொருட்கள் வாங்கும்போது உங்கள் முறை என்ன?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Save up full cash and buy without loan stress', 'label_ta' => 'முன்பே சேமித்து கடன் சுமையின்றி வாங்குவது 💵'],
                    ['key' => 'B', 'label_en' => 'Smart low-cost EMI with comfortable repayment', 'label_ta' => 'சுலபமான தவணை முறையில் வாங்குவது 💳'],
                    ['key' => 'C', 'label_en' => 'Wait for festive season discount offers', 'label_ta' => 'தீபாவளி அல்லது ஆஃபர் காலங்களில் வாங்குவது 🏷️'],
                    ['key' => 'D', 'label_en' => 'Prioritize top durability & quality over price tag', 'label_ta' => 'விலை பார்க்காமல் வாழ்நாள் தரம் பார்த்து வாங்குவது 👑'],
                ],
            ],
            [
                'category' => 'Money preferences',
                'question_text_en' => 'How should a couple manage their finances together?',
                'question_text_ta' => 'தம்பதியர் தங்கள் பொருளாதாரத்தை எப்படி நிர்வகிக்க வேண்டும்?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Full transparency with joint budgeting & goals', 'label_ta' => 'முழு வெளிப்படைத்தன்மையுடன் கூட்டு இலக்குகள் 🤝'],
                    ['key' => 'B', 'label_en' => 'Joint savings fund plus individual personal accounts', 'label_ta' => 'கூட்டு சேமிப்பு + இருவருக்கும் தனித்தனி பாக்கெட் மணி 💳'],
                    ['key' => 'C', 'label_en' => 'Clearly assigned monthly responsibilities', 'label_ta' => 'இருவருக்கும் தனித்தனி குடும்ப செலவு பொறுப்புகள் 📋'],
                    ['key' => 'D', 'label_en' => 'Flexible and supportive based on circumstances', 'label_ta' => 'சூழ்நிலைகளுக்கு ஏற்ப பரஸ்பர ஆதரவு 🕊️'],
                ],
            ],

            // ==========================================
            // 9. FUTURE PREFERENCES (எதிர்கால கனவுகள்)
            // ==========================================
            [
                'category' => 'Future preferences',
                'question_text_en' => 'What is your vision for work-life balance?',
                'question_text_ta' => 'வேலை மற்றும் குடும்ப வாழ்க்கையை சமநிலைப்படுத்துவதில் உங்கள் பார்வை?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Healthy work-life harmony where family comes first', 'label_ta' => 'குடும்பத்திற்கு முதலிடம் தரும் சமநிலை ⚖️'],
                    ['key' => 'B', 'label_en' => 'High career ambition & climbing professional heights', 'label_ta' => 'தொழிலில் உயர்ந்த நிலையை அடைவது 🏆'],
                    ['key' => 'C', 'label_en' => 'Dream of running own business or enterprise', 'label_ta' => 'சொந்த தொழில் அல்லது பிசினஸ் செய்வது 💼'],
                    ['key' => 'D', 'label_en' => 'Flexible remote work from home comfort', 'label_ta' => 'வீட்டில் இருந்தே வேலை செய்யும் வசதி 💻'],
                ],
            ],
            [
                'category' => 'Future preferences',
                'question_text_en' => 'What is one skill you would love to learn together with your spouse?',
                'question_text_ta' => 'திருமணத்திற்குப் பின் இருவரும் சேர்ந்து கற்க விரும்பும் விஷயம் எது?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Driving cars & exploring roads together', 'label_ta' => 'கார் டிரைவிங் கற்று லாங் டிரைவ் போவது 🚗'],
                    ['key' => 'B', 'label_en' => 'Learning a new language or dance style', 'label_ta' => 'புதிய மொழி அல்லது நடனம் கற்பது 💃'],
                    ['key' => 'C', 'label_en' => 'Gardening & Organic Vegetable farming', 'label_ta' => 'மாடித்தோட்டம் & இயற்கை விவசாயம் 🌿'],
                    ['key' => 'D', 'label_en' => 'Mutual funds, stock investing & wealth building', 'label_ta' => 'முதலீடு & சொத்து உருவாக்கம் 📈'],
                ],
            ],
            [
                'category' => 'Future preferences',
                'question_text_en' => 'How do you picture your peaceful retirement years?',
                'question_text_ta' => 'பணி ஓய்வு காலத்தை எங்கு எப்படி கழிக்க விரும்புகிறீர்கள்?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Organic farm in native village with cows & greenery', 'label_ta' => 'சொந்த ஊரில் பசுமையான இயற்கை பண்ணை வாழ்க்கை 🌾'],
                    ['key' => 'B', 'label_en' => 'Traveling the world and exploring new cultures', 'label_ta' => 'உலகம் முழுவதும் சுற்றுலா செல்வது ✈️'],
                    ['key' => 'C', 'label_en' => 'Spiritual devotion, temple service & charity', 'label_ta' => 'ஆன்மீக சேவை & எளிய மனிதர்களுக்கு உதவுவது 🛕'],
                    ['key' => 'D', 'label_en' => 'Cozy modern city life close to children', 'label_ta' => 'பிள்ளைகள் அருகில் அமைதியான நகர வாழ்க்கை 🏢'],
                ],
            ],

            // ==========================================
            // 10. FUN HYPOTHETICAL SITUATIONS (கற்பனை தருணங்கள்)
            // ==========================================
            [
                'category' => 'Fun hypothetical situations',
                'question_text_en' => 'If you win 1 Crore in a lucky contest, what will you do first?',
                'question_text_ta' => 'திடீரென 1 கோடி ரூபாய் பரிசு கிடைத்தால் முதலில் என்ன செய்வீர்கள்?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Buy a beautiful dream home for family', 'label_ta' => 'குடும்பத்திற்கு பிடித்த அழகிய கனவு இல்லம் வாங்குவேன் 🏡'],
                    ['key' => 'B', 'label_en' => 'Take my partner on a dream world tour', 'label_ta' => 'வாழ்க்கைத் துணையுடன் உலக சுற்றுலா போவேன் ✈️'],
                    ['key' => 'C', 'label_en' => 'Invest smartly in business & safe fixed deposits', 'label_ta' => 'தொழிலில் & வங்கியில் முதலீடு செய்து வளர்ப்பேன் 📈'],
                    ['key' => 'D', 'label_en' => 'Donate generously for children education', 'label_ta' => 'ஆதரவற்ற குழந்தைகளின் கல்விக்கு உதவுவேன் 💖'],
                ],
            ],
            [
                'category' => 'Fun hypothetical situations',
                'question_text_en' => 'If stranded on an island with your partner, what 1 comfort would you pick?',
                'question_text_ta' => 'ஒரு தீவில் துணையுடன் மாட்டிக் கொண்டால் 1 விருப்பப் பொருள் எதுவாக இருக்கும்?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Endless fresh South Indian Filter Coffee & Tea', 'label_ta' => 'இடைவிடாத சுடச்சுட ஃபில்டர் காபி / டீ ☕'],
                    ['key' => 'B', 'label_en' => 'Favorite music player with all nostalgic melodies', 'label_ta' => 'பிடித்த பாடல்கள் நிறைந்த மியூசிக் பிளேயர் 🎵'],
                    ['key' => 'C', 'label_en' => 'A complete outdoor cooking kit with spices', 'label_ta' => 'சுவையான உணவு சமைக்க கிச்சன் பொருட்கள் 🍳'],
                    ['key' => 'D', 'label_en' => 'Cozy hammock and warm blanket', 'label_ta' => 'கதகதப்பான தூளி மற்றும் போர்வை 🏕️'],
                ],
            ],
            [
                'category' => 'Fun hypothetical situations',
                'question_text_en' => 'If granted one superpower, which one would you choose?',
                'question_text_ta' => 'ஒரு சூப்பர் பவர் சக்தி கிடைத்தால் எதை தேர்ந்தெடுப்பீர்கள்?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Ability to fly anywhere in seconds', 'label_ta' => 'நினைத்த இடத்திற்கு நொடியில் பறந்து செல்லும் சக்தி 🦅'],
                    ['key' => 'B', 'label_en' => 'Ability to understand partner thoughts and mood', 'label_ta' => 'துணையின் மனநிலையை உடனே புரிந்து கொள்ளும் சக்தி 🧠'],
                    ['key' => 'C', 'label_en' => 'Time travel to relive sweet memories', 'label_ta' => 'கடந்த கால நினைவுகளுக்கு செல்லும் காலப் பயணம் ⏳'],
                    ['key' => 'D', 'label_en' => 'Power to heal any illness and spread joy', 'label_ta' => 'நோய்களை குணப்படுத்தி மகிழ்ச்சியை பரப்பும் சக்தி ✨'],
                ],
            ],
            [
                'category' => 'Fun hypothetical situations',
                'question_text_en' => 'If a time machine was real, which era would you travel to?',
                'question_text_ta' => 'டைம் மெஷின் இருந்தால் எந்த காலகட்டத்திற்கு செல்ல விரும்புவீர்கள்?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'The majestic Chola Empire & Tanjore Big Temple era', 'label_ta' => 'மாமன்னர் ராஜராஜ சோழன் காலகட்டம் 🏰'],
                    ['key' => 'B', 'label_en' => 'The golden retro 1980s Tamil music era', 'label_ta' => '80ஸ் பொற்கால இசை காலகட்டம் 📻'],
                    ['key' => 'C', 'label_en' => 'The futuristic Year 2100 with advanced technology', 'label_ta' => 'எதிர்கால அறிவியல் உலகம் 2100 🚀'],
                    ['key' => 'D', 'label_en' => 'The Indian Freedom Movement era', 'label_ta' => 'சுதந்திர போராட்ட தியாக வரலாறு காலம் 🇮🇳'],
                ],
            ],

            // ==========================================
            // 11. DATING & ROMANCE (காதல் & ரொமான்ஸ்)
            // ==========================================
            [
                'category' => 'Lifestyle',
                'question_text_en' => 'What is your idea of a perfect romantic date?',
                'question_text_ta' => 'உங்களுக்கு மிகவும் பிடித்த ரொமாண்டிக் டேட் எது?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Candlelight dinner with soft soothing music', 'label_ta' => 'மெல்லிய இசையுடன் மெழுகுவர்த்தி டின்னர் 🕯️'],
                    ['key' => 'B', 'label_en' => 'Holding hands during a sunset beach walk', 'label_ta' => 'மாலை கடற்கரையில் கைபிடித்து நடப்பது 🌅'],
                    ['key' => 'C', 'label_en' => 'Late-night highway drive with romantic songs', 'label_ta' => 'பாடல்களுடன் லேட் நைட் கார் டிரைவ் 🚗'],
                    ['key' => 'D', 'label_en' => 'Cooking dinner together at home followed by movie', 'label_ta' => 'வீட்டில் ஒன்றாக சமைத்து படம் பார்ப்பது 🎬'],
                ],
            ],
            [
                'category' => 'Lifestyle',
                'question_text_en' => 'Which surprise gift would touch your heart deepest?',
                'question_text_ta' => 'உங்கள் மனதை உருக வைக்கும் ஆச்சரியப் பரிசு எது?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Handwritten emotional love letter', 'label_ta' => 'மனதை தொடும் கையெழுத்து கடிதம் 💌'],
                    ['key' => 'B', 'label_en' => 'Personalized photo album of our cherished memories', 'label_ta' => 'நம் புகைப்பட நினைவுகள் நிறைந்த ஆல்பம் 📸'],
                    ['key' => 'C', 'label_en' => 'My favorite dessert or meal cooked with pure love', 'label_ta' => 'ஆசையோடு சமைத்த எனக்கு பிடித்த உணவு 🍲'],
                    ['key' => 'D', 'label_en' => 'Elegant perfume or timeless wrist watch', 'label_ta' => 'அழகான வாசனை திரவியம் அல்லது கைக்கடிகாரம் 🎁'],
                ],
            ],
            [
                'category' => 'Personality',
                'question_text_en' => 'What do you consider the most important pillar of a happy marriage?',
                'question_text_ta' => 'வாழ்க்கை முழுவதும் நிலைக்கும் திருமணத்திற்கு மிக முக்கிய தூண் எது?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Deep mutual trust, transparency & honesty', 'label_ta' => 'ஆழமான நம்பிக்கை & முழு வெளிப்படைத்தன்மை 💎'],
                    ['key' => 'B', 'label_en' => 'Unconditional warmth, care & sweet affection', 'label_ta' => 'மாறாத அன்பும் உண்மையான அக்கறையும் ❤️'],
                    ['key' => 'C', 'label_en' => 'Great friendship, fun & lots of daily laughter', 'label_ta' => 'நல்ல நட்பும் தினமும் சிரித்து மகிழ்வதும் 😄'],
                    ['key' => 'D', 'label_en' => 'Respecting each other individuality & dreams', 'label_ta' => 'ஒருவருக்கொருவர் கனவுகளுக்கு கொடுக்கும் மரியாதை 🌟'],
                ],
            ],
            [
                'category' => 'Lifestyle',
                'question_text_en' => 'Which color tone reflects your personal aesthetic vibe?',
                'question_text_ta' => 'உங்கள் விருப்பமான வண்ண அழகு எது?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Royal Navy Blue & Deep Ocean Shades', 'label_ta' => 'ராயல் நேவி ப்ளூ & கடல் நீலம் 💙'],
                    ['key' => 'B', 'label_en' => 'Rose Pink & Soft Peach Romance', 'label_ta' => 'ரோஸ் பிங்க் & மெல்லிய பீச் நிறம் 🌸'],
                    ['key' => 'C', 'label_en' => 'Peaceful Earthy Pastel & Ivory White', 'label_ta' => 'அமைதியான பாஸ்டல் & வெள்ளை 🤍'],
                    ['key' => 'D', 'label_en' => 'Traditional Royal Gold, Maroon & Mustard', 'label_ta' => 'மங்கலமான கோல்ட் & மெரூன் 💛'],
                ],
            ],
            [
                'category' => 'Lifestyle',
                'question_text_en' => 'When it pours rain outside, what is your favorite mood?',
                'question_text_ta' => 'மழை பெய்யும் மாலையில் உங்கள் விருப்பம் என்ன?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Hot onion bajjis & steaming filter tea by window', 'label_ta' => 'ஜன்னல் ஓரம் சுடச்சுட பஜ்ஜி & டீ ☕'],
                    ['key' => 'B', 'label_en' => 'Romantic music playlist with rain drop sounds', 'label_ta' => 'மெல்லிய மழைப் பாடல்கள் கேட்பது 🌧️'],
                    ['key' => 'C', 'label_en' => 'Stepping out to get wet and enjoy raindrops', 'label_ta' => 'மழையில் நனைந்து ஜாலியாக ரசிப்பது ☔'],
                    ['key' => 'D', 'label_en' => 'Snuggling under a warm cozy blanket with a movie', 'label_ta' => 'கதகதப்பான போர்வையில் ஓய்வு 🛌'],
                ],
            ],
            [
                'category' => 'Entertainment',
                'question_text_en' => 'What is your favorite music vibe for a long highway drive?',
                'question_text_ta' => 'நெடுஞ்சாலை பயணத்தில் ஒலிக்க வேண்டிய பாடல்கள் எவை?',
                'options' => [
                    ['key' => 'A', 'label_en' => 'Romantic melodious love anthems', 'label_ta' => 'மனதை வருடும் இனிமையான காதல் பாடல்கள் 🎵'],
                    ['key' => 'B', 'label_en' => 'Fast beats & upbeat energetic celebration songs', 'label_ta' => 'துள்ளலான எனர்ஜி பாடல்கள் 🥁'],
                    ['key' => 'C', 'label_en' => 'Retro 80s & 90s golden oldies classics', 'label_ta' => '80ஸ், 90ஸ் பொற்கால பாடல்கள் 📻'],
                    ['key' => 'D', 'label_en' => 'Calm acoustic and soothing instrumental tracks', 'label_ta' => 'அமைதியான வாத்திய இசை 🎻'],
                ],
            ],
        ];
    }
}
