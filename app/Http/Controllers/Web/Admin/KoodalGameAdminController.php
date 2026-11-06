<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\KoodalFindItChallenge;
use App\Models\KoodalSyncQuestion;
use App\Models\KoodalVibeStory;
use Illuminate\Http\Request;

class KoodalGameAdminController extends Controller
{
    /**
     * List Koodal Sync questions with category filter & search.
     */
    public function questions(Request $request)
    {
        $query = KoodalSyncQuestion::query();

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('question_text_en', 'like', "%{$search}%")
                  ->orWhere('question_text_ta', 'like', "%{$search}%");
            });
        }

        $questions = $query->latest('id')->paginate(20)->withQueryString();
        $categories = KoodalSyncQuestion::CATEGORIES;

        return view('admin.games.questions.index', [
            'questions' => $questions,
            'categories' => $categories,
        ]);
    }

    /**
     * Form to create a question.
     */
    public function createQuestion()
    {
        return view('admin.games.questions.form', [
            'question' => new KoodalSyncQuestion(),
            'categories' => KoodalSyncQuestion::CATEGORIES,
            'isEdit' => false,
        ]);
    }

    /**
     * Store new question.
     */
    public function storeQuestion(Request $request)
    {
        $request->validate([
            'category' => 'required|string|max:50',
            'question_text_en' => 'required|string|max:1000',
            'question_text_ta' => 'nullable|string|max:1000',
            'options' => 'required|array|min:2',
            'options.*.key' => 'required|string',
            'options.*.label_en' => 'required|string',
            'options.*.label_ta' => 'nullable|string',
        ]);

        KoodalSyncQuestion::create([
            'category' => $request->input('category'),
            'question_text_en' => $request->input('question_text_en'),
            'question_text_ta' => $request->input('question_text_ta'),
            'options' => $request->input('options'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.games.questions.index')
            ->with('success', 'Question created successfully!');
    }

    /**
     * Form to edit question.
     */
    public function editQuestion(int $id)
    {
        $question = KoodalSyncQuestion::findOrFail($id);

        return view('admin.games.questions.form', [
            'question' => $question,
            'categories' => KoodalSyncQuestion::CATEGORIES,
            'isEdit' => true,
        ]);
    }

    /**
     * Update question.
     */
    public function updateQuestion(Request $request, int $id)
    {
        $question = KoodalSyncQuestion::findOrFail($id);

        $request->validate([
            'category' => 'required|string|max:50',
            'question_text_en' => 'required|string|max:1000',
            'question_text_ta' => 'nullable|string|max:1000',
            'options' => 'required|array|min:2',
            'options.*.key' => 'required|string',
            'options.*.label_en' => 'required|string',
            'options.*.label_ta' => 'nullable|string',
        ]);

        $question->update([
            'category' => $request->input('category'),
            'question_text_en' => $request->input('question_text_en'),
            'question_text_ta' => $request->input('question_text_ta'),
            'options' => $request->input('options'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.games.questions.index')
            ->with('success', 'Question updated successfully!');
    }

    /**
     * Delete question.
     */
    public function destroyQuestion(int $id)
    {
        $question = KoodalSyncQuestion::findOrFail($id);
        $question->delete();

        return redirect()->route('admin.games.questions.index')
            ->with('success', 'Question deleted successfully!');
    }

    /**
     * List Find It Challenges.
     */
    public function findItChallenges()
    {
        $challenges = KoodalFindItChallenge::latest('id')->paginate(15);

        return view('admin.games.find_it.index', [
            'challenges' => $challenges,
        ]);
    }

    /**
     * Form to create Find It challenge.
     */
    public function createFindIt()
    {
        return view('admin.games.find_it.form', [
            'challenge' => new KoodalFindItChallenge(),
            'isEdit' => false,
        ]);
    }

    /**
     * Store Find It challenge.
     */
    public function storeFindIt(Request $request)
    {
        $request->validate([
            'title'        => 'required|string|max:100',
            'image_a'      => 'nullable|string',
            'image_b'      => 'nullable|string',
            'image_a_file' => 'nullable|image|max:10240',
            'image_b_file' => 'nullable|image|max:10240',
            'differences'  => 'required|array|min:1',
        ]);

        $imageA = $request->input('image_a', '/image/02_Koodal_Find_It.png');
        $imageB = $request->input('image_b', '/image/02_Koodal_Find_It.png');

        $uploadDir = public_path('images/games/find_it');
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        if ($request->hasFile('image_a_file')) {
            $fileA = $request->file('image_a_file');
            $nameA = time() . '_a_' . \Illuminate\Support\Str::random(6) . '.' . $fileA->getClientOriginalExtension();
            $fileA->move($uploadDir, $nameA);
            $imageA = '/images/games/find_it/' . $nameA;
        }

        if ($request->hasFile('image_b_file')) {
            $fileB = $request->file('image_b_file');
            $nameB = time() . '_b_' . \Illuminate\Support\Str::random(6) . '.' . $fileB->getClientOriginalExtension();
            $fileB->move($uploadDir, $nameB);
            $imageB = '/images/games/find_it/' . $nameB;
        }

        KoodalFindItChallenge::create([
            'title'       => $request->input('title'),
            'image_a'     => $imageA,
            'image_b'     => $imageB,
            'differences' => array_values($request->input('differences')),
            'is_active'   => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.games.find_it.index')
            ->with('success', 'Find It Challenge saved successfully!');
    }

    /**
     * Form to edit Find It challenge.
     */
    public function editFindIt(int $id)
    {
        $challenge = KoodalFindItChallenge::findOrFail($id);

        return view('admin.games.find_it.form', [
            'challenge' => $challenge,
            'isEdit'    => true,
        ]);
    }

    /**
     * Update Find It challenge.
     */
    public function updateFindIt(Request $request, int $id)
    {
        $challenge = KoodalFindItChallenge::findOrFail($id);

        $request->validate([
            'title'        => 'required|string|max:100',
            'image_a'      => 'nullable|string',
            'image_b'      => 'nullable|string',
            'image_a_file' => 'nullable|image|max:10240',
            'image_b_file' => 'nullable|image|max:10240',
            'differences'  => 'required|array|min:1',
        ]);

        $imageA = $request->input('image_a', $challenge->image_a);
        $imageB = $request->input('image_b', $challenge->image_b);

        $uploadDir = public_path('images/games/find_it');
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        if ($request->hasFile('image_a_file')) {
            $fileA = $request->file('image_a_file');
            $nameA = time() . '_a_' . \Illuminate\Support\Str::random(6) . '.' . $fileA->getClientOriginalExtension();
            $fileA->move($uploadDir, $nameA);
            $imageA = '/images/games/find_it/' . $nameA;
        }

        if ($request->hasFile('image_b_file')) {
            $fileB = $request->file('image_b_file');
            $nameB = time() . '_b_' . \Illuminate\Support\Str::random(6) . '.' . $fileB->getClientOriginalExtension();
            $fileB->move($uploadDir, $nameB);
            $imageB = '/images/games/find_it/' . $nameB;
        }

        $challenge->update([
            'title'       => $request->input('title'),
            'image_a'     => $imageA,
            'image_b'     => $imageB,
            'differences' => array_values($request->input('differences')),
            'is_active'   => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.games.find_it.index')
            ->with('success', 'Find It Challenge updated successfully!');
    }

    /**
     * Delete Find It challenge.
     */
    public function destroyFindIt(int $id)
    {
        $challenge = KoodalFindItChallenge::findOrFail($id);
        $challenge->delete();

        return redirect()->route('admin.games.find_it.index')
            ->with('success', 'Find It Challenge deleted!');
    }

    /**
     * List all Vibe Meter Story templates.
     */
    public function vibeStories(Request $request)
    {
        \App\Services\KoodalVibeStoryService::ensureDefaultTemplatesSeeded();

        $stories = KoodalVibeStory::orderBy('sort_order', 'asc')
            ->orderBy('min_score', 'desc')
            ->get();

        return view('admin.games.vibe_stories.index', [
            'stories' => $stories,
        ]);
    }

    /**
     * Form to create a new Vibe Story template.
     */
    public function createVibeStory()
    {
        return view('admin.games.vibe_stories.form', [
            'story'  => new KoodalVibeStory([
                'min_score' => 0,
                'max_score' => 100,
                'call_to_action' => '❤️ PREMIUM CALL',
                'cta_url' => '/membership',
                'is_active' => true,
                'sort_order' => 1,
            ]),
            'isEdit' => false,
        ]);
    }

    /**
     * Store new Vibe Story template (unlimited stories per score range allowed).
     */
    public function storeVibeStory(Request $request)
    {
        $request->validate([
            'category_key'   => 'nullable|string|max:50',
            'category_name'  => 'required|string|max:150',
            'min_score'      => 'required|integer|min:0|max:100',
            'max_score'      => 'required|integer|min:0|max:100|gte:min_score',
            'title'          => 'required|string|max:255',
            'tagline'        => 'nullable|string|max:255',
            'story_content'  => 'required|string',
            'call_to_action' => 'nullable|string|max:100',
            'cta_url'        => 'nullable|string|max:255',
            'sort_order'     => 'nullable|integer',
        ]);

        $minScore = (int)$request->input('min_score');
        $maxScore = (int)$request->input('max_score');

        $rawKey = strtolower(trim($request->input('category_key') ?: \Illuminate\Support\Str::slug($request->input('category_name'), '_')));
        $rawKey = substr($rawKey ?: 'vibe_story', 0, 40);
        $categoryKey = $rawKey;
        if (KoodalVibeStory::where('category_key', $categoryKey)->exists()) {
            $categoryKey = $rawKey . '_' . rand(100, 9999);
        }

        KoodalVibeStory::create([
            'category_key'   => $categoryKey,
            'category_name'  => $request->input('category_name'),
            'min_score'      => $minScore,
            'max_score'      => $maxScore,
            'title'          => $request->input('title'),
            'tagline'        => $request->input('tagline'),
            'story_content'  => $request->input('story_content'),
            'call_to_action' => $request->input('call_to_action', '❤️ PREMIUM CALL'),
            'cta_url'        => $request->input('cta_url', '/membership'),
            'is_active'      => $request->boolean('is_active', true),
            'sort_order'     => (int)$request->input('sort_order', 0),
        ]);

        return redirect()->route('admin.games.vibe_stories.index')
            ->with('success', 'Vibe Meter Story template created successfully!');
    }

    /**
     * Form to edit an existing Vibe Story template.
     */
    public function editVibeStory(int $id)
    {
        $story = KoodalVibeStory::findOrFail($id);

        return view('admin.games.vibe_stories.form', [
            'story'  => $story,
            'isEdit' => true,
        ]);
    }

    /**
     * Update an existing Vibe Story template (unlimited stories per score range allowed).
     */
    public function updateVibeStory(Request $request, int $id)
    {
        $story = KoodalVibeStory::findOrFail($id);

        $request->validate([
            'category_key'   => 'nullable|string|max:50',
            'category_name'  => 'required|string|max:150',
            'min_score'      => 'required|integer|min:0|max:100',
            'max_score'      => 'required|integer|min:0|max:100|gte:min_score',
            'title'          => 'required|string|max:255',
            'tagline'        => 'nullable|string|max:255',
            'story_content'  => 'required|string',
            'call_to_action' => 'nullable|string|max:100',
            'cta_url'        => 'nullable|string|max:255',
            'sort_order'     => 'nullable|integer',
        ]);

        $rawKey = strtolower(trim($request->input('category_key') ?: $story->category_key));
        $rawKey = substr($rawKey ?: 'vibe_story', 0, 40);
        $categoryKey = $rawKey;
        if (KoodalVibeStory::where('id', '!=', $story->id)->where('category_key', $categoryKey)->exists()) {
            $categoryKey = $rawKey . '_' . rand(100, 9999);
        }

        $story->update([
            'category_key'   => $categoryKey,
            'category_name'  => $request->input('category_name'),
            'min_score'      => (int)$request->input('min_score'),
            'max_score'      => (int)$request->input('max_score'),
            'title'          => $request->input('title'),
            'tagline'        => $request->input('tagline'),
            'story_content'  => $request->input('story_content'),
            'call_to_action' => $request->input('call_to_action', '❤️ PREMIUM CALL'),
            'cta_url'        => $request->input('cta_url', '/membership'),
            'is_active'      => $request->boolean('is_active', true),
            'sort_order'     => (int)$request->input('sort_order', 0),
        ]);

        return redirect()->route('admin.games.vibe_stories.index')
            ->with('success', 'Vibe Meter Story template updated successfully!');
    }

    /**
     * Duplicate/Copy an existing Vibe Story template with same content and score range.
     */
    public function copyVibeStory(int $id)
    {
        $story = KoodalVibeStory::findOrFail($id);

        $baseKey = substr(preg_replace('/_copy_\d+$/', '', $story->category_key), 0, 35);
        $newKey = $baseKey . '_copy_' . rand(100, 9999);

        KoodalVibeStory::create([
            'category_key'   => $newKey,
            'category_name'  => $story->category_name,
            'min_score'      => $story->min_score,
            'max_score'      => $story->max_score,
            'title'          => $story->title,
            'tagline'        => $story->tagline,
            'story_content'  => $story->story_content,
            'call_to_action' => $story->call_to_action,
            'cta_url'        => $story->cta_url,
            'is_active'      => $story->is_active,
            'sort_order'     => $story->sort_order,
        ]);

        return redirect()->route('admin.games.vibe_stories.index')
            ->with('success', 'Vibe Story copied successfully! You can now edit or delete the copied story.');
    }

    /**
     * Delete a Vibe Story template.
     */
    public function destroyVibeStory(int $id)
    {
        $story = KoodalVibeStory::findOrFail($id);
        $story->delete();

        return redirect()->route('admin.games.vibe_stories.index')
            ->with('success', 'Vibe Meter Story template deleted!');
    }
}
