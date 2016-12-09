<?php

namespace Poniverse\Ponyfm\Mail;

class NewTrack extends BaseNotification
{
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $artistName = $this->initiatingUser->display_name;
        $trackTitle = $this->activityRecord->resource->title;

        return $this->renderEmail(
            'new-track',
            "{$artistName} published \"{$trackTitle}\"",
            [
                'artist' => $artistName,
                'trackTitle' => $trackTitle,
            ]);
    }
}
