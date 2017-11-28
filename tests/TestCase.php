<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Storage;
use Laravel\BrowserKitTesting\TestCase as BaseTestCase;
use Poniverse\Ponyfm\Models\User;

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2015 Peter Deltchev
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

class TestCase extends BaseTestCase
{
    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://ponyfm-testing.poni';

    /**
     * The Pony.fm user used in tests.
     *
     * @var User
     */
    protected $user = null;

    protected static $initializedFiles = false;

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        $this->getTestFiles();

        return $app;
    }

    public function getTestFiles()
    {
        // Ensure we have the Pony.fm test files
        if (!static::$initializedFiles) {
            Storage::disk('local')->makeDirectory('test-files');
            $storage = Storage::disk('testing');

            // To add new test files, upload them to poniverse.net/files
            // and add them here with their last-modified date as a Unix
            // timestamp.
            $files = [
                'ponyfm-test.flac' => 1450965707,
                'ponyfm-transparent-cover-art.png' => 1451211579
            ];

            foreach ($files as $filename => $lastModifiedTimestamp) {
                if (!$storage->has($filename) ||
                    $storage->lastModified($filename) < $lastModifiedTimestamp
                ) {
                    echo "Downloading test file: ${filename}...".PHP_EOL;

                    $testFileUrl = "https://poniverse.net/files/ponyfm-test-files/${filename}";
                    $data = \Httpful\Request::getQuick($testFileUrl);

                    if ($data->code === 200) {
                        $storage->put(
                            $filename,
                            $data->body
                        );
                    } else {
                        $this->fail("A necessary test file was unavailable: ${testFileUrl}");
                    }
                }
            }

            // Delete any unnecessary test files
            foreach ($storage->allFiles() as $filename) {
                if (!isset($files[$filename])) {
                    $storage->delete($filename);
                }
            }

            static::$initializedFiles = true;
        }
    }

    public function tearDown()
    {
        Storage::disk('local')->deleteDirectory('testing-datastore');
        parent::tearDown();
    }

    /**
     * Returns an object for testing file uploads using the given test file.
     * In a test, to "attach" a file to the `track` field, call the following:
     *
     *      $this->call('POST', '/api/v1/tracks', [], [], ['track' => $file]);
     *      // then, deal with the response
     *
     * Adapted from: http://laravel.io/forum/03-09-2014-unit-test-progressive-unit-test-for-uploaded-files-with-validation?page=1#reply-27008
     *
     * @param $filename
     * @return \Symfony\Component\HttpFoundation\File\UploadedFile
     */
    public function getTestFileForUpload($filename)
    {
        Storage::disk('local')->makeDirectory('testing-datastore/tmp');
        Storage::disk('local')->copy("test-files/${filename}", "testing-datastore/tmp/${filename}");

        return new \Illuminate\Http\UploadedFile(storage_path("app/testing-datastore/tmp/${filename}"), $filename, null, null, null, true);
    }

    /**
     * Helper function for testing file uploads to the API.
     *
     * @param array $parameters
     * @param array $files
     */
    protected function callUploadWithParameters(array $parameters, array $files = [])
    {
        $this->expectsJobs([
            \Poniverse\Ponyfm\Jobs\EncodeTrackFile::class,
            \Poniverse\Ponyfm\Jobs\UpdateSearchIndexForEntity::class
        ]);
        $this->user = factory(User::class)->create();

        $file = $this->getTestFileForUpload('ponyfm-test.flac');

        $this->actingAs($this->user)
             ->call('POST', '/api/v1/tracks', $parameters, [], array_merge(['track' => $file], $files));

        $this->assertResponseStatus(202);
    }
}
