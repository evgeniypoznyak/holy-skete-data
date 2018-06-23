<?php

namespace App\Controller;

use GuzzleHttp\Client;
use function GuzzleHttp\Psr7\parse_header;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomePageController extends Controller
{

    private $httpHeaders = [
        'Access-Control-Allow-Headers' => 'Access-Control-Allow-Origin, Access-Control-Allow-Headers, Origin,Accept, X-Requested-With, Content-Type, Access-Control-Request-Method, Access-Control-Request-Headers',
        'Access-Control-Allow-Methods' => 'GET, POST, PATCH, PUT, DELETE, OPTIONS',
//        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Origin' => 'http://localhost:3000',
        'Content-Type' => 'application/json;charset=UTF-8',
        'Access-Control-Allow-Credentials' => 'true',
    ];

    /**
     * @Route("/walker", name="walker")
     */
    public function testWebsiteWalker()
    {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://azbyka.ru/days/");
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);

        $result = preg_replace(
            "#(<\s*a\s+[^>]*href\s*=\s*[\"'])(?!http)([^\"'>]+)([\"'>]+)#",
            '$1https://azbyka.ru/days/$2$3',
            $result
        );

//        $guzzle  = new Client();
//        $content =  $guzzle->get('https://azbyka.ru/days/');
        $crawler = new Crawler($result);
        $arr = [];
        foreach ($crawler as $domElement) {
            $arr [] = $domElement->nodeName;
        }

        return new JsonResponse([$result, $crawler]);

    }


    /**
     * @Route("/rss", name="rss")
     */
    public function getRssFeed(Request $request)
    {
        $url = $request->get('url');
        $guzzle = new Client();
        $rssFeed = $guzzle->request('GET', $url);
        $parsedXML = simplexml_load_string($rssFeed->getBody()->getContents());

        if ($parsedXML->channel) {
            $result = $parsedXML->channel;
        }

//        $httpHeaders = [
//            'Access-Control-Allow-Headers' => 'Access-Control-Allow-Origin, Access-Control-Allow-Headers, Origin,Accept, X-Requested-With, Content-Type, Access-Control-Request-Method, Access-Control-Request-Headers',
//            'Access-Control-Allow-Methods' => 'GET, POST, PATCH, PUT, DELETE, OPTIONS',
//            'Access-Control-Allow-Origin' => '*',
//            'Content-Type' => 'application/json;charset=UTF-8',
//            'Access-Control-Allow-Credentials' => 'true',
//        ];

        $response = new JsonResponse($result, '200', $this->$httpHeaders);

        return $response;

    }

    /**
     * @Route("/name/{name}", name="name")
     */
    public function nameAction($name = '')
    {
        echo 'Name: ' . $name;


        //  return new Response(base.html);
        return $this->render('base.html.twig');
        //return $this->render('');
    }


    /**
     * @Route("/email", name="email")
     */
    public function userEmailDataProcess(Request $request)
    {
        // $request->headers->get('referer');
        // $tempArray = explode('/', $request->headers->get('referer'));
        // $uri = end($tempArray);

        try {
            $requestArray = json_decode($request->getContent(), true);

            $resultEmail = 'test';

            $resultEmail = $this->sendEmail(
                $requestArray['emailRecipient'],
                $requestArray['name'],
                $requestArray['email'],
                $requestArray['message']
            );

            $responseTest = [
                'emailRecipient' => $requestArray['emailRecipient'],
                'senderName' => $requestArray['name'],
                'senderEmail' => $requestArray['email'],
                'senderMessage' => $requestArray['message'],
                'resultEmail' => $resultEmail,
            ];


            return new JsonResponse(['request' => $responseTest], '200', $this->httpHeaders);

        } catch (\Exception $exception) {
            return new JsonResponse(['request' => 'BAD REQUEST!'], '404', $this->httpHeaders);
        }

    }


    public function sendEmail($emailRecipient, $senderName, $senderEmail, $senderMessage)
    {

        $sendTo = '';
        switch ($emailRecipient) {
            case 'father Tikhon':
                $sendTo = 'ek35mm@gmail.com';
                break;
            case 'father Anatoly':
                $sendTo = 'evgene.pozniak@gmail.com';
                break;
            default:
        }

        $message = $senderMessage . ' (Email: ' . $senderEmail . ')';
        $subject = 'Email from: ' . $senderName;

     return  mail($sendTo, $subject, $senderMessage);
    }


}
