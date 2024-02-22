<?php

declare(strict_types=1);

namespace Kameleoon\Network;

use Kameleoon\Network\NetProvider;
use Kameleoon\Network\Response;
use Kameleoon\Network\ResponseContentType;
use Exception;

class NetProviderImpl implements NetProvider
{
    private string $kameleoonWorkDir;

    public function __construct(string $kameleoonWorkDir)
    {
        $this->kameleoonWorkDir = $kameleoonWorkDir;
    }

    private static function getContent($body, int $responseContentType)
    {
        switch ($responseContentType) {
            case ResponseContentType::TEXT:
                return $body;
            case ResponseContentType::JSON:
                return json_decode($body);
            default:
                return null;
        }
    }

    public function callSync(SyncRequest $request): Response
    {
        $ch = curl_init($request->url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request->httpMethod);
        curl_setopt($ch, CURLOPT_URL, $request->url);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, $request->timeout);
        if (!empty($request->headers)) {
            $headers = [];
            foreach ($request->headers as $headerName => $headerValue) {
                $headers[] = sprintf("%s: %s", $headerName, $headerValue);
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        if ($request->responseContentType !== ResponseContentType::NONE) {
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        }
        if (!is_null($request->body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $request->body);
        }
        $body = curl_exec($ch);
        $err = curl_error($ch);
        if (empty($err)) {
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            try {
                $content = self::getContent($body, $request->responseContentType);
                $response = new Response(null, $code, $content);
            } catch (Exception $ex) {
                $response = new Response($ex, $code, null);
            }
        } else {
            $response = new Response($err, null, null);
        }
        curl_close($ch);
        return $response;
    }

    public function callAsync(AsyncRequest $request): string
    {
        $requestText = "curl -s -S --tlsv1.2 --tls-max 1.2 -X POST";
        if ($request->headers !== null) {
            foreach ($request->headers as $headerName => $headerValue) {
                $requestText .= sprintf(' -H "%s: %s"', $headerName, $headerValue);
            }
        }
        $requestText .= sprintf(' "%s"', $request->url);
        if ($request->body !== null) {
            $requestText .= sprintf(' -d "%s"', $request->body);
        }
        $requestText .= ' & r=${r:=0};((r=r+1));if [ $r -eq 64 ];then r=0;wait;fi;' . PHP_EOL;
        $path = $this->getRequestsFilePath();
        file_put_contents($path, $requestText, FILE_APPEND | LOCK_EX);
        return $path;
    }

    private function getRequestsFilePath(): string
    {
        return sprintf("%srequests-%d.sh", $this->kameleoonWorkDir, floor(time() / 60));
    }
}
