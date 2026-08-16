<title inertia>{{ $playlist->title }} - {{ $playlist->user->display_name }} - Pony.fm</title>
<meta name="description" content="{{ Str::limit($playlist->description, 200, '...') }}">

<meta property="og:title" content="{{ $playlist->title }}" />
<meta property="og:type" content="music.playlist" />
<meta property="og:url" content="{{ url('playlist/' . $playlist->id . '-' . $playlist->slug) }}" />
<meta property="og:image" content="{{ $playlist->getCoverUrl(\App\Models\Image::NORMAL) }}" />
<meta property="og:description" content="{{ Str::limit($playlist->description, 200, '...') }}">
<meta property="og:site_name" content="Pony.fm" />

<meta name="twitter:card" content="summary" />
<meta name="twitter:site" content="@ponyfm" />
<meta name="twitter:title" content="{{ $playlist->title }}" />
