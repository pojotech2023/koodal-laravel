<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\MemorySnapChallenge;
use App\Models\MemorySnapQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MemorySnapAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = MemorySnapChallenge::withCount('questions');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('title', 'like', "%{$search}%");
        }

        $challenges = $query->latest('id')->paginate(15)->withQueryString();
        $totalCount = MemorySnapChallenge::count();

        return view('admin.games.memory_snap.index', [
            'challenges' => $challenges,
            'totalCount' => $totalCount,
        ]);
    }

    public function create()
    {
        $initialQuestions = [
            [
                'id' => 1,
                'image_url' => '',
                'question_text' => '',
                'option_a' => '',
                'option_b' => '',
                'option_c' => '',
                'option_d' => '',
                'correct_option' => 'A'
            ]
        ];

        return view('admin.games.memory_snap.form', [
            'challenge' => new MemorySnapChallenge(),
            'initialQuestions' => $initialQuestions,
            'isEdit' => false,
        ]);
    }

    public function edit($id)
    {
        $challenge = MemorySnapChallenge::with('questions')->findOrFail($id);

        $initialQuestions = $challenge->questions->map(function ($q) use ($challenge) {
            return [
                'id' => $q->id,
                'image_url' => $q->image_url ?: $challenge->image_url,
                'question_text' => $q->question_text,
                'option_a' => $q->option_a,
                'option_b' => $q->option_b,
                'option_c' => $q->option_c,
                'option_d' => $q->option_d,
                'correct_option' => $q->correct_option,
            ];
        })->toArray();

        if (empty($initialQuestions)) {
            $initialQuestions = [
                [
                    'id' => 1,
                    'image_url' => $challenge->image_url ?? '',
                    'question_text' => '',
                    'option_a' => '',
                    'option_b' => '',
                    'option_c' => '',
                    'option_d' => '',
                    'correct_option' => 'A'
                ]
            ];
        }

        return view('admin.games.memory_snap.form', [
            'challenge' => $challenge,
            'initialQuestions' => $initialQuestions,
            'isEdit' => true,
        ]);
    }

    public function show($id)
    {
        $challenge = MemorySnapChallenge::with('questions')->findOrFail($id);
        return response()->json($challenge);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'image_file' => 'nullable|file|mimes:jpg,jpeg,png,webp,gif,bmp,svg,avif|max:20480',
            'image_url' => 'nullable|string',
            'questions' => 'required|array|min:1',
            'questions.*.question_text' => 'required|string|max:1000',
            'questions.*.image_file' => 'nullable|file|mimes:jpg,jpeg,png,webp,gif,bmp,svg,avif|max:20480',
            'questions.*.image_url' => 'nullable|string',
            'questions.*.option_a' => 'required|string|max:255',
            'questions.*.option_b' => 'required|string|max:255',
            'questions.*.option_c' => 'required|string|max:255',
            'questions.*.option_d' => 'required|string|max:255',
            'questions.*.correct_option' => 'required|in:A,B,C,D',
        ]);

        $uploadDir = public_path('images/games/memory_snap');
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $mainImageUrl = $request->input('image_url');
        if ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            $ext = $file->getClientOriginalExtension() ?: 'png';
            $filename = time() . '_' . Str::random(6) . '.' . $ext;
            $file->move($uploadDir, $filename);
            $mainImageUrl = '/images/games/memory_snap/' . $filename;
        }

        $processedQuestions = [];
        $firstQuestionImage = null;
        $uploadedFiles = $request->file('questions', []);

        foreach ($request->input('questions') as $idx => $qData) {
            $qImageUrl = !empty($qData['image_url']) ? $qData['image_url'] : null;

            $file = $request->file("questions.{$idx}.image_file") ?? ($uploadedFiles[$idx]['image_file'] ?? null);
            if ($file && $file->isValid()) {
                $ext = $file->getClientOriginalExtension() ?: 'png';
                $filename = time() . '_' . $idx . '_' . Str::random(6) . '.' . $ext;
                $file->move($uploadDir, $filename);
                $qImageUrl = '/images/games/memory_snap/' . $filename;
            }

            if (empty($qImageUrl)) {
                $qImageUrl = $mainImageUrl ?: '/image/03_Koodal_Memory.png';
            }

            if (!$firstQuestionImage) {
                $firstQuestionImage = $qImageUrl;
            }

            $processedQuestions[] = [
                'image_url' => $qImageUrl,
                'question_text' => $qData['question_text'],
                'option_a' => $qData['option_a'],
                'option_b' => $qData['option_b'],
                'option_c' => $qData['option_c'],
                'option_d' => $qData['option_d'],
                'correct_option' => $qData['correct_option'],
                'sort_order' => $idx + 1,
            ];
        }

        if (empty($mainImageUrl)) {
            $mainImageUrl = $firstQuestionImage ?: '/image/03_Koodal_Memory.png';
        }

        $challenge = MemorySnapChallenge::create([
            'title' => $request->input('title'),
            'image_url' => $mainImageUrl,
            'is_active' => true,
        ]);

        foreach ($processedQuestions as $q) {
            $q['challenge_id'] = $challenge->id;
            MemorySnapQuestion::create($q);
        }

        return redirect()->route('admin.games.memory_snap.index')->with('success', 'Memory Challenge created successfully with multi-image questions!');
    }

    public function update(Request $request, $id)
    {
        $challenge = MemorySnapChallenge::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'image_file' => 'nullable|file|mimes:jpg,jpeg,png,webp,gif,bmp,svg,avif|max:20480',
            'image_url' => 'nullable|string',
            'questions' => 'required|array|min:1',
            'questions.*.question_text' => 'required|string|max:1000',
            'questions.*.image_file' => 'nullable|file|mimes:jpg,jpeg,png,webp,gif,bmp,svg,avif|max:20480',
            'questions.*.image_url' => 'nullable|string',
            'questions.*.option_a' => 'required|string|max:255',
            'questions.*.option_b' => 'required|string|max:255',
            'questions.*.option_c' => 'required|string|max:255',
            'questions.*.option_d' => 'required|string|max:255',
            'questions.*.correct_option' => 'required|in:A,B,C,D',
        ]);

        $uploadDir = public_path('images/games/memory_snap');
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $topLevelImageUploaded = false;
        $mainImageUrl = $challenge->image_url;
        if ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            $ext = $file->getClientOriginalExtension() ?: 'png';
            $filename = time() . '_' . Str::random(6) . '.' . $ext;
            $file->move($uploadDir, $filename);
            $mainImageUrl = '/images/games/memory_snap/' . $filename;
            $topLevelImageUploaded = true;
        } elseif ($request->filled('image_url')) {
            $mainImageUrl = $request->input('image_url');
            $topLevelImageUploaded = true;
        }

        $processedQuestions = [];
        $firstQuestionImage = null;
        $uploadedFiles = $request->file('questions', []);

        foreach ($request->input('questions') as $idx => $qData) {
            $qImageUrl = !empty($qData['image_url']) ? $qData['image_url'] : null;

            $file = $request->file("questions.{$idx}.image_file") ?? ($uploadedFiles[$idx]['image_file'] ?? null);
            if ($file && $file->isValid()) {
                $ext = $file->getClientOriginalExtension() ?: 'png';
                $filename = time() . '_' . $idx . '_' . Str::random(6) . '.' . $ext;
                $file->move($uploadDir, $filename);
                $qImageUrl = '/images/games/memory_snap/' . $filename;
            }

            if (empty($qImageUrl)) {
                $qImageUrl = $mainImageUrl ?: '/image/03_Koodal_Memory.png';
            }

            if (!$firstQuestionImage) {
                $firstQuestionImage = $qImageUrl;
            }

            $processedQuestions[] = [
                'challenge_id' => $challenge->id,
                'image_url' => $qImageUrl,
                'question_text' => $qData['question_text'],
                'option_a' => $qData['option_a'],
                'option_b' => $qData['option_b'],
                'option_c' => $qData['option_c'],
                'option_d' => $qData['option_d'],
                'correct_option' => $qData['correct_option'],
                'sort_order' => $idx + 1,
            ];
        }

        if (!$topLevelImageUploaded && !empty($firstQuestionImage)) {
            $mainImageUrl = $firstQuestionImage;
        } elseif (empty($mainImageUrl)) {
            $mainImageUrl = $firstQuestionImage ?: '/image/03_Koodal_Memory.png';
        }

        $challenge->update([
            'title' => $request->input('title'),
            'image_url' => $mainImageUrl,
            'is_active' => true,
        ]);

        // Replace questions
        $challenge->questions()->delete();

        foreach ($processedQuestions as $q) {
            MemorySnapQuestion::create($q);
        }

        return redirect()->route('admin.games.memory_snap.index')->with('success', 'Memory Challenge updated successfully with multi-image questions!');
    }

    public function destroy($id)
    {
        $challenge = MemorySnapChallenge::findOrFail($id);
        $challenge->delete();

        return redirect()->route('admin.games.memory_snap.index')->with('success', 'Memory Challenge deleted successfully!');
    }
}
