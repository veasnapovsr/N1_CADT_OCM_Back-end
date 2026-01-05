<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use PDF;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        
        /**
         * Create role for the user
         */
        // $super = \App\Models\Role::create(['name' => 'super', 'guard_name' => 'api' , 'tag' => 'core_service']);
        // $administrator = \App\Models\Role::create(['name' => 'admin', 'guard_name' => 'api' , 'tag' => 'core_service']);
        // $backendMember = \App\Models\Role::create(['name' => 'backend', 'guard_name' => 'api' , 'tag' => 'core_service']);
        // $client = \App\Models\Role::create(['name' => 'client', 'guard_name' => 'api' , 'tag' => 'client_service']);

        // /**
        //  * Create accounts
        //  */
        // /**
        //  * Create admin user for development purpose
        //  */
        // $chamroeunoum = \App\Models\User::create([
        //     'firstname' => 'Chamroeun' ,
        //     'lastname' => 'OUM' ,
        //     'email' => 'chamroeunoum@gmail.com' ,
        //     'active' => 1 ,
        //     'password' => bcrypt('031884Chamroeunoum') ,
        //     'phone' => '012391848' ,
        //     'username' => 'chamroeunoum'
        // ]);
        
        // $people = \App\Models\People\People::create([
        //     'firstname' => $chamroeunoum->firstname , 
        //     'lastname' => $chamroeunoum->lastname , 
        //     'gender' => 0 , // Male
        //     'dob' => \Carbon\Carbon::parse('1984-03-18 9:00') ,
        //     'mobile_phone' => $chamroeunoum->phone , 
        //     'email' => $chamroeunoum->email
        // ]);
        // $chamroeunoum->people_id = $people->id ;
        // $chamroeunoum->save();
        // $chamroeunoum->assignRole( $super );

        // /**
        //  * Create admin user for development purpose
        //  */
        // $rosachan = \App\Models\User::create([
        //     'firstname' => 'Rosa' ,
        //     'lastname' => 'CHAN' ,
        //     'email' => 'rosthika@gmail.com' ,
        //     'active' => 1 ,
        //     'password' => bcrypt('031884Chamroeunoum') ,
        //     'phone' => '017391848' ,
        //     'username' => 'rosachan'
        // ]);
        
        // $people = \App\Models\People\People::create([
        //     'firstname' => $rosachan->firstname , 
        //     'lastname' => $rosachan->lastname , 
        //     'gender' => 1 , // Male
        //     'dob' => \Carbon\Carbon::parse('1983-02-23 9:00') ,
        //     'mobile_phone' => $rosachan->phone , 
        //     'email' => $rosachan->email
        // ]);
        // $rosachan->people_id = $people->id ;
        // $rosachan->save();
        // $rosachan->assignRole( $administrator );

        // /**
        //  * Create client user for development purpose
        //  */
        // $puthireach = \App\Models\User::create([
        //     'firstname' => 'Puthireach' ,
        //     'lastname' => 'KONGCHAN' ,
        //     'email' => 'kongchanputhireach@gmail.com' ,
        //     'active' => 1 ,
        //     'password' => bcrypt('1234567890+1') ,
        //     'phone' => '012557200' ,
        //     'username' => 'kcputhireach'
        // ]);
        
        // $people = \App\Models\People\People::create([
        //     'firstname' => $puthireach->firstname , 
        //     'lastname' => $puthireach->lastname , 
        //     'gender' => 0 , // Male
        //     'dob' => \Carbon\Carbon::parse('1984-03-18 9:00') ,
        //     'mobile_phone' => $puthireach->phone , 
        //     'email' => $puthireach->email
        // ]);
        // $puthireach->people_id = $people->id ;
        // $puthireach->save();
        // $puthireach->assignRole( $backendMember );

        // /**
        //  * Create client user for development purpose
        //  */
        // $bellamudhita = \App\Models\User::create([
        //     'firstname' => 'Bella Mudhita' ,
        //     'lastname' => 'KONGCHAN' ,
        //     'email' => 'kongchanbellamudhita@gmail.com' ,
        //     'active' => 1 ,
        //     'password' => bcrypt('1234567890+1') ,
        //     'phone' => '010517515' ,
        //     'username' => 'kcbellamudhita'
        // ]);
        
        // $people = \App\Models\People\People::create([
        //     'firstname' => $bellamudhita->firstname , 
        //     'lastname' => $bellamudhita->lastname , 
        //     'gender' => 0 , // Male
        //     'dob' => \Carbon\Carbon::parse('1984-03-18 9:00') ,
        //     'mobile_phone' => $bellamudhita->phone , 
        //     'email' => $bellamudhita->email
        // ]);
        // $bellamudhita->people_id = $people->id ;
        // $bellamudhita->save();
        // $bellamudhita->assignRole( $client );

        // if( env('DB_CONNECTION','mysql') == 'pgsql' ){$this->call(ActivityLogTableSeeder::class);
        
        $this->call(AttendantCheckTimesTableSeeder::class);
        $this->call(AttendantsTableSeeder::class);
        $this->call(BooksTableSeeder::class);
        $this->call(ChaptersTableSeeder::class);
        $this->call(CountesiesTableSeeder::class);
        $this->call(CardsTableSeeder::class);
        $this->call(FoldersTableSeeder::class);
        $this->call(KuntiesTableSeeder::class);
        $this->call(LegalDraftsTableSeeder::class);
        $this->call(MatikasTableSeeder::class);
        $this->call(MatrasTableSeeder::class);
        $this->call(OfficersTableSeeder::class);
        $this->call(MeetingAttendantsTableSeeder::class);
        $this->call(PartsTableSeeder::class);
        $this->call(PositionsTableSeeder::class);
        $this->call(OrganizationsTableSeeder::class);
        $this->call(RegulatorFolderTableSeeder::class);
        $this->call(TimeslotsTableSeeder::class);
        $this->call(SettingsTableSeeder::class);
        $this->call(CommunesTableSeeder::class);
        $this->call(VillagesTableSeeder::class);
        $this->call(DistrictsTableSeeder::class);
        $this->call(ProvincesTableSeeder::class);
        $this->call(HolidaysTableSeeder::class);
        $this->call(RolesTableSeeder::class);
    }
    
}
