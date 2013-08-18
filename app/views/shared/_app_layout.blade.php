@extends('shared._layout')

@section('content')
	<header>
		<a href="/">Pony.fm</a>
		<div class="now-playing">
			<pfm-player></pfm-player>
		</div>
	</header>

	<div class="site-body">
		<ul class="sidebar" ng-controller="sidebar">
			@if (Auth::check())
				<li ng-class="{selected: stateIncludes('home')}"><a href="/">Dashboard <i class="icon-home"></i></a></li>
			@else
				<li ng-class="{selected: stateIncludes('home')}"><a href="/">Home <i class="icon-home"></i></a></li>
			@endif
			<li ng-class="{selected: (stateIncludes('content') && !isPinnedPlaylistSelected)}">
				<a href="/tracks">Discover <i class="icon-music"></i></a>
			</li>

			@if (Auth::check())
				<li ng-class="{selected: stateIncludes('account-content') || isActive('/account')}"><a href="/account/tracks">Account <i class="icon-user"></i></a></li>
			@endif

			<li ng-class="{selected: isActive('/about')}"><a href="/about">Meta <i class="icon-info"></i></a></li>

			@if (Auth::check())
				<li>
					<h3>
						<a href="#" ng-click="createPlaylist()" pfm-eat-click title="Create Playlist"><i class="icon-plus"></i></a>
						<a href="/account/playlists" ng-class="{selected: $state.is('account-content-playlists')}" title="View Playlists" class="view-all"><i class="icon-list"></i></a>
						Playlists
					</h3>
				</li>
				<li class="none" ng-show="!playlists.length"><span>no pinned playlists</span></li>
				<li class="dropdown" ng-repeat="playlist in playlists" ng-cloak ng-class="{selected: stateIncludes('content.playlist') && $state.params.id == playlist.id}">
					<a class="menu dropdown-toggle" pfm-eat-click href="#"><i class="icon-ellipsis-vertical"></i></a>
					<a href="{{Helpers::angular('playlist.url')}}" ng-bind="playlist.title"></a>

					<ul class="dropdown-menu">
						<li><a href="#" pfm-eat-click ng-click="editPlaylist(playlist)">Edit</a></li>
						<li><a href="#" pfm-eat-click ng-click="unpinPlaylist(playlist)">Unpin</a></li>
						<li><a href="#" pfm-eat-click ng-click="deletePlaylist(playlist)" ng-show="playlist.user_id == auth.user_id">Delete</a></li>
					</ul>
				</li>
			@endif
		</ul>
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