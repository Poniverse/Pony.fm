<?php

	namespace Commands;

	use Entities\Track;
	use Entities\TrackFile;
	use Illuminate\Support\Facades\Log;

	class UploadTrackCommand extends CommandBase {
		/**
		 * @return bool
		 */
		public function authorize() {
			return \Auth::user() != null;
		}

		/**
		 * @throws \Exception
		 * @return CommandResponse
		 */
		public function execute() {
			$user = \Auth::user();
			$trackFile = \Input::file('track');
			$audio = \AudioCache::get($trackFile->getPathname());

			$validator = \Validator::make(['track' => $trackFile], [
				'track' =>
				'required|'
				. 'audio_format:flac,pcm_s16le ([1][0][0][0] / 0x0001),pcm_s16be,adpcm_ms ([2][0][0][0] / 0x0002),pcm_s24le ([1][0][0][0] / 0x0001),pcm_s24be,pcm_f32le ([3][0][0][0] / 0x0003),pcm_f32be (fl32 / 0x32336C66)|'
				. 'audio_channels:1,2|'
				. 'sample_rate:44100,48000,88200,96000,176400,192000|'
				. 'min_duration:30'
			]);

			if ($validator->fails())
				return CommandResponse::fail($validator);

			$track = new Track();

			try {
				$track->user_id = $user->id;
				$track->title = pathinfo($trackFile->getClientOriginalName(), PATHINFO_FILENAME);
				$track->duration = $audio->getDuration();
				$track->is_listed = true;

				$track->save();

				$destination = $track->getDirectory();
				$track->ensureDirectoryExists();

				$source = $trackFile->getPathname();
				$index = 0;

				$processes = [];

				foreach (Track::$Formats as $name => $format) {
					$trackFile = new TrackFile();
					$trackFile->is_master = $name === 'FLAC' ? true : false;
					$trackFile->format = $name;
					$track->trackFiles()->save($trackFile);

					$target = $destination . '/' . $trackFile->getFilename(); //$track->getFilenameFor($name);

					$command = $format['command'];
					$command = str_replace('{$source}', '"' . $source . '"', $command);
					$command = str_replace('{$target}', '"' . $target . '"', $command);

					Log::info('Encoding ' . $track->id . ' into ' . $target);
					$this->notify('Encoding ' . $name, $index / count(Track::$Formats) * 100);

					$pipes = [];
					$proc = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'a']], $pipes);
					$processes[] = $proc;
				}

				foreach ($processes as $proc)
					proc_close($proc);

				$track->updateTags();

			} catch (\Exception $e) {
				$track->delete();
				throw $e;
			}

			return CommandResponse::succeed([
				'id' => $track->id,
				'name' => $track->name
			]);
		}
	}