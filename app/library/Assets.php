<?php
	use Assetic\Asset\AssetCollection;
	use Assetic\Asset\FileAsset;
	use Assetic\Asset\GlobAsset;
	use Assetic\Filter\CoffeeScriptFilter;

	class Assets {
		public static function scriptIncludes($area = 'app') {
			$js = self::scriptAssetCollection($area);

			if (Config::get('app.debug')) {
				$retVal = '';

				foreach ($js as $script) {
					$retVal .= '<script src="/' . $script->getSourceRoot() . '/' . $script->getSourcePath() . '?' . gmdate($js->getLastModified()) . '"></script>';
				}

				return $retVal;
			}

			return '<script src="/asset.php?area=' . $area . '&type=coffee&' . gmdate($js->getLastModified()) . '"></script>';
		}

		public static function styleIncludes($area = 'app') {
			$css = self::styleAssetCollection($area);

			if (Config::get('app.debug')) {
				$retVal = '';

				foreach ($css as $style) {
					if ($style instanceof CacheBusterAsset)
						continue;

					$retVal .= '<link rel="stylesheet" href="/' . $style->getSourceRoot() . '/' . $style->getSourcePath() . '?' . gmdate($css->getLastModified()) . '" />';
				}

				return $retVal;
			}

			return '<script>document.write(\'<link rel="stylesheet" href="asset.php?area=' . $area . '&type=less&' . gmdate($css->getLastModified()) . '" />\');</script>';
		}

		public static function scriptAssetCollection($area) {
			$coffeeScript = new CoffeeScriptFilter(Config::get('app.coffee'), Config::get('app.node'));

			if ($area == 'app') {
				$collection = new AssetCollection([
					new FileAsset('scripts/base/jquery-2.0.2.js'),
					new FileAsset('scripts/base/jquery-ui.js'),
					new FileAsset('scripts/base/jquery.cookie.js'),
					new FileAsset('scripts/base/jquery.colorbox.js'),
					new FileAsset('scripts/base/jquery.viewport.js'),
					new FileAsset('scripts/base/underscore.js'),
					new FileAsset('scripts/base/moment.js'),
					new FileAsset('scripts/base/soundmanager2-nodebug.js'),
					new FileAsset('scripts/base/angular.js'),
					new FileAsset('scripts/base/bindonce.js'),
					new FileAsset('scripts/base/ui-bootstrap-tpls-0.4.0.js'),
					new FileAsset('scripts/base/angular-ui-sortable.js'),
					new FileAsset('scripts/base/angular-ui-date.js'),
					new FileAsset('scripts/base/angular-ui-router.js'),
					new FileAsset('scripts/base/angularytics.js'),
					new AssetCollection([
						new GlobAsset('scripts/shared/*.coffee'),
					], [
						$coffeeScript
					]),
					new GlobAsset('scripts/shared/*.js'),
					new AssetCollection([
						new GlobAsset('scripts/app/*.coffee'),
						new GlobAsset('scripts/app/services/*.coffee'),
						new GlobAsset('scripts/app/filters/*.coffee'),
					], [
						$coffeeScript
					]),
					new GlobAsset('scripts/app/filters/*.js'),
					new AssetCollection([
						new GlobAsset('scripts/app/directives/*.coffee'),
						new GlobAsset('scripts/app/controllers/*.coffee'),
					], [
						$coffeeScript
					])
				]);

				if (Config::get('app.debug')) {
					$collection->add(new GlobAsset('scripts/debug/*.js'));

					$collection->add(new AssetCollection([
						new GlobAsset('scripts/debug/*.coffee'),
					], [
						$coffeeScript
					]));
				}

				return $collection;
			} else if ($area == 'embed') {
				$collection = new AssetCollection([
					new FileAsset('scripts/base/jquery-2.0.2.js'),
					new FileAsset('scripts/base/jquery.viewport.js'),
					new FileAsset('scripts/base/underscore.js'),
					new FileAsset('scripts/base/moment.js'),
					new FileAsset('scripts/base/jquery.timeago.js'),
					new FileAsset('scripts/base/soundmanager2-nodebug.js'),
					new AssetCollection([
						new GlobAsset('scripts/embed/*.coffee'),
					], [
						$coffeeScript
					])
				]);

				return $collection;
			}

			throw new Exception();
		}

		public static function styleAssetCollection($area) {
			if ($area == 'app') {
				$lastModifiedCollection = new AssetCollection([new GlobAsset("styles/*.less")]);

				$css = new AssetCollection([
					new FileAsset('styles/base/jquery-ui.css'),
					new FileAsset('styles/base/colorbox.css'),
					new FileAsset('styles/app.less'),
					new CacheBusterAsset($lastModifiedCollection->getLastModified())
				], [new \Assetic\Filter\LessFilter('node')]);

				if (Config::get('app.debug')) {
					$css->add(new FileAsset('styles/profiler.less'));
					$css->add(new FileAsset('styles/prettify.css'));
				}

				return $css;
			} else if ($area == 'embed') {
				$css = new AssetCollection([
					new FileAsset('styles/embed.less'),
				], [new \Assetic\Filter\LessFilter('node')]);

				return $css;
			}

			throw new Exception();
		}
	}