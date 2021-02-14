<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2015 Feld0.
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

use DB;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Poniverse\Ponyfm\Models\ShowSong;
use Poniverse\Ponyfm\Models\Track;
use Poniverse\Ponyfm\Models\TrackType;

class ClassifyMLPMA extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mlpma:classify
                            {--startAt=1 : Track to start importing from. Useful for resuming an interrupted import.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Adds Pony.fm-specific metadata to imported MLPMA tracks.';

    /**
     * A counter for the number of processed tracks.
     *
     * @var int
     */
    protected $currentTrack = 0;

    /**
     * Create a new command instance.
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
        // Get the list of tracks that need classification
        $tracks = DB::table('mlpma_tracks')
            ->orderBy('id')
            ->get();

        $this->comment('Importing tracks...');

        $totalTracks = count($tracks);

        $fileToStartAt = (int) $this->option('startAt') - 1;
        $this->comment("Skipping $fileToStartAt files...".PHP_EOL);

        $tracks = array_slice($tracks, $fileToStartAt);
        $this->currentTrack = $fileToStartAt;

        foreach ($tracks as $track) {
            $this->currentTrack++;
            $this->comment('['.$this->currentTrack.'/'.$totalTracks.'] Classifying track ['.$track->filename.']...');

            $parsedTags = json_decode($track->parsed_tags, true);

            //==========================================================================================================
            // Original, show song remix, fan song remix, show audio remix, or ponified song?
            //==========================================================================================================
            $sanitizedTrackTitle = $parsedTags['title'];
            $sanitizedTrackTitle = str_replace(['-', '+', '~', 'ft.', '*', '(', ')', '.'], ' ', $sanitizedTrackTitle);

            $queriedTitle = DB::connection()->getPdo()->quote($sanitizedTrackTitle);
            $officialSongs = ShowSong::select(['id', 'title'])
                ->whereRaw("
                MATCH (title)
                AGAINST ($queriedTitle IN BOOLEAN MODE)
                ")
                ->get();

            // If it has "Ingram" in the name, it's definitely an official song remix.
            if (Str::contains(Str::lower($track->filename), 'ingram')) {
                $this->info('This is an official song remix!');

                list($trackType, $linkedSongIds) = $this->classifyTrack(
                    $track->filename,
                    $officialSongs,
                    true,
                    $parsedTags
                );

            // If it has "remix" in the name, it's definitely a remix.
            } else {
                if (Str::contains(Str::lower($sanitizedTrackTitle), 'remix')) {
                    $this->info('This is some kind of remix!');

                    list($trackType, $linkedSongIds) = $this->classifyTrack(
                        $track->filename,
                        $officialSongs,
                        false,
                        $parsedTags
                    );

                // No idea what this is. Have the pony at the terminal figure it out!
                } else {
                    list($trackType, $linkedSongIds) = $this->classifyTrack(
                        $track->filename,
                        $officialSongs,
                        false,
                        $parsedTags
                    );
                }
            }

            //==========================================================================================================
            // Attach the data and publish the track!
            //==========================================================================================================

            $track = Track::find($track->track_id);

            $track->track_type_id = $trackType;
            $track->published_at = $parsedTags['released_at'];
            $track->save();

            if (count($linkedSongIds) > 0) {
                $track->showSongs()->sync($linkedSongIds);
            }

            echo PHP_EOL;
        }
    }

    /**
     * Determines what type of track the given file is. If unable to guess, the user
     * is asked to identify it interactively.
     *
     * @param string $filename
     * @param ShowSong[] $officialSongs
     * @param bool|false $isRemixOfOfficialTrack
     * @return array
     */
    protected function classifyTrack($filename, $officialSongs, $isRemixOfOfficialTrack, $tags)
    {
        $trackTypeId = null;
        $linkedSongIds = [];

        foreach ($officialSongs as $song) {
            $this->comment('=> Matched official song: ['.$song->id.'] '.$song->title);
        }

        if ($isRemixOfOfficialTrack && count($officialSongs) === 1) {
            $linkedSongIds = [$officialSongs[0]->id];
        } else {
            if ($isRemixOfOfficialTrack && count($officialSongs) > 1) {
                $this->question('Multiple official songs matched! Please enter the ID of the correct one.');
            } else {
                if (count($officialSongs) > 0) {
                    $this->question('This looks like a remix of an official song!');
                    $this->question('Press "r" if the match above is right!');
                } else {
                    $this->question('Exactly what kind of track is this?');
                }
            }
            $this->question('If this is a medley, multiple song ID\'s can be separated by commas. ');
            $this->question('                                                                    ');
            $this->question('  '.$filename.'    ');
            $this->question('                                                                    ');
            $this->question('    Title:  '.$tags['title'].'    ');
            $this->question('    Album:  '.$tags['album'].'    ');
            $this->question('    Artist: '.$tags['artist'].'    ');
            $this->question('                                                                    ');
            $this->question('    r = official song remix (accept all "guessed" matches)          ');
            $this->question('    # = official song remix (enter the ID(s) of the show song(s))   ');
            $this->question('    a = show audio remix                                            ');
            $this->question('    f = fan track remix                                             ');
            $this->question('    p = ponified track                                              ');
            $this->question('    o = original track                                              ');
            $this->question('                                                                    ');
            $input = $this->ask('[r/#/a/f/p/o]: ');

            switch ($input) {
                case 'r':
                    $trackTypeId = TrackType::OFFICIAL_TRACK_REMIX;
                    foreach ($officialSongs as $officialSong) {
                        $linkedSongIds[] = (int) $officialSong->id;
                    }
                    break;

                case 'a':
                    $trackTypeId = TrackType::OFFICIAL_AUDIO_REMIX;
                    break;

                case 'f':
                    $trackTypeId = TrackType::FAN_TRACK_REMIX;
                    break;

                case 'p':
                    $trackTypeId = TrackType::PONIFIED_TRACK;
                    break;

                case 'o':
                    $trackTypeId = TrackType::ORIGINAL_TRACK;
                    break;

                default:
                    $trackTypeId = TrackType::OFFICIAL_TRACK_REMIX;
                    $linkedSongIds = explode(',', $input);
                    $linkedSongIds = array_map(function ($item) {
                        return (int) $item;
                    }, $linkedSongIds);
            }
        }

        return [$trackTypeId, $linkedSongIds];
    }
}
