<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Support\Facades\DB;

class DocumentService
{
    public function store($request): array
    {
        $request->validated();

        return DB::transaction(function () use ($request) {
            $storedFilePath = $request->file('file')->store('documents', 'public');

            $document = Document::query()->create([
                'project_id' => $request->project_id,
                'title' => $request->title,
                'category' => $request->category,
            ]);

            $version = DocumentVersion::query()->create([
                'document_id' => $document->getKey(),
                'version_no' => 1,
                'file_path' => $storedFilePath,
            ]);

            $document->load('project', 'versions');

            $documentData = $document->toArray();

            if (!empty($documentData['versions'])) {
                foreach ($documentData['versions'] as &$docVersion) {
                    $docVersion['file_url'] = asset('storage/' . $docVersion['file_path']);
                }
            }

            return [
                'message' => 'Document uploaded successfully.',
                'document' => $documentData,
                'version' => [
                    'id' => $version->id,
                    'document_id' => $version->document_id,
                    'version_no' => $version->version_no,
                    'file_path' => $version->file_path,
                    'file_url' => asset('storage/' . $version->file_path),
                    'created_at' => $version->created_at,
                    'updated_at' => $version->updated_at,
                ],
                'status' => 201,
            ];
        });
    }
    public function addVersion($documentId, $request): array
    {
        $request->validated();

        $document = Document::query()->find($documentId);

        if (! $document) {
            throw new \Exception('Document not found.', 404);
        }

        $storedFilePath = $request->file('file')->store('documents', 'public');

        $lastVersion = DocumentVersion::query()
            ->where('document_id', $document->getKey())
            ->max('version_no');

        $newVersionNumber = $lastVersion ? $lastVersion + 1 : 1;

        $version = DocumentVersion::query()->create([
            'document_id' => $document->getKey(),
            'version_no' => $newVersionNumber,
            'file_path' => $storedFilePath,
        ]);

        $document->load('project', 'versions');

        $documentData = $document->toArray();

        if (!empty($documentData['versions'])) {
            foreach ($documentData['versions'] as &$docVersion) {
                $docVersion['file_url'] = asset('storage/' . $docVersion['file_path']);
            }
        }

        return [
            'message' => 'New document version uploaded successfully.',
            'document' => $documentData,
            'version' => [
                'id' => $version->id,
                'document_id' => $version->document_id,
                'version_no' => $version->version_no,
                'file_path' => $version->file_path,
                'file_url' => asset('storage/' . $version->file_path),
                'created_at' => $version->created_at,
                'updated_at' => $version->updated_at,
            ],
            'status' => 201,
        ];
    }
    public function show($documentId): array
    {
        $document = Document::query()
            ->with(['project', 'versions'])
            ->find($documentId);

        if (! $document) {
            throw new \Exception('Document not found.', 404);
        }

        $documentData = $document->toArray();

        if (!empty($documentData['versions'])) {
            foreach ($documentData['versions'] as &$version) {
                $version['file_url'] = asset('storage/' . $version['file_path']);
                $version['download_url'] = url('/api/documents/versions/' . $version['id'] . '/download');
            }
        }

        return [
            'message' => 'Document fetched successfully.',
            'document' => $documentData,
            'status' => 200,
        ];
    }
    public function downloadVersion($versionId)
    {
        $version = DocumentVersion::query()->find($versionId);

        if (! $version) {
            throw new \Exception('Document version not found.', 404);
        }

        $fullPath = storage_path('app/public/' . $version->file_path);

        if (! file_exists($fullPath)) {
            throw new \Exception('File not found on server.', 404);
        }

        return [
            'file_path' => $fullPath,
            'file_name' => basename($version->file_path),
        ];
    }
}
