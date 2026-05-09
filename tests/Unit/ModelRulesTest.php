<?php

namespace Tests\Unit;

use App\Models\Project;
use App\Models\Space;
use App\Models\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ModelRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_rules_require_name(): void
    {
        $validator = Validator::make([], Project::rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_space_rules_accept_valid_payload(): void
    {
        $payload = [
            'type' => Space::TYPE_ROOM,
            'wall_area' => 22.5,
            'floor_area' => 18.0,
            'wall_finish_type' => Space::FINISH_TYPES[0],
            'ceiling_finish_type' => 'none',
            'toilet_type' => 'none',
            'is_balcony_floor_tiled' => false,
        ];

        $validator = Validator::make($payload, Space::rules());

        $this->assertTrue($validator->passes());
    }

    public function test_work_item_rules_reject_invalid_quality_level(): void
    {
        $payload = [
            'name' => 'Test Work Item',
            'quality_level' => 'gold',
        ];

        $validator = Validator::make($payload, WorkItem::rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('quality_level', $validator->errors()->toArray());
    }
}
