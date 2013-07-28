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

	Route::get('/tracks', 'TracksController@getIndex');
	Route::get('/tracks/popular', 'TracksController@getIndex');
	Route::get('/tracks/random', 'TracksController@getIndex');

	Route::get('/albums', 'AlbumsController@getIndex');
	Route::get('/artists', 'ArtistsController@getIndex');
	Route::get('/playlists', 'PlaylistsController@getIndex');

	Route::get('/login', function() { return View::make('auth.login'); });
	Route::get('/register', function() { return View::make('auth.register'); });

	Route::get('/about', function() { return View::make('pages.about'); });
	Route::get('/faq', function() { return View::make('pages.faq'); });

	Route::get('i{id}/{type}.png', 'ImagesController@getImage');

	Route::get('u{id}/avatar_{type}.png', 'UsersController@getAvatar');

	Route::get('playlist/{id}/{slug}', 'PlaylistsController@getPlaylist');
	Route::get('playlist/{id}-{slug}', 'PlaylistsController@getPlaylist');
	Route::get('p{id}', 'PlaylistsController@getShortlink');

	Route::group(['prefix' => 'api/web'], function() {
		Route::get('/taxonomies/all', 'Api\Web\TaxonomiesController@getAll');

		Route::get('/playlists/show/{id}', 'Api\Web\PlaylistsController@getShow');

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

			Route::post('/account/settings/save', 'Api\Web\AccountController@postSave');
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

	Route::get('/', 'HomeController@getIndex');