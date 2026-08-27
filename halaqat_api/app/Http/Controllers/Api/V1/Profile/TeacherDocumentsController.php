<?php

namespace App\Http\Controllers\Api\V1\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Profile\StoreTeacherDocumentRequest;
use App\Http\Resources\Api\V1\Profile\TeacherDocumentCollectionResource;
use App\Http\Resources\Api\V1\Profile\TeacherDocumentResponseResource;
use App\Models\TeacherDocument;
use App\Services\Profile\TeacherDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeacherDocumentsController extends Controller
{
    public function index(Request $request): TeacherDocumentCollectionResource
    {
        $teacher = $request->user();
        abort_unless($teacher?->isTeacher(), 403);

        return new TeacherDocumentCollectionResource(
            TeacherDocument::query()
                ->where('teacher_id', $teacher->id)
                ->latest('id')
                ->paginate(50),
        );
    }

    public function store(StoreTeacherDocumentRequest $request, TeacherDocumentService $service): TeacherDocumentResponseResource|JsonResponse
    {
        $document = $service->create($request->user(), $request->validated(), $request->file('file'));

        return (new TeacherDocumentResponseResource($document->fresh()))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Request $request, TeacherDocument $documentId, TeacherDocumentService $service): JsonResponse
    {
        $service->delete($request->user(), $documentId);

        return response()->json([], 204);
    }
}
