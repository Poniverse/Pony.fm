<!DOCTYPE html>
<html lang="en-CA">
<head>
	<meta charset="UTF-8">
	<title>@section('title')Pony.fm
		@yield_section</title>
	<meta itemprop="name" content="Pony.fm">
	{{-- <meta itemprop="image" content="https://pony.fm/favicon.ico"> --}}
	<meta property="og:title" content="Pony.fm - The Pony Music Hosting Site" />
	<meta property="og:type" content="website" />
	<meta property="og:url" content="https://pony.fm/" />
	<meta property="og:image" content="https://pony.fm/favicon.ico" />
	<meta property="og:site_name" content="Pony.fm" />
	<meta property="fb:admins" content="1165335382" />

	{{ HTML::style( 'css/app-embed.css?' . filemtime(path('public').'/css/app.css') ) }}
	{{ Asset::styles() }}

	<?php Asset::add('jquery', 'https://ajax.googleapis.com/ajax/libs/jquery/1.9.0/jquery.min.js'); ?>
	<?php Asset::add('jquery-ui', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.10.0/jquery-ui.min.js', 'jquery'); ?>
	<?php Asset::add('scripts', 'js/app.js?' . filemtime(path('public').'/js/app.js'), 'jquery'); ?>
</head>
<body class="embed">
	<div class="fixed-image-width">
		@if($track->explicit && !(Auth::check() && Auth::user()->can_see_explicit_content))
		<div class="explicit alert-box alert">
			<em>Enable explicit content in {{ HTML::link(URL::to_action('account@edit'), 'your account', ['target' => '_blank']) }} to play this track.</em>

			<div class="stats">
				<span>Hosted by <a href="{{URL::to('/')}}" target="_blank">Pony.fm</a></span>
			</div>
		</div>
		@else
		<div class="player-small {{Auth::check() ? 'can-favourite' : ''}} {{Track_Plays::hasPlayed($track->id) ? 'played' : 'unplayed'}} {{Track_Plays::hasFavourited($track->id) ? 'favourited' : ''}}" data-track-id="{{ $track->id }}" data-duration="{{ $track->duration * 1000 }}">
			<div class="play" disabled="disabled">
				<div><i class="icon-play icon-1x"></i></div>
				{{ HTML::image($track->get_cover_url('normal')) }}
			</div>
			<div class="meta">
				@if (Auth::check())
				<a href="#" class="favourite"><i title="Favourite this track!" class="favourite-icon icon-star-empty"></i></a>
				@endif
				<div class="progressbar">
					<div class="progress-container">
						<div class="loader"></div>
						<div class="seeker"></div>
					</div>
				</div>
				<span class="title">{{ HTML::link( $track->url, $track->title, ['target' => '_blank'] ) }}</span>
				<span>by: <strong>{{ HTML::link($track->user->url, $track->artist, ['target' => '_blank']) }}</strong> / {{ HTML::link($track->genre->url, $track->genre->title, ['target' => '_blank']) }} / {{ HTML::timestamp($track->published_at) }}</span>
				<div class="clear"></div>
			</div>
			<div class="stats">
				Views: <strong>{{ $track->views }}</strong> / Plays: <strong>{{ $track->plays }}</strong> / Downloads: <strong>{{ $track->downloads }}</strong> /
				<span>Hosted by <a href="{{URL::to('/')}}" target="_blank">Pony.fm</a></span>
			</div>
		</div>
		@endif
	</div>

	<script>
		var pfm = {token: '{{ Session::token() }}'}
	</script>
	{{ Asset::scripts() }}
	<script type="text/javascript">
		var _gaq = _gaq || [];
		_gaq.push(['_setAccount', 'UA-29463256-1']);
		_gaq.push(['_setDomainName', 'pony.fm']);
		_gaq.push(['_trackPageview']);

		(function() {
			var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
			ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
			var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
		})();
	</script>
</body>
</html>