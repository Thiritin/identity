<?php

namespace App\Domains\Auth\Services;

use Illuminate\Support\Facades\Http;

class Admin
{
    private $url;

    protected $http;

    public function __construct()
    {
        $this->url = config('services.hydra.admin');
        $this->http = Http::baseUrl($this->url . '/admin')->acceptJson();
    }

    public function apps()
    {
        return new Admin();
    }

    /**
     * Base Methods
     */
    public function getRequest(string $path, array $query = []): array
    {
        return $this->http->get($path, $query)->json();
    }

    public function postRequest(string $path, array $body): array
    {
        return $this->http->post($path, $body)->json();
    }

    public function deleteRequest(string $path, array $body = []): bool
    {
        return $this->http->delete($path, $body)->successful();
    }

    public function patchRequest(string $path, array $body): array
    {
        return $this->http->patch($path, $body)->json();
    }

    public function putRequest(string $path, array $body): array
    {
        return $this->http->put($path, $body)->json();
    }
}
