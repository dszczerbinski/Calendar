<?php


namespace App\Services;

use Google_Client;
use Google_Service_Calendar;


class GoogleApiService
{
    public function createGoogle()
    {
        $client = new Google_Client();
        $client->setApplicationName('Calendar');
        $client->addScope('https://www.googleapis.com/auth/calendar');
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
        $calendarId = 'daniels@biznesport.pl';
        $optParams = array(
            'maxResults' => 10,
            'orderBy' => 'startTime',
            'singleEvents' => true,
            'timeMin' => date('c'),
        );
        $results = $service->events->listEvents($calendarId, $optParams);
        $events = $results->getItems();

        if (isset($_POST['register'])) {
            $clientemail = $_POST['clientemail'];
            $date = $_POST['date'];
            $specialist = $_POST['specialist'];
            $nameSurname = $_POST['nameSurname'];
            $phoneNumber = $_POST['phoneNumber'];
            $starttime = $_POST['time'];
            $startEvent = $date . "T" . $starttime . ":00+01:00";
            $endEvent = $startEvent;
            $endEvent[12] = intval($endEvent[12]) + 1;


            $event = new \Google_Service_Calendar_Event(array(
                'summary' => 'Termin zajęty',
                'location' => 'Gabinet ul. Wojska Polskiego',
                'description' => 'Wizyta u specjalisty ' . $specialist . '
Imię i nazwisko klienta: ' . $nameSurname . '
Numer telefonu klienta: ' . $phoneNumber . '
Email klienta: ' . $clientemail,

                'start' => array(
                    'dateTime' => $startEvent,
                    'timeZone' => 'Europe/Warsaw',
                ),
                'end' => array(
                    'dateTime' => $endEvent,
                    'timeZone' => 'Europe/Warsaw',
                ),
                'attendees' => array(
                    array('email' => 'daniels@biznesport.pl'),
                    array('email' => 'danielspraktyka@gmail.com'),
                    array('email' => $clientemail),
                ),
//                'reminders' => array(
//                    'useDefault' => FALSE,
//                    'overrides' => array(
//                        array('method' => 'email', 'minutes' => 24 * 60),
//                        array('method' => 'popup', 'minutes' => 10),
//                    ),
//                ),
            ));

            $calendarId = 'daniels@biznesport.pl';
            $event = $service->events->insert($calendarId, $event);
            header("Refresh:0");
        }
        return $events;
    }
}
