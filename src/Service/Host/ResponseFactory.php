<?php

declare(strict_types=1);

namespace FD\LogViewer\Service\Host;

use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Throwable;

class ResponseFactory
{
    /**
     * @throws Throwable
     */
    public function toStreamedResponse(HttpClientInterface $httpClient, ResponseInterface $httpResponse): StreamedResponse
    {
        return new StreamedResponse(
            function () use ($httpClient, $httpResponse) {
                $outputStream = fopen('php://output', 'wb');
                assert(is_resource($outputStream));

                $chunked = in_array('chunked', $httpResponse->getHeaders(false)['transfer-encoding'] ?? []);
                foreach ($httpClient->stream($httpResponse) as $chunk) {
                    $content = $chunk->getContent();
                    if ($chunked) {
                        $length = dechex(strlen($content));
                        fwrite($outputStream, "{$length}\r\n{$content}\r\n");
                    } else {
                        fwrite($outputStream, $content);
                    }
                }
                if ($chunked) {
                    fwrite($outputStream, "0\r\n\r\n");
                }
                fclose($outputStream);
            },
            $httpResponse->getStatusCode(),
            array_filter($httpResponse->getHeaders(false), function ($key) {
                return $key !== 'set-cookie';
            }, ARRAY_FILTER_USE_KEY)
        );
    }
}
