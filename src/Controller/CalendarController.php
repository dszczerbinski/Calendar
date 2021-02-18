<?php
// src/Controller/CalendarController.php
namespace App\Controller;

use App\Services\GoogleApiService;
use Doctrine\ORM\EntityManagerInterface;
use Google_Client;
use Google_Service_Calendar;
use OpenPayU_Order;
use OpenPayU_Util;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Constraints\Date;


require_once realpath(dirname(__FILE__, 3)). '/vendor/autoload.php';        //'C:\composer\calendar\vendor\autoload.php';

class CalendarController extends AbstractController
{
    /**
     * @var GoogleApiService
     */
    private $googleAPI;

    public function __construct(GoogleApiService $googleAPI)
    {
        $this->googleAPI = $googleAPI;
    }

    /**
     * @Route("/")
     */
    public function calendar(): Response
    {
        $googleService = $this->googleAPI->createGoogle();
        $ip = $_SERVER['REMOTE_ADDR'];
        $eventsList = $this->googleAPI->getEventList($googleService);

        if (isset($_POST['register'])) {
            //session_start();
            $_SESSION["registered"] = "yes";

            $response = $this->googleAPI->createPayUEvent();
            return $this->redirect($response->redirectUri);
        }
        return $this->render('calendar.html.twig', [
            'events' => $eventsList,
            'ip' => $ip,
        ]);
    }

    /**
     * @Route("/orderstatus")
     */
    public function success(): Response
    {
        session_start();
        if(isset($_SESSION["registered"])){
            if(isset($_GET['error'])){
                echo  nl2br ("Transakcja nieudana, spróbuj ponownie! \n Przekierowanie na strone główna nastąpi za 5 sekund...");
                session_unset();
                header( "refresh:5;url=https://kalendarz.biznesport.com.pl" );
            }
            else{
                $googleService = $this->googleAPI->createGoogle();
                $eventsList = $this->googleAPI->getEventList($googleService);
                $this->googleAPI->createGoogleEvent($googleService);
                echo  nl2br ("Transakcja udana! \n 
            Jeśli posiadasz Kalendarz Google to wizyta została automatycznie dodana 
            do twojego kalendarza i otrzymasz powiadomienie dzień przed o umówionej wizycie \n
            Przekierowanie na strone główna nastąpi za 5 sekund...");
            }
            session_unset();
            header( "refresh:5;url=https://kalendarz.biznesport.com.pl" );
        }
        else {
            echo "Nie zarejestrowano żadnej transakcji, wypełnij formularz jeszcze raz i opłać wizytę";
        }

        return $this->render('base.html.twig');
    }
}