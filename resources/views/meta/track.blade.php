<title inertia>{{ $track->title }} - {{ $track->user->display_name }} - Pony.fm</title>
<meta name="description" content="{{ Str::limit($track->description, 200, '...') }}">

<meta property="og:title" content="{{ $track->title }}" />
<meta property="og:type" content="music.song" />
<meta property="og:url" content="{{ url('tracks/' . $track->id . '-' . $track->slug) }}" />
<meta property="og:image" content="{{ $track->getCoverUrl(\App\Models\Image::NORMAL) }}" />
<meta property="og:image:width" content="350" />
<meta property="og:image:height" content="350" />
<meta property="og:description" content="{{ Str::limit($track->description, 200, '...') }}">
<meta property="og:site_name" content="Pony.fm" />
<meta property="og:audio" content="{{ $track->getStreamUrl('MP3') }}" />
<meta property="og:audio:secure_url" content="{{ $track->getStreamUrl('MP3') }}" />
<meta property="og:audio:type" content="audio/mpeg" />
<meta property="music:duration" content="{{ round($track->duration) }}" />

<meta name="twitter:card" content="player" />
<meta name="twitter:site" content="@ponyfm" />
<meta name="twitter:title" content="{{ $track->title }}" />
<meta name="twitter:description" content="{{ Str::limit($track->description, 200, '...') }}" />
<meta name="twitter:image" content="{{ $track->getCoverUrl(\App\Models\Image::NORMAL) }}" />
<meta name="twitter:player" content="{{ url('t' . $track->id . '/embed?twitter') }}" />
<meta name="twitter:player:width" content="480" />
<meta name="twitter:player:height" content="130" />
<meta name="twitter:player:stream" content="{{ $track->getStreamUrl('MP3') }}" />
<meta name="twitter:player:stream:content_type" content="audio/mpeg" />

<link rel="alternate" type="application/json+oembed" href="{{ url('/oembed?url=') . url('tracks/' . $track->id . '-' . $track->slug) }}" title="{{ $track->title }}" />
