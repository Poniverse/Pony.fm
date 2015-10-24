<?php

namespace Poniverse\Ponyfm\Commands;

use Poniverse\Ponyfm\Album;
use Illuminate\Support\Facades\Auth;

class DeleteAlbumCommand extends CommandBase
{
    private $_albumId;
    private $_album;

    function __construct($albumId)
    {
        $this->_albumId = $albumId;
        $this->_album = ALbum::find($albumId);
    }

    /**
     * @return bool
     */
    public function authorize()
    {
        $user = Auth::user();

        return $this->_album && $user != null && $this->_album->user_id == $user->id;
    }

    /**
     * @throws \Exception
     * @return CommandResponse
     */
    public function execute()
    {
        foreach ($this->_album->tracks as $track) {
            $track->album_id = null;
            $track->track_number = null;
            $track->updateTags();
            $track->save();
        }

        $this->_album->delete();

        return CommandResponse::succeed();
    }
}