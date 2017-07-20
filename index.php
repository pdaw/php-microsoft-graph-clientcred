<?php

use Microsoft\Graph\Core\GraphConstants;
use Noodlehaus\Config;
use Silex\Application;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Microsoft\Graph\Graph;
use Microsoft\Graph\Model\Calendar as CalendarModel;
use Microsoft\Graph\Model\Event as EventModel;
use GuzzleHttp\Client as GuzzleClient;
use Silex\Provider\TwigServiceProvider;

require_once __DIR__ . '/vendor/autoload.php';

$app = prepareApplication();

$app->get('/', function () use ($app) {
    $accessToken = getAccessToken($app);

    $graph = new Graph();
    $graph->setAccessToken($accessToken);

    # an effort to configure own options for a http client
    # (there is no other way with this sdk)
    $guzzleClient = new GuzzleClient([
        'base_uri' => $app['config']['resource'],
        # only for demo/test purposes to make it easy and not cert-dependent
        'verify' => false,
        'headers' => [
            'Host' => $app['config']['resource'],
            'Content-Type' => 'application/json',
            'SdkVersion' => 'Graph-php-' . GraphConstants::SDK_VERSION,
            'Authorization' => 'Bearer ' . $accessToken
        ]
    ]);

    /** @var CalendarModel[] $calendars */
    $calendars = $graph->createCollectionRequest("GET",
        "/users/" . $app['config']['user_principal_name'] . "/calendars")
        ->setReturnType(CalendarModel::class)
        ->execute($guzzleClient);

    $calendarsForView = [];

    foreach ($calendars as $calendar) {
        $events = $graph->createCollectionRequest(
            "GET",
            '/users/' . $app['config']['user_principal_name'] . "/calendars/" . $calendar->getId() . '/events'
        )
            ->setReturnType(EventModel::class)
            ->execute();

        $eventsForView = [];

        /** @var EventModel $event */
        foreach ($events as $event) {
            $eventsForView[] = [
                'subject' => $event->getSubject(),
                'start' => $event->getStart()->getDateTime(),
                'end' => $event->getEnd()->getDateTime()
            ];
        }

        $calendarsForView[] = [
            'name' => $calendar->getName(),
            'events' => $eventsForView
        ];
    }

    return $app['twig']->render('index.twig', [
        'calendars' => $calendarsForView,
    ]);
});

$app->run();

function prepareApplication(): Application
{
    $app = new Application();
    $app['debug'] = true;

    // configuration
    $app['config'] = new Config('config.yml');

    // logging system
    $app->register(new MonologServiceProvider(), [
        'monolog.logfile' => __DIR__ . '/development.log',
    ]);

    // template engine
    $app->register(new TwigServiceProvider(), [
        'twig.path' => __DIR__,
    ]);

    // session
    $app->register(new SessionServiceProvider());

    return $app;
}

function getAccessToken(Application $app): string
{
    if (null === $accessToken = $app['session']->get('access_token')) {
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'cache-control' => 'no-cache',
        ];

        $data = [
            'grant_type' => $app['config']['grant_type'],
            'client_id' => $app['config']['client_id'],
            'client_secret' => $app['config']['client_secret'],
            'resource' => $app['config']['resource']
        ];

        $resourceUrl = 'https://login.microsoftonline.com/' . $app['config']['tenant_id'] . '/oauth2/token';

        try {
            $request = Requests::post($resourceUrl, $headers, $data);
            $body = json_decode($request->body);
            $accessToken = $body->access_token;

            $app['session']->set('access_token', $accessToken);

            return $accessToken;
        } catch (Exception $e) {
            throw new Exception('There is a problem with getting an access token', 0, $e);
        }
    }

    return $accessToken;
}
