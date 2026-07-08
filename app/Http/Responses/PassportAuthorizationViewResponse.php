<?php

namespace App\Http\Responses;

use Illuminate\Http\Request;
use Laravel\Passport\Contracts\AuthorizationViewResponse;

class PassportAuthorizationViewResponse implements AuthorizationViewResponse
{
    protected array $parameters = [];

    public function withParameters(array $parameters = []): static
    {
        $this->parameters = $parameters;

        return $this;
    }

    public function toResponse($request)
    {
        return response()->view('oauth.authorize', $this->parameters);
    }
}
