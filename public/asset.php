<?php

	if ((isset($_GET['file']) && strstr($_GET['file'], '..'))) {
		exit();
	}

	require __DIR__.'/../bootstrap/autoload.php';
	require __DIR__.'/../bootstrap/start.php';

	use Assetic\Asset\AssetCache;
	use Assetic\Asset\AssetCollection;
	use Assetic\Asset\FileAsset;
	use Assetic\Asset\GlobAsset;
	use Assetic\Cache\FilesystemCache;
	use Assetic\Filter\CoffeeScriptFilter;
	use Assetic\Filter\LessFilter;
	use Assetic\Filter\UglifyCssFilter;
	use Assetic\Filter\UglifyJs2Filter;

	$bundle = null;
	$cacheDirectory = storage_path() . '/cache';

	if ($_GET['type'] == 'coffee') {
		header('Content-Type: text/javascript');

		if (!isset($_GET['file']) || !Config::get('app.debug')) {
			$bundle = Assets::scriptAssetCollection($_GET['area']);
			$bundle->ensureFilter(new UglifyJs2Filter(Config::get('app.uglify-js')));
			$bundle->setTargetPath('scripts');
		} else {
			$filePath = trim($_GET['file'], '/');
			$bundle = new AssetCollection([new FileAsset($filePath)], [new CoffeeScriptFilter(Config::get('app.coffee'))]);
			$bundle->setTargetPath($filePath);
		}

		$bundle = new AssetCache($bundle, new FilesystemCache("$cacheDirectory/scripts"));
	} else if ($_GET['type'] == 'less') {
		header('Content-Type: text/css');

		if (!isset($_GET['file']) || !Config::get('app.debug')) {
			$bundle = Assets::styleAssetCollection($_GET['area']);
			$bundle->ensureFilter(new UglifyCssFilter(Config::get('app.uglify-css')));
			$bundle->setTargetPath('styles');
		} else {
			$filePath = trim($_GET['file'], '/');
			$lastModifiedCollection = new AssetCollection([new GlobAsset("styles/*.less")]);
			$bundle = new AssetCollection([new FileAsset($filePath), new CacheBusterAsset($lastModifiedCollection->getLastModified())], [new LessFilter('node')]);
			$bundle->setTargetPath($filePath);
		}

		$bundle = new AssetCache($bundle, new FilesystemCache("$cacheDirectory/styles"));
	} else {
		exit();
	}

	$time = gmdate($bundle->getLastModified());

	if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $time == $_SERVER['HTTP_IF_MODIFIED_SINCE']) {
		header('HTTP/1.0 304 Not Modified');
		exit();
	}

	header('Last-Modified: ' . $time);
	header('Cache-Control: max-age=' . (60 * 60 * 24 * 7));

	echo $bundle->dump();