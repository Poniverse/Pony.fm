<?php

namespace Poniverse\Ponyfm\Console\Commands;

use Illuminate\Console\Command;
use Poniverse\Ponyfm\Models\Image;
use Symfony\Component\HttpFoundation\File\File;

class RebuildImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rebuild:images';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resizes all images to fit the specifications in Models/Image';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $images = Image::all();
        foreach ($images as $image) {
            $this->info("Regenerating images for ".$image->filename);
            $image->clearExisting();

            $originalFile = new File($image->getFile(Image::ORIGINAL));
            foreach (Image::$ImageTypes as $imageType) {
                Image::processFile($originalFile, $image->getFile($imageType['id']), $imageType);
            }
        }
    }
}
