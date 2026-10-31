<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\EventWeatherAiService;
use Illuminate\Http\JsonResponse;

class EventController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Event::query()->orderBy('date_event')->get(),
        ]);
    }

    public function analyze(Event $event, EventWeatherAiService $service): JsonResponse
    {
        $analysis = $service->analyze($event);

        return response()->json([
            'event' => [
                'id' => $event->id,
                'title' => $event->title,
                'date_event' => $event->date_event?->format('Y-m-d'),
                'governorate' => $event->governorate,
                'location' => $event->location,
                'type' => $event->type,
            ],
            'weather' => $analysis['weather'],
            'risk_level' => $analysis['risk_level'],
            'ai_summary' => $analysis['ai_summary'],
            'recommendations' => $analysis['recommendations'],
        ]);
    }
}
