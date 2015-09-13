<?php

namespace App\Http\Controllers;

use App\AlbumDownloader;
use App;
use App\Album;
use App\ResourceLogItem;
use App\Track;
use Illuminate\Support\Facades\Redirect;
use View;

class AlbumsController extends Controller
{
    public function getIndex()
    {
        return View::make('albums.index');
    }

    public function getShow($id, $slug)
    {
        $album = Album::find($id);
        if (!$album) {
            App::abort(404);
        }

        if ($album->slug != $slug) {
            return Redirect::action('AlbumsController@getAlbum', [$id, $album->slug]);
        }

        return View::make('albums.show');
    }

    public function getShortlink($id)
    {
        $album = Album::find($id);
        if (!$album) {
            App::abort(404);
        }

        return Redirect::action('AlbumsController@getTrack', [$id, $album->slug]);
    }

    public function getDownload($id, $extension)
    {
        $album = Album::with('tracks', 'user')->find($id);
        if (!$album) {
            App::abort(404);
        }

        $format = null;
        $formatName = null;

        foreach (Track::$Formats as $name => $item) {
            if ($item['extension'] == $extension) {
                $format = $item;
                $formatName = $name;
                break;
            }
        }

        if ($format == null) {
            App::abort(404);
        }

        ResourceLogItem::logItem('album', $id, ResourceLogItem::DOWNLOAD, $format['index']);
        $downloader = new AlbumDownloader($album, $formatName);
        $downloader->download();
    }
}
