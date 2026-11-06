<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Puzzle;
use App\Models\PuzzleDifference;
use App\Services\PuzzleImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PuzzleAdminController extends Controller
{
    public function index()
    {
        $puzzles = Puzzle::withCount('differences')->latest('id')->paginate(15);
        $minDifferences = config('game.min_differences', 7);

        return view('admin.puzzles.index', [
            'puzzles' => $puzzles,
            'minDifferences' => $minDifferences,
        ]);
    }

    public function create()
    {
        return view('admin.puzzles.form', [
            'puzzle' => new Puzzle(),
            'isEdit' => false,
            'minDifferences' => config('game.min_differences', 7),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'original_image' => 'required|image|mimes:jpeg,jpg,png,webp|max:5120',
            'modified_image' => 'required|image|mimes:jpeg,jpg,png,webp|max:5120',
            'difficulty' => 'required|string|in:easy,medium,hard',
            'time_limit_seconds' => 'required|integer|min:30|max:600',
            'is_active' => 'boolean',
            'differences' => 'required|array',
            'differences.*.label' => 'required|string|max:100',
            'differences.*.x_pct' => 'required|numeric|between:0,100',
            'differences.*.y_pct' => 'required|numeric|between:0,100',
            'differences.*.radius_pct' => 'required|numeric|between:0.5,50',
            'differences.*.animation_type' => 'required|string|in:fly_away,fall,swing_open,drift,pulse,blink,fade_out,lottie',
            'differences.*.animate_on' => 'required|string|in:original,modified',
        ]);

        $origFile = $request->file('original_image');
        $modFile = $request->file('modified_image');

        [$origW, $origH] = PuzzleImageService::getImageDimensions($origFile->getPathname());
        [$modW, $modH] = PuzzleImageService::getImageDimensions($modFile->getPathname());

        if ($origW !== $modW || $origH !== $modH) {
            throw ValidationException::withMessages([
                'modified_image' => "Original ({$origW}x{$origH}) and modified ({$modW}x{$modH}) images must have identical dimensions.",
            ]);
        }

        $minDifferences = config('game.min_differences', 7);
        $differencesData = $request->input('differences', []);
        $isActive = $request->boolean('is_active', false);

        if ($isActive && count($differencesData) < $minDifferences) {
            throw ValidationException::withMessages([
                'is_active' => "Puzzle can only be activated if it has at least {$minDifferences} differences (currently " . count($differencesData) . ").",
            ]);
        }

        $origPath = $origFile->store('puzzles', 'public');
        $modPath = $modFile->store('puzzles', 'public');

        $puzzle = Puzzle::create([
            'title' => $request->input('title'),
            'original_image' => $origPath,
            'modified_image' => $modPath,
            'width' => $origW,
            'height' => $origH,
            'difficulty' => $request->input('difficulty'),
            'time_limit_seconds' => $request->input('time_limit_seconds', 180),
            'is_active' => $isActive,
        ]);

        $this->saveDifferences($puzzle, $request);

        return redirect()->route('admin.puzzles.index')
            ->with('success', 'Puzzle created successfully!');
    }

    public function edit(int $id)
    {
        $puzzle = Puzzle::with('differences')->findOrFail($id);

        return view('admin.puzzles.form', [
            'puzzle' => $puzzle,
            'isEdit' => true,
            'minDifferences' => config('game.min_differences', 7),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $puzzle = Puzzle::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'original_image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120',
            'modified_image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120',
            'difficulty' => 'required|string|in:easy,medium,hard',
            'time_limit_seconds' => 'required|integer|min:30|max:600',
            'is_active' => 'boolean',
            'differences' => 'required|array',
            'differences.*.label' => 'required|string|max:100',
            'differences.*.x_pct' => 'required|numeric|between:0,100',
            'differences.*.y_pct' => 'required|numeric|between:0,100',
            'differences.*.radius_pct' => 'required|numeric|between:0.5,50',
            'differences.*.animation_type' => 'required|string|in:fly_away,fall,swing_open,drift,pulse,blink,fade_out,lottie',
            'differences.*.animate_on' => 'required|string|in:original,modified',
        ]);

        $origPath = $puzzle->original_image;
        $modPath = $puzzle->modified_image;
        $origW = $puzzle->width;
        $origH = $puzzle->height;

        if ($request->hasFile('original_image') || $request->hasFile('modified_image')) {
            $origFile = $request->file('original_image');
            $modFile = $request->file('modified_image');

            $tempOrigPath = $origFile ? $origFile->getPathname() : Storage::disk('public')->path($puzzle->original_image);
            $tempModPath = $modFile ? $modFile->getPathname() : Storage::disk('public')->path($puzzle->modified_image);

            [$newOrigW, $newOrigH] = PuzzleImageService::getImageDimensions($tempOrigPath);
            [$newModW, $newModH] = PuzzleImageService::getImageDimensions($tempModPath);

            if ($newOrigW !== $newModW || $newOrigH !== $newModH) {
                throw ValidationException::withMessages([
                    'modified_image' => "Original ({$newOrigW}x{$newOrigH}) and modified ({$newModW}x{$newModH}) images must have identical dimensions.",
                ]);
            }

            if ($origFile) {
                $origPath = $origFile->store('puzzles', 'public');
            }
            if ($modFile) {
                $modPath = $modFile->store('puzzles', 'public');
            }

            $origW = $newOrigW;
            $origH = $newOrigH;
        }

        $minDifferences = config('game.min_differences', 7);
        $differencesData = $request->input('differences', []);
        $isActive = $request->boolean('is_active', false);

        if ($isActive && count($differencesData) < $minDifferences) {
            throw ValidationException::withMessages([
                'is_active' => "Puzzle can only be activated if it has at least {$minDifferences} differences (currently " . count($differencesData) . ").",
            ]);
        }

        $puzzle->update([
            'title' => $request->input('title'),
            'original_image' => $origPath,
            'modified_image' => $modPath,
            'width' => $origW,
            'height' => $origH,
            'difficulty' => $request->input('difficulty'),
            'time_limit_seconds' => $request->input('time_limit_seconds', 180),
            'is_active' => $isActive,
        ]);

        $this->saveDifferences($puzzle, $request);

        return redirect()->route('admin.puzzles.index')
            ->with('success', 'Puzzle updated successfully!');
    }

    public function destroy(int $id)
    {
        $puzzle = Puzzle::findOrFail($id);
        $puzzle->delete();

        return redirect()->route('admin.puzzles.index')
            ->with('success', 'Puzzle deleted successfully!');
    }

    public function compareImages(Request $request)
    {
        $request->validate([
            'original_image' => 'nullable|file',
            'modified_image' => 'nullable|file',
            'puzzle_id' => 'nullable|integer',
        ]);

        $origPath = null;
        $modPath = null;

        if ($request->hasFile('original_image') && $request->hasFile('modified_image')) {
            $origPath = $request->file('original_image')->getPathname();
            $modPath = $request->file('modified_image')->getPathname();
        } elseif ($request->filled('puzzle_id')) {
            $puzzle = Puzzle::find($request->input('puzzle_id'));
            if ($puzzle) {
                $origPath = Storage::disk('public')->path($puzzle->original_image);
                $modPath = Storage::disk('public')->path($puzzle->modified_image);
            }
        }

        if (!$origPath || !$modPath || !file_exists($origPath) || !file_exists($modPath)) {
            return response()->json(['error' => 'Images not found or not provided'], 422);
        }

        $suggestions = PuzzleImageService::suggestHotspots($origPath, $modPath);

        return response()->json(['suggestions' => $suggestions]);
    }

    private function saveDifferences(Puzzle $puzzle, Request $request): void
    {
        $submittedDiffs = $request->input('differences', []);
        $keepIds = [];

        foreach ($submittedDiffs as $index => $diffData) {
            $spritePath = null;
            $lottiePath = null;

            if (isset($diffData['id']) && $diffData['id']) {
                $existing = PuzzleDifference::find($diffData['id']);
                if ($existing) {
                    $spritePath = $existing->sprite_path;
                    $lottiePath = $existing->lottie_path;
                }
            }

            if ($request->hasFile("differences.{$index}.sprite")) {
                $spritePath = $request->file("differences.{$index}.sprite")->store('puzzles/sprites', 'public');
            }
            if ($request->hasFile("differences.{$index}.lottie")) {
                $lottiePath = $request->file("differences.{$index}.lottie")->store('puzzles/lottie', 'public');
            }

            $diff = PuzzleDifference::updateOrCreate(
                [
                    'id' => $diffData['id'] ?? null,
                    'puzzle_id' => $puzzle->id,
                ],
                [
                    'label' => $diffData['label'],
                    'x_pct' => $diffData['x_pct'],
                    'y_pct' => $diffData['y_pct'],
                    'radius_pct' => $diffData['radius_pct'],
                    'animation_type' => $diffData['animation_type'],
                    'animate_on' => $diffData['animate_on'],
                    'sprite_path' => $spritePath,
                    'sprite_x_pct' => isset($diffData['sprite_x_pct']) && $diffData['sprite_x_pct'] !== '' ? $diffData['sprite_x_pct'] : null,
                    'sprite_y_pct' => isset($diffData['sprite_y_pct']) && $diffData['sprite_y_pct'] !== '' ? $diffData['sprite_y_pct'] : null,
                    'sprite_w_pct' => isset($diffData['sprite_w_pct']) && $diffData['sprite_w_pct'] !== '' ? $diffData['sprite_w_pct'] : null,
                    'sprite_h_pct' => isset($diffData['sprite_h_pct']) && $diffData['sprite_h_pct'] !== '' ? $diffData['sprite_h_pct'] : null,
                    'lottie_path' => $lottiePath,
                    'sort_order' => $index + 1,
                ]
            );

            $keepIds[] = $diff->id;
        }

        $puzzle->differences()->whereNotIn('id', $keepIds)->delete();
    }
}
