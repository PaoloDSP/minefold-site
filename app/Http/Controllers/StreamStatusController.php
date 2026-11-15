<?php

namespace App\Http\Controllers;

use App\Services\StreamStatus;
use Illuminate\Http\JsonResponse;

class StreamStatusController extends Controller
{
    public function __invoke(StreamStatus $stream): JsonResponse
    {
        return response()->json($stream->current())->header('Cache-Control', 'public, max-age=20');
    }
}
