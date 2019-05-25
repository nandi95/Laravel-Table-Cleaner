<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Class DeleteTableDuplicates
 *
 * @package App\Console\Commands
 */
class DeleteTableDuplicates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delete-table-duplicates {--table=} {--column=} {--force-delete=} {--delete-previous=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete duplicates leaving only most recent records.';

    /**
     * @var bool
     */
    protected $hadDuplicates = false;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (!$table = $this->option('table')) {
            $table = $this->ask('What is the table name');
            if (!Schema::hasTable($table)) {
                $this->error('No such table, please try again.');
                die;
            }
            if (!Schema::hasColumn($table, 'id')) {
                $this->error('Only table that has an incrementing primary key "id" column is compatible.');
                die;
            }
        } else {
            if (!Schema::hasTable($table)) {
                $this->error('No such table, please try again.');
                die;
            }
        }

        if (!$column = $this->option('column')) {
            $column = $this->ask('Which column the script should search by');
            if (!Schema::hasColumn($table, $column)) {
                $this->error('No such column in the given table, please try again.');
                die;
            }
        } else {
            if (!Schema::hasColumn($table, $column)) {
                $this->error('No such column in the given table, please try again.');
                die;
            }
        }

        if (!$isForceDeletes = $this->option('force-delete')) {
            $isForceDeletes = $this->ask('Use force delete (true/false)', 'false');
        }
        if ($isForceDeletes === 'true' && !Schema::hasColumn($table, 'deleted_at')) {
            $this->error('The given table does not have a "deleted_at" column.');
            die;
        }

        $isForceDeletes = $isForceDeletes === 'true' ? true : false;

        if (!$deleteOldRecords = $this->option('delete-previous')) {
            $deleteOldRecords = $this->ask('Should the script delete already soft-deleted records (true/false)', 'false');
        }
        $deleteOldRecords = $deleteOldRecords === 'true' ? true : false;

        if ($deleteOldRecords) {
            DB::table($table)->where('deleted_at', '!=', null)->delete();
        }

        DB::table($table)->orderBy('id', 'DESC')->chunk(100, function ($records) use ($table, $column, $isForceDeletes) {
            $this->line('');
            foreach ($records as $record) {
                if ($isForceDeletes) {
                    $duplicateRecordsNum = DB::table($table)->where($column, $record->{$column})->where('id', '<', $record->id)->delete();
                    if ($duplicateRecordsNum !== 0) {
                        $this->hadDuplicates = true;
                        $this->line($duplicateRecordsNum . ' duplicates found and deleted for the record->id: ' . $record->id . '.');
                    }
                } else {
                    $duplicateRecordsNum = DB::table($table)->where($column, $record->{$column})->where('id', '<', $record->id)->where('deleted_at', '=', null)->update(['deleted_at' => now()]);
                    if ($duplicateRecordsNum !== 0) {
                        $this->hadDuplicates = true;
                        $this->line($duplicateRecordsNum . ' duplicates found and soft-deleted for the record->id: ' . $record->id . '.');
                    }
                }
            }
        });
        $this->line('');
        $this->info(($this->hadDuplicates ? 'All existing duplicates have been removed in the \'' : 'No duplicates found in the \'') . $table . '\' table.');
    }
}
