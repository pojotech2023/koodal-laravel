<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('koodal_vibe_stories', function (Blueprint $table) {
            $table->id();
            $table->string('category_key', 50)->unique();
            $table->string('category_name', 150);
            $table->unsignedSmallInteger('min_score')->default(0);
            $table->unsignedSmallInteger('max_score')->default(100);
            $table->string('title', 255);
            $table->string('tagline', 255)->nullable();
            $table->longText('story_content');
            $table->string('call_to_action', 100)->default('❤️ PREMIUM CALL');
            $table->string('cta_url', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['min_score', 'max_score', 'is_active']);
        });

        // Seed initial default 5 full sample stories provided in requirements
        $now = now();
        DB::table('koodal_vibe_stories')->insert([
            [
                'category_key' => 'strong_connection',
                'category_name' => 'Strong Connection (80–100%)',
                'min_score' => 80,
                'max_score' => 100,
                'title' => '{groom_name} ❤️ {bride_name} — Strong Connection',
                'tagline' => 'Your Koodal Vibe: {score}% — Strong Connection!',
                'story_content' => "சில connections சின்ன சின்ன விஷயங்கள்லதான் ஆரம்பிக்கும்… ஒரே மாதிரியான choices, பிடிச்ச interests, ஒருத்தரை ஒருத்தர் புரிஞ்சுக்கிற அந்த அழகான feeling! ❤️\n\n{groom_name} & {bride_name}, உங்க Compatibility Test answers-ஐப் பார்த்தா, உங்களுக்குள் சில அழகான common interests இருக்கிறது. அதே நேரம், ஒருத்தர் இன்னொருத்தருக்குப் புதுசா அறிமுகப்படுத்தக்கூடிய விஷயங்களும் இருக்கு.\n\n{observation_detail}\n{memory_detail}\n\n**ஒருத்தர் கவனிக்கிறாங்க… இன்னொருத்தர் நினைவில் வச்சுக்கிறாரு.** ❤️\n\nசில நேரங்களில் நம்மள கவனிக்கிற ஒருத்தர் நமக்குப் பக்கத்துல இருக்கிறது ஒரு அழகான feeling. அதே மாதிரி, நம்ம சொன்ன சின்ன சின்ன விஷயங்களைக்கூட நினைவில் வச்சிருக்கிற ஒருத்தர் கிடைக்கிறது இன்னும் special!\n\n{teamwork_detail}\n\n{favourite_scene}\n\nகடல் அலைகள் ஓசைக்கு நடுவுல ஒரு சின்ன conversation… சிரிப்பு… ஒருத்தரை ஒருத்தர் இன்னும் கொஞ்சம் புரிஞ்சுக்கிற தருணம்… இப்படித்தானே சில அழகான memories ஆரம்பிக்கும்! ❤️\n\n**உங்க Vibe Meter: {score}% — Strong Connection!**\n\n{groom_name} & {bride_name}, உங்க Koodal Story ஒரு அழகான beginning-ல இருக்கு. இன்னும் பேசிப் பழகினா, ஒருத்தரை ஒருத்தர் புரிஞ்சுக்க இன்னும் நிறைய விஷயங்கள் கிடைக்கலாம்.\n\nஉங்க அடுத்த அழகான chapter-ஐ ஆரம்பிக்கலாமா?",
                'call_to_action' => '❤️ PREMIUM CALL',
                'cta_url' => '/membership',
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'category_key' => 'good_connection',
                'category_name' => 'Good Connection + Discovery (60–79%)',
                'min_score' => 60,
                'max_score' => 79,
                'title' => '{groom_name} ❤️ {bride_name} — Good Connection + Discovery',
                'tagline' => 'Your Koodal Vibe: {score}% — Good Connection + Discovery!',
                'story_content' => "ஒருத்தரை ஒருத்தர் தெரிஞ்சுக்க ஆரம்பிக்கும்போது, சில விஷயங்கள் உடனே common-ஆ இருக்கும்… இன்னும் சில விஷயங்களைப் பேசிப் பழகும்போதுதான் தெரிஞ்சுக்க முடியும். அதுதான் ஒரு புதிய connection-ஐ interesting-ஆ மாற்றும்! ❤️\n\n{groom_name} & {bride_name}, உங்க Compatibility Test answers-ல சில common interests இருக்கு. அதே நேரம், ஒருத்தருடைய favourite விஷயங்களை இன்னொருத்தர் புதுசா தெரிஞ்சுக்கிற வாய்ப்பும் இருக்கு.\n\n{observation_detail}\n{memory_detail}\n\n**{bride_name} சின்ன சின்ன விஷயங்களைக் கவனிக்கிறாங்க… {groom_name} அந்த விஷயங்களை நினைவில் வச்சுக்கிறாரு.** ஒருத்தருடைய strength இன்னொருத்தருக்கு ஒரு புதிய விஷயத்தைக் கற்றுக்கொடுக்கலாம். ❤️\n\n{teamwork_detail}\n\nஒருத்தர் முன்னேறும்போது இன்னொருத்தர் அவருக்குப் பக்கபலமா இருப்பது எவ்வளவு அழகான விஷயம் தெரியுமா?\n\nஇதே மாதிரி வாழ்க்கையிலும் ஒருத்தரோட முயற்சிக்கு இன்னொருத்தர் encouragement கொடுத்து, வெற்றிகளைச் சேர்ந்து கொண்டாடுற அந்த support இருவருடைய வாழ்க்கைப் பயணத்தையும் இன்னும் அழகாக்கலாம். ❤️\n\n{favourite_scene}\n\nஅங்க போனதும் யாருக்கு என்ன பிடிக்கும், யாருக்கு என்ன மாதிரியான memories இருக்கு, future-ல என்ன செய்யணும்னு ஆசைப்படுறாங்கன்னு பேச ஆரம்பிக்கலாம்.\n\nஒரு சின்ன outing idea-ல இருந்து, ஒருத்தரை ஒருத்தர் இன்னும் நல்லா தெரிஞ்சுக்கிற அழகான conversation ஆரம்பிக்கலாம் இல்லையா? ❤️\n\n**உங்க Vibe Meter: {score}% — Good Connection + Discovery!**\n\nஉங்களுக்குள் இருக்கிற common interests-ஐ enjoy பண்ணுங்க. வித்தியாசமான interests-ஐயும் ஒருத்தருக்கு ஒருத்தர் அறிமுகப்படுத்திக்கோங்க.\n\nஇந்த Koodal Story இன்னும் அழகான memories-க்கு ஒரு beginning-ஆ இருக்கட்டும்!",
                'call_to_action' => '❤️ PREMIUM CALL',
                'cta_url' => '/membership',
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'category_key' => 'different_interesting',
                'category_name' => 'Different, Yet Interesting (40–59%)',
                'min_score' => 40,
                'max_score' => 59,
                'title' => '{groom_name} ❤️ {bride_name} — Different, Yet Interesting',
                'tagline' => 'Your Koodal Vibe: {score}% — Different, Yet Interesting!',
                'story_content' => "சில பேரை நமக்கு உடனே புரிஞ்சிடும்… சில பேரைப் பற்றி தெரிஞ்சுக்கும்போதுதான் இன்னும் நிறைய interesting விஷயங்கள் இருக்குனு தெரிய வரும்! ❤️\n\n{groom_name} & {bride_name}, உங்க Compatibility Test answers-ல சில விஷயங்கள் வேறுபட்டிருக்கலாம். ஆனா அதுவே ஒருத்தரை ஒருத்தர் புதுசா தெரிஞ்சுக்க ஒரு வாய்ப்பாகவும் மாறலாம்.\n\nஏன்னா, ஒருத்தருக்கு பிடிச்ச விஷயம் இன்னொருத்தருக்கு புதுசா இருக்கலாம். அந்தப் புதுமைதான் conversation-க்கு ஒரு நல்ல starting point கொடுக்கலாம்.\n\n{observation_detail}\n{memory_detail}\n\nஇரண்டு பேரும் ஒரே மாதிரி இருக்க வேண்டிய அவசியமில்லை. சில நேரங்களில் ஒருத்தர் கவனிக்கிற விஷயத்தை இன்னொருத்தர் கவனிக்காம இருக்கலாம்; இன்னொருத்தர் நினைவில் வச்சிருக்கிற விஷயம் முதல் நபருக்கு புதுசா இருக்கலாம்.\n\nஅப்படித்தானே ஒருத்தரை ஒருத்தர் தெரிஞ்சுக்கிற பயணம் interesting-ஆ மாறும்? ❤️\n\n{teamwork_detail}\n\nஅந்த game-ல கிடைத்த support, ரெண்டு பேரும் சேர்ந்து ஒரு goal-ஐ அடையும்போது mutual support எவ்வளவு முக்கியம்னு காட்டுற ஒரு அழகான moment.\n\nஇதே மாதிரி வாழ்க்கையிலும் ஒருத்தரோட முயற்சிக்கு இன்னொருத்தர் support கொடுத்து, ஒருத்தரோட சந்தோஷத்தை இன்னொருத்தர் கொண்டாடுற உறவு உருவானா, வாழ்க்கைப் பயணம் இன்னும் அழகாகலாம். ❤️\n\n{favourite_scene}\n\n{groom_name}, உங்களுக்கு பிடிச்ச விஷயங்களை {bride_name}-க்கு அறிமுகப்படுத்தலாமே! {bride_name}, உங்களுக்கு பிடிச்சதை {groom_name}-க்கு recommend பண்ணலாமே!\n\nஅந்த conversation-ல இருந்து travel, food, childhood memories, future dreams என நிறைய விஷயங்களைப் பேசலாம்.\n\nஒரே மாதிரி answers இல்லாததால ஒரு story முடிஞ்சிடாது. சில நேரங்களில் அந்த differences-ல இருந்துதான் அடுத்த conversation ஆரம்பிக்கும். ❤️\n\n**உங்க Vibe Meter: {score}% — Different, Yet Interesting!**\n\nஇன்னும் ஒருத்தரை ஒருத்தர் தெரிஞ்சுக்க நிறைய விஷயங்கள் இருக்கலாம். கொஞ்சம் நேரம் எடுத்துக்கிட்டு, open-ஆ பேசிப் பாருங்க.\n\nஉங்க Koodal Story-யில் அடுத்தது என்ன வரும்னு ஒரு conversation ஆரம்பிச்சுப் பாருங்களேன்!",
                'call_to_action' => '❤️ PREMIUM CALL',
                'cta_url' => '/membership',
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'category_key' => 'story_unfold',
                'category_name' => 'A Story Yet to Unfold (20–39%)',
                'min_score' => 20,
                'max_score' => 39,
                'title' => '{groom_name} ❤️ {bride_name} — A Story Yet to Unfold',
                'tagline' => 'Your Koodal Vibe: {score}% — A Story Yet to Unfold!',
                'story_content' => "ஒவ்வொரு story-க்கும் ஒரு beginning இருக்கும். சில கதைகளில் முதல் பக்கத்திலேயே நிறைய similarities தெரியும்… சில கதைகளில் அடுத்த பக்கங்களைப் படிக்கும்போதுதான் ஒருத்தரை ஒருத்தர் புரிஞ்சுக்க முடியும். ❤️\n\n{groom_name} & {bride_name}, உங்க Compatibility Test answers-ல சில differences இருக்கலாம். ஆனா இன்னும் ஒருத்தரை ஒருத்தர் தெரிஞ்சுக்க வேண்டிய விஷயங்கள் நிறைய இருக்குனு இந்த game ஒரு starting point கொடுக்குது.\n\n{observation_detail}\n{memory_detail}\n\n{bride_name} details-ஐ கவனிக்கிறாங்க; {groom_name} தகவல்களை நினைவில் வச்சுக்கிறாரு. இந்த game results உங்க ரெண்டு பேருடைய தனித்தனி strengths-ஐக் காட்டுது.\n\nஒருத்தரை ஒருத்தர் இன்னும் நல்லா தெரிஞ்சுக்கும்போது, இந்த மாதிரியான சின்ன சின்ன differences-ஐப் பற்றிக்கூட பேசிப் புரிஞ்சுக்கலாம். ❤️\n\n{teamwork_detail}\n\nஒருத்தர் வெற்றிபெறும்போது இன்னொருத்தர் support பண்ணுற அந்த moment ரொம்ப அழகானது. வாழ்க்கையிலும் ஒருத்தரோட முயற்சிக்கு இன்னொருத்தர் பக்கபலமா இருந்து, சின்ன வெற்றிகளிலிருந்து பெரிய achievements வரைக்கும் சேர்ந்து கொண்டாடுற உறவு உருவாகலாம்.\n\nஇப்போ ஒரு simple conversation-ல ஆரம்பிக்கலாமா?\n\n{favourite_scene}\n\nஅதிலிருந்து இன்னும் நிறைய கேள்விகள் வரலாம்:\n\n“உங்களுக்கு ஏன் அந்த place பிடிக்கும்?”\n“அந்த food-ஐ எப்போல இருந்து விரும்புறீங்க?”\n“உங்க favourite memory என்ன?”\n\nஇப்படி ஒரு simple question-ல ஆரம்பிச்சு, ஒருத்தரை ஒருத்தர் இன்னும் நல்லா தெரிஞ்சுக்கலாம் இல்லையா? ❤️\n\n**உங்க Vibe Meter: {score}% — A Story Yet to Unfold!**\n\nஇது உங்க story-யோட முடிவு கிடையாது. ஒருத்தரை ஒருத்தர் புரிஞ்சுக்க இன்னும் என்னென்ன விஷயங்கள் இருக்குனு கண்டுபிடிக்க ஒரு beginning.\n\nஅடுத்த பக்கத்தை ஒரு conversation-ல இருந்து ஆரம்பிக்கலாமா?",
                'call_to_action' => '❤️ PREMIUM CALL',
                'cta_url' => '/membership',
                'is_active' => true,
                'sort_order' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'category_key' => 'start_conversation',
                'category_name' => "Let's Start with a Conversation (0–19%)",
                'min_score' => 0,
                'max_score' => 19,
                'title' => "{groom_name} ❤️ {bride_name} — Let's Start with a Conversation",
                'tagline' => "Your Koodal Vibe: {score}% — Let's Start with a Conversation!",
                'story_content' => "{groom_name} & {bride_name}, சில நேரங்களில் ஒரு game நம்மைப் பற்றி கொஞ்சம் காட்டும்… ஆனா ஒருத்தரை ஒருத்தர் உண்மையா தெரிஞ்சுக்க இன்னும் நிறைய பேச வேண்டியிருக்கும். ❤️\n\nஉங்க Compatibility Test answers-ல இப்போதைக்கு அதிகமான common points தெரியாமல் இருக்கலாம். பரவாயில்லை… ஒருத்தருடைய interests-ஐ இன்னொருத்தர் தெரிஞ்சுக்கிறதிலிருந்தும் ஒரு அழகான conversation ஆரம்பிக்கலாம்.\n\n{observation_detail}\n{memory_detail}\n\nஇந்த results மட்டும் வைத்து அவங்க ஒருத்தருக்கு ஒருத்தர் பொருத்தமானவர்களா இல்லையா என்று முடிவு செய்ய வேண்டியதில்லை.\n\n{teamwork_detail}\n\nஅந்த game-ல இருவரும் சேர்ந்து விளையாடிய விதம் ஒரு அழகான moment. ஒருத்தருக்கு ஒருத்தர் support பண்ணுறது, சேர்ந்து சிரிக்கிறது, சின்ன வெற்றிகளைக் கொண்டாடுறது போன்ற விஷயங்கள் ஒரு relationship-ல முக்கியமான memories-ஆ மாறலாம். ❤️\n\nஇப்போ உங்களுக்குள்ள ஒரு conversation ஆரம்பிக்க ஒரு easy idea இருக்கு!\n\n{favourite_scene}\n\n{groom_name}, உங்களுக்கு ஏன் அந்த place பிடிக்கும்னு {bride_name}-கிட்ட சொல்லுங்க. {bride_name}, உங்களுக்கு பிடித்ததை {groom_name}-கிட்ட சொல்லுங்க.\n\nஅங்கிருந்து travel, food, hobbies, favourite memories என மெதுவா conversation-ஐத் தொடரலாம்.\n\nசில நேரங்களில் ஒருத்தரை ஒருத்தர் தெரிஞ்சுக்க ஆரம்பிக்க ஒரு பெரிய plan தேவையில்லை… ஒரு simple question போதும்! ❤️\n\n**உங்க Vibe Meter: {score}% — Let's Start with a Conversation!**\n\nஇன்னும் நிறைய விஷயங்களைப் பேசித் தெரிஞ்சுக்கலாம். ஒருத்தரை ஒருத்தர் கேட்டு, புரிஞ்சுக்க முயற்சி பண்ணி, உங்களுக்கு இருவருக்கும் comfortable-ஆ இருந்தா அடுத்த step எடுக்கலாம்.\n\nஉங்க Koodal Story-யின் முதல் conversation-ஐ ஆரம்பிக்கலாமா?",
                'call_to_action' => '❤️ PREMIUM CALL',
                'cta_url' => '/membership',
                'is_active' => true,
                'sort_order' => 5,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('koodal_vibe_stories');
    }
};
