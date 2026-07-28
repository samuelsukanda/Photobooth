<?php

namespace App\Console\Commands;

use App\Models\Photo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;

class GenerateThumbnails extends Command
{
    protected $signature = 'photos:generate-thumbnails';
    protected $description = 'Generate thumbnails for existing photos that do not have one';

    public function handle(): int
    {
        $photos = Photo::whereNull('thumbnail')->whereNotNull('image')->get();

        if ($photos->isEmpty()) {
            $this->info('All photos already have thumbnails.');
            return self::SUCCESS;
        }

        $this->info("Generating thumbnails for {$photos->count()} photos...");
        $bar = $this->output->createProgressBar($photos->count());
        $bar->start();

        $manager = ImageManager::usingDriver(Driver::class);
        $success = 0;
        $failed = 0;

        foreach ($photos as $photo) {
            try {
                $disk = Storage::disk('public');

                if (!$disk->exists($photo->image)) {
                    $this->newLine();
                    $this->warn("  Skipped photo #{$photo->id}: file not found ({$photo->image})");
                    $failed++;
                    $bar->advance();
                    continue;
                }

                $imageData = $disk->get($photo->image);
                $thumb = $manager->decodeBinary($imageData);
                $thumb->scaleDown(width: 400);

                $extension = pathinfo($photo->image, PATHINFO_EXTENSION);
                $baseName = pathinfo($photo->image, PATHINFO_FILENAME);
                $thumbPath = 'photos/thumbs/' . $baseName . '.webp';

                $disk->put($thumbPath, $thumb->encode(new WebpEncoder(quality: 60))->toString());

                $photo->update(['thumbnail' => $thumbPath]);
                $success++;
            } catch (\Throwable $e) {
                $this->newLine();
                $this->error("  Failed photo #{$photo->id}: {$e->getMessage()}");
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Done! Success: {$success}, Failed: {$failed}");

        return self::SUCCESS;
    }
}
