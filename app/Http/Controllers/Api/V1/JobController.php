<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\AlumniJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = AlumniJob::where('status', 'approved')->latest();

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        return response()->json($query->paginate(20));
    }

    public function show(AlumniJob $job): JsonResponse
    {
        $job->load('author:id,name');
        return response()->json($job);
    }
}
