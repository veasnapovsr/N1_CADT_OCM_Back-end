<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RegulatorSignaturesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('regulator_signatures')->delete();
        
        \DB::table('regulator_signatures')->insert(array (
            0 => 
            array (
                'id' => 1,
                'signature_id' => 111,
                'regulator_id' => 64845,
                'created_at' => NULL,
                'updated_at' => NULL,
                'deleted_at' => NULL,
            ),
        ));
        
        
    }
}