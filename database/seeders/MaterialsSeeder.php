<?php

namespace Database\Seeders;

use App\Models\Material;
use Illuminate\Database\Seeder;

class MaterialsSeeder extends Seeder
{
    private const MATERIALS = [
        ['name' => 'Cement',            'unit' => 'Bag'],
        ['name' => 'Sand',              'unit' => 'Cubic Meter'],
        ['name' => 'Water',             'unit' => 'Barrel'],
        ['name' => 'Wall Ceramic',      'unit' => 'm²'],
        ['name' => 'Ceiling Ceramic',   'unit' => 'm²'],
        ['name' => 'Ceramic Adhesive',  'unit' => 'Bag'],
        ['name' => 'Ceramic Spacers',   'unit' => 'Box'],
        ['name' => 'Floor Tiles',       'unit' => 'm²'],
        ['name' => 'Paint',             'unit' => 'Bucket'],
        ['name' => 'Putty',             'unit' => 'Bag'],
        ['name' => 'Roller',            'unit' => 'Piece'],
        ['name' => 'Brush',             'unit' => 'Piece'],
        ['name' => 'Sandpaper',         'unit' => 'Sheet'],
        ['name' => 'Electrical Tape',   'unit' => 'Meter'],
        ['name' => 'Electrical Boxes',  'unit' => 'Piece'],
        ['name' => 'Wires',             'unit' => 'Meter'],
        ['name' => 'Power Outlets',     'unit' => 'Piece'],
        ['name' => 'Switches',          'unit' => 'Piece'],
        ['name' => 'Lighting Fixture',  'unit' => 'Piece'],
        ['name' => 'Faucet',            'unit' => 'Piece'],
        ['name' => 'Boiler',            'unit' => 'Piece'],
        ['name' => 'Gypsum Board',      'unit' => 'Board'],
    ];

    public function run(): void
    {
        Material::upsert(self::MATERIALS, ['name'], ['unit']);
    }
}