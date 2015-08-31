<?php

namespace App\Commands;

use App\Playlist;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CreatePlaylistCommand extends CommandBase
{
    private $_input;

    function __construct($input)
    {
        $this->_input = $input;
    }

    /**
     * @return bool
     */
    public function authorize()
    {
        $user = \Auth::user();

        return $user != null;
    }

    /**
     * @throws \Exception
     * @return CommandResponse
     */
    public function execute()
    {
        $rules = [
            'title' => 'required|min:3|max:50',
            'is_public' => 'required',
            'is_pinned' => 'required'
        ];

        $validator = Validator::make($this->_input, $rules);

        if ($validator->fails()) {
            return CommandResponse::fail($validator);
        }

        $playlist = new Playlist();
        $playlist->user_id = Auth::user()->id;
        $playlist->title = $this->_input['title'];
        $playlist->description = $this->_input['description'];
        $playlist->is_public = $this->_input['is_public'] == 'true';

        $playlist->save();

        if ($this->_input['is_pinned'] == 'true') {
            $playlist->pin(Auth::user()->id);
        }

        return CommandResponse::succeed([
            'id' => $playlist->id,
            'title' => $playlist->title,
            'slug' => $playlist->slug,
            'created_at' => $playlist->created_at,
            'description' => $playlist->description,
            'url' => $playlist->url,
            'is_pinned' => $this->_input['is_pinned'] == 'true',
            'is_public' => $this->_input['is_public'] == 'true'
        ]);
    }
}