<?php
/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2017 Isaac Avram
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Image;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
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
        $this->info("Regenerating Images");
        $progressBar = $this->output->createProgressBar(Image::count());

        Image::chunk(1000, function($images) use ($progressBar) {
            foreach ($images as $image) {
                try {
                    $image->buildCovers();
                } catch (FileNotFoundException $e) {
                    $name = $image->filename;
                    $id = $image->id;

                    $this->error("Unable to process image $name (id: $id): ".$e->getMessage());
                }

                $progressBar->advance();
            }
        });
    }
}
