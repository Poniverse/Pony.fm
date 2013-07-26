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

			return '<link rel="stylesheet" href="asset.php?area=' . $area . '&type=less&' . gmdate($css->getLastModified()) . '" />';
		}

		public static function scriptAssetCollection($area) {
			if ($area == 'app')
				return new AssetCollection([
					new FileAsset('scripts/base/jquery-2.0.2.js'),
					new FileAsset('scripts/base/jquery-ui.js'),
					new FileAsset('scripts/base/underscore.js'),
					new FileAsset('scripts/base/angular.js'),
					new FileAsset('scripts/base/ui-bootstrap-tpls-0.4.0.js'),
					new FileAsset('scripts/base/angular-ui-date.js'),
					new FileAsset('scripts/base/angular-ui-router.js'),
					new AssetCollection([
						new GlobAsset('scripts/shared/*.coffee'),
						new GlobAsset('scripts/app/*.coffee'),
						new GlobAsset('scripts/app/services/*.coffee'),
						new GlobAsset('scripts/app/filters/*.coffee'),
						new GlobAsset('scripts/app/filters/*.js'),
						new GlobAsset('scripts/app/directives/*.coffee'),
						new GlobAsset('scripts/app/controllers/*.coffee'),
					], [
						new CoffeeScriptFilter(Config::get('app.coffee'))
					])
				]);

			throw new Exception();
		}

		public static function styleAssetCollection($area) {
			if ($area == 'app') {
				$lastModifiedCollection = new AssetCollection([new GlobAsset("styles/*.less")]);

				$css = new AssetCollection([
					new FileAsset('styles/base/jquery-ui.css'),
					new FileAsset('styles/app.less'),
					new CacheBusterAsset($lastModifiedCollection->getLastModified())
				], [new \Assetic\Filter\LessFilter('node')]);

				return $css;
			}

			throw new Exception();
		}
	}