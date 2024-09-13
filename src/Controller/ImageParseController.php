<?php

namespace App\Controller;

use App\Form\UrlType;
use DOMElement;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class ImageParseController extends AbstractController
{
    #[Route('/', name: 'image-parse')]
    public function parse(Request $request)
    {
        $form = $this->createForm(UrlType::class);
        $form->handleRequest($request);

        $totalSize = 0;
        $imagesData = [];
        if ($form->isSubmitted() && $form->isValid()) {
            $url = $form->get('url')->getData();
            $client = new Client();
            try {
                $crawler = new Crawler($client->get($url)->getBody()->getContents());

                $images = $crawler->filter('img');
                /** @var DOMElement $img */
                foreach ($images as $img) {
                    $imgSrc = $img->getAttribute('src');
                    if ($imgSrc) {
                        $imgUrl = (parse_url($imgSrc, PHP_URL_HOST)) ? $imgSrc : $url . '/' . ltrim($imgSrc, '/');
                        try {
                            $imageResponse = $client->head($imgUrl);
                            $contentLength = $imageResponse->getHeaderLine('Content-Length');
                            $imageSize = $contentLength ? (int)$contentLength : 0;
                            $totalSize += $imageSize;
                            $imagesData[] = [
                                'url' => $imgUrl,
                                'size' => $imageSize / 1024 / 1024
                            ];
                        } catch (Exception $e) {
                            continue;
                        }
                    }
                }
                $imageCount = $images->count();
            } catch (Exception $e) {
                throw new \HttpException($e->getMessage(), $e->getCode());
            }
        }

        return $this->render('base.html.twig', [
            'form' => $form->createView(),
            'imageCount' => $imageCount ?? 0,
            'imagesData' => $imagesData,
            'totalSize' => $totalSize / 1024 / 1024,
            'error' => $error ?? null,
        ]);
    }
}