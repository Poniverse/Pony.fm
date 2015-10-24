<?php

namespace Poniverse\Ponyfm\Commands;

use Poniverse\Ponyfm\Playlist;
use Illuminate\Support\Facades\Auth;

class DeletePlaylistCommand extends CommandBase
{
    private $_playlistId;
    private $_playlist;

    function __construct($playlistId)
    {
        $this->_playlistId = $playlistId;
        $this->_playlist = Playlist::find($playlistId);
    }

    /**
     * @return bool
     */
    public function authorize()
    {
        $user = Auth::user();

        return $this->_playlist && $user != null && $this->_playlist->user_id == $user->id;
    }

    /**
     * @throws \Exception
     * @return CommandResponse
     */
    public function execute()
    {
        foreach ($this->_playlist->pins as $pin) {
            $pin->delete();
        }

        $this->_playlist->delete();

        return CommandResponse::succeed();
    }
}