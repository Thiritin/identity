<?php

namespace App\Services\Hydra;

use Illuminate\Support\Facades\Http;

class Admin
{
    private $url;
    protected $http;

    public function __construct(string $url)
    {
        $this->url = $url;
        $this->http = Http::baseUrl($url)->accept("application/json")->withoutVerifying();
    }

    public function apps()
    {
        return new \App\Services\Hydra\Admin($this->url);
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

    public function deleteRequest(string $path, array $body): bool
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
