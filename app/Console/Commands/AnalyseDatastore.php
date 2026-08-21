<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2026 Poniverse.
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

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Image;
use App\Models\Track;
use App\Models\TrackFile;
use Helpers;
use Illuminate\Console\Command;

class AnalyseDatastore extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'datastore:analyse
                            {--report= : Write a per-file CSV inventory to this path.}
                            {--examples=10 : How many example paths to print per problem category.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyses every file in the datastore against the database: identifies the uploaded masters, classifies each file as needed or not, and reports files that are missing, orphaned, or safe to delete. Read-only; never modifies anything.';

    /**
     * Every classification a file on disk can receive, with the verdict on
     * whether it's needed and an explanation of why.
     *
     * Verdicts: "keep" (needed to serve the site), "delete" (safe to remove),
     * "review" (needs a human decision — often the only remaining copy of an
     * upload).
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const array CATEGORIES = [
        'master'               => ['keep',   'Master file for a live track at its current version — the canonical copy of the upload.'],
        'master-old'           => ['review', 'Master file for a superseded version. The only remaining copy of that version\'s upload.'],
        'transcode'            => ['keep',   'Pre-generated, non-cacheable format (FLAC/MP3/Opus) at the current version.'],
        'cache-active'         => ['keep',   'On-demand cacheable format (OGG/AAC/ALAC) that hasn\'t expired yet.'],
        'cache-expired'        => ['delete', 'Expired cacheable format. `track-cache:clear` removes these; they regenerate on request.'],
        'transcode-old'        => ['delete', 'Non-master file for a superseded version. Regenerable from the version\'s master.'],
        'best-remaining-copy'  => ['review', 'Would be deletable, but its track/version has no master on disk — this may be the best remaining copy. Archive imports (MLPMA, EQBeats) didn\'t always get masters.'],
        'deleted-track-master' => ['review', 'Master file for a soft-deleted track. Deleting it makes the track unrestorable.'],
        'deleted-track-file'   => ['delete', 'Non-master file for a soft-deleted track. Regenerable from the master if restored.'],
        'track-unversioned'    => ['review', 'Pre-versioning filename (`{id}.{ext}`). Redundant if the versioned file exists; run `version-files` otherwise.'],
        'album-zip'            => ['delete', 'Legacy cached album zip. Downloads are now streamed, so these are never read.'],
        'track-orphan'         => ['review', 'In a tracks directory but matches no track/TrackFile record.'],
        'image'                => ['keep',   'Image file matching an Image record.'],
        'image-orphan'         => ['review', 'In an images directory but matches no Image record (or its extension differs).'],
        'queued-original'      => ['review', 'Leftover raw upload in queued-tracks with NO master on disk for that version — possibly the only copy of the upload (failed/incomplete encode).'],
        'queued-superseded'    => ['delete', 'Leftover raw upload in queued-tracks whose master was successfully generated. Normally deleted after encoding.'],
        'queued-orphan'        => ['review', 'File in queued-tracks that matches no track in the database.'],
        'tmp'                  => ['delete', 'Scratch file in the tmp directory.'],
        'unrecognised'         => ['review', 'Filename doesn\'t match any known naming scheme.'],
        'other'                => ['review', 'Top-level directory or file the application doesn\'t manage.'],
    ];

    /** @var array<int, Track> keyed by track ID; includes soft-deleted tracks */
    private array $tracks = [];

    /** @var array<string, TrackFile> keyed by "trackId|version|extension" */
    private array $trackFiles = [];

    /** @var array<string, string> format name => extension, e.g. "ALAC" => "alac.m4a" */
    private array $extensions = [];

    /** @var array<string, array{count: int, bytes: int}> keyed by category */
    private array $stats = [];

    /** @var array<string, string[]> category => example paths */
    private array $examples = [];

    /** @var array<string, true> "trackId|version|extension" for every track file found on disk */
    private array $foundTrackFiles = [];

    /** Image files that Image records expect but which aren't on disk. */
    private int $missingImages = 0;

    /** @var array<string, bool> "trackId|version" => memoized "does a master file exist on disk" checks */
    private array $masterOnDiskMemo = [];

    /** @var array<string, array{count: int, bytes: int}> keyed by format name, for current-version masters of live tracks */
    private array $masterFormats = [];

    /** CSV report being written, if --report was given. */
    private ?\SplFileObject $report = null;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() : int
    {
        $root = (string) config('ponyfm.files_directory');

        if (! is_dir($root)) {
            $this->error("Datastore directory does not exist: $root");

            return 1;
        }

        if ($this->option('report')) {
            try {
                $this->report = new \SplFileObject((string) $this->option('report'), 'w');
            } catch (\RuntimeException $e) {
                $this->error('Unable to open report file for writing: '.$e->getMessage());

                return 1;
            }
            $this->report->fputcsv(['path', 'category', 'verdict', 'bytes', 'notes']);
        }

        $this->info("Analysing datastore at: $root");
        $this->loadDatabase();
        $this->line(sprintf(
            'Loaded %s tracks (%s deleted), %s track files, %s images from the database.',
            $this->formatCount(count($this->tracks)),
            $this->formatCount(count(array_filter($this->tracks, fn ($t) => $t->deleted_at !== null))),
            $this->formatCount(count($this->trackFiles)),
            $this->formatCount(Image::count())
        ));
        $this->newLine();

        $this->scanTracksDirectory($root.'/tracks');
        $this->scanImagesDirectory($root.'/images');
        $this->scanQueuedTracksDirectory($root.'/queued-tracks');
        $this->scanFlatDirectory($root.'/tmp', 'tmp');
        $this->scanUnknownTopLevelEntries($root);

        $this->printSummary();
        $this->printMasterSummary();
        $this->printMissingFiles();
        $this->printExamples();

        if ($this->report !== null) {
            $this->report = null;
            $this->info('Full inventory written to: '.(string) $this->option('report'));
        }

        return 0;
    }

    /**
     * Loads tracks and track files into in-memory indexes so classification
     * never has to hit the database per file.
     */
    private function loadDatabase() : void
    {
        foreach (Track::$Formats as $name => $format) {
            $this->extensions[$name] = $format['extension'];
        }

        foreach (Track::withTrashed()->get(['id', 'current_version', 'deleted_at']) as $track) {
            $this->tracks[$track->id] = $track;
        }

        foreach (TrackFile::query()->get(['track_id', 'version', 'format', 'is_master', 'is_cacheable', 'expires_at']) as $trackFile) {
            if (! isset($this->extensions[$trackFile->format])) {
                // A format that's been removed from Track::$Formats; its files
                // will show up as orphans, which is accurate enough.
                continue;
            }

            $key = $trackFile->track_id.'|'.$trackFile->version.'|'.$this->extensions[$trackFile->format];
            $this->trackFiles[$key] = $trackFile;
        }
    }

    /**
     * Scans tracks/<bucket>/ directories, classifying every audio file, album
     * zip, and stray against the TrackFile index.
     */
    private function scanTracksDirectory(string $path) : void
    {
        foreach ($this->filesIn($path) as $file) {
            [$category, $notes] = $this->classifyTrackFile($file->getFilename(), $file->getSize());
            $this->record($category, $file->getPathname(), $file->getSize(), $notes);
        }
    }

    /**
     * @return array{0: string, 1: string} [category, notes]
     */
    private function classifyTrackFile(string $filename, int $bytes) : array
    {
        // Album zips must be checked before the unversioned pattern, which
        // would otherwise swallow them ("2.mp3.zip" parses as id 2, ext "mp3.zip").
        if (preg_match('/^(\d+)\.(.+)\.zip$/', $filename)) {
            return ['album-zip', ''];
        }

        if (preg_match('/^(\d+)-v(\d+)\.(.+)$/', $filename, $matches)) {
            $trackId = (int) $matches[1];
            $version = (int) $matches[2];
            $extension = $matches[3];

            $track = $this->tracks[$trackId] ?? null;
            if ($track === null) {
                return ['track-orphan', "no track with ID $trackId"];
            }

            $key = "$trackId|$version|$extension";
            $this->foundTrackFiles[$key] = true;

            $trackFile = $this->trackFiles[$key] ?? null;
            if ($trackFile === null) {
                return ['track-orphan', "track $trackId exists but has no TrackFile record for v$version .$extension"];
            }

            [$category, $notes] = $this->classifyKnownTrackFile($track, $trackFile, $version, $bytes);

            // A deletable file whose track/version has no master on disk may be
            // the best remaining copy of that audio — some archive imports
            // (MLPMA, EQBeats) never had a master to begin with. Never suggest
            // deleting those.
            if (self::CATEGORIES[$category][0] === 'delete' && ! $this->masterOnDisk($trackId, $version)) {
                $notes = ($notes !== '' ? $notes.'; ' : '')."no master on disk for track $trackId v$version";

                return ['best-remaining-copy', $notes];
            }

            return [$category, $notes];
        }

        if (preg_match('/^(\d+)\.(.+)$/', $filename, $matches)) {
            $trackId = (int) $matches[1];
            $track = $this->tracks[$trackId] ?? null;
            $notes = $track === null
                ? "no track with ID $trackId"
                : "current version is v{$track->current_version}";

            return ['track-unversioned', $notes];
        }

        return ['unrecognised', ''];
    }

    /**
     * Classifies a versioned track file that matched both a track and a
     * TrackFile record, before the no-master-on-disk escalation is applied.
     *
     * @return array{0: string, 1: string} [category, notes]
     */
    private function classifyKnownTrackFile(Track $track, TrackFile $trackFile, int $version, int $bytes) : array
    {
        if ($track->deleted_at !== null) {
            return [
                $trackFile->is_master ? 'deleted-track-master' : 'deleted-track-file',
                "track {$track->id} was deleted {$track->deleted_at}",
            ];
        }

        $isCurrent = $version === (int) $track->current_version;

        if ($trackFile->is_master) {
            if (! $isCurrent) {
                return ['master-old', "current version is v{$track->current_version}"];
            }

            if (! isset($this->masterFormats[$trackFile->format])) {
                $this->masterFormats[$trackFile->format] = ['count' => 0, 'bytes' => 0];
            }
            $this->masterFormats[$trackFile->format]['count']++;
            $this->masterFormats[$trackFile->format]['bytes'] += $bytes;

            return ['master', ''];
        }

        if (! $isCurrent) {
            return ['transcode-old', "current version is v{$track->current_version}"];
        }

        if ($trackFile->is_cacheable) {
            $isActive = $trackFile->expires_at !== null && $trackFile->expires_at->isFuture();

            return $isActive
                ? ['cache-active', 'expires '.$trackFile->expires_at]
                : ['cache-expired', ''];
        }

        return ['transcode', ''];
    }

    /**
     * Finds the master TrackFile record for a given track and version, if one
     * exists. Lossy uploads have lossy masters (MP3/AAC/OGG), and some archive
     * imports have no master record at all.
     */
    private function masterRecordFor(int $trackId, int $version) : ?TrackFile
    {
        foreach ($this->extensions as $extension) {
            $trackFile = $this->trackFiles["$trackId|$version|$extension"] ?? null;
            if ($trackFile !== null && $trackFile->is_master) {
                return $trackFile;
            }
        }

        return null;
    }

    private function masterOnDisk(int $trackId, int $version) : bool
    {
        $key = "$trackId|$version";

        if (! isset($this->masterOnDiskMemo[$key])) {
            $master = $this->masterRecordFor($trackId, $version);
            $this->masterOnDiskMemo[$key] = $master !== null && file_exists($master->getFile());
        }

        return $this->masterOnDiskMemo[$key];
    }

    /**
     * Scans images/<bucket>/ directories against the Image table, then reports
     * expected image files that are missing from disk.
     */
    private function scanImagesDirectory(string $path) : void
    {
        $images = [];
        foreach (Image::query()->get(['id', 'extension']) as $image) {
            $images[$image->id] = $image->extension;
        }

        $typeNames = array_column(Image::$ImageTypes, 'name');
        $typePattern = implode('|', $typeNames);
        $found = [];

        foreach ($this->filesIn($path) as $file) {
            $filename = $file->getFilename();

            if (! preg_match('/^(\d+)_('.$typePattern.')\.(.+)$/', $filename, $matches)) {
                $this->record('unrecognised', $file->getPathname(), $file->getSize(), 'not an {id}_{type}.{ext} image filename');
                continue;
            }

            $imageId = (int) $matches[1];
            $type = $matches[2];
            $extension = $matches[3];

            if (! isset($images[$imageId])) {
                $this->record('image-orphan', $file->getPathname(), $file->getSize(), "no image with ID $imageId");
            } elseif ($images[$imageId] !== $extension) {
                $this->record('image-orphan', $file->getPathname(), $file->getSize(), "image $imageId's extension is .{$images[$imageId]}, not .$extension");
            } else {
                $found["{$imageId}_{$type}"] = true;
                $this->record('image', $file->getPathname(), $file->getSize(), '');
            }
        }

        // Missing image files are worth knowing about, but a missing thumbnail
        // is recoverable (rebuild:images) so this is informational only.
        $missing = 0;
        foreach ($images as $imageId => $extension) {
            foreach ($typeNames as $type) {
                if (! isset($found["{$imageId}_{$type}"])) {
                    $missing++;
                    $this->example('missing-image', "images: {$imageId}_{$type}.{$extension}");
                }
            }
        }
        $this->missingImages = $missing;
    }

    /**
     * Scans queued-tracks/, where raw uploads ("{trackId}v{version}") live
     * between upload and encoding. EncodeTrackFile deletes them on success, so
     * anything here is either mid-encode or left over from a failure — and a
     * leftover with no master on disk may be the only copy of that upload.
     */
    private function scanQueuedTracksDirectory(string $path) : void
    {
        foreach ($this->filesIn($path) as $file) {
            $filename = $file->getFilename();

            if (! preg_match('/^(\d+)v(\d+)$/', $filename, $matches)) {
                $this->record('unrecognised', $file->getPathname(), $file->getSize(), 'not a {trackId}v{version} queued upload');
                continue;
            }

            $trackId = (int) $matches[1];
            $version = (int) $matches[2];

            $track = $this->tracks[$trackId] ?? null;
            if ($track === null) {
                $this->record('queued-orphan', $file->getPathname(), $file->getSize(), "no track with ID $trackId");
                continue;
            }

            if ($this->masterOnDisk($trackId, $version)) {
                $this->record('queued-superseded', $file->getPathname(), $file->getSize(), "master for track $trackId v$version exists");
            } else {
                $this->record('queued-original', $file->getPathname(), $file->getSize(), "no master on disk for track $trackId v$version");
            }
        }
    }

    /**
     * Records every file under the given directory with a single category.
     */
    private function scanFlatDirectory(string $path, string $category) : void
    {
        foreach ($this->filesIn($path) as $file) {
            $this->record($category, $file->getPathname(), $file->getSize(), '');
        }
    }

    /**
     * Anything at the top level of the datastore besides the four managed
     * directories (e.g. an old mlpma/ import dump) gets sized up and flagged.
     */
    private function scanUnknownTopLevelEntries(string $root) : void
    {
        $managed = ['tracks', 'images', 'queued-tracks', 'tmp'];

        foreach (new \FilesystemIterator($root, \FilesystemIterator::SKIP_DOTS) as $entry) {
            if (in_array($entry->getFilename(), $managed)) {
                continue;
            }

            if ($entry->isDir()) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($entry->getPathname(), \FilesystemIterator::SKIP_DOTS)
                );
                foreach ($iterator as $file) {
                    if ($file->isFile()) {
                        $this->record('other', $file->getPathname(), $file->getSize(), 'unmanaged top-level directory: '.$entry->getFilename());
                    }
                }
            } else {
                $this->record('other', $entry->getPathname(), $entry->getSize(), 'unmanaged top-level file');
            }
        }
    }

    /**
     * Yields every regular file directly inside the given directory, or inside
     * its immediate subdirectories (the numbered buckets).
     *
     * @return \Generator|\SplFileInfo[]
     */
    private function filesIn(string $path) : \Generator
    {
        if (! is_dir($path)) {
            return;
        }

        foreach (new \FilesystemIterator($path, \FilesystemIterator::SKIP_DOTS) as $entry) {
            if ($entry->isFile()) {
                yield $entry;
            } elseif ($entry->isDir()) {
                foreach (new \FilesystemIterator($entry->getPathname(), \FilesystemIterator::SKIP_DOTS) as $file) {
                    if ($file->isFile()) {
                        yield $file;
                    }
                }
            }
        }
    }

    /**
     * Tallies a classified file into the stats, examples, and CSV report.
     */
    private function record(string $category, string $path, int $bytes, string $notes) : void
    {
        if (! isset($this->stats[$category])) {
            $this->stats[$category] = ['count' => 0, 'bytes' => 0];
        }
        $this->stats[$category]['count']++;
        $this->stats[$category]['bytes'] += $bytes;

        [$verdict] = self::CATEGORIES[$category];
        if ($verdict !== 'keep') {
            $this->example($category, $path.($notes !== '' ? " ($notes)" : ''));
        }

        if ($this->report !== null) {
            $this->report->fputcsv([$path, $category, $verdict, $bytes, $notes]);
        }
    }

    private function formatCount(int $count) : string
    {
        return number_format((float) $count);
    }

    private function example(string $category, string $text) : void
    {
        if (count($this->examples[$category] ?? []) < (int) $this->option('examples')) {
            $this->examples[$category][] = $text;
        }
    }

    private function printSummary() : void
    {
        $rows = [];
        $totals = ['keep' => 0, 'delete' => 0, 'review' => 0];
        $totalFiles = 0;
        $totalBytes = 0;

        foreach (self::CATEGORIES as $category => [$verdict, $description]) {
            if (! isset($this->stats[$category])) {
                continue;
            }

            $stat = $this->stats[$category];
            $rows[] = [
                $category,
                strtoupper($verdict),
                $this->formatCount($stat['count']),
                Helpers::formatBytes($stat['bytes']),
                $description,
            ];
            $totals[$verdict] += $stat['bytes'];
            $totalFiles += $stat['count'];
            $totalBytes += $stat['bytes'];
        }

        $this->table(['Category', 'Verdict', 'Files', 'Size', 'What it is'], $rows);
        $this->newLine();
        $this->info(sprintf(
            'Total: %s files, %s — %s needed, %s deletable, %s needs review.',
            $this->formatCount($totalFiles),
            Helpers::formatBytes($totalBytes),
            Helpers::formatBytes($totals['keep']),
            Helpers::formatBytes($totals['delete']),
            Helpers::formatBytes($totals['review'])
        ));
        $this->newLine();
    }

    private function printMasterSummary() : void
    {
        $current = $this->stats['master'] ?? ['count' => 0, 'bytes' => 0];
        $old = $this->stats['master-old'] ?? ['count' => 0, 'bytes' => 0];
        $deleted = $this->stats['deleted-track-master'] ?? ['count' => 0, 'bytes' => 0];
        $queued = $this->stats['queued-original'] ?? ['count' => 0, 'bytes' => 0];

        $this->info('== Uploaded masters ==');
        $this->line('The raw upload is deleted after encoding, so the is_master track file is the canonical copy of each upload.');
        $this->line('Lossy uploads (including archive imports) have lossy masters — those are still the best copy that exists.');
        $this->line(sprintf('  Current-version masters on disk: %s (%s)', $this->formatCount($current['count']), Helpers::formatBytes($current['bytes'])));

        ksort($this->masterFormats);
        $lossyCount = 0;
        $lossyBytes = 0;
        foreach ($this->masterFormats as $format => $stat) {
            $isLossless = in_array($format, Track::$LosslessFormats);
            $this->line(sprintf(
                '    %-12s %s (%s)%s',
                $format.':',
                $this->formatCount($stat['count']),
                Helpers::formatBytes($stat['bytes']),
                $isLossless ? '' : ' — lossy master'
            ));
            if (! $isLossless) {
                $lossyCount += $stat['count'];
                $lossyBytes += $stat['bytes'];
            }
        }
        if ($lossyCount > 0) {
            $this->line(sprintf('    (%s masters are lossy: %s)', $this->formatCount($lossyCount), Helpers::formatBytes($lossyBytes)));
        }

        $this->line(sprintf('  Old-version masters on disk:     %s (%s)', $this->formatCount($old['count']), Helpers::formatBytes($old['bytes'])));
        $this->line(sprintf('  Masters of deleted tracks:       %s (%s)', $this->formatCount($deleted['count']), Helpers::formatBytes($deleted['bytes'])));
        $this->line(sprintf('  Raw uploads still in queue:      %s (%s)', $this->formatCount($queued['count']), Helpers::formatBytes($queued['bytes'])));
        $this->newLine();
    }

    /**
     * Reports TrackFile records whose file should be on disk but isn't.
     * Missing masters are unrecoverable; missing transcodes can be rebuilt
     * from the master; missing cacheable files regenerate on demand.
     */
    private function printMissingFiles() : void
    {
        $missingMasters = [];
        $missingTranscodes = [];

        foreach ($this->trackFiles as $key => $trackFile) {
            if (isset($this->foundTrackFiles[$key])) {
                continue;
            }

            $track = $this->tracks[$trackFile->track_id] ?? null;
            if ($track === null || $track->deleted_at !== null) {
                continue;
            }

            $isCurrent = (int) $trackFile->version === (int) $track->current_version;
            // TrackFile::getFilename() substitutes the track's *current* version,
            // so build the name from the record itself to report old versions correctly.
            $filename = "{$trackFile->track_id}-v{$trackFile->version}.{$this->extensions[$trackFile->format]}";

            if ($trackFile->is_master) {
                $missingMasters[] = $filename.($isCurrent ? '' : ' (old version)');
            } elseif ($isCurrent && ! $trackFile->is_cacheable) {
                $missingTranscodes[] = $filename;
            }
        }

        // Tracks with no master TrackFile record at all won't appear in the
        // loop above — old archive imports sometimes never got one. For these,
        // whatever file remains on disk is effectively the master.
        $tracksWithoutMasterRecord = [];
        foreach ($this->tracks as $trackId => $track) {
            if ($track->deleted_at !== null) {
                continue;
            }
            if ($this->masterRecordFor($trackId, (int) $track->current_version) === null) {
                $tracksWithoutMasterRecord[] = "track $trackId (current version v{$track->current_version})";
            }
        }

        $this->info('== Missing files ==');
        $this->line(sprintf('  Masters missing from disk (unrecoverable!):         %s', $this->formatCount(count($missingMasters))));
        $this->line(sprintf('  Live tracks with no master record at all:           %s', $this->formatCount(count($tracksWithoutMasterRecord))));
        $this->line(sprintf('  Current non-cacheable transcodes missing from disk: %s', $this->formatCount(count($missingTranscodes))));
        $this->line(sprintf('  Image files missing from disk (rebuild:images):     %s', $this->formatCount($this->missingImages)));

        $limit = (int) $this->option('examples');
        foreach (array_slice($missingMasters, 0, $limit) as $filename) {
            $this->warn('    missing master: '.$filename);
        }
        foreach (array_slice($tracksWithoutMasterRecord, 0, $limit) as $label) {
            $this->warn('    no master record: '.$label);
        }
        foreach (array_slice($missingTranscodes, 0, $limit) as $filename) {
            $this->line('    missing transcode: '.$filename);
        }
        $this->newLine();
    }

    private function printExamples() : void
    {
        $printable = array_filter(
            $this->examples,
            fn ($category) => $category !== 'missing-image',
            ARRAY_FILTER_USE_KEY
        );

        if (count($printable) === 0) {
            return;
        }

        $this->info('== Examples (up to '.$this->option('examples').' per category; use --report= for the full list) ==');
        foreach ($printable as $category => $paths) {
            $this->line("  [$category]");
            foreach ($paths as $path) {
                $this->line('    '.$path);
            }
        }
    }
}
