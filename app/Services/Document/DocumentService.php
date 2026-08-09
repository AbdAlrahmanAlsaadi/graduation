<?php

namespace App\Services\Document;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentService
{
    public function store($request): array
    {
        $request->validated();

        return DB::transaction(function () use ($request) {
            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension();

            $baseName = $request->custom_name
                ? Str::slug($request->custom_name)
                : Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));

            if ($baseName === '') {
                $baseName = 'document';
            }

            $fileName = $baseName . '.' . $extension;
            $counter = 2;

            while (Storage::disk('public')->exists('documents/' . $fileName)) {
                $fileName = $baseName . '-' . $counter . '.' . $extension;
                $counter++;
            }

            $storedFilePath = $file->storeAs('documents', $fileName, 'public');

            $document = Document::query()->create([
                'project_id' => $request->project_id,
                'title' => $request->title,
                'type' => $request->type,
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

    $file = $request->file('file');
    $extension = $file->getClientOriginalExtension();

    $baseName = $request->custom_name
        ? Str::slug($request->custom_name)
        : Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));

    if ($baseName === '') {
        $baseName = 'document-version';
    }

    $lastVersion = DocumentVersion::query()
        ->where('document_id', $document->getKey())
        ->max('version_no');

    $newVersionNumber = $lastVersion ? $lastVersion + 1 : 1;

    $fileName = $baseName . '-v' . $newVersionNumber . '.' . $extension;
    $counter = 2;

    while (Storage::disk('public')->exists('documents/' . $fileName)) {
        $fileName = $baseName . '-v' . $newVersionNumber . '-' . $counter . '.' . $extension;
        $counter++;
    }

    $storedFilePath = $file->storeAs('documents', $fileName, 'public');

    $version = DocumentVersion::query()->create([
        'document_id' => $document->getKey(),
        'version_no' => $newVersionNumber,
        'file_path' => $storedFilePath,
    ]);

    $document->load('project', 'versions');

    $documentData = $document->toArray();

    if (! empty($documentData['versions'])) {
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
    public function getProjectDocuments($projectId, string $type): array
    {
        $project = Project::query()->find($projectId);

        if (! $project) {
            throw new \Exception('Project not found.', 404);
        }

        $documents = Document::query()
            ->with([
                'versions' => fn($query) => $query->latest(),
            ])
            ->where('project_id', $projectId)
            ->where('type', $type)
            ->get()
            ->map(function ($document) {

                $latestVersion = $document->versions->first();

                return [
                    'id' => $document->id,
                    'title' => $document->title,
                    'type' => $document->type,

                    'versions_count' => $document->versions->count(),

                    'latest_version' => $latestVersion ? [
                        'version_no' => $latestVersion->version_no,
                        'file_url' => asset('storage/' . $latestVersion->file_path),
                        'created_at' => $latestVersion->created_at,
                    ] : null,
                ];
            });

        return [
            'message' => ucfirst($type) . 's fetched successfully.',
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'documents' => $documents,
            'status' => 200,
        ];
    }
    public function getProjectContracts($projectId): array
    {
        return $this->getProjectDocuments($projectId, 'contract');
    }
    }
