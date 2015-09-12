<?php

namespace App\Commands;

use App\Track;

class DeleteTrackCommand extends CommandBase
{
    private $_trackId;
    private $_track;

    function __construct($trackId)
    {
        $this->_trackId = $trackId;
        $this->_track = Track::find($trackId);
    }

    /**
     * @return bool
     */
    public function authorize()
    {
        $user = \Auth::user();

        return $this->_track && $user != null && $this->_track->user_id == $user->id;
    }

    /**
     * @throws \Exception
     * @return CommandResponse
     */
    public function execute()
    {
        if ($this->_track->album_id != null) {
            $album = $this->_track->album;
            $this->_track->album_id = null;
            $this->_track->track_number = null;
            $this->_track->delete();
            $album->updateTrackNumbers();
        } else {
            $this->_track->delete();
        }

        return CommandResponse::succeed();
    }
}