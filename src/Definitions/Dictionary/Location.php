<?php

namespace Byte5\Definitions\Dictionary;

use Illuminate\Support\Fluent;

class Location extends Fluent
{
    /**
     * @param  float  $latitude  Latitude, in degrees, of the location.
     * @param  float  $longitude  Longitude, in degrees, of the location.
     */
    public function __construct(float $latitude, float $longitude, ?float $altitude = null, string $relevantText = '')
    {
        $data = compact('latitude', 'longitude', 'altitude', 'relevantText');
        $attributes = collect($data)->filter()->toArray();
        parent::__construct($attributes);
    }

    /**
     * Altitude, in meters, of the location.
     */
    public function setAltitude(float $altitude): self
    {
        $this->attributes['altitude'] = $altitude;

        return $this;
    }

    /**
     * Latitude, in degrees, of the location.
     */
    public function setLatitude(float $latitude): self
    {
        $this->attributes['latitude'] = $latitude;

        return $this;
    }

    /**
     * Longitude, in degrees, of the location.
     */
    public function setLongitude(float $longitude): self
    {
        $this->attributes['longitude'] = $longitude;

        return $this;
    }

    /**
     * Text displayed on the lock screen when the pass is currently relevant.
     * For example, a description of the nearby location such as
     * “Store nearby on 1st and Main.”
     */
    public function setRelevantText(string $relevantText): self
    {
        $this->attributes['relevantText'] = $relevantText;

        return $this;
    }
}
