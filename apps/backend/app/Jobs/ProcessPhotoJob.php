<?php

namespace App\Jobs;

use App\Models\Photo;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;

class ProcessPhotoJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(
        public Photo $photo,
    ) {
        $this->onQueue('thumbnails');
    }

    public function handle(): void
    {
        $raw = Storage::disk('public')->get($this->photo->image);
        if (!$raw) return;

        $thumb_path = null;
        try {
            $manager = ImageManager::usingDriver(Driver::class);
            $thumb = $manager->decodeBinary($raw);
            $thumb->scaleDown(width: 400);

            $baseName = pathinfo($this->photo->image, PATHINFO_FILENAME);
            $thumb_path = 'photos/thumbs/' . $baseName . '.webp';

            Storage::disk('public')->put($thumb_path, $thumb->encode(new WebpEncoder(quality: 60))->toString());

            $this->photo->update(['thumbnail' => $thumb_path]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}