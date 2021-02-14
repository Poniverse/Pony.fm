<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AlbumsController;
use App\Http\Controllers\Api\Mobile;
use App\Http\Controllers\Api\V1;
use App\Http\Controllers\Api\Web;
use App\Http\Controllers\ArtistsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ImagesController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\PlaylistsController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\TracksController;
use App\Http\Controllers\UploaderController;
use Illuminate\Support\Facades\Route;

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2015-2017 Feld0.
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

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/dashboard', [TracksController::class, 'getIndex']);
Route::get('/tracks', [TracksController::class, 'getIndex'])->name('tracks.discover');
Route::get('/tracks/popular', [TracksController::class, 'getIndex']);
Route::get('/tracks/random', [TracksController::class, 'getIndex']);

Route::get('tracks/{id}-{slug}', [TracksController::class, 'getTrack']);
Route::get('tracks/{id}-{slug}/edit', [TracksController::class, 'getEdit']);
Route::get('tracks/{id}-{slug}/stats', [StatsController::class, 'getIndex']);
Route::get('t{id}', [TracksController::class, 'getShortlink'])->where('id', '\d+');
Route::get('t{id}/embed', [TracksController::class, 'getEmbed']);
Route::get('t{id}/stream.{extension}', [TracksController::class, 'getStream']);
Route::get('t{id}/dl.{extension}', [TracksController::class, 'getDownload']);

Route::get('albums', [AlbumsController::class, 'getIndex']);
Route::get('albums/{id}-{slug}', [AlbumsController::class, 'getShow']);
Route::get('a{id}', [AlbumsController::class, 'getShortlink'])->where('id', '\d+');
Route::get('a{id}/dl.{extension}', [AlbumsController::class, 'getDownload']);

Route::get('artists', [ArtistsController::class, 'getIndex']);
Route::get('playlists', [PlaylistsController::class, 'getIndex']);

Route::get('/register', [AccountController::class, 'getRegister']);
Route::get('/login', [AuthController::class, 'getLogin']);
Route::post('/auth/logout', [AuthController::class, 'postLogout']);
Route::get('/auth/oauth', [AuthController::class, 'getOAuth']);
Route::post('/auth/poniverse-sync', [AuthController::class, 'postPoniverseAccountSync'])->middleware('throttle:60,1');

Route::get('/about', function () {
    return View::make('pages.about');
});
Route::get('/faq', function () {
    return View::make('pages.faq');
});
Route::get('/mlpforums-advertising-program', function () {
    return View::make('pages.mlpforums-advertising-program');
});

Route::get('/hwc2016-rules', function () {
    return View::make('pages.hwc-terms');
});

Route::get('i{id}/{type}.{extension}', [ImagesController::class, 'getImage'])->where('id', '\d+');

Route::get('playlist/{id}-{slug}', [PlaylistsController::class, 'getPlaylist']);
Route::get('p{id}', [PlaylistsController::class, 'getShortlink'])->where('id', '\d+');
Route::get('p{id}/dl.{extension}', [PlaylistsController::class, 'getDownload']);

Route::get('notifications', [AccountController::class, 'getNotifications']);

Route::prefix('notifications/email')->group(function () {
    Route::get('/unsubscribe/{subscriptionKey}', [NotificationsController::class, 'getEmailUnsubscribe'])->name('email:unsubscribe');
    Route::get('/unsubscribed', [NotificationsController::class, 'getEmailUnsubscribePage'])->name('email:confirm-unsubscribed');
    Route::get('/click/{emailKey}', [NotificationsController::class, 'getEmailClick'])->name('email:click');
});

Route::get('oembed', [TracksController::class, 'getOembed']);

Route::prefix('api/v1')->middleware('json-exceptions')->group(function () {
    Route::get('/tracks/radio-details/{hash}', [Api\V1\TracksController::class, 'getTrackRadioDetails']);
    Route::post('/tracks/radio-details/{hash}', [Api\V1\TracksController::class, 'getTrackRadioDetails']);
    Route::get('/tracks/{id}', [Api\V1\TracksController::class, 'getTrackDetails'])->where('id', '\d+');

    Route::middleware('auth.oauth:ponyfm:tracks:upload')->group(function () {
        Route::post('tracks', [Api\V1\TracksController::class, 'postUploadTrack']);
        Route::get('/tracks/{id}/upload-status', [Api\V1\TracksController::class, 'getUploadStatus']);
    });
});

Route::prefix('api/web')->middleware('cors')->group(function () {
    Route::post('/alexa', [Api\Web\AlexaController::class, 'handle']);

    Route::get('/taxonomies/all', [Api\Web\TaxonomiesController::class, 'getAll']);
    Route::get('/search', [Api\Web\SearchController::class, 'getSearch']);

    Route::get('/tracks', [Api\Web\TracksController::class, 'getIndex']);
    Route::get('/tracks/{id}', [Api\Web\TracksController::class, 'getShow'])->where('id', '\d+');
    Route::get('/tracks/cached/{id}/{format}', [Api\Web\TracksController::class, 'getCachedTrack'])->where(['id' => '\d+', 'format' => '.+']);
    Route::get('/tracks/{id}/stats', [Api\Web\StatsController::class, 'getTrackStats'])->where('id', '\d+');

    Route::get('/albums', [Api\Web\AlbumsController::class, 'getIndex']);
    Route::get('/albums/{id}', [Api\Web\AlbumsController::class, 'getShow'])->where('id', '\d+');
    Route::get('/albums/cached/{id}/{format}', [Api\Web\AlbumsController::class, 'getCachedAlbum'])->where(['id' => '\d+', 'format' => '.+']);

    Route::get('/playlists', [Api\Web\PlaylistsController::class, 'getIndex']);
    Route::get('/playlists/show/{id}', [Api\Web\PlaylistsController::class, 'getShow']);
    Route::get('/playlists/{id}', [Api\Web\PlaylistsController::class, 'getShow'])->where('id', '\d+');
    Route::get('/playlists/cached/{id}/{format}', [Api\Web\PlaylistsController::class, 'getCachedPlaylist'])->where(['id' => '\d+', 'format' => '.+']);

    Route::get('/comments/{type}/{id}', [Api\Web\CommentsController::class, 'getIndex'])->where('id', '\d+');

    Route::get('/artists', [Api\Web\ArtistsController::class, 'getIndex']);
    Route::post('/artists', [Api\Web\ArtistsController::class, 'postIndex']);
    Route::get('/artists/{slug}', [Api\Web\ArtistsController::class, 'getShow']);
    Route::get('/artists/{slug}/content', [Api\Web\ArtistsController::class, 'getContent']);
    Route::get('/artists/{slug}/favourites', [Api\Web\ArtistsController::class, 'getFavourites']);

    Route::get('/dashboard', [Api\Web\DashboardController::class, 'getIndex']);

    Route::get('/announcements', [Api\Web\AnnouncementsController::class, 'getIndex']);

    Route::middleware('auth')->group(function () {
        Route::post('/tracks/upload', [Api\Web\TracksController::class, 'postUpload']);
        Route::get('/tracks/{id}/upload-status', [Api\Web\TracksController::class, 'getUploadStatus']);
        Route::post('/tracks/delete/{id}', [Api\Web\TracksController::class, 'postDelete']);
        Route::post('/tracks/edit/{id}', [Api\Web\TracksController::class, 'postEdit']);

        Route::post('/tracks/{id}/version-upload', [Api\Web\TracksController::class, 'postUploadNewVersion']);
        Route::get('/tracks/{id}/version-change/{version}', [Api\Web\TracksController::class, 'getChangeVersion']);
        Route::get('/tracks/{id}/version-upload-status', [Api\Web\TracksController::class, 'getVersionUploadStatus']);
        Route::get('/tracks/{id}/versions', [Api\Web\TracksController::class, 'getVersionList']);

        Route::post('/albums/create', [Api\Web\AlbumsController::class, 'postCreate']);
        Route::post('/albums/delete/{id}', [Api\Web\AlbumsController::class, 'postDelete']);
        Route::post('/albums/edit/{id}', [Api\Web\AlbumsController::class, 'postEdit']);

        Route::post('/playlists/create', [Api\Web\PlaylistsController::class, 'postCreate']);
        Route::post('/playlists/delete/{id}', [Api\Web\PlaylistsController::class, 'postDelete']);
        Route::post('/playlists/edit/{id}', [Api\Web\PlaylistsController::class, 'postEdit']);
        Route::post('/playlists/{id}/add-track', [Api\Web\PlaylistsController::class, 'postAddTrack']);
        Route::post('/playlists/{id}/remove-track', [Api\Web\PlaylistsController::class, 'postRemoveTrack']);

        Route::post('/comments/{type}/{id}', [Api\Web\CommentsController::class, 'postCreate'])->where('id', '\d+');

        Route::post('/account/settings/save/{userSlug}', [Api\Web\AccountController::class, 'postSave']);

        Route::post('/favourites/toggle', [Api\Web\FavouritesController::class, 'postToggle']);

        Route::post('/follow/toggle', [Api\Web\FollowController::class, 'postToggle']);

        Route::post('/dashboard/read-news', [Api\Web\DashboardController::class, 'postReadNews']);
        Route::get('/account/settings/{slug}', [Api\Web\AccountController::class, 'getSettings']);

        Route::get('/notifications', [Api\Web\NotificationsController::class, 'getNotifications']);
        Route::put('/notifications/mark-as-read', [Api\Web\NotificationsController::class, 'putMarkAsRead']);
        Route::post('/notifications/subscribe', [Api\Web\NotificationsController::class, 'postSubscribe']);
        Route::post('/notifications/unsubscribe', [Api\Web\NotificationsController::class, 'postUnsubscribe']);

        Route::get('/tracks/edit/{id}', [Api\Web\TracksController::class, 'getEdit']);

        Route::get('/users/{userId}', [Api\Web\AccountController::class, 'getUser'])->where('userId', '\d+');

        Route::get('/users/{userId}/tracks', [Api\Web\TracksController::class, 'getOwned'])->where('userId', '\d+');
        Route::get('/users/{userSlug}/tracks', [Api\Web\TracksController::class, 'getOwned']);

        Route::get('/users/{userId}/albums', [Api\Web\AlbumsController::class, 'getOwned'])->where('userId', '\d+');
        Route::get('/users/{userSlug}/albums', [Api\Web\AlbumsController::class, 'getOwned']);

        Route::get('/users/{userId}/images', [Api\Web\ImagesController::class, 'getOwned'])->where('userId', '\d+');
        Route::get('/users/{userSlug}/images', [Api\Web\ImagesController::class, 'getOwned']);

        Route::get('/users/{userId}/playlists', [Api\Web\PlaylistsController::class, 'getOwned'])->where('userId', '\d+');
        Route::get('/users/{userSlug}/playlists', [Api\Web\PlaylistsController::class, 'getOwned']);

        Route::get('/albums/edit/{id}', [Api\Web\AlbumsController::class, 'getEdit']);

        Route::get('/playlists/pinned', [Api\Web\PlaylistsController::class, 'getPinned']);

        Route::get('/favourites/tracks', [Api\Web\FavouritesController::class, 'getTracks']);
        Route::get('/favourites/albums', [Api\Web\FavouritesController::class, 'getAlbums']);
        Route::get('/favourites/playlists', [Api\Web\FavouritesController::class, 'getPlaylists']);
    });

    Route::prefix('admin')->middleware('auth', 'can:access-admin-area')->group(function () {
        Route::get('/genres', [Api\Web\GenresController::class, 'getIndex']);
        Route::post('/genres', [Api\Web\GenresController::class, 'postCreate']);
        Route::put('/genres/{id}', [Api\Web\GenresController::class, 'putRename'])->where('id', '\d+');
        Route::delete('/genres/{id}', [Api\Web\GenresController::class, 'deleteGenre'])->where('id', '\d+');

        Route::get('/showsongs', [Api\Web\ShowSongsController::class, 'getIndex']);
        Route::post('/showsongs', [Api\Web\ShowSongsController::class, 'postCreate']);
        Route::put('/showsongs/{id}', [Api\Web\ShowSongsController::class, 'putRename'])->where('id', '\d+');
        Route::delete('/showsongs/{id}', [Api\Web\ShowSongsController::class, 'deleteSong'])->where('id', '\d+');

        Route::get('/tracks', [Api\Web\TracksController::class, 'getAllTracks']);
        Route::get('/tracks/unclassified', [Api\Web\TracksController::class, 'getClassifierQueue']);

        Route::get('/announcements', [Api\Web\AnnouncementsController::class, 'getAdminIndex']);
        Route::get('/announcements/{id}', [Api\Web\AnnouncementsController::class, 'getItemById'])->where('id', '\d+');
        Route::post('/announcements', [Api\Web\AnnouncementsController::class, 'postCreate']);
        Route::put('/announcements/{id}', [Api\Web\AnnouncementsController::class, 'putUpdate'])->where('id', '\d+');
        Route::delete('/announcements/{id}', [Api\Web\AnnouncementsController::class, 'deleteItem'])->where('id', '\d+');
    });

    Route::get('/auth/current', [Api\Web\AccountController::class, 'getCurrentUser']);
    Route::post('/auth/logout', [Api\Web\AuthController::class, 'postLogout']);
});

Route::prefix('admin')->middleware('auth', 'can:access-admin-area')->group(function () {
    Route::get('/genres', [AdminController::class, 'getGenres']);
    Route::get('/tracks', [AdminController::class, 'getTracks']);
    Route::get('/tracks/unclassified', [AdminController::class, 'getClassifierQueue']);
    Route::get('/show-songs', [AdminController::class, 'getShowSongs']);
    Route::get('/users', [AdminController::class, 'getUsers']);
    Route::get('/announcements', [AdminController::class, 'getAnnouncements']);
    Route::get('/', [AdminController::class, 'getIndex']);
});

Route::get('u{id}', [ArtistsController::class, 'getShortlink'])->where('id', '\d+');
Route::get('users/{id}-{slug}', [ArtistsController::class, 'getShortlink'])->where('id', '\d+');

Route::prefix('{slug}')->group(function () {
    Route::get('/', [ArtistsController::class, 'getProfile']);
    Route::get('/content', [ArtistsController::class, 'getContent']);
    Route::get('/favourites', [ArtistsController::class, 'getFavourites']);

    Route::prefix('account')->middleware('auth')->group(function () {
        Route::get('/tracks', [ContentController::class, 'getTracks']);
        Route::get('/tracks/edit/{id}', [ContentController::class, 'getTracks']);
        Route::get('/albums', [ContentController::class, 'getAlbums']);
        Route::get('/albums/edit/{id}', [ContentController::class, 'getAlbums']);
        Route::get('/albums/create', [ContentController::class, 'getAlbums']);
        Route::get('/playlists', [ContentController::class, 'getPlaylists']);

        Route::get('/uploader', [UploaderController::class, 'getIndex']);

        Route::get('/', [AccountController::class, 'getIndex'])->name('account:settings');
    });
});

Route::get('/', [HomeController::class, 'getIndex']);

Route::domain('api.pony.fm')->group(function () {
    Route::get('tracks/latest', [Api\Mobile\TracksController::class, 'latest']);
    Route::get('tracks/popular', [Api\Mobile\TracksController::class, 'popular']);
});
