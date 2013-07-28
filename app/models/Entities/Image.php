<?php

	namespace Entities;

	use External;
	use Illuminate\Support\Facades\Config;
	use Illuminate\Support\Facades\URL;

	class Image extends \Eloquent {
		const NORMAL = 1;
		const ORIGINAL = 2;
		const THUMBNAIL = 3;
		const SMALL = 4;

		public static $ImageTypes = [
			self::NORMAL   =>  ['id' =>  self::NORMAL,    'name' => 'normal',    'width' => 350,  'height' => 350],
			self::ORIGINAL =>  ['id' =>  self::ORIGINAL,  'name' => 'original',  'width' => null, 'height' => null],
			self::SMALL    =>  ['id' =>  self::SMALL, 	  'name' => 'small',      'width' => 100,  'height' => 100],
			self::THUMBNAIL => ['id' =>  self::THUMBNAIL, 'name' => 'thumbnail', 'width' => 50,   'height' => 50]
		];

		public static function getImageTypeFromName($name) {
			foreach (self::$ImageTypes as $cover) {
				if ($cover['name'] != $name)
					continue;

				return $cover;
			}

			return null;
		}

		public static function upload($file, $user) {
			$hash = md5_file($file->getPathname());
			$image = Image::whereHash($hash)->whereUploadedBy($user->id)->first();

			if ($image)
				return $image;

			$image = new Image();
			try {
				$image->uploaded_by = $user->id;
				$image->size = $file->getSize();
				$image->filename = $file->getClientOriginalName();
				$image->extension = $file->getClientOriginalExtension();
				$image->mime = $file->getMimeType();
				$image->hash = $hash;
				$image->save();

				$image->ensureDirectoryExists();
				foreach (self::$ImageTypes as $coverType) {
					$command = 'convert 2>&1 "' . $file->getPathname() . '" -background transparent -flatten +matte -strip -quality 95 -format png ';
					if (isset($coverType['width']) && isset($coverType['height']))
						$command .= '-thumbnail ' . $coverType['width'] . 'x' . $coverType['height'] . '^ -gravity center -extent ' . $coverType['width'] . 'x' . $coverType['height'] . ' ';

					$command .= '"' . $image->getFile($coverType['id']) . '"';
					External::execute($command);
				}

				return $image;
			}
			catch (\Exception $e) {
				$image->delete();
				throw $e;
			}
		}

		protected $table = 'images';

		public function getUrl($type = self::NORMAL) {
			$type = self::$ImageTypes[$type];
			return URL::to('i' . $this->id . '/' . $type['name'] . '.png');
		}

		public function getFile($type = self::NORMAL) {
			return $this->getDirectory() . '/' . $this->getFilename($type);
		}

		public function getFilename($type = self::NORMAL) {
			$typeInfo = self::$ImageTypes[$type];
			return $this->id . '_' . $typeInfo['name'] . '.png';
		}

		public function getDirectory() {
			$dir = (string) ( floor( $this->id / 100 ) * 100 );
			return Config::get('app.files_directory') . '/images/' . $dir;
		}

		public function ensureDirectoryExists() {
			$destination = $this->getDirectory();

			if (!is_dir($destination))
				mkdir($destination, 755, true);
		}
	}