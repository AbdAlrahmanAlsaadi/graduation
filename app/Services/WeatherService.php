<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Facades\Http;

class WeatherService
{
    public function getTodayWeatherForProject($projectId): array
    {
        $project = Project::query()->find($projectId);

        if (! $project) {
            throw new \Exception('Project not found.', 404);
        }

        if ($project->latitude === null || $project->longitude === null || $project->latitude === '' || $project->longitude === '') {
            throw new \Exception('Project location coordinates are missing.', 422);
        }

        $response = Http::timeout(15)->get('https://api.open-meteo.com/v1/forecast', [
            'latitude' => $project->latitude,
            'longitude' => $project->longitude,
            'current' => 'temperature_2m,relative_humidity_2m,weather_code,wind_speed_10m',
            'daily' => 'temperature_2m_max,temperature_2m_min,precipitation_sum',
            'timezone' => 'auto',
            'forecast_days' => 1,
        ]);

        if (! $response->successful()) {
            throw new \Exception('Failed to fetch weather data.', 502);
        }

        $weatherData = $response->json();
        $weatherCode = $weatherData['current']['weather_code'] ?? null;

        return [
            'message' => 'Project weather fetched successfully.',
            'data' => [
                'project' => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'location' => $project->location,
                    'latitude' => $project->latitude,
                    'longitude' => $project->longitude,
                ],
                'current_weather' => [
                    'temperature' => $weatherData['current']['temperature_2m'] ?? null,
                    'humidity' => $weatherData['current']['relative_humidity_2m'] ?? null,
                    'weather_code' => $weatherCode,
                    'weather_description' => $this->mapWeatherCode($weatherCode),
                    'wind_speed' => $weatherData['current']['wind_speed_10m'] ?? null,
                    'time' => $weatherData['current']['time'] ?? null,
                ],
                'today_forecast' => [
                    'temperature_max' => $weatherData['daily']['temperature_2m_max'][0] ?? null,
                    'temperature_min' => $weatherData['daily']['temperature_2m_min'][0] ?? null,
                    'precipitation_sum' => $weatherData['daily']['precipitation_sum'][0] ?? null,
                    'date' => $weatherData['daily']['time'][0] ?? null,
                ],
            ],
            'status' => 200,
        ];
    }

    private function mapWeatherCode($code): string
    {
        return match ((int) $code) {
            0 => 'Clear sky',
            1 => 'Mainly clear',
            2 => 'Partly cloudy',
            3 => 'Overcast',
            45, 48 => 'Fog',
            51, 53, 55 => 'Drizzle',
            56, 57 => 'Freezing drizzle',
            61, 63, 65 => 'Rain',
            66, 67 => 'Freezing rain',
            71, 73, 75 => 'Snow fall',
            77 => 'Snow grains',
            80, 81, 82 => 'Rain showers',
            85, 86 => 'Snow showers',
            95 => 'Thunderstorm',
            96, 99 => 'Thunderstorm with hail',
            default => 'Unknown',
        };
    }
}
