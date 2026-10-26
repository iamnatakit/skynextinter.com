<?php
namespace App\Libraries\Platform;

use RuntimeException;

class Http
{
    /** Targets are supplied only by fixed service adapters, never by visitors. */
    public static function request(string $url,string $method,array $headers=[],string|array|null $body=null): array
    {
        $payload = is_array($body) ? http_build_query($body) : $body;
        $response = false;
        $status = 0;

        $curlErr = null;
        if (function_exists('curl_init')) {
            $curl = curl_init($url);
            $curlOpts = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                CURLOPT_USERAGENT => 'SkyNext-Platform/1.0'
            ];
            curl_setopt_array($curl, $curlOpts);
            if ($payload !== null) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
            }
            $response = curl_exec($curl);
            $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($response === false) {
                $curlErr = curl_error($curl) . ' (code ' . curl_errno($curl) . ')';
            }
            curl_close($curl);
        } else {
            $cleanHeaders = implode("\r\n", $headers);
            if (!preg_match('/user-agent/i', $cleanHeaders)) {
                $cleanHeaders .= "\r\nUser-Agent: SkyNext-Platform/1.0";
            }
            if ($payload !== null && !preg_match('/content-length/i', $cleanHeaders)) {
                $cleanHeaders .= "\r\nContent-Length: " . strlen($payload);
            }
            $opts = [
                'http' => [
                    'method' => $method,
                    'header' => $cleanHeaders,
                    'content' => $payload,
                    'timeout' => 30,
                    'ignore_errors' => true
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
            $context = stream_context_create($opts);
            $response = @file_get_contents($url, false, $context);
            if (isset($http_response_header) && is_array($http_response_header)) {
                foreach ($http_response_header as $hdr) {
                    if (preg_match('#^HTTP/\d+\.\d+\s+(\d+)#i', $hdr, $m)) {
                        $status = (int)$m[1];
                    }
                }
            }
            if ($response === false) {
                $lastErr = error_get_last();
                $curlErr = $lastErr['message'] ?? 'stream connection failed';
            }
        }

        if ($response === false) {
            $msg = 'บริการภายนอกไม่ตอบรับ กรุณาตรวจสถานะการเชื่อมต่อ';
            if ($curlErr) {
                $msg .= ' (' . $curlErr . ')';
            }
            throw new RuntimeException($msg);
        }

        $json = json_decode($response, true);
        if ($status < 200 || $status >= 300) {
            $errDetail = $json['error']['message'] ?? (is_string($json['error']??null)?$json['error']:null) ?? $json['message'] ?? '';
            if (!$errDetail && is_string($response) && trim($response) !== '') {
                $errDetail = mb_substr(trim(strip_tags($response)), 0, 300);
            }
            if ($errDetail) {
                throw new RuntimeException('ผู้ให้บริการแจ้งข้อผิดพลาด: ' . $errDetail . ' (HTTP ' . $status . ')');
            }
            throw new RuntimeException('บริการภายนอกไม่ตอบรับ (HTTP ' . $status . ') กรุณาตรวจสถานะในบัญชีผู้ให้บริการ');
        }

        if (!is_array($json)) {
            throw new RuntimeException('ข้อมูลตอบกลับจากผู้ให้บริการไม่ถูกต้อง');
        }
        return $json;
    }
}

