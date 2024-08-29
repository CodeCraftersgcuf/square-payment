<?php

namespace App\Utils;

use Square\SquareClient;
use Square\Exceptions\ApiException;

class LocationInfo
{
    protected $currency;
    protected $country;
    protected $location_id;
    protected $square_client;

    public function __construct()
    {
        // You can safely remove the dotenv loading as Laravel already handles this.
        $access_token = env('SQUARE_ACCESS_TOKEN');

        // Initialize the Square client.
        $this->square_client = new SquareClient([
            'accessToken' => $access_token,
            'environment' => env('ENVIRONMENT'),
        ]);

        try {
            $location = $this->square_client->getLocationsApi()
                ->retrieveLocation(env('SQUARE_LOCATION_ID'))
                ->getResult()
                ->getLocation();

            $this->location_id = $location->getId();
            $this->currency = $location->getCurrency();
            $this->country = $location->getCountry();
        } catch (ApiException $e) {
            // Handle the exception or log the error
            logger()->error('Error retrieving location information from Square: ' . $e->getMessage());
        }
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function getCountry()
    {
        return $this->country;
    }

    public function getId()
    {
        return $this->location_id;
    }
}
