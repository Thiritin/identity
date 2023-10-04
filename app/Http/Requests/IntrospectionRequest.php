<?php

namespace App\Http\Requests;

use App\Models\App;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;

class IntrospectionRequest extends FormRequest
{
    private App|null $client = null;

    public function rules(): array
    {
        return [
            "client_id" => "required|string",
            "scope" => "nullable|string",
            "token" => "required|string",
        ];
    }

    public function authorize(): bool
    {
        $client = $this->getApp();
        // Reject as unauthrorized if not authenticated.
        if (is_null($client)) {
            throw new AuthenticationException();
        }
        // Legacy, client_secret came later so some apps could have this as null.
        if (is_null($client->client_secret)) {
            return false;
        }
        if (Hash::check($this->bearerToken(), $client->client_secret)) {
            return true;
        }
        return false;
    }

    public function getApp()
    {
        if ($this->client instanceof App) {
            return $this->client;
        }

        $this->client = App::firstWhere('client_id', "=", $this->post('client_id'));
        return $this->client;
    }
}
