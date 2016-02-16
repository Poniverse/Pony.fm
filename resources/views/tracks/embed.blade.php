{{--
    Pony.fm - A community for pony fan music.
    Copyright (C) 2015 Peter Deltchev

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
--}}<!DOCTYPE html>
<html lang="en-CA">
<head>
    <meta charset="UTF-8">
    <title>{{$track->title}} by {{$track->user->display_name}} on Pony.fm</title>
    <meta itemprop="name" content="Pony.fm">
    <meta property="og:title" content="Pony.fm - The Pony Music Hosting Site" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="https://pony.fm/" />
    <meta property="og:image" content="https://pony.fm/favicon.ico" />
    <meta property="og:site_name" content="Pony.fm" />
    <meta property="fb:admins" content="1165335382" />
    <base href="/" />

    {!! Assets::styleIncludes('embed') !!}
</head>
<body>
    @if($track->explicit && !(Auth::check() && Auth::user()->can_see_explicit_content))
        <div class="explicit alert alert-danger">
            <em>Enable explicit content in <a href="{{ URL::to('/account/settings') }}" target="_blank">your account</a> to play this track.</em>
            <div class="stats">
                <span>Hosted by <a href="{{URL::to('/')}}" target="_blank">Pony.fm</a></span>
            </div>
        </div>
    @else
        <div class="player loading {{Auth::check() ? 'can-favourite' : ''}} {{$user['is_favourited'] ? 'favourited' : ''}}" data-track-id="{{ $track->id }}" data-duration="{{ $track->duration * 1000 }}">
            <div class="play" disabled="disabled">
                <div class="button"><i class="icon-play"></i></div>
                <img src="{{ $track->getCoverUrl(\Poniverse\Ponyfm\Models\Image::SMALL) }}" />
            </div>
            <div class="meta">
                @if (Auth::check())
                    <a href="#" class="favourite"><i title="Favourite this track!" class="favourite-icon icon-star-empty"></i></a>
                @endif
                <div class="progressbar">
                    <div class="loader"></div>
                    <div class="seeker"></div>
                </div>
                <span class="title"><a href="{{ $track->url }}" target="_blank">{{ $track->title }}</a></span>
                <span>by: <strong><a href="{{ $track->user->url }}" target="_blank">{{ $track->user->display_name }}</a></strong> / {{$track->genre->name}} / {!! Helpers::timestamp($track->published_at) !!}</span>
            </div>
            <div class="stats">
                Views: <strong>{{ $track->view_count }}</strong> / Plays: <strong>{{ $track->play_count }}</strong> / Downloads: <strong>{{ $track->download_count }}</strong> /
                <span>Hosted by <a href="{{URL::to('/')}}" target="_blank">Pony.fm</a></span>
            </div>
        </div>
    @endif

    @if (Config::get("app.debug"))
        <script src="http://localhost:61999/build/scripts/embed.js"></script>
        <script src="http://localhost:61999/webpack-dev-server.js"></script>
    @else
        <script src="/build/scripts/embed.js"></script>
    @endif

    @if(config('ponyfm.google_analytics_id'))
        <script type="text/javascript">
            var _gaq = _gaq || [];
            _gaq.push(['_setAccount', '{{ config('ponyfm.google_analytics_id') }}']);
            _gaq.push(['_setDomainName', 'pony.fm']);
            _gaq.push(['_trackPageview']);

            (function() {
                var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
                ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
                var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
            })();
        </script>
    @endif
</body>
</html>
