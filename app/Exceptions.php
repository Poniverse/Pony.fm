<?php namespace Poniverse\Ponyfm;

use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Class TrackFileNotFoundException
 * @package Poniverse\Ponyfm
 *
 * This exception is used to indicate that the requested `TrackFile` object
 * does not exist. This is useful when dealing with albums or playlists that
 * contain tracks for which no lossless master is available (and thus, lossless
 * `TrackFiles` don't exist for).
 */
class TrackFileNotFoundException extends ModelNotFoundException {}
