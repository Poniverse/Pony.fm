<?php

namespace Poniverse\Ponyfm\Http\Controllers\Api\Web;

use Illuminate\Http\Request;
use Poniverse\Ponyfm\Http\Controllers\Controller;
use Psr\Log\LoggerInterface;

class AlexaController extends Controller
{
    public function handle(Request $request, LoggerInterface $logger)
    {
        $type = $request->json('request.type');
        $intent = $request->json('request.intent.name');

        $logger->debug('Incoming Alexa Request', [
            'type' => $type,
            'intent' => $intent
        ]);

        $logger->debug('Incoming Alexa Full Request', $request->json()->all());

        switch ($type) {
            case 'LaunchRequest':
                return $this->launch();
            case 'PlayAudio';
                return $this->play();
//            case 'AudioPlayer.PlaybackNearlyFinished':
//                return $this->queueNextTrack();
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
            case 'AMAZON.ResumeIntent':
                return $this->play();
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
            'sessionAttributes' => (object)[],
            'response' => [
                "outputSpeech" => [
                    "type" => "SSML",
                    "ssml" => "<speak>If you want to play music, say 'Alexa, ask pony fm to play'</speak>"
                ],
                'shouldEndSession' => true,
            ],
        ];
    }

    public function unknown()
    {
        return [
            'version' => '1.0',
            'sessionAttributes' => (object)[],
            'response' => [
                "outputSpeech" => [
                    "type" => "SSML",
                    "ssml" => "<speak>Sorry, I don't recognise that command.</speak>"
                ],
                'shouldEndSession' => true,
            ],
        ];
    }

    public function author()
    {
        return [
            'version' => '1.0',
            'sessionAttributes' => (object)[],
            'response' => [
                "outputSpeech" => [
                    "type" => "SSML",
                    "ssml" => "
                        <speak>
                            Pony.fm was built by Pixel Wavelength for Viola to keep all her music in one place.
                        </speak>
                    "
                ],
                'shouldEndSession' => true,
            ],
        ];
    }

    public function play()
    {
        return [
            'version' => '1.0',
            'sessionAttributes' => (object)[],
            'response' => [
                'directives' => [
                    [
                        'type' => 'AudioPlayer.Play',
                        'playBehavior' => 'REPLACE_ALL',
                        'audioItem' => [
                            'stream' => [
                                'url' => 'https://pony.fm/t13840/stream.mp3',
                                'token' => 't13840',
                                'offsetInMilliseconds' => 0,
                            ],
                        ],
                    ],
                ],
                'shouldEndSession' => true,
            ],
        ];
    }

    public function queueNextTrack()
    {
        return [
            'version' => '1.0',
            'sessionAttributes' => (object)[],
            'response' => [
                'directives' => [
                    [
                        'type' => 'AudioPlayer.Play',
                        'playBehavior' => 'ENQUEUE',
                        'audioItem' => [
                            'stream' => [
                                'url' => 'https://pony.fm/t13840/stream.mp3',
                                'token' => 't13840',
                                'expectedPreviousToken' => 'fma',
                                'offsetInMilliseconds' => 0,
                            ],
                        ],
                    ],
                ]
            ],
        ];
    }

    public function stop()
    {
        return [
            'version' => '1.0',
            'sessionAttributes' => (object)[],
            'response' => [
                'directives' => [
                    [
                        'type' => 'AudioPlayer.Stop'
                    ],
                ],
                'shouldEndSession' => true,
            ],
        ];
    }
}
