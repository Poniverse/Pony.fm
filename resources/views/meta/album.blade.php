<title inertia>{{ $album->title }} - {{ $album->user->display_name }} - Pony.fm</title>
<meta name="description" content="{{ Str::limit($album->description, 200, '...') }}">

<meta property="og:title" content="{{ $album->title }}" />
<meta property="og:type" content="music.album" />
<meta property="og:url" content="{{ url('albums/' . $album->id . '-' . $album->slug) }}" />
<meta property="og:image" content="{{ $album->getCoverUrl(\App\Models\Image::NORMAL) }}" />
<meta property="og:description" content="{{ Str::limit($album->description, 200, '...') }}">
<meta property="og:site_name" content="Pony.fm" />

<meta name="twitter:card" content="summary" />
<meta name="twitter:site" content="@ponyfm" />
<meta name="twitter:title" content="{{ $album->title }}" />
<meta name="twitter:image" content="{{ $album->getCoverUrl(\App\Models\Image::NORMAL) }}" />
