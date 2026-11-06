<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadFlipImageRequest;
use App\Models\FlipSetting;
use App\Models\FlipTileImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FlipTileImageController extends Controller
{
    public function index()
    {
        $images = FlipTileImage::latest()->paginate(15);
        $activeCount = FlipTileImage::where('is_active', true)->count();
        $setting = FlipSetting::instance();

        return view('admin.flip.index', [
            'images' => $images,
            'activeCount' => $activeCount,
            'setting' => $setting,
        ]);
    }

    public function upload(UploadFlipImageRequest $request)
    {
        $files = $request->file('images');
        $uploadedCount = 0;

        foreach ($files as $file) {
            $path = $this->storeAndResizeImage($file, 'flip-tiles', 300, 300);
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $cleanName = ucfirst(str_replace(['_', '-'], ' ', $originalName));

            FlipTileImage::create([
                'name' => $cleanName,
                'path' => $path,
                'is_active' => true,
            ]);

            $uploadedCount++;
        }

        return redirect()->route('admin.flip-tiles.index')
            ->with('success', "Successfully uploaded {$uploadedCount} image(s).");
    }

    public function toggle(FlipTileImage $image)
    {
        $image->is_active = !$image->is_active;
        $image->save();

        $statusStr = $image->is_active ? 'activated' : 'deactivated';

        return redirect()->route('admin.flip-tiles.index')
            ->with('success', "Image '{$image->name}' has been {$statusStr}.");
    }

    public function destroy(FlipTileImage $image)
    {
        if ($image->path && Storage::disk('public')->exists($image->path)) {
            Storage::disk('public')->delete($image->path);
        }

        $name = $image->name;
        $image->delete();

        return redirect()->route('admin.flip-tiles.index')
            ->with('success', "Image '{$name}' deleted.");
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'card_back' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'turn_timeout_seconds' => ['required', 'integer', 'min:10', 'max:300'],
            'preview_seconds' => ['required', 'integer', 'min:1', 'max:10'],
            'mismatch_show_seconds' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        $setting = FlipSetting::instance();

        if ($request->hasFile('card_back')) {
            if ($setting->card_back_path && Storage::disk('public')->exists($setting->card_back_path)) {
                Storage::disk('public')->delete($setting->card_back_path);
            }
            $setting->card_back_path = $this->storeAndResizeImage($request->file('card_back'), 'flip-tiles/settings', 300, 300);
        }

        $setting->turn_timeout_seconds = (int) $request->input('turn_timeout_seconds');
        $setting->preview_seconds = (int) $request->input('preview_seconds');
        $setting->mismatch_show_seconds = (int) $request->input('mismatch_show_seconds');
        $setting->save();

        return redirect()->route('admin.flip-tiles.index')
            ->with('success', 'Game settings updated successfully.');
    }

    protected function storeAndResizeImage($file, string $folder = 'flip-tiles', int $maxWidth = 300, int $maxHeight = 300): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $filename = Str::random(40) . '.' . $extension;
        $path = $folder . '/' . $filename;

        if (extension_loaded('gd') && in_array($extension, ['jpg', 'jpeg', 'png', 'webp'])) {
            try {
                $srcPath = $file->getRealPath();
                $info = getimagesize($srcPath);
                if ($info) {
                    [$width, $height, $type] = $info;
                    $ratio = min($maxWidth / $width, $maxHeight / $height);

                    if ($ratio < 1.0) {
                        $newWidth = (int) round($width * $ratio);
                        $newHeight = (int) round($height * $ratio);

                        $srcImg = match ($type) {
                            IMAGETYPE_JPEG => imagecreatefromjpeg($srcPath),
                            IMAGETYPE_PNG => imagecreatefrompng($srcPath),
                            IMAGETYPE_WEBP => imagecreatefromwebp($srcPath),
                            default => null,
                        };

                        if ($srcImg) {
                            $dstImg = imagecreatetruecolor($newWidth, $newHeight);
                            if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_WEBP) {
                                imagealphablending($dstImg, false);
                                imagesavealpha($dstImg, true);
                            }
                            imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

                            $storagePath = storage_path('app/public/' . $folder);
                            if (!file_exists($storagePath)) {
                                mkdir($storagePath, 0755, true);
                            }
                            $fullTarget = $storagePath . '/' . $filename;

                            match ($type) {
                                IMAGETYPE_JPEG => imagejpeg($dstImg, $fullTarget, 90),
                                IMAGETYPE_PNG => imagepng($dstImg, $fullTarget, 8),
                                IMAGETYPE_WEBP => imagewebp($dstImg, $fullTarget, 90),
                                default => false,
                            };

                            imagedestroy($srcImg);
                            imagedestroy($dstImg);

                            return $path;
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Fall back to direct store
            }
        }

        return $file->storeAs($folder, $filename, 'public');
    }
}
