<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DocFlowAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $backendRoleId = $this->ensureRole(
            'backend',
            'backend',
            'docflow',
            'DocFlow',
            'DocFlow',
            'core_service'
        );

        $permissionId = $this->ensurePermission('docflow.access', 'DocFlow Access');
        $countesyId = $this->ensureCountesy('DocFlow');

        $accounts = [
            [
                'email' => 'docflow.admin.department@ocm.gov.kh',
                'firstname' => 'Admin Department',
                'lastname' => 'DocFlow',
                'organization' => 'នាយកដ្ឋានរដ្ឋបាល',
                'position' => 'មន្ត្រី',
            ],
            [
                'email' => 'docflow.department.head@ocm.gov.kh',
                'firstname' => 'Department Head',
                'lastname' => 'DocFlow',
                'organization' => 'ប្រធាននាយកដ្ឋាន',
                'position' => 'ប្រធាននាយកដ្ឋាន',
            ],
            [
                'email' => 'docflow.cabinet.director@ocm.gov.kh',
                'firstname' => 'Cabinet Director',
                'lastname' => 'DocFlow',
                'organization' => 'នាយកខុទ្ទកាល័យ',
                'position' => 'នាយកខុទ្ទកាល័យ',
            ],
            [
                'email' => 'docflow.office.dpm@ocm.gov.kh',
                'firstname' => 'Office DPM',
                'lastname' => 'DocFlow',
                'organization' => 'ខុទ្ទកាល័យឯកឧត្តមឧបនាយករដ្ឋមន្ត្រីប្រចាំការ',
                'position' => 'មន្ត្រី',
            ],
            [
                'email' => 'docflow.specialist.unit@ocm.gov.kh',
                'firstname' => 'Specialist Unit',
                'lastname' => 'DocFlow',
                'organization' => 'អង្គភាពជំនាញ',
                'position' => 'អនុប្រធានការិយាល័យ',
            ],
        ];

        foreach ($accounts as $account) {
            $organizationId = $this->ensureOrganization($account['organization']);
            $positionId = $this->ensurePosition($account['position']);
            $structureId = $this->ensureOrganizationStructure($organizationId, $account['organization']);
            $structurePositionId = $this->ensureOrganizationStructurePosition(
                $structureId,
                $positionId,
                $account['position']
            );

            DB::table('organization_structure_position_permissions')->updateOrInsert(
                [
                    'organization_structure_position_id' => $structurePositionId,
                    'permission_id' => $permissionId,
                ],
                [
                    'updated_at' => $now,
                    'deleted_at' => null,
                ]
            );

            $peopleId = $this->ensurePeople($account, $organizationId, $positionId);
            $userId = $this->ensureUser($account, $peopleId);
            $officerId = $this->ensureOfficer($account, $userId, $peopleId, $organizationId, $structurePositionId, $countesyId);
            $this->ensureOfficerJob($account, $officerId, $structurePositionId, $countesyId);

            DB::table('user_role')->updateOrInsert(
                [
                    'user_id' => $userId,
                    'role_id' => $backendRoleId,
                ],
                [
                    'updated_at' => $now,
                    'deleted_at' => null,
                ]
            );
        }
    }

    private function ensureRole(
        string $name,
        string $keyName,
        string $subRole,
        string $khName,
        string $enName,
        string $tag
    ): int {
        $now = now();

        DB::table('roles')->updateOrInsert(
            ['key_name' => $keyName, 'sub_role' => $subRole],
            [
                'name' => $name,
                'khname' => $khName,
                'enname' => $enName,
                'guard_name' => 'api',
                'tag' => $tag,
                'updated_at' => $now,
                'deleted_at' => null,
            ]
        );

        return (int) DB::table('roles')
            ->where('key_name', $keyName)
            ->where('sub_role', $subRole)
            ->value('id');
    }

    private function ensurePermission(string $code, string $name): int
    {
        $now = now();

        DB::table('permissions')->updateOrInsert(
            ['code' => $code],
            [
                'name' => $name,
                'guard_name' => 'api',
                'tag' => 'docflow',
                'updated_at' => $now,
                'deleted_at' => null,
            ]
        );

        return (int) DB::table('permissions')->where('code', $code)->value('id');
    }

    private function ensureCountesy(string $name): int
    {
        $now = now();

        DB::table('countesies')->updateOrInsert(
            ['name' => $name],
            [
                'desp' => $name,
                'active' => 1,
                'updated_at' => $now,
                'deleted_at' => null,
            ]
        );

        return (int) DB::table('countesies')->where('name', $name)->value('id');
    }

    private function ensureOrganization(string $name): int
    {
        $now = now();

        DB::table('organizations')->updateOrInsert(
            ['name' => $name],
            [
                'keyname' => Str::slug($name) ?: md5($name),
                'desp' => $name,
                'active' => 1,
                'updated_at' => $now,
                'deleted_at' => null,
            ]
        );

        return (int) DB::table('organizations')->where('name', $name)->value('id');
    }

    private function ensurePosition(string $name): int
    {
        $now = now();

        DB::table('positions')->updateOrInsert(
            ['name' => $name],
            [
                'desp' => $name,
                'active' => 1,
                'updated_at' => $now,
                'deleted_at' => null,
            ]
        );

        return (int) DB::table('positions')->where('name', $name)->value('id');
    }

    private function ensureOrganizationStructure(int $organizationId, string $name): int
    {
        $now = now();

        DB::table('organization_structures')->updateOrInsert(
            ['organization_id' => $organizationId],
            [
                'name' => $name,
                'pid' => 0,
                'tpid' => '0',
                'active' => 1,
                'updated_at' => $now,
                'deleted_at' => null,
            ]
        );

        return (int) DB::table('organization_structures')
            ->where('organization_id', $organizationId)
            ->value('id');
    }

    private function ensureOrganizationStructurePosition(int $structureId, int $positionId, string $name): int
    {
        $now = now();

        DB::table('organization_structure_positions')->updateOrInsert(
            [
                'organization_structure_id' => $structureId,
                'position_id' => $positionId,
            ],
            [
                'name' => $name,
                'pid' => 0,
                'tpid' => '0',
                'total_jobs' => 1,
                'updated_at' => $now,
                'deleted_at' => null,
            ]
        );

        return (int) DB::table('organization_structure_positions')
            ->where('organization_structure_id', $structureId)
            ->where('position_id', $positionId)
            ->value('id');
    }

    private function ensurePeople(array $account, int $organizationId, int $positionId): int
    {
        $now = now();

        DB::table('people')->updateOrInsert(
            ['email' => $account['email']],
            [
                'firstname' => $account['firstname'],
                'lastname' => $account['lastname'],
                'enfirstname' => $account['firstname'],
                'enlastname' => $account['lastname'],
                'gender' => 1,
                'organization_id' => $organizationId,
                'position_id' => $positionId,
                'updated_at' => $now,
                'deleted_at' => null,
            ]
        );

        return (int) DB::table('people')->where('email', $account['email'])->value('id');
    }

    private function ensureUser(array $account, int $peopleId): int
    {
        $now = now();

        DB::table('users')->updateOrInsert(
            ['email' => $account['email']],
            [
                'firstname' => $account['firstname'],
                'lastname' => $account['lastname'],
                'username' => $account['email'],
                'password' => Hash::make('DocFlow@123'),
                'active' => '1',
                'people_id' => $peopleId,
                'updated_at' => $now,
                'deleted_at' => null,
            ]
        );

        return (int) DB::table('users')->where('email', $account['email'])->value('id');
    }

    private function ensureOfficer(
        array $account,
        int $userId,
        int $peopleId,
        int $organizationId,
        int $structurePositionId,
        int $countesyId
    ): int {
        $now = now();

        DB::table('officers')->updateOrInsert(
            ['user_id' => $userId],
            [
                'code' => 'DOCFLOW-' . $userId,
                'people_id' => $peopleId,
                'organization_id' => $organizationId,
                'position_id' => $structurePositionId,
                'leader' => 0,
                'countesy_id' => $countesyId,
                'email' => $account['email'],
                'updated_at' => $now,
                'deleted_at' => null,
            ]
        );

        return (int) DB::table('officers')->where('user_id', $userId)->value('id');
    }

    private function ensureOfficerJob(array $account, int $officerId, int $structurePositionId, int $countesyId): void
    {
        $now = now();

        DB::table('officer_jobs')->updateOrInsert(
            [
                'officer_id' => $officerId,
                'organization_structure_position_id' => $structurePositionId,
            ],
            [
                'countesy_id' => $countesyId,
                'email' => $account['email'],
                'start' => '2026-01-01',
                'end' => null,
                'updated_at' => $now,
                'deleted_at' => null,
            ]
        );
    }
}
