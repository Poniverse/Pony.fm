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
		<section class="sidebar">
			<nav>
				<ul>
					<li><h3>Discover</h3></li>
					<li ng-class="{selected: $state.includes('tracks')}"><a href="/tracks">Music <i class="icon-music"></i></a></li>
					<li ng-class="{selected: $state.includes('albums')}"><a href="/albums">Albums <i class="icon-music"></i></a></li>
					<li ng-class="{selected: $state.includes('playlists')}"><a href="/playlists">Playlists <i class="icon-music"></i></a></li>
					<li ng-class="{selected: $state.includes('artists')}"><a href="/artists">Artists <i class="icon-user"></i></a></li>

					@if (Auth::check())
						<li><h3>Playlists</h3></li>
						<li class="none"><span>no playlists</span></li>

						<li>
							<h3>Account</h3>
						</li>
						<li ng-class="{selected: $state.includes('account-favourites')}"><a href="/account/favourites">Favourites</a></li>
						<li ng-class="{selected: $state.includes('account-content')}"><a href="/account/content/tracks">Your Content</a></li>
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
		<section ui-view class="site-content" ng-animate="'site-content-animate'">
			@yield('app_content')
		</section>
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