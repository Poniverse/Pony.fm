<?php

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

namespace Poniverse\Ponyfm\Models;

use External;
use Illuminate\Database\Eloquent\Model;
use Config;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Poniverse\Ponyfm\Models\Image
 *
 * @property integer $id
 * @property string $filename
 * @property string $mime
 * @property string $extension
 * @property integer $size
 * @property string $hash
 * @property integer $uploaded_by
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
        self::NORMAL => ['id' => self::NORMAL, 'name' => 'normal', 'width' => 350, 'height' => 350],
        self::ORIGINAL => ['id' => self::ORIGINAL, 'name' => 'original', 'width' => null, 'height' => null],
        self::SMALL => ['id' => self::SMALL, 'name' => 'small', 'width' => 100, 'height' => 100],
        self::THUMBNAIL => ['id' => self::THUMBNAIL, 'name' => 'thumbnail', 'width' => 50, 'height' => 50]
    ];

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
        $image = Image::whereHash($hash)->whereUploadedBy($userId)->first();

        if ($image) {
            if ($forceReupload) {
                // delete existing versions of the image
                $filenames = scandir($image->getDirectory());
                $imagePrefix = $image->id.'_';

                $filenames = array_filter($filenames, function (string $filename) use ($imagePrefix) {
                    return Str::startsWith($filename, $imagePrefix);
                });

                foreach ($filenames as $filename) {
                    unlink($image->getDirectory().'/'.$filename);
                }
            } else {
                return $image;
            }
        } else {
            $image = new Image();
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
                if ($coverType['id'] === self::ORIGINAL && $image->mime === 'image/jpeg') {
                    $command = 'cp "'.$file->getPathname().'" '.$image->getFile($coverType['id']);
                } else {
                    // ImageMagick options reference: http://www.imagemagick.org/script/command-line-options.php
                    $command = 'convert 2>&1 "'.$file->getPathname().'" -background white -alpha remove -alpha off -strip';

                    if ($image->mime === 'image/jpeg') {
                        $command .= ' -quality 100 -format jpeg';
                    } else {
                        $command .= ' -quality 95 -format png';
                    }

                    if (isset($coverType['width']) && isset($coverType['height'])) {
                        $command .= " -thumbnail ${coverType['width']}x${coverType['height']}^ -gravity center -extent ${coverType['width']}x${coverType['height']}";
                    }

                    $command .= ' "'.$image->getFile($coverType['id']).'"';
                }

                External::execute($command);
                chmod($image->getFile($coverType['id']), 0644);
            }

            return $image;
        } catch (\Exception $e) {
            $image->delete();
            throw $e;
        }
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

        if (!is_dir($destination)) {
            mkdir($destination, 0777, true);
        }
    }
}
