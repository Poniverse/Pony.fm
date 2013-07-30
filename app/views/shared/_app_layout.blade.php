@extends('shared._layout')

@section('content')
	<header>
		<div>
			<h1><a href="/">Pony.fm</a></h1>
			<div class="now-playing">
				<div class="current-track">
					<div class="transport">
						<div class="bar" style="width: 44%;"></div>
					</div>
					<div class="image"></div>
					<ul class="buttons">
						<li><a class="previous" href="#"><i class="icon-fast-backward"></i></a></li>
						<li><a class="play" href="#"><i class="icon-pause"></i></a></li>
						<li><a class="next" href="#"><i class="icon-fast-forward"></i></a></li>
						<li><a class="volume" href="#"><i class="icon-volume-up"></i></a></li>
					</ul>
					<div class="title">
						<span class="song"><a href="#">Love Me Cheerilee</a></span>
						<span class="artist"><a href="#">MandoPony</a></span>
					</div>
				</div>
			</div>
		</div>
	</header>

	<div class="site-body">
		<section class="sidebar" ng-controller="sidebar">
			<nav>
				<ul>
					@if (Auth::check())
						<li ng-class="{selected: $state.includes('home')}"><a href="/">Dashboard</a></li>
					@else
						<li ng-class="{selected: $state.includes('home')}"><a href="/">Home</a></li>
					@endif
					<li><a href="/tracks">Now Playing</a></li>
					<li><h3>Discover</h3></li>
					<li ng-class="{selected: $state.includes('tracks')}"><a href="/tracks">Music <i class="icon-music"></i></a></li>
					<li ng-class="{selected: $state.includes('albums')}"><a href="/albums">Albums <i class="icon-music"></i></a></li>
					<li ng-class="{selected: $state.includes('playlists')}"><a href="/playlists">Playlists <i class="icon-music"></i></a></li>
					<li ng-class="{selected: $state.includes('artists')}"><a href="/artists">Artists <i class="icon-user"></i></a></li>

					@if (Auth::check())
						<li>
							<h3>
								<a href="#" ng-click="createPlaylist()" pfm-eat-click title="Create Playlist"><i class="icon-plus"></i></a>
								<a href="/account/playlists" ng-class="{selected: $state.is('account-content-playlists')}" title="View Playlists" class="view-all"><i class="icon-list"></i></a>
								Playlists
							</h3>
						</li>
						<li class="none" ng-show="!playlists.length"><span>no pinned playlists</span></li>
						<li class="dropdown" ng-repeat="playlist in playlists" ng-cloak ng-class="{selected: $state.is('playlist') && $state.params.id == playlist.id}">
							<a class="menu dropdown-toggle" pfm-eat-click href="#"><i class="icon-ellipsis-vertical"></i></a>
							<a href="{{Helpers::angular('playlist.url')}}" ng-bind="playlist.title"></a>

							<ul class="dropdown-menu">
								<li><a href="#" pfm-eat-click ng-click="editPlaylist(playlist)">Edit</a></li>
								<li><a href="#" pfm-eat-click ng-click="unpinPlaylist(playlist)">Unpin</a></li>
								<li><a href="#" pfm-eat-click ng-click="deletePlaylist(playlist)" ng-show="playlist.user_id == auth.user_id">Delete</a></li>
							</ul>
						</li>

						<li>
							<h3>
								<a href="#" title="Upload Track"><i class="icon-upload"></i></a>
								Account
							</h3>
						</li>
						<li ng-class="{selected: $state.includes('account-favourites')}"><a href="/account/favourites">Favourites</a></li>
						<li ng-class="{selected: $state.includes('account-content')}"><a href="/account/tracks">Your Content</a></li>
						<li ng-class="{selected: isActive('/account')}"><a href="/account">Settings</a></li>
					@endif

					<li><h3>Meta</h3></li>

					@if (!Auth::check())
						<li ng-class="{selected: isActive('/login')}"><a href="/login">Login</a></li>
						<li ng-class="{selected: isActive('/register')}"><a href="/register">Register</a></li>
					@endif

					<li ng-class="{selected: isActive('/about')}"><a href="/about">About</a></li>
					<li ng-class="{selected: isActive('/faq')}"><a href="/faq">FAQ</a></li>

					@if (Auth::check())
						<li><a href="#" ng-click="logout()" pfm-eat-click>Logout</a></li>
					@endif
				</ul>
			</nav>
		</section>
		<ui-view class="site-content">
			@yield('app_content')
		</ui-view>
	</div>

	<ng-include src="'templates/partials/upload-dialog.html'" />

@endsection

@section('styles')
	{{ Assets::styleIncludes() }}
@endsection

@section('scripts')

	<script>
		window.pfm = {
			token: "{{Session::token()}}",
			auth: {
				@if (Auth::check())
					isLogged: true,
					user: {{Auth::user()->toJson()}}
				@else
					isLogged: false
				@endif
			}
		};
	</script>

	{{ Assets::scriptIncludes() }}
@endsection