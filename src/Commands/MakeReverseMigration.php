<?php
namespace GGuney\RMigration\Commands;

use Illuminate\Console\Command;

class MakeReverseMigration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:reverseMigration {table_name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cretes migration from a given table.';

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
        $seedsPath = 'migrations/';

        $tableName = lcfirst($this->argument('table_name'));
        $migrationName = camel_case($tableName);
        $columns = $this->setColumns($tableName);
    }

    /**
     * Get table column names.
     *
     * @param string $tableName
     * @return array
     */
    private function setColumns($tableName)
    {
        $columns = \DB::select('SHOW COLUMNS FROM '.$tableName);
        dd($columns);
        $createStatement = \DB::select('SHOW CREATE TABLE '.$tableName)[0]->{'Create Table'};
        dd(explode("\n", $createStatement));
        $a = preg_match_all("/`(.+)` (\w+)\(? ?(\d*) ?\)?/", $createStatement, $_matches, PREG_SET_ORDER);
        dd($_matches);
        return explode(',',$createStatement);
    }



}
