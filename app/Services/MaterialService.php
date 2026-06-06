<?php

namespace App\Services;

use App\Models\Material;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

class MaterialService
{
    /**
     * Get all materials ordered by name.
     */
    public function getAll(): Collection
    {
        return Material::query()->orderBy('name')->get();
    }

    /**
     * Find a material by its ID.
     */
    public function findById(int $id): Material
    {
        $material = Material::query()->find($id);

        if (! $material) {
            throw new RuntimeException('Material not found.', 404);
        }

        return $material;
    }

    /**
     * Create a new material.
     *
     * @param array{name:string, unit:string} $data
     */
    public function create(array $data): Material
    {
        return Material::query()->create($data);
    }

    /**
     * Update an existing material.
     *
     * @param array{name?:string, unit?:string} $data
     */
    public function update(int $id, array $data): Material
    {
        $material = $this->findById($id);
        $material->update($data);

        return $material->fresh();
    }

    /**
     * Delete a material by ID.
     */
    public function delete(int $id): bool
    {
        $material = $this->findById($id);

        return (bool) $material->delete();
    }

    /**
     * Get materials with attached work items and ordered pivot data.
     */
    public function getMaterialsWithWorkItems(): Collection
    {
        return Material::query()
            ->with(['workItems' => fn ($query) => $query->orderBy('work_item_materials.sort_order')])
            ->orderBy('name')
            ->get();
    }
}
