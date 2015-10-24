<?php

namespace Poniverse\Ponyfm\Commands;

use Poniverse\Ponyfm\Follower;
use Poniverse\Ponyfm\ResourceUser;
use Illuminate\Support\Facades\Auth;

class ToggleFollowingCommand extends CommandBase
{
    private $_resourceType;
    private $_resourceId;

    function __construct($resourceType, $resourceId)
    {
        $this->_resourceId = $resourceId;
        $this->_resourceType = $resourceType;
    }

    /**
     * @return bool
     */
    public function authorize()
    {
        $user = Auth::user();

        return $user != null;
    }

    /**
     * @throws \Exception
     * @return CommandResponse
     */
    public function execute()
    {
        $typeId = $this->_resourceType . '_id';
        $existing = Follower::where($typeId, '=', $this->_resourceId)->whereUserId(Auth::user()->id)->first();
        $isFollowed = false;

        if ($existing) {
            $existing->delete();
        } else {
            $follow = new Follower();
            $follow->$typeId = $this->_resourceId;
            $follow->user_id = Auth::user()->id;
            $follow->created_at = time();
            $follow->save();
            $isFollowed = true;
        }

        $resourceUser = ResourceUser::get(Auth::user()->id, $this->_resourceType, $this->_resourceId);
        $resourceUser->is_followed = $isFollowed;
        $resourceUser->save();

        return CommandResponse::succeed(['is_followed' => $isFollowed]);
    }
}