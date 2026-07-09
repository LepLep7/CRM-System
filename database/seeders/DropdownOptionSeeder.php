<?php

namespace Database\Seeders;

use App\Models\DropdownOption;
use Illuminate\Database\Seeder;

class DropdownOptionSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'scope_of_service' => ['Freight Forwarding', 'Warehousing', 'Customs Clearance', 'Last Mile Delivery', 'Supply Chain Consulting'],
            'country' => ['Malaysia', 'Singapore', 'Thailand', 'Indonesia', 'China', 'Vietnam'],
            'port' => ['Port Klang', 'Port of Tanjung Pelepas', 'Penang Port', 'Johor Port', 'Singapore Port'],
        ];

        foreach ($data as $category => $values) {
            foreach ($values as $index => $value) {
                DropdownOption::create([
                    'category' => $category,
                    'value' => $value,
                    'sort_order' => $index,
                ]);
            }
        }
    }
}