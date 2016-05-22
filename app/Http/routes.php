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

Route::get('/dashboard', 'TracksController@getIndex');
Route::get('/tracks', ['as' => 'tracks.discover', 'uses' => 'TracksController@getIndex']);
Route::get('/tracks/popular', 'TracksController@getIndex');
Route::get('/tracks/random', 'TracksController@getIndex');

Route::get('tracks/{id}-{slug}', 'TracksController@getTrack');
Route::get('tracks/{id}-{slug}/edit', 'TracksController@getEdit');
Route::get('tracks/{id}-{slug}/stats', 'StatsController@getIndex');
Route::get('t{id}', 'TracksController@getShortlink' )->where('id', '\d+');
Route::get('t{id}/embed', 'TracksController@getEmbed' );
Route::get('t{id}/stream.{extension}', 'TracksController@getStream' );
Route::get('t{id}/dl.{extension}', 'TracksController@getDownload' );

Route::get('albums', 'AlbumsController@getIndex');
Route::get('albums/{id}-{slug}', 'AlbumsController@getShow');
Route::get('a{id}', 'AlbumsController@getShortlink')->where('id', '\d+');
Route::get('a{id}/dl.{extension}', 'AlbumsController@getDownload' );

Route::get('artists', 'ArtistsController@getIndex');
Route::get('playlists', 'PlaylistsController@getIndex');

Route::get('/register', 'AccountController@getRegister');
Route::get('/login', 'AuthController@getLogin');
Route::post('/auth/logout', 'AuthController@postLogout');
Route::get('/auth/oauth', 'AuthController@getOAuth');

Route::get('/about', function() { return View::make('pages.about'); });
Route::get('/faq', function() { return View::make('pages.faq'); });
Route::get('/mlpforums-advertising-program', function() { return View::make('pages.mlpforums-advertising-program'); });

Route::get('i{id}/{type}.{extension}', 'ImagesController@getImage')->where('id', '\d+');

Route::get('playlist/{id}-{slug}', 'PlaylistsController@getPlaylist');
Route::get('p{id}', 'PlaylistsController@getShortlink')->where('id', '\d+');
Route::get('p{id}/dl.{extension}', 'PlaylistsController@getDownload' );



Route::group(['prefix' => 'api/v1', 'middleware' => 'json-exceptions'], function() {
    Route::get('/tracks/radio-details/{hash}', 'Api\V1\TracksController@getTrackRadioDetails');
    Route::post('/tracks/radio-details/{hash}', 'Api\V1\TracksController@getTrackRadioDetails');

    Route::group(['middleware' => 'auth.oauth:ponyfm:tracks:upload'], function() {
        Route::post('tracks', 'Api\V1\TracksController@postUploadTrack');
        Route::get('/tracks/{id}/upload-status', 'Api\V1\TracksController@getUploadStatus');
    });
});


Route::group(['prefix' => 'api/web'], function() {
    Route::get('/taxonomies/all', 'Api\Web\TaxonomiesController@getAll');
    Route::get('/search', 'Api\Web\SearchController@getSearch');

    Route::get('/tracks', 'Api\Web\TracksController@getIndex');
    Route::get('/tracks/{id}', 'Api\Web\TracksController@getShow')->where('id', '\d+');
    Route::get('/tracks/cached/{id}/{format}', 'Api\Web\TracksController@getCachedTrack')->where(['id' => '\d+', 'format' => '.+']);
    Route::get('/tracks/{id}/stats', 'Api\Web\StatsController@getTrackStats')->where('id', '\d+');

    Route::get('/albums', 'Api\Web\AlbumsController@getIndex');
    Route::get('/albums/{id}', 'Api\Web\AlbumsController@getShow')->where('id', '\d+');
    Route::get('/albums/cached/{id}/{format}', 'Api\Web\AlbumsController@getCachedAlbum')->where(['id' => '\d+', 'format' => '.+']);

    Route::get('/playlists', 'Api\Web\PlaylistsController@getIndex');
    Route::get('/playlists/show/{id}', 'Api\Web\PlaylistsController@getShow');
    Route::get('/playlists/{id}', 'Api\Web\PlaylistsController@getShow')->where('id', '\d+');
    Route::get('/playlists/cached/{id}/{format}', 'Api\Web\PlaylistsController@getCachedPlaylist')->where(['id' => '\d+', 'format' => '.+']);

    Route::get('/comments/{type}/{id}', 'Api\Web\CommentsController@getIndex')->where('id', '\d+');

    Route::get('/artists', 'Api\Web\ArtistsController@getIndex');
    Route::get('/artists/{slug}', 'Api\Web\ArtistsController@getShow');
    Route::get('/artists/{slug}/content', 'Api\Web\ArtistsController@getContent');
    Route::get('/artists/{slug}/favourites', 'Api\Web\ArtistsController@getFavourites');

    Route::get('/dashboard', 'Api\Web\DashboardController@getIndex');

    Route::group(['middleware' => 'auth'], function() {
        Route::post('/tracks/upload', 'Api\Web\TracksController@postUpload');
        Route::get('/tracks/{id}/upload-status', 'Api\Web\TracksController@getUploadStatus');
        Route::post('/tracks/delete/{id}', 'Api\Web\TracksController@postDelete');
        Route::post('/tracks/edit/{id}', 'Api\Web\TracksController@postEdit');

        Route::post('/albums/create', 'Api\Web\AlbumsController@postCreate');
        Route::post('/albums/delete/{id}', 'Api\Web\AlbumsController@postDelete');
        Route::post('/albums/edit/{id}', 'Api\Web\AlbumsController@postEdit');

        Route::post('/playlists/create', 'Api\Web\PlaylistsController@postCreate');
        Route::post('/playlists/delete/{id}', 'Api\Web\PlaylistsController@postDelete');
        Route::post('/playlists/edit/{id}', 'Api\Web\PlaylistsController@postEdit');
        Route::post('/playlists/{id}/add-track', 'Api\Web\PlaylistsController@postAddTrack');
        Route::post('/playlists/{id}/remove-track', 'Api\Web\PlaylistsController@postRemoveTrack');

        Route::post('/comments/{type}/{id}', 'Api\Web\CommentsController@postCreate')->where('id', '\d+');

        Route::post('/account/settings/save/{slug}', 'Api\Web\AccountController@postSave');

        Route::post('/favourites/toggle', 'Api\Web\FavouritesController@postToggle');

        Route::post('/follow/toggle', 'Api\Web\FollowController@postToggle');

        Route::post('/dashboard/read-news', 'Api\Web\DashboardController@postReadNews');
    });

    Route::group(['middleware' => 'auth'], function() {
        Route::get('/account/settings/{slug}', 'Api\Web\AccountController@getSettings');
        Route::get('/account/notifications', 'Api\Web\NotificationsController@getNotifications');

        Route::get('/tracks/owned', 'Api\Web\TracksController@getOwned');
        Route::get('/tracks/edit/{id}', 'Api\Web\TracksController@getEdit');

        Route::get('/users/{userId}/albums', 'Api\Web\AlbumsController@getOwned')->where('id', '\d+');
        Route::get('/users/{userId}/images', 'Api\Web\ImagesController@getOwned')->where('id', '\d+');

        Route::get('/albums/edit/{id}', 'Api\Web\AlbumsController@getEdit');

        Route::get('/playlists/owned', 'Api\Web\PlaylistsController@getOwned');
        Route::get('/playlists/pinned', 'Api\Web\PlaylistsController@getPinned');

        Route::get('/favourites/tracks', 'Api\Web\FavouritesController@getTracks');
        Route::get('/favourites/albums', 'Api\Web\FavouritesController@getAlbums');
        Route::get('/favourites/playlists', 'Api\Web\FavouritesController@getPlaylists');
    });

    Route::group(['prefix' => 'admin', 'middleware' => ['auth', 'can:access-admin-area']], function() {
        Route::get('/genres', 'Api\Web\GenresController@getIndex');
        Route::post('/genres', 'Api\Web\GenresController@postCreate');
        Route::put('/genres/{id}', 'Api\Web\GenresController@putRename')->where('id', '\d+');
        Route::delete('/genres/{id}', 'Api\Web\GenresController@deleteGenre')->where('id', '\d+');

        Route::get('/tracks', 'Api\Web\TracksController@getAllTracks');
    });

    Route::post('/auth/logout', 'Api\Web\AuthController@postLogout');
});


Route::group(['prefix' => 'admin', 'middleware' => ['auth', 'can:access-admin-area']], function() {
    Route::get('/genres', 'AdminController@getGenres');
    Route::get('/tracks', 'AdminController@getTracks');
    Route::get('/', 'AdminController@getIndex');
});

Route::get('u{id}', 'ArtistsController@getShortlink')->where('id', '\d+');
Route::get('users/{id}-{slug}', 'ArtistsController@getShortlink')->where('id', '\d+');


Route::group(['prefix' => '{slug}'], function() {
    Route::get('/', 'ArtistsController@getProfile');
    Route::get('/content', 'ArtistsController@getContent');
    Route::get('/favourites', 'ArtistsController@getFavourites');


    Route::group(['prefix' => 'account', 'middleware' => 'auth'], function() {
        Route::get('/tracks', 'ContentController@getTracks');
        Route::get('/tracks/edit/{id}', 'ContentController@getTracks');
        Route::get('/albums', 'ContentController@getAlbums');
        Route::get('/albums/edit/{id}', 'ContentController@getAlbums');
        Route::get('/albums/create', 'ContentController@getAlbums');
        Route::get('/playlists', 'ContentController@getPlaylists');

        Route::get('/uploader', 'UploaderController@getIndex');

        Route::get('/', 'AccountController@getIndex');
    });
});

Route::get('/', 'HomeController@getIndex');

Route::group(['domain' => 'api.pony.fm'], function() {
    Route::get('tracks/latest', ['uses' => 'Api\Mobile\TracksController@latest']);
    Route::get('tracks/popular', [ 'uses' => 'Api\Mobile\TracksController@popular']);
    /*    Route::get('tracks/id', [ 'uses' => 'Api\Mobile\TracksController@track']);
        Route::get('user', ['uses' => 'Api\Mobile\UserController@user']);*/
});
