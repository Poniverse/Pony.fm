<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2016 Feld0
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

namespace Poniverse\Ponyfm\Console\Commands;

use Config;
use DB;
use File;
use getID3;
use Illuminate\Console\Command;
use Poniverse\Ponyfm\Models\Image;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FixMLPMAImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mlpma:fix-images
                                {--startAt=1 : Track to start importing from. Useful for resuming an interrupted import.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Re-imports MLPMA cover art';

    /**
     * Create a new command instance.
     *
     */
    public function __construct()
    {
        parent::__construct();
    }

    protected $currentFile;


    /**
     * File extensions to ignore when importing the archive.
     *
     * @var array
     */
    protected $ignoredExtensions = [
        'db',
        'jpg',
        'png',
        'txt',
        'rtf',
        'wma',
        'wmv'
    ];

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $mlpmaPath = Config::get('ponyfm.files_directory').'/mlpma';
        $tmpPath = Config::get('ponyfm.files_directory').'/tmp';

        $this->comment('Enumerating MLP Music Archive source files...');
        $files = File::allFiles($mlpmaPath);
        $this->info(sizeof($files).' files found!');

        $this->comment('Importing tracks...');
        $totalFiles = sizeof($files);
        $fileToStartAt = (int) $this->option('startAt') - 1;

        $this->comment("Skipping $fileToStartAt files...".PHP_EOL);
        $files = array_slice($files, $fileToStartAt);
        $this->currentFile = $fileToStartAt;


        foreach ($files as $file) {
            $this->currentFile++;

            $this->info('['.$this->currentFile.'/'.$totalFiles.'] Importing track ['.$file->getFilename().']...');
            if (in_array($file->getExtension(), $this->ignoredExtensions)) {
                $this->comment('This is not an audio file! Skipping...'.PHP_EOL);
                continue;
            }
            // Get this track's MLPMA record
            $importedTrack = DB::table('mlpma_tracks')
                                ->where('filename', '=', $file->getFilename())
                                ->join('tracks', 'mlpma_tracks.track_id', '=', 'tracks.id')
                                ->first();
            $artistId = $importedTrack->user_id;


            //==========================================================================================================
            // Extract the original tags.
            //==========================================================================================================

            $getId3 = new getID3;
            // all tags read by getID3, including the cover art
            $allTags = $getId3->analyze($file->getPathname());


            //==========================================================================================================
            // Extract the cover art, if any exists.
            //==========================================================================================================
            $coverId = null;

            if (array_key_exists('comments', $allTags) && array_key_exists('picture', $allTags['comments'])) {
                $this->comment('Extracting cover art!');
                $image = $allTags['comments']['picture'][0];
                if ($image['image_mime'] === 'image/png') {
                    $extension = 'png';
                } elseif ($image['image_mime'] === 'image/jpeg') {
                    $extension = 'jpg';
                } elseif ($image['image_mime'] === 'image/gif') {
                    $extension = 'gif';
                } else {
                    $this->error('Unknown cover art format!');
                }
                // write temporary image file
                $imageFilename = $file->getFilename().".cover.$extension";
                $imageFilePath = "$tmpPath/".$imageFilename;
                File::put($imageFilePath, $image['data']);
                $imageFile = new UploadedFile($imageFilePath, $imageFilename, $image['image_mime']);
                $cover = Image::upload($imageFile, $artistId, true);
                $coverId = $cover->id;
            } else {
                $this->comment('No cover art found!');
            }
        }
    }
}
