<?php

namespace App\Http\Controllers;

use App\Http\Requests\Project\GetProjectWeatherByDateRequest;
use App\Http\Responses\Response;
use App\Services\Weather\WeatherService;
use Illuminate\Http\Request;
use Throwable;

class WeatherController extends Controller
{
    protected WeatherService $weatherService;

    public function __construct(WeatherService $weatherService)
    {
        $this->weatherService = $weatherService;
    }

    public function getTodayByProject($projectId)
    {
        try {
            $data = $this->weatherService->getTodayWeatherForProject($projectId);

            return Response::success(
                $data['message'],
                $data['data'],
                (int) $data['status']
            );
        } catch (Throwable $throwable) {
            $code = is_int($throwable->getCode()) && $throwable->getCode() > 0
                ? $throwable->getCode()
                : 500;

            return Response::error($throwable->getMessage(), $code);
        }}

    public function getByDate($projectId, GetProjectWeatherByDateRequest $request)
    {
        try {

            $data = $this->weatherService
                ->getWeatherForProjectByDate(
                    $projectId,
                    $request->date
                );

            return Response::success(
                $data['message'],
                $data['data'],
                (int) $data['status']
            );
        } catch (Throwable $throwable) {

            $code = is_int($throwable->getCode()) && $throwable->getCode() > 0
                ? $throwable->getCode()
                : 500;

            return Response::error($throwable->getMessage(), $code);
        }
    }
}
