<?php

namespace App\Traits;

use Vinkla\Hashids\HashidsManager;

/**
 * Bind a model to a route based on the hash of
 * its id (or other specified key).
 *
 * @package App
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HashidsRoutable
{
    /**
     * Instantiate appropriate Hashids connection
     *
     * @return \Hashids\Hashids
     */
    protected function getHashidsInstance(): \Hashids\Hashids
    {
        return app(HashidsManager::class)->connection($this->getHashidsConnection());
    }

    /**
     * Determine Hashids connection to use
     *
     * @return null|string
     */
    protected function getHashidsConnection(): ?string
    {
        return null;
    }

    /**
     * Encode a parameter
     *
     * @param  int  $parameter
     * @return string
     */
    protected function encodeParameter($parameter): string
    {
        return $this->getHashidsInstance()->encode($parameter);
    }

    /**
     * Decode parameter
     *
     * @param  string  $parameter
     * @return null|int Decoded value or null on failure
     */
    protected function decodeParameter($parameter): ?int
    {
        if (count($decoded = $this->getHashidsInstance()->decode($parameter)) !== 1) {
            // We are expecting a single value from the decode parameter,
            // if none or multiple are returned we just fail
            return null;
        }

        return $decoded[0];
    }

    /**
     * Instruct implicit route binding to use
     * our custom hashed parameter.
     *
     * This is long and crazy to avoid parameters
     * collisions.
     *
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return 'hashidsRoutableHashParam';
    }

    /**
     * Determine which attribute to encode
     *
     * @return string
     */
    public function getRouteHashKeyName(): string
    {
        return $this->getKeyName();
    }

    /**
     * Get beginning value
     *
     * @return string
     */
    public function getRouteHashKey(): string
    {
        return $this->getAttribute($this->getRouteHashKeyName());
    }

    /**
     * Encode real parameter to url value for bindings
     *
     * @return string
     */
    public function getHashidsRoutableHashParamAttribute(): string
    {
        return $this->encodeParameter($this->getRouteHashKey());
    }
}
