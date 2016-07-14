ALTER TABLE users ALTER created_at TYPE timestamp(0) without time zone, ALTER updated_at TYPE timestamp(0) without time zone, ALTER bio SET DEFAULT '';

ALTER TABLE tracks ALTER created_at TYPE timestamp(0) without time zone,
ALTER updated_at TYPE timestamp(0) without time zone,
ALTER deleted_at TYPE timestamp(0) without time zone,
ALTER published_at TYPE timestamp(0) without time zone,
ALTER released_at TYPE timestamp(0) without time zone,
ALTER license_id DROP NOT NULL,
ALTER genre_id DROP NOT NULL,
ALTER track_type_id DROP NOT NULL,
ALTER description DROP NOT NULL,
ALTER lyrics DROP NOT NULL,
ALTER cover_id DROP NOT NULL,
ALTER album_id DROP NOT NULL,
ALTER track_number DROP NOT NULL,
ALTER hash DROP NOT NULL,
ALTER metadata DROP NOT NULL,
ALTER original_tags DROP NOT NULL,
ALTER is_vocal SET DEFAULT false,
ALTER is_explicit SET DEFAULT false,
ALTER is_downloadable SET DEFAULT false,
ALTER view_count SET DEFAULT 0,
ALTER play_count SET DEFAULT 0,
ALTER download_count SET DEFAULT 0,
ALTER favourite_count SET DEFAULT 0,
ALTER comment_count SET DEFAULT 0,
ALTER is_latest SET DEFAULT false,
ALTER is_listed SET DEFAULT true;

ALTER TABLE track_files ALTER created_at TYPE timestamp(0) without time zone, ALTER updated_at TYPE timestamp(0) without time zone;
ALTER TABLE images ALTER created_at TYPE timestamp(0) without time zone, ALTER updated_at TYPE timestamp(0) without time zone;
ALTER TABLE genres ALTER created_at TYPE timestamp(0) without time zone, ALTER updated_at TYPE timestamp(0) without time zone;
ALTER TABLE resource_users ALTER is_followed SET DEFAULT false, ALTER is_favourited SET DEFAULT false, ALTER is_pinned SET DEFAULT false, ALTER view_count SET DEFAULT 0, ALTER play_count SET DEFAULT 0, ALTER download_count SET DEFAULT 0;
ALTER TABLE comments ALTER ip_address DROP NOT NULL, ALTER profile_id DROP NOT NULL, ALTER track_id DROP NOT NULL, ALTER album_id DROP NOT NULL, ALTER playlist_id DROP NOT NULL, ALTER created_at TYPE timestamp(0) without time zone, ALTER updated_at TYPE timestamp(0) without time zone, ALTER deleted_at TYPE timestamp(0) without time zone;
ALTER TABLE playlists ALTER created_at TYPE timestamp(0) without time zone, ALTER updated_at TYPE timestamp(0) without time zone, ALTER deleted_at TYPE timestamp(0) without time zone, ALTER track_count SET DEFAULT 0, ALTER view_count SET DEFAULT 0, ALTER download_count SET DEFAULT 0, ALTER favourite_count SET DEFAULT 0, ALTER follow_count SET DEFAULT 0, ALTER comment_count SET DEFAULT 0;
ALTER TABLE playlist_track ALTER created_at TYPE timestamp(0) without time zone, ALTER updated_at TYPE timestamp(0) without time zone;
ALTER TABLE albums ALTER created_at TYPE timestamp(0) without time zone, ALTER updated_at TYPE timestamp(0) without time zone, ALTER deleted_at TYPE timestamp(0) without time zone;
ALTER TABLE show_songs ALTER created_at TYPE timestamp(0) without time zone, ALTER updated_at TYPE timestamp(0) without time zone, ALTER deleted_at TYPE timestamp(0) without time zone;
