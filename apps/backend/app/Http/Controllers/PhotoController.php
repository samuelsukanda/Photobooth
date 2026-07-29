<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessPhotoJob;
use App\Models\Photo;
use App\Models\Guest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;

class PhotoController extends Controller
{
    public function index()
    {
        $photos = Photo::with('guest.event')->latest()->get()->transform(function ($photo) {
            $photo->image_url = $photo->image ? '/storage/' . $photo->image : null;
            $photo->audio_url = $photo->audio ? '/storage/' . $photo->audio : null;
            $photo->thumbnail_url = $photo->thumbnail
                ? '/storage/' . $photo->thumbnail
                : $photo->image_url;
            return $photo;
        });

        return response()->json($photos);
    }

    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|string',
            'guest_token' => 'required|string|exists:guests,token',
        ]);

        $guest = Guest::where('token', $request->guest_token)->firstOrFail();

        $image_parts = explode(";base64,", $request->image);
        $image_base64 = base64_decode($image_parts[1]);

        $max_size = 15 * 1024 * 1024;
        if (strlen($image_base64) > $max_size) {
            return response()->json(['message' => 'Ukuran foto terlalu besar. Maksimal 15MB setelah encode.'], 413);
        }

        $file_name = 'photos/' . Str::uuid() . '.webp';
        $saved = false;
        try {
            $manager = ImageManager::usingDriver(Driver::class);
            $img = $manager->decodeBinary($image_base64);
            $img->scaleDown(width: 2000, height: 2000);
            Storage::disk('public')->put($file_name, $img->encode(new WebpEncoder(quality: 80))->toString());
            $saved = true;
        } catch (\Throwable $e) {
            report($e);
        }

        if (!$saved) {
            $dimensions = @getimagesizefromstring($image_base64);
            if ($dimensions && ($dimensions[0] > 4000 || $dimensions[1] > 4000)) {
                return response()->json(['message' => 'Foto terlalu besar. Maksimal 4000px.'], 413);
            }
            $image_type_aux = explode("image/", $image_parts[0]);
            $image_type = $image_type_aux[1] ?? 'webp';
            $file_name = 'photos/' . Str::uuid() . '.' . $image_type;
            Storage::disk('public')->put($file_name, $image_base64);
        }

        $photo = Photo::create([
            'guest_id' => $guest->id,
            'image' => $file_name,
        ]);

        dispatch(new ProcessPhotoJob($photo));

        if ($request->has('audio') && $request->audio) {
            $audio_parts = explode(";base64,", $request->audio);
            $audio_mime = $audio_parts[0];
            $audio_type = 'webm';
            if (preg_match('/audio\/(\w+)/', $audio_mime, $matches)) {
                $audio_type = $matches[1];
            }
            $audio_base64 = base64_decode($audio_parts[1]);
            $audio_file_name = 'audio/' . Str::uuid() . '.' . $audio_type;

            Storage::disk('public')->put($audio_file_name, $audio_base64);
            $photo->update(['audio' => $audio_file_name]);
        }

        return response()->json([
            'message' => 'Photo saved successfully',
            'photo' => $photo,
        ]);
    }
}
