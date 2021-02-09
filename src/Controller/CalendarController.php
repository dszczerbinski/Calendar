<?php
// src/Controller/CalendarController.php
namespace App\Controller;

use Google_Client;
use Google_Service_Calendar;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

require_once 'C:\composer\calendar\vendor\autoload.php';
//define('CALENDAR_ID', 'daniels@biznesport.pl');
//define('CREDENTIALS_PATH', 'C:\composer\calendar\credentials.json');
//define('SCOPES', Google_Service_Calendar::CALENDAR);

class CalendarController extends AbstractController
{
    /**
     * @Route("/")
     */
    public function calendar(): Response
    {
        $client = new Google_Client();
        $client->setApplicationName('Google Calendar API PHP Quickstart');
        $client->setScopes(Google_Service_Calendar::CALENDAR_READONLY);
        $client->setAuthConfig('C:\composer\calendar\credentials.json');
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        // Load previously authorized token from a file, if it exists.
        // The file token.json stores the user's access and refresh tokens, and is
        // created automatically when the authorization flow completes for the first
        // time.
        $tokenPath = 'C:\composer\calendar\token.json';
        if (file_exists($tokenPath)) {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $client->setAccessToken($accessToken);
        }

        // If there is no previous token or it's expired.
        if ($client->isAccessTokenExpired()) {
            // Refresh the token if possible, else fetch a new one.
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            } else {
                // Request authorization from the user.
                $authUrl = $client->createAuthUrl();
                printf("Open the following link in your browser:\n%s\n", $authUrl);
                print 'Enter verification code: ';
                $authCode = trim(fgets(STDIN));

                // Exchange authorization code for an access token.
                $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
                $client->setAccessToken($accessToken);

                // Check to see if there was an error.
                if (array_key_exists('error', $accessToken)) {
                    throw new Exception(join(', ', $accessToken));
                }
            }
            // Save the token to a file.
            if (!file_exists(dirname($tokenPath))) {
                mkdir(dirname($tokenPath), 0700, true);
            }
            file_put_contents($tokenPath, json_encode($client->getAccessToken()));
        }
        // Get the API client and construct the service object.
        $service = new Google_Service_Calendar($client);

// Print the next 10 events on the user's calendar.
        $calendarId = 'primary';
        $optParams = array(
            'maxResults' => 10,
            'orderBy' => 'startTime',
            'singleEvents' => true,
            'timeMin' => date('c'),
        );
        $results = $service->events->listEvents($calendarId, $optParams);
        $events = $results->getItems();


        //ROZDZIELANIE SPECJALISTOW NA TABLICE
//        $specialistA=[];
//        $specialistB=[];
//        $specialistC=[];

//        //SPECJALISTA A
//        foreach ($events as $event) {
//            foreach($event->attendees as $attendee){
//                if($attendee->email==='danielspraktyka@gmail.com'){
//                    array_push($specialistA, $event);
//                }
//                if($attendee->email==='danielspraktyka2@gmail.com'){
//                    array_push($specialistB, $event);
//                }
//                if($attendee->email==='danielspraktyka3@gmail.com'){
//                    array_push($specialistC, $event);
//                }
//            }
//        }


        return $this->render('calendar.html.twig', [
            'events' => $events,

        ]);
    }
}