<?php

namespace AliMPay\Utils;

class QRCodeGenerator
{
    private $logger;
    private $apiEndpoint = 'https://api.qrserver.com/v1/create-qr-code/';

    public function __construct()
    {
        $this->logger = Logger::getInstance();
    }

    public function generateQRCode(string $transferUrl, string $savePath = null, int $size = 300): string
    {
        $savePath = $savePath ?: $this->defaultSavePath();
        $this->ensureDirectory(dirname($savePath));

        $qrImageData = $this->downloadQrCode($transferUrl, $size);

        if (file_put_contents($savePath, $qrImageData) === false) {
            throw new \RuntimeException('Failed to save QR code to file');
        }

        $this->logger->info('QR code generated successfully', [
            'file_path' => $savePath,
            'size' => $size
        ]);

        return $savePath;
    }

    public function generateQRCodeBase64(string $transferUrl, int $size = 300): string
    {
        return base64_encode($this->downloadQrCode($transferUrl, $size));
    }

    public function generateQRCodeUrl(string $transferUrl, int $size = 300): string
    {
        return $this->buildQrApiUrl($transferUrl, $size);
    }

    public function generate(string $text): string
    {
        return $this->generateQRCodeBase64($text, 200);
    }

    private function defaultSavePath(): string
    {
        return __DIR__ . '/../../qrcodes/qrcode_' . date('YmdHis') . '_' . mt_rand(1000, 9999) . '.png';
    }

    private function ensureDirectory(string $directory): void
    {
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new \RuntimeException('Failed to create directory: ' . $directory);
        }
    }

    private function buildQrApiUrl(string $transferUrl, int $size): string
    {
        $safeSize = max(120, min($size, 1000));

        return $this->apiEndpoint . '?' . http_build_query([
            'size' => $safeSize . 'x' . $safeSize,
            'data' => $transferUrl
        ]);
    }

    private function downloadQrCode(string $transferUrl, int $size): string
    {
        $url = $this->buildQrApiUrl($transferUrl, $size);

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_USERAGENT => 'AliMPay/1.0'
            ]);

            $data = curl_exec($ch);
            $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($data !== false && $statusCode >= 200 && $statusCode < 300) {
                return $data;
            }

            throw new \RuntimeException('Failed to download QR code: ' . ($error ?: 'HTTP ' . $statusCode));
        }

        $data = @file_get_contents($url);
        if ($data === false) {
            throw new \RuntimeException('Failed to download QR code and curl extension is unavailable');
        }

        return $data;
    }
}
