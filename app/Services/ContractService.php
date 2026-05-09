<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Project;
use Illuminate\Support\Str;

class ContractService
{
    public function store($request): array
    {
        $validated = $request->validated();

        $project = Project::query()->find($request->project_id);

        if (! $project) {
            throw new \Exception('Project not found.', 404);
        }

        $ownerId = $request->owner_id ?? $project->owner_id;

        $companySignaturePath = $this->uploadSignature(
            $request,
            'company_signature',
            'company'
        );

        $ownerSignaturePath = $this->uploadSignature(
            $request,
            'owner_signature',
            'owner'
        );

        $contract = Contract::query()->create([
            'project_id' => $request->project_id,
            'owner_id' => $ownerId,
            'contract_no' => $request->contract_no,
            'title' => $request->title,
            'contract_date' => $request->contract_date,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'contract_value' => $request->contract_value,
            'currency' => $request->currency,
            'status' => $request->status ?? 'Draft',
            'description' => $request->description,
            'company_signature' => $companySignaturePath,
            'owner_signature' => $ownerSignaturePath,
        ]);

        $contract->load('project', 'owner');

        return [
            'message' => 'Contract created successfully.',
            'contract' => $contract,
            'status' => 201,
        ];
    }

    public function getContractPdfData($contractId): array
    {
        $contract = Contract::query()
            ->with(['project', 'owner'])
            ->find($contractId);

        if (! $contract) {
            throw new \Exception('Contract not found.', 404);
        }

        return [
            'message' => 'Contract PDF data retrieved successfully.',
            'status' => 200,
            'data' => [
                'contract' => $contract,
            ],
        ];
    }

    private function uploadSignature($request, string $field, string $prefix): string
    {
        if (! $request->hasFile($field)) {
            throw new \Exception("The {$field} field is required.", 422);
        }

        $file = $request->file($field);

        $fileName = $prefix . '-' . time() . '-' . Str::random(10) . '.' . $file->getClientOriginalExtension();

        $directory = public_path('signatures');

        if (! file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $file->move($directory, $fileName);

        return 'signatures/' . $fileName;
    }
}
