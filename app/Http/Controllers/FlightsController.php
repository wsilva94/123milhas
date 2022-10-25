<?php

namespace App\Http\Controllers;

use App\services\Flights;
use Illuminate\Http\Response;

class FlightsController extends Controller
{
    public function __construct()
    {
        if (env('APP_ENV') === 'local') {
            exec('php artisan cache:clear');
        }
    }

    public function getFlightsData()
    {
        $flights = (new Flights())->getData();

        return response()->json($flights, Response::HTTP_OK);
    }
}
