<?php
// src/Controller/CalendarController.php
namespace App\Controller;

use App\Services\GoogleApiService;
use Doctrine\ORM\EntityManagerInterface;
use Google_Client;
use Google_Service_Calendar;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Constraints\Date;


require_once 'C:\composer\calendar\vendor\autoload.php';

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
        $events=$this->googleAPI->createGoogle();
        return $this->render('calendar.html.twig', [
            'events' => $events,
            ]);
    }
}