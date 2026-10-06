<?php

declare(strict_types=1);

class ApiException extends RuntimeException
{
    private int $statusCode;
    private array $payload;

    public function __construct(int $statusCode, string $message, array $payload = [], ?Throwable $previous = null)
    {
        parent::__construct($message, $statusCode, $previous);
        $this->statusCode = $statusCode;
        $this->payload = $payload;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }
}

class ApiClient
{
    private string $baseUrl;
    private int $timeout;

    public function __construct(string $baseUrl, int $timeout = 20)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
    }

    public function request(string $method, string $path, array $options = []): array
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('Ekstensi cURL belum aktif di PHP.');
        }

        $method = strtoupper($method);
        $url = $this->buildUrl($path, $options['query'] ?? []);
        $headers = [
            'Accept: application/json',
        ];

        if (!empty($options['token'])) {
            $headers[] = 'Authorization: Bearer ' . trim((string) $options['token']);
        }

        $curl = curl_init($url);
        if ($curl === false) {
            throw new RuntimeException('Gagal membuat koneksi ke backend.');
        }

        $payload = $options['body'] ?? null;
        $files = $options['files'] ?? [];
        $useMultipart = !empty($options['multipart']) || !empty($files);

        $curlOptions = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
        ];

        if (in_array($method, ['GET', 'HEAD'], true)) {
            unset($curlOptions[CURLOPT_CUSTOMREQUEST]);
        } elseif ($useMultipart) {
            $multipart = [];

            if (is_array($payload)) {
                foreach ($payload as $key => $value) {
                    if (is_bool($value)) {
                        $multipart[$key] = $value ? '1' : '0';
                        continue;
                    }

                    if (is_scalar($value) || $value === null) {
                        $multipart[$key] = (string) ($value ?? '');
                    }
                }
            }

            foreach ($files as $field => $file) {
                if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                    continue;
                }

                if (!class_exists(CURLFile::class)) {
                    throw new RuntimeException('Ekstensi CURLFile belum aktif di PHP.');
                }

                $multipart[$field] = new CURLFile(
                    (string) $file['tmp_name'],
                    (string) ($file['type'] ?? 'application/octet-stream'),
                    (string) ($file['name'] ?? basename((string) $file['tmp_name']))
                );
            }

            $curlOptions[CURLOPT_POSTFIELDS] = $multipart;
        } elseif ($payload !== null) {
            $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($json === false) {
                throw new RuntimeException('Gagal memproses data permintaan.');
            }

            $headers[] = 'Content-Type: application/json';
            $curlOptions[CURLOPT_HTTPHEADER] = $headers;
            $curlOptions[CURLOPT_POSTFIELDS] = $json;
        }

        curl_setopt_array($curl, $curlOptions);
        $response = curl_exec($curl);

        if ($response === false) {
            $error = curl_error($curl);
            throw new RuntimeException('Gagal menghubungi backend: ' . $error);
        }

        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Respons backend tidak valid.');
        }

        if ($status < 200 || $status >= 300) {
            $message = (string) ($decoded['message'] ?? 'Permintaan gagal diproses.');
            throw new ApiException($status, $message, $decoded);
        }

        return $decoded;
    }

    public function get(string $path, array $options = []): array
    {
        return $this->request('GET', $path, $options);
    }

    public function post(string $path, array $options = []): array
    {
        return $this->request('POST', $path, $options);
    }

    public function put(string $path, array $options = []): array
    {
        return $this->request('PUT', $path, $options);
    }

    public function patch(string $path, array $options = []): array
    {
        return $this->request('PATCH', $path, $options);
    }

    public function delete(string $path, array $options = []): array
    {
        return $this->request('DELETE', $path, $options);
    }

    private function buildUrl(string $path, array $query = []): string
    {
        $path = '/' . ltrim($path, '/');
        $url = $this->baseUrl . $path;

        if (!empty($query)) {
            $filtered = [];
            foreach ($query as $key => $value) {
                if ($value === null || $value === '') {
                    continue;
                }

                $filtered[$key] = $value;
            }

            if (!empty($filtered)) {
                $url .= '?' . http_build_query($filtered);
            }
        }

        return $url;
    }
}
