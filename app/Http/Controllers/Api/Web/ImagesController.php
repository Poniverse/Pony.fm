<?php

namespace Api\Web;

use App\Image;
use Cover;
use Illuminate\Support\Facades\Response;

class ImagesController extends \ApiControllerBase
{
    public function getOwned()
    {
        $query = Image::where('uploaded_by', \Auth::user()->id);
        $images = [];
        foreach ($query->get() as $image) {
            $images[] = [
                'id' => $image->id,
                'urls' => [
                    'small' => $image->getUrl(Image::SMALL),
                    'normal' => $image->getUrl(Image::NORMAL),
                    'thumbnail' => $image->getUrl(Image::THUMBNAIL),
                    'original' => $image->getUrl(Image::ORIGINAL)
                ],
                'filename' => $image->filename
            ];
        }

        return Response::json($images, 200);
    }
}