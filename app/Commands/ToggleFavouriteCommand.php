<?php

namespace App\Commands;

use App\Favourite;
use App\ResourceUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ToggleFavouriteCommand extends CommandBase
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
        $existing = Favourite::where($typeId, '=', $this->_resourceId)->whereUserId(Auth::user()->id)->first();
        $isFavourited = false;

        if ($existing) {
            $existing->delete();
        } else {
            $fav = new Favourite();
            $fav->$typeId = $this->_resourceId;
            $fav->user_id = Auth::user()->id;
            $fav->created_at = time();
            $fav->save();
            $isFavourited = true;
        }

        $resourceUser = ResourceUser::get(Auth::user()->id, $this->_resourceType, $this->_resourceId);
        $resourceUser->is_favourited = $isFavourited;
        $resourceUser->save();

        $resourceTable = $this->_resourceType . 's';

        // We do this to prevent a race condition. Sure I could simply increment the count columns and re-save back to the db
        // but that would require an additional SELECT and the operation would be non-atomic. If two log items are created
        // for the same resource at the same time, the cached values will still be correct with this method.

        DB::table($resourceTable)->whereId($this->_resourceId)->update([
            'favourite_count' =>
                DB::raw('(
					SELECT
						COUNT(id)
					FROM
						favourites
					WHERE ' .
                    $typeId . ' = ' . $this->_resourceId . ')')
        ]);

        return CommandResponse::succeed(['is_favourited' => $isFavourited]);
    }
}