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

namespace Poniverse\Ponyfm\Models;

use Config;
use External;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Poniverse\Ponyfm\Models\Image.
 *
 * @property int $id
 * @property string $filename
 * @property string $mime
 * @property string $extension
 * @property int $size
 * @property string $hash
 * @property int $uploaded_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\Image whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\Image whereFilename($value)
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\Image whereMime($value)
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\Image whereExtension($value)
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\Image whereSize($value)
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\Image whereHash($value)
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\Image whereUploadedBy($value)
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\Image whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\Image whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Image extends Model
{
    const NORMAL = 1;
    const ORIGINAL = 2;
    const THUMBNAIL = 3;
    const SMALL = 4;

    public static $ImageTypes = [
        self::NORMAL =>     ['id' => self::NORMAL,      'name' => 'normal',     'width' => 350,     'height' => 350,    'geometry' => '350'],
        self::ORIGINAL =>   ['id' => self::ORIGINAL,    'name' => 'original',   'width' => null,    'height' => null,   'geometry' => null],
        self::SMALL =>      ['id' => self::SMALL,       'name' => 'small',      'width' => 100,     'height' => 100,    'geometry' => '100x100^'],
        self::THUMBNAIL =>  ['id' => self::THUMBNAIL,   'name' => 'thumbnail',  'width' => 50,      'height' => 50,     'geometry' => '50x50^'],
    ];

    const MIME_JPEG = 'image/jpeg';

    public static function getImageTypeFromName($name)
    {
        foreach (self::$ImageTypes as $cover) {
            if ($cover['name'] != $name) {
                continue;
            }

            return $cover;
        }

        return null;
    }

    /**
     * @param UploadedFile $file
     * @param int|User $user
     * @param bool $forceReupload forces the image to be re-processed even if a matching hash is found
     * @return Image
     * @throws \Exception
     */
    public static function upload(UploadedFile $file, $user, bool $forceReupload = false)
    {
        $userId = $user;
        if ($user instanceof User) {
            $userId = $user->id;
        }

        $hash = md5_file($file->getPathname());
        $image = self::whereHash($hash)->whereUploadedBy($userId)->first();

        if ($image) {
            if ($forceReupload) {
                $image->clearExisting(true);
            } else {
                return $image;
            }
        } else {
            $image = new self();
        }

        try {
            $image->uploaded_by = $userId;
            $image->size = $file->getSize();
            $image->filename = $file->getClientOriginalName();
            $image->extension = $file->getClientOriginalExtension();
            $image->mime = $file->getMimeType();
            $image->hash = $hash;
            $image->save();

            $image->ensureDirectoryExists();
            foreach (self::$ImageTypes as $coverType) {
                self::processFile($file, $image->getFile($coverType['id']), $coverType);
            }

            return $image;
        } catch (\Exception $e) {
            $image->delete();
            throw $e;
        }
    }

    /**
     * Converts the image into the specified cover type to the specified path.
     *
     * @param File $image The image file to be processed
     * @param string $path The path to save the processed image file
     * @param array $coverType The type to process the image to
     */
    private static function processFile(File $image, string $path, $coverType)
    {
        if ($coverType['id'] === self::ORIGINAL && $image->getMimeType() === self::MIME_JPEG) {
            if ($image->getPathname() === $path) {
                Log::warning("Attempted to copy an original file $path to itself.");
            } else {
                $command = 'cp "'.$image->getPathname().'" '.$path;
            }
        } else {
            // ImageMagick options reference: http://www.imagemagick.org/script/command-line-options.php
            $command = 'convert 2>&1 "'.$image->getPathname().'" -background white -alpha remove -alpha off -strip';

            if ($image->getMimeType() === self::MIME_JPEG) {
                $command .= ' -quality 100 -format jpeg';
            } else {
                $command .= ' -quality 95 -format png';
            }

            if (isset($coverType['geometry'])) {
                $command .= " -gravity center -thumbnail ${coverType['geometry']} -extent ${coverType['geometry']}";
            }

            $command .= ' "'.$path.'"';
        }

        External::execute($command);
        chmod($path, 0644);
    }

    protected $table = 'images';

    public function getUrl($type = self::NORMAL)
    {
        $type = self::$ImageTypes[$type];

        return action('ImagesController@getImage', ['id' => $this->id, 'type' => $type['name'], 'extension' => $this->extension]);
    }

    public function getFile($type = self::NORMAL)
    {
        return $this->getDirectory().'/'.$this->getFilename($type);
    }

    public function getFilename($type = self::NORMAL)
    {
        $typeInfo = self::$ImageTypes[$type];

        return $this->id.'_'.$typeInfo['name'].'.'.$this->extension;
    }

    public function getDirectory()
    {
        $dir = (string) (floor($this->id / 100) * 100);

        return Config::get('ponyfm.files_directory').'/images/'.$dir;
    }

    public function ensureDirectoryExists()
    {
        $destination = $this->getDirectory();
        umask(0);

        if (! is_dir($destination)) {
            mkdir($destination, 0777, true);
        }
    }

    /**
     * Deletes any generated files if they exist.
     * @param bool $includeOriginal Set to true if the original image should be deleted as well.
     */
    public function clearExisting(bool $includeOriginal = false)
    {
        $files = scandir($this->getDirectory());
        $filePrefix = $this->id.'_';
        $originalName = $filePrefix.self::$ImageTypes[self::ORIGINAL]['name'];

        $files = array_filter($files, function ($file) use ($originalName, $includeOriginal, $filePrefix) {
            if (Str::startsWith($file, $originalName) && ! $includeOriginal) {
                return false;
            } else {
                return Str::startsWith($file, $filePrefix);
            }
        });

        foreach ($files as $file) {
            unlink($this->getDirectory().'/'.$file);
        }
    }

    /**
     * Builds the cover images for the image, overwriting if needed.
     *
     * @throws FileNotFoundException If the original file cannot be found.
     */
    public function buildCovers()
    {
        $originalFile = new File($this->getFile(self::ORIGINAL));

        foreach (self::$ImageTypes as $imageType) {
            //Ignore original imagetype
            if ($imageType['id'] === self::ORIGINAL) {
                continue;
            }

            self::processFile($originalFile, $this->getFile($imageType['id']), $imageType);
        }
    }
}
