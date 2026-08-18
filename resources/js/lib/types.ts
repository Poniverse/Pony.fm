/** Shapes returned by the model mappers on the Laravel side. */

export interface UserSummary {
    id: number;
    name: string;
    slug: string;
    url: string;
    is_archived: boolean;
    avatars: { small: string; normal: string };
    created_at: string | { date: string };
}

export interface AuthUser extends UserSummary {
    is_admin: boolean;
}

export interface TrackStats {
    views: number;
    plays: number;
    downloads: number;
    comments: number;
    favourites: number;
}

export interface GenreRef {
    id: number;
    slug: string;
    name: string;
    url?: string;
}

export interface TrackSummary {
    id: number;
    title: string;
    user: { id: number; name: string; slug?: string; url: string; avatars?: { thumbnail: string; small: string } };
    stats: TrackStats;
    url: string;
    slug: string;
    is_vocal: boolean;
    is_explicit: boolean;
    is_downloadable: boolean;
    is_published: boolean;
    published_at: string | { date: string } | null;
    duration: string;
    genre: GenreRef | null;
    track_type_id: number;
    covers: { thumbnail: string; small: string; normal: string; original: string };
    /** Only streams whose file exists are non-null; opus is absent until
     *  the backfill reaches the track. mp3 is always available. */
    streams: { opus?: string | null; mp3?: string; aac?: string | null; ogg?: string | null };
    user_data?: { stats: Record<string, number>; is_favourited: boolean };
    permissions?: { delete: boolean; edit: boolean };
    album?: { title: string; url: string };
}

export interface CommentData {
    id: number;
    created_at: string | { date: string };
    content: string;
    user: UserSummary;
}

export interface TrackShow extends TrackSummary {
    description: string;
    lyrics: string;
    comments: CommentData[];
    formats?: { name: string; extension: string; url: string; size: string; isCacheable: boolean }[];
    share: { url: string; html: string; bbcode: string; twitterUrl: string; tumblrUrl: string };
}

export interface AlbumSummary {
    id: number;
    title: string;
    slug: string;
    url: string;
    user: { id: number; name: string; slug?: string; url: string; avatars?: { thumbnail: string; small: string } };
    covers: { small: string; normal: string; original?: string };
    track_count: number;
    stats: { views: number; downloads: number; comments: number; favourites: number };
    user_data?: { is_favourited: boolean };
    permissions?: { delete: boolean; edit: boolean };
}

export interface PlaylistSummary {
    id: number;
    title: string;
    slug: string;
    url: string;
    is_public: boolean;
    user: { id: number; name: string; slug?: string; url: string; avatars?: { thumbnail: string; small: string } };
    covers: { small: string; normal: string };
    track_count: number;
    stats?: { views: number; downloads: number; comments: number; favourites: number };
    user_data?: { is_favourited: boolean; is_pinned?: boolean };
}

export interface CollectionFormat {
    name: string;
    extension: string;
    url: string;
    size: string;
    isCacheable: boolean;
    isMixedLosslessness?: boolean;
}

export interface CollectionShare {
    url: string;
    html?: string;
    bbcode?: string;
    twitterUrl: string;
    tumblrUrl: string;
}

export interface AlbumShow extends AlbumSummary {
    tracks: TrackSummary[];
    comments: CommentData[];
    formats?: CollectionFormat[];
    description: string;
    is_downloadable: number | boolean;
    share: CollectionShare;
}

export interface PlaylistShow extends PlaylistSummary {
    tracks: TrackSummary[];
    comments: CommentData[];
    formats: CollectionFormat[];
    description?: string;
    share: CollectionShare;
}

export interface ArtistData {
    id: number;
    name: string;
    slug: string;
    is_archived: boolean;
    avatars: { small: string; normal: string };
    avatar_colors: string[];
    created_at: string | { date: string };
    followers: number;
    bio: string;
    mlpforums_username: string | null;
    message_url: string | null;
    user_data: { is_following: boolean };
    permissions: { edit: boolean };
    isAdmin: boolean;
}

export interface NotificationData {
    id: number;
    date: string;
    thumbnail_url: string;
    text: string;
    url: string;
    is_read: boolean;
}

/** Props Inertia shares on every page (see HandleInertiaRequests). */
export interface SharedProps {
    auth: { user: AuthUser | null };
    environment: string;
    flash: { message: string | null };
    [key: string]: unknown;
}
