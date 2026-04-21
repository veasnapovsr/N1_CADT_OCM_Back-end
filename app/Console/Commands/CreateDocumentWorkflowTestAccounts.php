<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateDocumentWorkflowTestAccounts extends Command
{
    protected $signature = 'docflow:create-test-accounts
        {--seed-transaction : Create a fresh pending document transaction for the department head test account}
        {--seed-office-transaction : Restore or create a pending document transaction for the office DPM test account from the department head account}';

    protected $description = 'Create five sample backend accounts for document workflow testing';

    public function handle()
    {
        $now = Carbon::now()->format('Y-m-d H:i:s');
        $password = 'DocFlow@123';

        $definitions = [
            [
                'key' => 'admin_department',
                'label' => 'នាយកដ្ឋានរដ្ឋបាល',
                'lastname' => 'សាកល្បង',
                'firstname' => 'រដ្ឋបាល',
                'enlastname' => 'Test',
                'enfirstname' => 'AdminDepartment',
                'username' => 'docflow.admin.department@ocm.gov.kh',
                'email' => 'docflow.admin.department@ocm.gov.kh',
                'phone' => '099900001',
                'organization_name' => 'នាយកដ្ឋានរដ្ឋបាល',
                'position_name' => 'មន្ត្រី',
                'ensure_focal' => false,
            ],
            [
                'key' => 'department_head',
                'label' => 'ប្រធាននាយកដ្ឋាន',
                'lastname' => 'សាកល្បង',
                'firstname' => 'ប្រធាននាយកដ្ឋាន',
                'enlastname' => 'Test',
                'enfirstname' => 'DepartmentHead',
                'username' => 'docflow.department.head@ocm.gov.kh',
                'email' => 'docflow.department.head@ocm.gov.kh',
                'phone' => '099900002',
                'organization_name' => 'នាយកដ្ឋានរដ្ឋបាល',
                'position_name' => 'ប្រធាននាយកដ្ឋាន',
                'ensure_focal' => false,
            ],
            [
                'key' => 'cabinet_director',
                'label' => 'នាយកខុទ្ទកាល័យ',
                'lastname' => 'សាកល្បង',
                'firstname' => 'នាយកខុទ្ទកាល័យ',
                'enlastname' => 'Test',
                'enfirstname' => 'CabinetDirector',
                'username' => 'docflow.cabinet.director@ocm.gov.kh',
                'email' => 'docflow.cabinet.director@ocm.gov.kh',
                'phone' => '099900003',
                'organization_name' => 'ខុទ្ទកាល័យឯកឧត្តមឧបនាយករដ្ឋមន្រ្តីប្រចាំការ',
                'position_name' => 'នាយកខុទ្ទកាល័យ',
                'ensure_focal' => false,
            ],
            [
                'key' => 'deputy_pm_office',
                'label' => 'ខុទ្ទកាល័យឯកឧត្តមឧបនាយករដ្ឋមន្ត្រីប្រចាំការ',
                'lastname' => 'សាកល្បង',
                'firstname' => 'ខុទ្ទកាល័យឧបនាយករដ្ឋមន្ត្រី',
                'enlastname' => 'Test',
                'enfirstname' => 'DeputyPMOffice',
                'username' => 'docflow.office.dpm@ocm.gov.kh',
                'email' => 'docflow.office.dpm@ocm.gov.kh',
                'phone' => '099900004',
                'organization_name' => 'ខុទ្ទកាល័យឯកឧត្តមឧបនាយករដ្ឋមន្រ្តីប្រចាំការ',
                'position_name' => 'មន្ត្រី',
                'ensure_focal' => true,
            ],
            [
                'key' => 'specialist_unit',
                'label' => 'អង្គភាពជំនាញ',
                'lastname' => 'សាកល្បង',
                'firstname' => 'អង្គភាពជំនាញ',
                'enlastname' => 'Test',
                'enfirstname' => 'SpecialistUnit',
                'username' => 'docflow.specialist.unit@ocm.gov.kh',
                'email' => 'docflow.specialist.unit@ocm.gov.kh',
                'phone' => '099900005',
                'organization_name' => 'នាយកដ្ឋានបច្ចេកវិទ្យានិងប្រតិបត្តិការឌីជីថល',
                'position_name' => 'អនុប្រធានការិយាល័យ',
                'ensure_focal' => true,
            ],
        ];

        $permissionTemplatePositionId = 6;
        $permissionIds = DB::table('organization_structure_position_permissions')
            ->where('organization_structure_position_id', $permissionTemplatePositionId)
            ->pluck('permission_id')
            ->all();

        if (empty($permissionIds)) {
            $this->error('No permission template found on organization_structure_position_id=6.');
            return self::FAILURE;
        }

        $backendRoleId = DB::table('roles')
            ->where('name', 'backend')
            ->orderBy('id')
            ->value('id');

        if (!$backendRoleId) {
            $this->error('Backend role not found.');
            return self::FAILURE;
        }

        $results = [];
        $accountsByKey = [];

        DB::beginTransaction();

        try {
            $this->resetSequence('public.users', 'public.users_id_seq');
            $this->resetSequence('public.officers', 'public.officers_id_seq');
            $this->resetSequence('public.people', 'public.people_id_seq');

            $nextSharedId = max(
                (int) DB::table('users')->max('id'),
                (int) DB::table('officers')->max('id')
            ) + 1;
            $nextPeopleId = (int) DB::table('people')->max('id') + 1;

            foreach ($definitions as $definition) {
                $positionNode = $this->findPositionNode(
                    $definition['organization_name'],
                    $definition['position_name']
                );

                if ($positionNode === null) {
                    throw new \RuntimeException(
                        'Position node not found for ' . $definition['organization_name'] . ' / ' . $definition['position_name']
                    );
                }

                $existingUser = DB::table('users')
                    ->where('username', $definition['username'])
                    ->first();

                if ($existingUser) {
                    $officer = DB::table('officers')->where('user_id', $existingUser->id)->first();
                    if (!$officer) {
                        throw new \RuntimeException('Existing user ' . $definition['username'] . ' has no linked officer.');
                    }

                    $this->syncOfficerPositionNode(
                        (int) $officer->id,
                        $positionNode,
                        $now
                    );

                    $this->ensureUserRole((int) $existingUser->id, (int) $backendRoleId);
                    $this->ensurePositionPermissions((int) $positionNode->organization_structure_position_id, $permissionIds, $now);

                    if ($definition['ensure_focal']) {
                        $this->ensureFocalPeople(
                            (int) $positionNode->organization_structure_id,
                            (int) $officer->id,
                            $now
                        );
                    }

                    $results[] = [
                        'status' => 'existing',
                        'label' => $definition['label'],
                        'key' => $definition['key'],
                        'username' => $definition['username'],
                        'password' => $password,
                        'user_id' => (int) $existingUser->id,
                        'officer_id' => (int) $officer->id,
                        'organization' => $definition['organization_name'],
                        'position' => $definition['position_name'],
                    ];

                    $accountsByKey[$definition['key']] = [
                        'user_id' => (int) $existingUser->id,
                        'officer_id' => (int) $officer->id,
                    ];

                    continue;
                }

                $sharedId = $nextSharedId++;
                $peopleId = $nextPeopleId++;

                DB::table('people')->insert([
                    'id' => $peopleId,
                    'public_key' => md5('people:' . $peopleId . ':' . $definition['username']),
                    'firstname' => $definition['firstname'],
                    'lastname' => $definition['lastname'],
                    'enfirstname' => $definition['enfirstname'],
                    'enlastname' => $definition['enlastname'],
                    'gender' => 1,
                    'dob' => '1990-01-01',
                    'mobile_phone' => $definition['phone'],
                    'office_phone' => $definition['phone'],
                    'email' => $definition['email'],
                    'nationality' => 'ខ្មែរ',
                    'national' => 'ខ្មែរ',
                    'body_condition' => 0,
                    'body_condition_desp' => '',
                    'created_by' => 1,
                    'updated_by' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('users')->insert([
                    'id' => $sharedId,
                    'public_key' => md5('user:' . $sharedId . ':' . $definition['username']),
                    'lastname' => $definition['lastname'],
                    'firstname' => $definition['firstname'],
                    'phone' => $definition['phone'],
                    'username' => $definition['username'],
                    'email' => $definition['email'],
                    'role' => '0',
                    'email_verified_at' => $now,
                    'password' => Hash::make($password),
                    'login_count' => 0,
                    'active' => 1,
                    'authy_id' => 0,
                    'people_id' => $peopleId,
                    'created_by' => 1,
                    'updated_by' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('officers')->insert([
                    'id' => $sharedId,
                    'public_key' => md5('officer:' . $sharedId . ':' . $definition['username']),
                    'code' => 'DOCFLOW-' . $sharedId,
                    'date' => Carbon::now()->format('Y-m-d'),
                    'official_date' => Carbon::now()->format('Y-m-d'),
                    'unofficial_date' => Carbon::now()->format('Y-m-d'),
                    'user_id' => $sharedId,
                    'people_id' => $peopleId,
                    'email' => $definition['email'],
                    'phone' => $definition['phone'],
                    'countesy_id' => 0,
                    'salary_rank' => 'ក.៣.៤',
                    'officer_type' => '',
                    'organization_id' => (int) $positionNode->organization_id,
                    'position_id' => (int) $positionNode->position_id,
                    'rank_id' => 0,
                    'leader' => 0,
                    'created_by' => 1,
                    'updated_by' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('officer_jobs')->insert([
                    'organization_structure_position_id' => (int) $positionNode->organization_structure_position_id,
                    'unofficial_position_id' => 0,
                    'officer_id' => $sharedId,
                    'countesy_id' => 0,
                    'start' => $now,
                    'end' => null,
                    'created_by' => 1,
                    'updated_by' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $this->ensureUserRole($sharedId, (int) $backendRoleId);
                $this->ensurePositionPermissions((int) $positionNode->organization_structure_position_id, $permissionIds, $now);

                if ($definition['ensure_focal']) {
                    $this->ensureFocalPeople(
                        (int) $positionNode->organization_structure_id,
                        $sharedId,
                        $now
                    );
                }

                $results[] = [
                    'status' => 'created',
                    'label' => $definition['label'],
                    'key' => $definition['key'],
                    'username' => $definition['username'],
                    'password' => $password,
                    'user_id' => $sharedId,
                    'officer_id' => $sharedId,
                    'organization' => $definition['organization_name'],
                    'position' => $definition['position_name'],
                ];

                $accountsByKey[$definition['key']] = [
                    'user_id' => $sharedId,
                    'officer_id' => $sharedId,
                ];
            }

            if ($this->option('seed-transaction')) {
                $this->ensureFreshDepartmentHeadTransaction($accountsByKey, $now);
            }

            if ($this->option('seed-office-transaction')) {
                $this->ensureFreshOfficeDpmTransaction($accountsByKey, $now);
            }

            $this->resetSequence('public.users', 'public.users_id_seq');
            $this->resetSequence('public.officers', 'public.officers_id_seq');
            $this->resetSequence('public.people', 'public.people_id_seq');

            DB::commit();
        } catch (\Throwable $throwable) {
            DB::rollBack();
            $this->error($throwable->getMessage());
            return self::FAILURE;
        }

        $this->table(
            ['status', 'flow_label', 'username', 'password', 'user_id', 'officer_id', 'organization', 'position'],
            array_map(function ($row) {
                return [
                    $row['status'],
                    $row['label'],
                    $row['username'],
                    $row['password'],
                    $row['user_id'],
                    $row['officer_id'],
                    $row['organization'],
                    $row['position'],
                ];
            }, $results)
        );

        return self::SUCCESS;
    }

    private function findPositionNode(string $organizationName, string $positionName)
    {
        $organizationNames = array_values(array_unique(array_filter([
            $organizationName,
            str_replace('រដ្ឋមន្ត្រី', 'រដ្ឋមន្រ្តី', $organizationName),
            str_replace('រដ្ឋមន្រ្តី', 'រដ្ឋមន្ត្រី', $organizationName),
        ])));

        return DB::table('organization_structure_positions as osp')
            ->join('organization_structures as os', 'os.id', '=', 'osp.organization_structure_id')
            ->join('organizations as org', 'org.id', '=', 'os.organization_id')
            ->join('positions as p', 'p.id', '=', 'osp.position_id')
            ->whereNull('osp.deleted_at')
            ->whereIn('org.name', $organizationNames)
            ->where('p.name', $positionName)
            ->select([
                'osp.id as organization_structure_position_id',
                'osp.organization_structure_id',
                'osp.position_id',
                'os.organization_id',
            ])
            ->orderBy('osp.id')
            ->first();
    }

    private function ensureUserRole(int $userId, int $roleId): void
    {
        $exists = DB::table('user_role')
            ->where('user_id', $userId)
            ->where('role_id', $roleId)
            ->exists();

        if (!$exists) {
            DB::table('user_role')->insert([
                'user_id' => $userId,
                'role_id' => $roleId,
            ]);
        }
    }

    private function ensurePositionPermissions(int $positionId, array $permissionIds, string $now): void
    {
        foreach ($permissionIds as $permissionId) {
            $exists = DB::table('organization_structure_position_permissions')
                ->where('organization_structure_position_id', $positionId)
                ->where('permission_id', $permissionId)
                ->exists();

            if (!$exists) {
                DB::table('organization_structure_position_permissions')->insert([
                    'organization_structure_position_id' => $positionId,
                    'permission_id' => $permissionId,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'created_by' => 1,
                    'updated_by' => 1,
                ]);
            }
        }
    }

    private function syncOfficerPositionNode(int $officerId, object $positionNode, string $now): void
    {
        DB::table('officers')
            ->where('id', $officerId)
            ->update([
                'organization_id' => (int) $positionNode->organization_id,
                'position_id' => (int) $positionNode->position_id,
                'updated_by' => 1,
                'updated_at' => $now,
            ]);

        $hasTargetJob = DB::table('officer_jobs')
            ->where('officer_id', $officerId)
            ->where('organization_structure_position_id', (int) $positionNode->organization_structure_position_id)
            ->whereNull('end')
            ->exists();

        if ($hasTargetJob) {
            return;
        }

        DB::table('officer_jobs')
            ->where('officer_id', $officerId)
            ->whereNull('end')
            ->update([
                'end' => $now,
                'updated_by' => 1,
                'updated_at' => $now,
            ]);

        DB::table('officer_jobs')->insert([
            'organization_structure_position_id' => (int) $positionNode->organization_structure_position_id,
            'unofficial_position_id' => 0,
            'officer_id' => $officerId,
            'countesy_id' => 0,
            'start' => $now,
            'end' => null,
            'created_by' => 1,
            'updated_by' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function ensureFocalPeople(int $organizationStructureId, int $officerId, string $now): void
    {
        $exists = DB::table('document_organization_focal_people')
            ->where('organization_structure_id', $organizationStructureId)
            ->where('officer_id', $officerId)
            ->whereNull('deleted_at')
            ->exists();

        if (!$exists) {
            DB::table('document_organization_focal_people')->insert([
                'organization_structure_id' => $organizationStructureId,
                'officer_id' => $officerId,
                'priority' => null,
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function resetSequence(string $table, string $sequence): void
    {
        DB::statement(
            "select setval('{$sequence}', (select coalesce(max(id), 1) from {$table}), true)"
        );
    }

    private function ensureFreshDepartmentHeadTransaction(array $accountsByKey, string $now): void
    {
        $sender = $accountsByKey['admin_department'] ?? null;
        $receiver = $accountsByKey['department_head'] ?? null;

        if ($sender == null || $receiver == null) {
            throw new \RuntimeException('Document workflow test accounts are incomplete.');
        }

        $existingTransactionId = DB::table('document_transaction_receivers as receivers')
            ->join('document_transactions as transactions', 'transactions.id', '=', 'receivers.document_transaction_id')
            ->whereNull('receivers.deleted_at')
            ->whereNull('transactions.deleted_at')
            ->where('transactions.sender_id', $sender['user_id'])
            ->where('transactions.previous_transaction_id', 0)
            ->where('transactions.status', \App\Models\Document\Transaction::STATUS_PENDING)
            ->where('receivers.receiver_id', $receiver['officer_id'])
            ->whereNull('receivers.accepted_at')
            ->orderByDesc('transactions.id')
            ->value('transactions.id');

        if ($existingTransactionId) {
            return;
        }

        $timestamp = Carbon::now();
        $documentNumber = 'DOCFLOW-' . $timestamp->format('YmdHis');

        $document = \App\Models\Document\Document::create([
            'public_key' => md5('docflow:test:' . $documentNumber),
            'number' => $documentNumber,
            'objective' => 'Document workflow test for department head',
            'created_by' => $sender['user_id'],
            'updated_by' => $sender['user_id'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $transaction = \App\Models\Document\Transaction::create([
            'document_id' => $document->id,
            'sender_id' => $sender['user_id'],
            'subject' => 'Document workflow test for department head',
            'date_in' => $now,
            'previous_transaction_id' => 0,
            'tpid' => '',
            'status' => \App\Models\Document\Transaction::STATUS_DRAFT,
            'created_by' => $sender['user_id'],
            'updated_by' => $sender['user_id'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        \App\Models\Document\Receiver::create([
            'document_transaction_id' => $transaction->id,
            'receiver_id' => $receiver['officer_id'],
            'created_at' => $now,
            'updated_at' => $now,
            'created_by' => $sender['user_id'],
            'updated_by' => $sender['user_id'],
        ]);

        $transaction->send();
    }

    private function ensureFreshOfficeDpmTransaction(array $accountsByKey, string $now): void
    {
        $sender = $accountsByKey['department_head'] ?? null;
        $cabinetDirector = $accountsByKey['cabinet_director'] ?? null;
        $receiver = $accountsByKey['deputy_pm_office'] ?? null;

        if ($sender == null || $cabinetDirector == null || $receiver == null) {
            throw new \RuntimeException('Document workflow office test accounts are incomplete.');
        }

        $activeTransactionId = DB::table('document_transaction_receivers as receivers')
            ->join('document_transactions as transactions', 'transactions.id', '=', 'receivers.document_transaction_id')
            ->whereNull('receivers.deleted_at')
            ->whereNull('transactions.deleted_at')
            ->where('transactions.sender_id', $cabinetDirector['user_id'])
            ->where('transactions.status', \App\Models\Document\Transaction::STATUS_PENDING)
            ->where('receivers.receiver_id', $receiver['officer_id'])
            ->whereNull('receivers.accepted_at')
            ->orderByDesc('transactions.id')
            ->value('transactions.id');

        if ($activeTransactionId) {
            return;
        }

        $legacyTransactionIds = DB::table('document_transaction_receivers as receivers')
            ->join('document_transactions as transactions', 'transactions.id', '=', 'receivers.document_transaction_id')
            ->whereNull('receivers.deleted_at')
            ->whereNull('transactions.deleted_at')
            ->where('transactions.sender_id', $sender['user_id'])
            ->where('transactions.status', \App\Models\Document\Transaction::STATUS_PENDING)
            ->where('receivers.receiver_id', $receiver['officer_id'])
            ->whereNull('receivers.accepted_at')
            ->orderByDesc('transactions.id')
            ->pluck('transactions.id')
            ->unique()
            ->values()
            ->all();

        if (!empty($legacyTransactionIds)) {
            DB::table('document_transactions')
                ->whereIn('id', $legacyTransactionIds)
                ->update([
                    'deleted_at' => $now,
                    'deleted_by' => $sender['user_id'],
                    'updated_at' => $now,
                    'updated_by' => $sender['user_id'],
                ]);
        }

        $timestamp = Carbon::now();
        $documentNumber = 'DOCFLOW-OFFICE-' . $timestamp->format('YmdHis');

        $document = \App\Models\Document\Document::create([
            'public_key' => md5('docflow:office:' . $documentNumber),
            'number' => $documentNumber,
            'objective' => 'Document workflow test for office DPM',
            'created_by' => $sender['user_id'],
            'updated_by' => $sender['user_id'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $cabinetTransaction = \App\Models\Document\Transaction::create([
            'document_id' => $document->id,
            'sender_id' => $sender['user_id'],
            'subject' => 'Document workflow test for office DPM',
            'date_in' => $now,
            'previous_transaction_id' => 0,
            'tpid' => '',
            'status' => \App\Models\Document\Transaction::STATUS_FINISHED,
            'sent_at' => $now,
            'created_by' => $sender['user_id'],
            'updated_by' => $sender['user_id'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        \App\Models\Document\Receiver::create([
            'document_transaction_id' => $cabinetTransaction->id,
            'receiver_id' => $cabinetDirector['officer_id'],
            'accepted_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
            'created_by' => $sender['user_id'],
            'updated_by' => $sender['user_id'],
        ]);

        $transaction = \App\Models\Document\Transaction::create([
            'document_id' => $document->id,
            'sender_id' => $cabinetDirector['user_id'],
            'subject' => 'Document workflow test for office DPM',
            'date_in' => $now,
            'previous_transaction_id' => $cabinetTransaction->id,
            'tpid' => $cabinetTransaction->id . ':',
            'status' => \App\Models\Document\Transaction::STATUS_DRAFT,
            'created_by' => $cabinetDirector['user_id'],
            'updated_by' => $cabinetDirector['user_id'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        \App\Models\Document\Receiver::create([
            'document_transaction_id' => $transaction->id,
            'receiver_id' => $receiver['officer_id'],
            'created_at' => $now,
            'updated_at' => $now,
            'created_by' => $cabinetDirector['user_id'],
            'updated_by' => $cabinetDirector['user_id'],
        ]);

        $cabinetTransaction->update([
            'next_transaction_id' => $transaction->id,
            'updated_at' => $now,
            'updated_by' => $sender['user_id'],
        ]);

        $transaction->send();
    }
}