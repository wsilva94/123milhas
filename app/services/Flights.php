<?php

namespace App\services;

use GuzzleHttp\Client;
use Illuminate\Support\Str;

interface IFlights
{
    public function getData();
}

class Flights implements IFlights
{
    protected $client;

    protected $url_api;

    public function __construct()
    {
        $this->url_api = env('URL_API_FLIGHTS');

        $this->client = new Client();
    }

    public function getData()
    {
        $flights = [];
        $flights['flights'] = $this->getDataApi();

        foreach ($flights['flights'] as $flight) {
            $fare = $flight['fare'];

            if (!isset($flights['groups'][$fare])) {
                $flights['groups'][$fare] = $this->getSkeleton($fare);
            }

            if ($flight['inbound']) {
                $flights['groups'][$fare]['inbound'][] = $flight;
            } else {
                $flights['groups'][$fare]['outbound'][] = $flight;
            }
        }
        $groups = $this->groupByPrice($flights);

        $this->setReturnGroups($groups, $flights);

        return $this->groups;
    }

    private function getDataApi(): array
    {
        return json_decode($this->client->get($this->url_api)->getBody(), true);
    }

    private function getSkeleton(string $fare)
    {
        return [
            'uniqueId' => $fare,
            'totalPrice' => 0,
            'inbound' => [],
            'outbound' => [],
        ];
    }

    private function groupByPrice(array $flights)
    {
        $groups = [];

        foreach ($flights['groups'] as $flight) {
            $collection = collect($flight['inbound']);
            $sumInboundGroup = array_sum(array_keys($collection->groupBy('price')->toArray()));

            $collection = collect($flight['outbound']);
            $sumOutboundGroup = array_sum(array_keys($collection->groupBy('price')->toArray()));

            $groups[] = [
                'uniqueId' => Str::random(9),
                'totalPrice' => ($sumInboundGroup + $sumOutboundGroup),
                'inbound' => $flight['inbound'],
                'outbound' => $flight['outbound'],
            ];
        }

        return $groups;
    }

    private function setReturnGroups(array&$groups, array&$flights)
    {
        $groups = collect($groups)->sortBy('totalPrice');
        $groups = $groups->values()->all();

        $this->groups['flights'] = $flights['flights'];
        $this->groups['groups'] = $groups;

        $this->groups['groups'] += [
            'totalGroups' => count($groups),
            'totalFlights' => count($flights['flights']),
            'cheapestPrice' => $groups[0]['totalPrice'],
            'cheapestGroup' => $groups[0]['uniqueId'],
        ];
    }
}
