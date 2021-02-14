<?php

namespace Poniverse\Ponyfm\Http\Controllers\Api\Web;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Session\Store;
use Poniverse\Ponyfm\Http\Controllers\Controller;
use Poniverse\Ponyfm\Models\AlexaSession;
use Poniverse\Ponyfm\Models\Track;
use Psr\Log\LoggerInterface;

class AlexaController extends Controller
{
    /**
     * @var AlexaSession
     */
    protected $session;

    public function handle(Request $request, LoggerInterface $logger)
    {
        $type = $request->json('request.type');
        $intent = $request->json('request.intent.name');

        $sessId = $request->json('session.user.userId', $request->json('context.System.user.userId'));

        if ($sessId) {
            $this->session = AlexaSession::find($sessId);

            if (! $this->session) {
                $this->session = new AlexaSession();
                $this->session->id = $sessId;
            }
        }

        $logger->debug('Incoming Alexa Request', [
            'type' => $type,
            'intent' => $intent,
        ]);

        $logger->debug('Incoming Alexa Full Request', [
            'json' => json_encode($request->json()->all(), JSON_PRETTY_PRINT),
        ]);

        /** @var JsonResponse $response */
        $response = $this->handleType($request);

        if ($response instanceof JsonResponse) {
            $logger->debug('Alexa Response', ['json' => $response->getContent()]);
        }

        if ($this->session) {
            $this->session->save();
        }

        return $response;
    }

    public function handleType(Request $request)
    {
        $type = $request->json('request.type');

        switch ($type) {
            case 'LaunchRequest':
                return $this->launch();
            case 'PlayAudio':
                return $this->play();
            case 'AudioPlayer.PlaybackNearlyFinished':
                return $this->queueNextTrack();
            case 'IntentRequest':
                return $this->handleIntent($request);
            default:
                return response()->make('', 204);
        }
    }

    public function handleIntent(Request $request)
    {
        $intent = $request->json('request.intent.name');

        switch ($intent) {
            case 'AMAZON.PauseIntent':
                return $this->stop();
            case 'PlayAudio':
            case 'AMAZON.ResumeIntent':
                return $this->play();
            case 'AMAZON.NextIntent':
                return $this->queueNextTrack(true);
            case 'AMAZON.PreviousIntent':
                return $this->previousTrack();
            case 'Author':
                return $this->author();
            default:
                return response()->make('', 204);
        }
    }

    public function launch()
    {
        return [
            'version' => '1.0',
            'sessionAttributes' => (object) [],
            'response' => [
                'outputSpeech' => [
                    'type' => 'SSML',
                    'ssml' => "<speak>If you want to play music, say 'Alexa, ask pony fm to play'</speak>",
                ],
                'shouldEndSession' => true,
            ],
        ];
    }

    public function unknown()
    {
        return [
            'version' => '1.0',
            'sessionAttributes' => (object) [],
            'response' => [
                'outputSpeech' => [
                    'type' => 'SSML',
                    'ssml' => "<speak>Sorry, I don't recognise that command.</speak>",
                ],
                'shouldEndSession' => true,
            ],
        ];
    }

    public function author()
    {
        return [
            'version' => '1.0',
            'sessionAttributes' => (object) [],
            'response' => [
                'outputSpeech' => [
                    'type' => 'SSML',
                    'ssml' => '
                        <speak>
                            Pony.fm was built by Pixel Wavelength for Viola to keep all her music in one place.
                        </speak>
                    ',
                ],
                'shouldEndSession' => true,
            ],
        ];
    }

    public function play()
    {
        $track = array_first(Track::popular(1));

        $this->session->put('current_position', 1);
        $this->session->put('track_id', $track['id']);

        return [
            'version' => '1.0',
            'sessionAttributes' => (object) [],
            'response' => [
                'directives' => [
                    [
                        'type' => 'AudioPlayer.Play',
                        'playBehavior' => 'REPLACE_ALL',
                        'audioItem' => [
                            'stream' => [
                                'url' => $track['streams']['mp3'],
                                'token' => '1',
                                'offsetInMilliseconds' => 0,
                            ],
                        ],
                    ],
                ],
                'shouldEndSession' => true,
            ],
        ];
    }

    public function queueNextTrack($replace = false)
    {
        $trackId = $this->session->get('track_id');
        $position = $this->session->get('current_position', 1);
        $trackHistory = $this->session->get('track_history', []);
        $playlist = $this->session->get('playlist', []);
        $playlistNum = $this->session->get('playlist-num', 1);

        if (count($playlist) === 0) {
            $playlist = Track::popular(30);

            $this->session->put('playlist', $playlist);
        }

        if ($position === 30) {
            $playlist = Track::popular(30, false, $playlistNum * 30);

            $position = 1;
            $playlistNum++;
        }

        if (count($playlist) === 0) {
            return [
                'version' => '1.0',
                'sessionAttributes' => (object) [],
                'response' => [
                    'outputSpeech' => [
                        'type' => 'SSML',
                        'ssml' => "
                        <speak>
                            You've reached the end of the popular tracks today. To start from the beginning say 'Alexa, ask pony fm to play'
                        </speak>
                    ", ],
                    'directives' => [
                        [
                            'type' => 'AudioPlayer.Stop',
                        ],
                    ],
                    'shouldEndSession' => true,
                ],
            ];
        }

        $track = $playlist[$position - 1];

        $trackHistory[] = $trackId;

        $this->session->put('current_position', $position + 1);
        $this->session->put('track_id', $track['id']);
        $this->session->put('track_history', $trackHistory);
        $this->session->put('playlist-num', $playlistNum);

        $stream = [
            'url' => $track['streams']['mp3'],
            'token' => $track['id'],
            'offsetInMilliseconds' => 0,
        ];

        if (! $replace) {
            $stream['expectedPreviousToken'] = $trackId;
        }

        return [
            'version' => '1.0',
            'sessionAttributes' => (object) [],
            'response' => [
                'directives' => [
                    [
                        'type' => 'AudioPlayer.Play',
                        'playBehavior' => $replace ? 'REPLACE_ALL' : 'ENQUEUE',
                        'audioItem' => [
                            'stream' => $stream,
                        ],
                    ],
                ],
            ],
        ];
    }

    public function previousTrack()
    {
        $trackId = $this->session->get('track_id');
        $position = $this->session->get('current_position', 1);
        $trackHistory = $this->session->get('track_history', []);
        $playlist = $this->session->get('playlist', []);

        $track = $playlist[$position - 2];

        $trackHistory[] = $trackId;

        $this->session->put('current_position', $position - 1);
        $this->session->put('track_id', $track['id']);
        $this->session->put('track_history', $trackHistory);

        $stream = [
            'url' => $track['streams']['mp3'],
            'token' => $track['id'],
            'offsetInMilliseconds' => 0,
        ];

        return [
            'version' => '1.0',
            'sessionAttributes' => (object) [],
            'response' => [
                'directives' => [
                    [
                        'type' => 'AudioPlayer.Play',
                        'playBehavior' => 'REPLACE_ALL',
                        'audioItem' => [
                            'stream' => $stream,
                        ],
                    ],
                ],
            ],
        ];
    }

    public function stop()
    {
        return [
            'version' => '1.0',
            'sessionAttributes' => (object) [],
            'response' => [
                'directives' => [
                    [
                        'type' => 'AudioPlayer.Stop',
                    ],
                ],
                'shouldEndSession' => true,
            ],
        ];
    }
}
