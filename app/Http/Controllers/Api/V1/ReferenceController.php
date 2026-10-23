<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CountryResource;
use App\Http\Resources\StateResource;
use App\Models\Country;
use Illuminate\Http\JsonResponse;

class ReferenceController extends Controller
{
    public function countries(): JsonResponse
    {
        return $this->collection('Countries.', Country::orderBy('name')->get(), CountryResource::class);
    }

    public function states(Country $country): JsonResponse
    {
        return $this->collection('States.', $country->states()->orderBy('name')->get(), StateResource::class);
    }
}
