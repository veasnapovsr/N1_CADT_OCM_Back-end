<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TimeslotsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('timeslots')->delete();
        
        \DB::table('timeslots')->insert(array (
            0 => 
            array (
                'id' => 4,
                'title' => 'ព្រឹក',
                'start' => '7:00',
                'end' => '11:00',
                'effective_day' => '1,2,3,4,5,6',
                'active' => 1,
                'rest_duration' => '60',
                'created_at' => '2025-10-22 00:00:00',
                'updated_at' => '2025-10-22 00:00:00',
                'deleted_at' => NULL,
                'uneffective_day' => '0',
            ),
            1 => 
            array (
                'id' => 1,
                'title' => 'ព្រឹក',
                'start' => '07:00',
                'end' => '11:00',
                'effective_day' => '1,2,3,4,5',
                'active' => 1,
                'rest_duration' => '60',
                'created_at' => '2023-11-03 14:44:15',
                'updated_at' => '2023-11-03 14:44:15',
                'deleted_at' => NULL,
                'uneffective_day' => '6,0',
            ),
            2 => 
            array (
                'id' => 2,
                'title' => 'ល្ងាច',
                'start' => '12:00',
                'end' => '16:00',
                'effective_day' => '1,2,3,4,5',
                'active' => 1,
                'rest_duration' => '60',
                'created_at' => '2023-11-03 14:44:15',
                'updated_at' => '2023-11-03 14:44:15',
                'deleted_at' => NULL,
                'uneffective_day' => '6,0',
            ),
            3 => 
            array (
                'id' => 3,
                'title' => 'យប់',
                'start' => '18:00',
                'end' => '22:00',
                'effective_day' => '1,2,3,4,5',
                'active' => 1,
                'rest_duration' => '60',
                'created_at' => '2023-11-03 14:44:15',
                'updated_at' => '2023-11-03 14:44:15',
                'deleted_at' => NULL,
                'uneffective_day' => '6,0',
            ),
            4 => 
            array (
                'id' => 5,
                'title' => 'រសៀល',
                'start' => '12:00',
                'end' => '16:00',
                'effective_day' => '1,2,3,4,5,6',
                'active' => 1,
                'rest_duration' => '60',
                'created_at' => '2025-10-22 00:00:00',
                'updated_at' => '2025-10-22 00:00:00',
                'deleted_at' => NULL,
                'uneffective_day' => '0',
            ),
            5 => 
            array (
                'id' => 6,
                'title' => 'យប់',
                'start' => '18:00',
                'end' => '22:00',
                'effective_day' => '1,2,3,4,5,6',
                'active' => 1,
                'rest_duration' => '60',
                'created_at' => '2025-10-22 00:00:00',
                'updated_at' => '2025-10-22 14:36:57',
                'deleted_at' => NULL,
                'uneffective_day' => '0',
            ),
        ));
        
        
    }
}