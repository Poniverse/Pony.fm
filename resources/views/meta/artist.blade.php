<title inertia>{{ $artist->display_name }} - Pony.fm</title>
<meta name="description" content="{{ Str::limit($artist->bio ?: ($artist->display_name . ' on Pony.fm'), 200, '...') }}">

<meta property="og:title" content="{{ $artist->display_name }}" />
<meta property="og:type" content="profile" />
<meta property="og:url" content="{{ url($artist->slug) }}" />
<meta property="og:image" content="{{ $artist->getAvatarUrl(\App\Models\Image::NORMAL) }}" />
<meta property="og:site_name" content="Pony.fm" />

<meta name="twitter:card" content="summary" />
<meta name="twitter:site" content="@ponyfm" />
<meta name="twitter:title" content="{{ $artist->display_name }}" />
<meta name="twitter:image" content="{{ $artist->getAvatarUrl(\App\Models\Image::NORMAL) }}" />
