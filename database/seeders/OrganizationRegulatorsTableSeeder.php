<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class OrganizationRegulatorsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('organization_regulators')->delete();
        
        \DB::table('organization_regulators')->insert(array (
            0 => 
            array (
                'id' => 1,
                'organization_id' => 2,
                'regulator_id' => 64845,
                'created_at' => NULL,
                'updated_at' => NULL,
                'deleted_at' => NULL,
            ),
            1 => 
            array (
                'id' => 2,
                'organization_id' => 145,
                'regulator_id' => 64845,
                'created_at' => NULL,
                'updated_at' => NULL,
                'deleted_at' => NULL,
            ),
            2 => 
            array (
                'id' => 3,
                'organization_id' => 386,
                'regulator_id' => 64845,
                'created_at' => NULL,
                'updated_at' => NULL,
                'deleted_at' => NULL,
            ),
        ));
        
        
    }
}