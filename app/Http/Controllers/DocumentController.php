<?php

namespace App\Http\Controllers;

use App\Http\Requests\Document\StoreDocumentRequest;
use App\Http\Requests\Document\StoreDocumentVersionRequest;
use App\Services\DocumentService;
use App\Http\Responses\Response;
use Throwable;

class DocumentController extends Controller
{
    private DocumentService $documentService;

    public function __construct(DocumentService $documentService)
    {
        $this->documentService = $documentService;
    }

    public function store(StoreDocumentRequest $request)
    {
        try {
            $data = $this->documentService->store($request);
            return Response::success($data['message'], [
                'document' => $data['document'],
                'version' => $data['version'],
            ], (int) $data['status']);
        } catch (Throwable $throwable) {
            $code = is_int($throwable->getCode()) && $throwable->getCode() > 0
                ? $throwable->getCode()
                : 500;

            return Response::error($throwable->getMessage(), $code);
        }
    }
    public function addVersion(StoreDocumentVersionRequest $request, $documentId)
    {
        try {
            $data = $this->documentService->addVersion($documentId, $request);

            return Response::success(
                $data['message'],
                [
                    'document' => $data['document'],
                    'version' => $data['version'],
                ],
                (int) $data['status']
            );
        } catch (Throwable $throwable) {
            $code = is_int($throwable->getCode()) && $throwable->getCode() > 0
                ? $throwable->getCode()
                : 500;

            return Response::error($throwable->getMessage(), $code);
        }
    }
    public function show($documentId)
    {
        try {
            $data = $this->documentService->show($documentId);

            return Response::success(
                $data['message'],
                $data['document'],
                (int) $data['status']
            );
        } catch (Throwable $throwable) {
            $code = is_int($throwable->getCode()) && $throwable->getCode() > 0
                ? $throwable->getCode()
                : 500;

            return Response::error($throwable->getMessage(), $code);
        }
    }
    public function downloadVersion($versionId)
    {
        try {
            $data = $this->documentService->downloadVersion($versionId);

            return response()->download($data['file_path'], $data['file_name']);
        } catch (Throwable $throwable) {
            $code = is_int($throwable->getCode()) && $throwable->getCode() > 0
                ? $throwable->getCode()
                : 500;

            return Response::error($throwable->getMessage(), $code);
        }
    }



    public function getProjectDocuments($projectId)
    {
        try {

            $data = $this->documentService
                ->getProjectDocuments($projectId);

            return Response::success(
                $data['message'],
                [
                    'project' => $data['project'],
                    'documents' => $data['documents'],
                ],
                (int) $data['status']
            );
        } catch (Throwable $throwable) {

            $code = is_int($throwable->getCode())
                && $throwable->getCode() > 0
                ? $throwable->getCode()
                : 500;

            return Response::error(
                $throwable->getMessage(),
                $code
            );
        }
    }
}
