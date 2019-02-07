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

    // php bin/console server:run


    private $httpHeaders = [
        'Access-Control-Allow-Headers' => 'Access-Control-Allow-Origin, Access-Control-Allow-Headers, Origin,Accept, X-Requested-With, Content-Type, Access-Control-Request-Method, Access-Control-Request-Headers',
        'Access-Control-Allow-Methods' => 'GET, POST, PATCH, PUT, DELETE, OPTIONS',
        'Access-Control-Allow-Origin' => '*',
        // 'Access-Control-Allow-Origin' => 'http://localhost:3000',
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
        foreach ( $crawler as $domElement ){
            $arr [] = $domElement->nodeName;
        }

        return new JsonResponse([$result, $crawler]);

    }


    /**
     * @Route("/rss", name="rss")
     * @param Request $request
     * @return JsonResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getRssFeed(Request $request)
    {
        try {
            $url = $request->get('url');
            $guzzle = new Client();
            $rssFeed = $guzzle->request('GET', $url);
            $content = $rssFeed->getBody()->getContents();
            $simpleXMLE = simplexml_load_string(
                $content,
                'SimpleXMLElement',
                LIBXML_NOCDATA | LIBXML_NOBLANKS
            );

            return new JsonResponse(
                [
                    'url' => $request->get('url'),
                    'rss' => $simpleXMLE,
                    'status' => 'ok',
                ],
                JsonResponse::HTTP_CREATED,
                $this->httpHeaders
            );

        } catch (Exception $e) {
            return new JsonResponse(
                [
                    'message' => 'Something went wrong',
                    'status' => '500',
                ],
                JsonResponse::HTTP_CREATED,
                $this->httpHeaders
            );
        }

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
            $resultEmail = false;

            if ($requestArray) {
                $emailRecipient = trim($requestArray['emailRecipient']);
//                return new JsonResponse(['request' => 'OK'], '200', $this->httpHeaders);
                $senderName = trim($requestArray['name']);
                $senderEmail = trim($requestArray['email']);
                $senderMessage = trim($requestArray['message']);

                $resultEmail = $this->sendEmail($emailRecipient, $senderName, $senderEmail, $senderMessage);

                $responseTest = [
                    'emailRecipient' => $emailRecipient,
                    'senderName' => $senderName,
                    'senderEmail' => $senderEmail,
                    'senderMessage' => $senderMessage,
                    'resultEmail' => $resultEmail,
                ];

                return new JsonResponse(['request' => $requestArray], '200', $this->httpHeaders);


                if ($resultEmail) {
                    // return new JsonResponse(['request' => 'OK'], '200', $this->httpHeaders);
                    return new JsonResponse(['request' => $requestArray], '200', $this->httpHeaders);
                }

            } else {
                return new JsonResponse(['request' => 'email in process...'], '200', $this->httpHeaders);
            }


        } catch (\Exception $exception) {
            return new JsonResponse(['request' => $requestArray], '404', $this->httpHeaders);
        }

    }


    public function sendEmail($emailRecipient, $senderName, $senderEmail, $senderMessage)
    {

        $sendTo = '';
        switch ($emailRecipient) {
            case 'father Tikhon':
                // $sendTo = 'ek35mm@gmail.com';
                $sendTo = 'rocor.tikhon@gmail.com';
                break;
            case 'father Anatoly':
                $sendTo = 'evgene.pozniak@gmail.com';
                //$sendTo = 'setruta@mail.ru';
                break;
            default:
        }

        $message = $senderMessage . ' (Email: ' . $senderEmail . ')';
        $subject = 'Email from: ' . $senderName;

        return mail($sendTo, $subject, $message);
    }


}
