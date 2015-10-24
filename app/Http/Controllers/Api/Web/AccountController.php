<?php

namespace Poniverse\Ponyfm\Http\Controllers\Api\Web;

use Poniverse\Ponyfm\Http\Controllers\ApiControllerBase;
use Poniverse\Ponyfm\Commands\SaveAccountSettingsCommand;
use Cover;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Response;

class AccountController extends ApiControllerBase
{
    public function getSettings()
    {
        $user = Auth::user();

        return Response::json([
            'bio' => $user->bio,
            'can_see_explicit_content' => $user->can_see_explicit_content == 1,
            'display_name' => $user->display_name,
            'sync_names' => $user->sync_names == 1,
            'mlpforums_name' => $user->mlpforums_name,
            'gravatar' => $user->gravatar ? $user->gravatar : $user->email,
            'avatar_url' => !$user->uses_gravatar ? $user->getAvatarUrl() : null,
            'uses_gravatar' => $user->uses_gravatar == 1
        ], 200);
    }

    public function postSave()
    {
        return $this->execute(new SaveAccountSettingsCommand(Input::all()));
    }
}