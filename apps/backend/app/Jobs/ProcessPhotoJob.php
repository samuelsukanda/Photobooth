<?php

namespace App\Jobs;

use App\Models\Photo;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;

class ProcessPhotoJob
{

    public function __construct(
        public Photo $photo,
        public string $imageBase64,
    ) {}

    public function handle(): void
    {
        $decoded = base64_decode(explode(',', $this->imageBase64)[1] ?? $this->imageBase64);

        $thumb_path = null;
        try {
            $manager = ImageManager::usingDriver(Driver::class);
            $thumb = $manager->decodeBinary($decoded);
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
