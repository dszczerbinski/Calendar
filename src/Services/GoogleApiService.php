<?php


namespace App\Services;

use Google_Client;
use Google_Service_Calendar;
use OpenPayU_Configuration;
use OpenPayU_Exception;
use OpenPayU_Order;
use OpenPayU_Util;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;


class GoogleApiService
{
    public function createGoogle()
    {
        if(!isset($_SESSION)){
            session_start();
        }
        $client = new Google_Client();
        $client->setApplicationName('Calendar');
        $client->addScope('https://www.googleapis.com/auth/calendar');
        $client->setAuthConfig(realpath(dirname(__FILE__, 3)). '/credentials.json'); //'C:\composer\calendar\credentials.json');
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        // Load previously authorized token from a file, if it exists.
        // The file token.json stores the user's access and refresh tokens, and is
        // created automatically when the authorization flow completes for the first
        // time.
        $tokenPath = realpath(dirname(__FILE__, 3)). '/token.json';
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

        return $service;
    }

    public function getEventList(Google_Service_Calendar $service) {
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

        return $events;
    }

    public function createPayUEvent() {
        $_SESSION["clientemail"] = $_POST['clientemail'];
        $_SESSION["date"] = $_POST['date'];
        $_SESSION["specialist"] = $_POST['specialist'];
        $_SESSION["nameSurname"] = $_POST['nameSurname'];
        $_SESSION["phoneNumber"] = $_POST['phoneNumber'];
        $_SESSION["starttime"] = $_POST['time'];
        $_SESSION["startEvent"] = $_SESSION["date"] . "T" . $_SESSION["starttime"] . ":00+01:00";
        $_SESSION["endEvent"] = $_SESSION["startEvent"];
        $_SESSION["endEvent"][12] = intval($_SESSION["endEvent"][12]) + 1;

        $_SESSION["specmail"]="danielspraktyka3@gmail.com";
        switch($_SESSION["specialist"]) {
            case "dr Mariusz Pudzianowski":
                $_SESSION["specmail"]="danielspraktyka@gmail.com";
                break;
            case "dr Adam Małysz":
                $_SESSION["specmail"]="danielspraktyka2@gmail.com";
                break;
            case "dr Jacek Krzynówek":
                break;
        }


        /// PAYU ///
        ///
        OpenPayU_Configuration::setEnvironment('sandbox');

        //set POS ID and Second MD5 Key (from merchant admin panel)
        OpenPayU_Configuration::setMerchantPosId('403458');
        OpenPayU_Configuration::setSignatureKey('12e86e6c9c643dcb05b98fa1b0260906');

        //set Oauth Client Id and Oauth Client Secret (from merchant admin panel)
        OpenPayU_Configuration::setOauthClientId('403458');
        OpenPayU_Configuration::setOauthClientSecret('bacb02b7f09ba32ca6bd26997acfb2fd');

        $pieces = explode(" ", $_SESSION["nameSurname"]);


        $order = array();

        $order['notifyUrl'] = 'https://kalendarz.biznesport.com.pl/orderstatus';
        $order['continueUrl'] = 'https://kalendarz.biznesport.com.pl/orderstatus';

        $order['customerIp'] = $_SERVER['REMOTE_ADDR'];
        $order['merchantPosId'] = OpenPayU_Configuration::getOauthClientId() ? OpenPayU_Configuration::getOauthClientId() : OpenPayU_Configuration::getMerchantPosId();
        $order['description'] = 'Wizyta w gabinecie';
        $order['currencyCode'] = 'PLN';
        $order['totalAmount'] = 1000;
        $order['extOrderId'] = uniqid('', true);

        $order['products'][0]['name'] = 'Wizyta';
        $order['products'][0]['unitPrice'] = 1000;
        $order['products'][0]['quantity'] = 1;

        $order['buyer']['email'] = $_SESSION["clientemail"];
        $order['buyer']['phone'] = $_SESSION["phoneNumber"];
        $order['buyer']['firstName'] = $pieces[0];
        $order['buyer']['lastName'] = $pieces[1];
        $order['buyer']['language'] = 'pl';

        $response = OpenPayU_Order::create($order);

        return $response->getResponse();
    }
    public function createGoogleEvent($service){
        $event = new \Google_Service_Calendar_Event(array(
            'summary' => 'Termin zajęty',
            'location' => 'Gabinet ul. Wojska Polskiego',
            'description' => 'Wizyta u specjalisty ' . $_SESSION["specialist"] . '
Imię i nazwisko klienta: ' . $_SESSION["nameSurname"] . '
Numer telefonu klienta: ' . $_SESSION["phoneNumber"] . '
Email klienta: ' . $_SESSION["clientemail"],

            'start' => array(
                'dateTime' => $_SESSION["startEvent"],
                'timeZone' => 'Europe/Warsaw',
            ),
            'end' => array(
                'dateTime' => $_SESSION["endEvent"],
                'timeZone' => 'Europe/Warsaw',
            ),
            'attendees' => array(
                array('email' => 'daniels@biznesport.pl'),
                array('email' => $_SESSION["specmail"]),
                array('email' => $_SESSION["clientemail"]),
            ),
            'reminders' => array(
                'useDefault' => FALSE,
                'overrides' => array(
                    array('method' => 'email', 'minutes' => 24 * 60),
                    array('method' => 'popup', 'minutes' => 24 * 60),
                ),
            ),
        ));
        $calendarId = 'daniels@biznesport.pl';
        $event = $service->events->insert($calendarId, $event);
    }
}
