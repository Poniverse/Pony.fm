<?php

	/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/

	Route::get('/dashboard', 'TracksController@getIndex');
	Route::get('/tracks', 'TracksController@getIndex');
	Route::get('/tracks/popular', 'TracksController@getIndex');
	Route::get('/tracks/random', 'TracksController@getIndex');

	Route::get('tracks/{id}-{slug}', 'TracksController@getTrack');
	Route::get('t{id}', 'TracksController@getShortlink' );
	Route::get('t{id}/stream', 'TracksController@getStream' );
	Route::get('t{id}/dl.{extension}', 'TracksController@getDownload' );

	Route::get('albums', 'AlbumsController@getIndex');
	Route::get('albums/{id}-{slug}', 'AlbumsController@getShow');
	Route::get('a{id}', 'AlbumsController@getShortlink')->where('id', '\d+');
	Route::get('a{id}/dl.{extension}', 'AlbumsController@getDownload' );

	Route::get('artists', 'ArtistsController@getIndex');
	Route::get('playlists', 'PlaylistsController@getIndex');

	Route::get('/login', function() { return View::make('auth.login'); });
	Route::get('/register', function() { return View::make('auth.register'); });

	Route::get('/about', function() { return View::make('pages.about'); });
	Route::get('/faq', function() { return View::make('pages.faq'); });

	Route::get('i{id}/{type}.png', 'ImagesController@getImage')->where('id', '\d+');

	Route::get('u{id}/avatar_{type}.png', 'UsersController@getAvatar')->where('id', '\d+');

	Route::get('playlist/{id}-{slug}', 'PlaylistsController@getPlaylist');
	Route::get('p{id}', 'PlaylistsController@getShortlink')->where('id', '\d+');
	Route::get('p{id}/dl.{extension}', 'PlaylistsController@getDownload' );

	Route::group(['prefix' => 'api/web'], function() {
		Route::get('/taxonomies/all', 'Api\Web\TaxonomiesController@getAll');

		Route::get('/playlists/show/{id}', 'Api\Web\PlaylistsController@getShow');

		Route::get('/tracks/recent', 'Api\Web\TracksController@getRecent');
		Route::get('/tracks', 'Api\Web\TracksController@getIndex');
		Route::get('/tracks/{id}', 'Api\Web\TracksController@getShow')->where('id', '\d+');

		Route::get('/albums', 'Api\Web\AlbumsController@getIndex');
		Route::get('/albums/{id}', 'Api\Web\AlbumsController@getShow')->where('id', '\d+');

		Route::get('/playlists/{id}', 'Api\Web\PlaylistsController@getShow')->where('id', '\d+');

		Route::get('/comments/{type}/{id}', 'Api\Web\CommentsController@getIndex')->where('id', '\d+');

		Route::get('/artists', 'Api\Web\ArtistsController@getIndex');
		Route::get('/artists/{slug}', 'Api\Web\ArtistsController@getShow');
		Route::get('/artists/{slug}/content', 'Api\Web\ArtistsController@getContent');
		Route::get('/artists/{slug}/favourites', 'Api\Web\ArtistsController@getFavourites');

		Route::get('/dashboard', 'Api\Web\DashboardController@getIndex');

		Route::group(['before' => 'auth|csrf'], function() {
			Route::post('/tracks/upload', 'Api\Web\TracksController@postUpload');
			Route::post('/tracks/delete/{id}', 'Api\Web\TracksController@postDelete');
			Route::post('/tracks/edit/{id}', 'Api\Web\TracksController@postEdit');

			Route::post('/albums/create', 'Api\Web\AlbumsController@postCreate');
			Route::post('/albums/delete/{id}', 'Api\Web\AlbumsController@postDelete');
			Route::post('/albums/edit/{id}', 'Api\Web\AlbumsController@postEdit');

			Route::post('/playlists/create', 'Api\Web\PlaylistsController@postCreate');
			Route::post('/playlists/delete/{id}', 'Api\Web\PlaylistsController@postDelete');
			Route::post('/playlists/edit/{id}', 'Api\Web\PlaylistsController@postEdit');
			Route::post('/playlists/{id}/add-track', 'Api\Web\PlaylistsController@postAddTrack');

			Route::post('/comments/{type}/{id}', 'Api\Web\CommentsController@postCreate')->where('id', '\d+');

			Route::post('/account/settings/save', 'Api\Web\AccountController@postSave');

			Route::post('/favourites/toggle', 'Api\Web\FavouritesController@postToggle');
		});

		Route::group(['before' => 'auth'], function() {
			Route::get('/account/settings', 'Api\Web\AccountController@getSettings');

			Route::get('/images/owned', 'Api\Web\ImagesController@getOwned');

			Route::get('/tracks/owned', 'Api\Web\TracksController@getOwned');
			Route::get('/tracks/edit/{id}', 'Api\Web\TracksController@getEdit');

			Route::get('/albums/owned', 'Api\Web\AlbumsController@getOwned');
			Route::get('/albums/edit/{id}', 'Api\Web\AlbumsController@getEdit');

			Route::get('/playlists/owned', 'Api\Web\PlaylistsController@getOwned');
			Route::get('/playlists/pinned', 'Api\Web\PlaylistsController@getPinned');
		});

		Route::group(['before' => 'csrf'], function(){
			Route::post('/auth/login', 'Api\Web\AuthController@postLogin');
			Route::post('/auth/logout', 'Api\Web\AuthController@postLogout');
		});
	});

	Route::group(['prefix' => 'account'], function() {
		Route::group(['before' => 'auth'], function(){
			Route::get('/favourites/tracks', 'FavouritesController@getTracks');
			Route::get('/favourites/albums', 'FavouritesController@getAlbums');
			Route::get('/favourites/playlists', 'FavouritesController@getPlaylists');

			Route::get('/tracks', 'ContentController@getTracks');
			Route::get('/tracks/edit/{id}', 'ContentController@getTracks');
			Route::get('/albums', 'ContentController@getAlbums');
			Route::get('/albums/edit/{id}', 'ContentController@getAlbums');
			Route::get('/albums/create', 'ContentController@getAlbums');
			Route::get('/playlists', 'ContentController@getPlaylists');

			Route::get('/', 'AccountController@getIndex');
		});
	});

	Route::get('u{id}', 'ArtistsController@getShortlink')->where('id', '\d+');
	Route::get('users/{id}-{slug}', 'ArtistsController@getShortlink')->where('id', '\d+');
	Route::get('{slug}', 'ArtistsController@getProfile');
	Route::get('{slug}/content', 'ArtistsController@getProfile');
	Route::get('{slug}/favourites', 'ArtistsController@getProfile');

	Route::get('/', 'HomeController@getIndex');