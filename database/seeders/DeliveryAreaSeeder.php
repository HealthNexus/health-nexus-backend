<?php

namespace Database\Seeders;

use App\Models\DeliveryArea;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DeliveryAreaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $deliveryAreas = [
            // KNUST Campus and immediate surroundings
            [
                'code' => 'knust_campus',
                'name' => 'KNUST Campus',
                'description' => 'Main university campus including all halls and departments',
                'base_fee' => 150, // Lower fee for campus
                'is_active' => true,
                'sort_order' => 1,
                'landmarks' => [
                    'KNUST Main Gate',
                    'Great Hall',
                    'University Hospital',
                    'Central Administration',
                    'Library'
                ]
            ],
            [
                'code' => 'ayeduase',
                'name' => 'Ayeduase',
                'description' => 'Residential area adjacent to KNUST campus',
                'base_fee' => 200,
                'is_active' => true,
                'sort_order' => 2,
                'landmarks' => [
                    'Ayeduase Gate',
                    'STC Station',
                    'Ayeduase New Site',
                    'KNUST Hospital'
                ]
            ],
            [
                'code' => 'bomso',
                'name' => 'Bomso',
                'description' => 'Popular student residential area near KNUST',
                'base_fee' => 250,
                'is_active' => true,
                'sort_order' => 3,
                'landmarks' => [
                    'Bomso Market',
                    'Bomso Roundabout',
                    'St. Monica\'s College',
                    'Bomso Junction'
                ]
            ],
            [
                'code' => 'kentinkrono',
                'name' => 'Kentinkrono',
                'description' => 'Residential area popular with KNUST students',
                'base_fee' => 300,
                'is_active' => true,
                'sort_order' => 4,
                'landmarks' => [
                    'Kentinkrono Station',
                    'Royal Golf Club',
                    'Kentinkrono Market',
                    'Star Oil Filling Station'
                ]
            ],
            [
                'code' => 'daban',
                'name' => 'Daban',
                'description' => 'Student accommodation area near KNUST',
                'base_fee' => 280,
                'is_active' => true,
                'sort_order' => 5,
                'landmarks' => [
                    'Daban Junction',
                    'Daban Market',
                    'Pentagon Hostel Area',
                    'Goil Filling Station'
                ]
            ],
            [
                'code' => 'anloga',
                'name' => 'Anloga Junction',
                'description' => 'Major junction area with student hostels',
                'base_fee' => 250,
                'is_active' => true,
                'sort_order' => 6,
                'landmarks' => [
                    'Anloga Junction',
                    'VIP Station',
                    'Anloga Market',
                    'Shell Filling Station'
                ]
            ],
            [
                'code' => 'kotei',
                'name' => 'Kotei',
                'description' => 'Residential area south of KNUST',
                'base_fee' => 350,
                'is_active' => true,
                'sort_order' => 7,
                'landmarks' => [
                    'Kotei Roundabout',
                    'Kotei Market',
                    'Presbyterian Church',
                    'Total Filling Station'
                ]
            ],
            [
                'code' => 'ayigya',
                'name' => 'Ayigya',
                'description' => 'Town near KNUST with student accommodation',
                'base_fee' => 400,
                'is_active' => true,
                'sort_order' => 8,
                'landmarks' => [
                    'Ayigya Town',
                    'Ayigya Police Station',
                    'Methodist Church',
                    'Ayigya SHS'
                ]
            ],
            [
                'code' => 'forest',
                'name' => 'Forest Hill',
                'description' => 'Upscale residential area near KNUST',
                'base_fee' => 450,
                'is_active' => true,
                'sort_order' => 9,
                'landmarks' => [
                    'Forest Hill Residential Area',
                    'International School',
                    'Forest Hill Club',
                    'Embassy Area'
                ]
            ],
            [
                'code' => 'maxima',
                'name' => 'Maxima',
                'description' => 'Commercial and residential area',
                'base_fee' => 350,
                'is_active' => true,
                'sort_order' => 10,
                'landmarks' => [
                    'Maxima Station',
                    'Shoprite Kumasi',
                    'Prempeh Assembly Hall',
                    'Max FM'
                ]
            ],
            [
                'code' => 'north_campus',
                'name' => 'North Campus Extension',
                'description' => 'Extended campus area and new developments',
                'base_fee' => 300,
                'is_active' => true,
                'sort_order' => 11,
                'landmarks' => [
                    'North Campus Gate',
                    'New Lecture Halls',
                    'Sports Complex',
                    'Research Centers'
                ]
            ],
            [
                'code' => 'atasomanso',
                'name' => 'Atasomanso',
                'description' => 'Residential area near KNUST',
                'base_fee' => 380,
                'is_active' => true,
                'sort_order' => 12,
                'landmarks' => [
                    'Atasomanso Junction',
                    'Community Center',
                    'Basic School',
                    'Market Square'
                ]
            ]
        ];

        foreach ($deliveryAreas as $area) {
            DeliveryArea::firstOrCreate(
                ['code' => $area['code']],
                $area
            );
        }
    }
}
