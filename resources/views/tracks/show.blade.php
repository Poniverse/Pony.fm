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
--}}

@extends('shared._app_layout')

@section('title'){{ $track->title }} - {{ $track->user->display_name }} | @endsection
@section('description'){{ str_limit($track->description, $limit = 200, $end = '...') }}@endsection

@section('metadata')
    <meta property="og:title" content="{{ $track->title }}" />
    <meta property="og:type" content="music.song" />
    <meta property="og:url" content="{{ url('tracks/' . $track->id . '-' . $track->slug) }}" />
    <meta property="og:image" content="{{ $track->getCoverUrl(\Poniverse\Ponyfm\Models\Image::NORMAL) }}" />
    <meta property="og:image:width" content="350" />
    <meta property="og:image:height" content="350" />
    <meta property="og:description" content="{{ str_limit($track->description, $limit = 200, $end = '...') }}">
    <meta property="og:site_name" content="Pony.fm" />
    <meta property="og:audio" content="{{ $track->getStreamUrl('MP3') }}" />
    <meta property="og:audio:secure_url" content="{{ $track->getStreamUrl('MP3') }}" />
    <meta property="og:audio:type" content="audio/mpeg" />
    <meta property="music:duration" content="{{ round($track->duration) }}" />
    <meta property="fb:admins" content="1165335382" />

    <meta name="twitter:card" content="player" />
    <meta name="twitter:site" content="@ponyfm" />
    <meta name="twitter:title" content="{{ $track->title }}" />
    <meta name="twitter:description" content="{{ str_limit($track->description, $limit = 200, $end = '...') }}" />
    <meta name="twitter:image" content="{{ $track->getCoverUrl(\Poniverse\Ponyfm\Models\Image::NORMAL) }}" />
    <meta name="twitter:player" content="{{ url('t' . $track->id . '/embed?twitter') }}" />
    <meta name="twitter:player:width" content="480" />
    <meta name="twitter:player:height" content="130" />
    <meta name="twitter:player:stream" content="{{ $track->getStreamUrl('MP3') }}" />
    <meta name="twitter:player:stream:content_type" content="audio/mpeg" />
@endsection

@section('app_content')
    <div class="resource-details track-details">
        <header>
            <div class="hidden-xs single-player">
                <img src="{{ $track->getCoverUrl(\Poniverse\Ponyfm\Models\Image::THUMBNAIL) }}" style="opacity: 1;">
            </div>
            <h1>{{ $track->title }}</h1>
            <h2>
                by: <a href="{{ url($track->user->slug) }}">{{ $track->user->display_name }}</a>
            </h2>
        </header>

        <div class="stretch-to-bottom details-columns">
            <div class="right">
                <img class="cover" src="{{ $track->getCoverUrl(\Poniverse\Ponyfm\Models\Image::NORMAL) }}"/>

                <ul class="stats">
                    <li>Published: <strong>{!! Helpers::timestamp($track->published_at) !!}</strong></li>
                    <li>Views: <strong>{{ $track->view_count }}</strong></li>
                    <li>Plays: <strong>{{ $track->play_count }}</strong></li>
                    <li>Downloads: <strong>{{ $track->download_count }}</strong></li>
                    <li>Favourites: <strong>{{ $track->favourite_count }}</strong></li>
                </ul>
            </div>
            <div class="left">
                <div class="description">
                    <h2>Description</h2>
                    <p>{{ $track->description }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection
