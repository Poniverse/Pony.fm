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

	Route::group(['prefix' => 'api/web'], function() {
		Route::get('/taxonomies/all', 'Api\Web\TaxonomiesController@getAll');

		Route::group(['before' => 'auth|csrf'], function() {
			Route::post('/tracks/upload', 'Api\Web\TracksController@postUpload');
			Route::post('/tracks/delete/{id}', 'Api\Web\TracksController@postDelete');
			Route::post('/tracks/edit/{id}', 'Api\Web\TracksController@putEdit');
		});

		Route::group(['before' => 'auth'], function() {
			Route::get('/tracks/owned', 'Api\Web\TracksController@getOwned');
			Route::get('/tracks/edit/{id}', 'Api\Web\TracksController@getEdit');
		});

		Route::group(['before' => 'csrf'], function(){
			Route::post('/auth/login', 'Api\Web\AuthController@postLogin');
			Route::post('/auth/logout', 'Api\Web\AuthController@postLogout');
		});
	});

	Route::group(['prefix' => 'account'], function() {
		Route::group(['before' => 'auth'], function(){
			Route::get('/favorites', 'FavoritesController@getTracks');
			Route::get('/favorites/albums', 'FavoritesController@getAlbums');
			Route::get('/favorites/playlists', 'FavoritesController@getPlaylists');

			Route::get('/content/tracks', 'ContentController@getTracks');
			Route::get('/content/tracks/{id}', 'ContentController@getTracks');
			Route::get('/content/albums', 'ContentController@getAlbums');
			Route::get('/content/playlists', 'ContentController@getPlaylists');

			Route::get('/', 'AccountController@getIndex');
		});
	});

	Route::get('/', 'HomeController@getIndex');