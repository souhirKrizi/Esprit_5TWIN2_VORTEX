<?php

namespace App\Http\Controllers\Canicule;

use App\Services\Canicule\CarteCaniculeService;
use Illuminate\View\View;

class AdminCarteCaniculeController extends AdminCaniculeController
{
    public function index(CarteCaniculeService $carte): View
    {
        return view('admin.canicule.carte', ['geojson' => $carte->geojson()]);
    }
}