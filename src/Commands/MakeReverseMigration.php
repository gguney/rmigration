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
    protected $signature = 'make:reverseMigration {--create=} {--all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cretes migration from a given table.';
    /**
     * @var array $ignoredColumns
     */
    private $ignoredColumns = ['created_at', 'updated_at', 'deleted_at'];
    private $morph = [
        'text'       => 'text',
        'int'        => 'integer',
        'varchar'    => 'string',
        'tinyint'    => 'tinyInteger',
        'bigint'     => 'bigInteger',
        'smallint'   => 'smallInteger',
        'mediumint'  => 'mediumInteger',
        'blob'       => 'binary',
        'char'       => 'char',
        'text'       => 'text',
        'mediumtext' => 'mediumText',
        'enum'       => 'enum',
        'decimal'    => 'decimal',
        'double'     => 'double',
        'float'      => 'float',
        'bool'       => 'boolean',
        'boolean'    => 'boolean',
        'timestamp'  => 'timestamp',
        'date'       => 'date',
        'datetime'   => 'timestamp',
        'time'       => 'time'
    ];

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
        $dbName = \DB::connection()
                     ->getDatabaseName();

        if ($this->option('all')) {
            $tables = $this->getAllTables();
            foreach ($tables as $table) {
                $tableName = $table->{'Tables_in_' . $dbName};
                $this->generate($tableName);
            }
        } else if ($this->option('create')) {
            $tableName = snake_case($this->option('create'));
            $this->generate($tableName);
        }
    }

    private function generate($tableName)
    {
        $migrationName = $this->getMigrationName($tableName);
        $string = $this->getFields($tableName);
        $keys = $this->getKeys($tableName);
        $stub = $this->replaceStub($tableName, $string, $keys);
        $this->saveFile('migrations', $migrationName, $stub);
    }

    private function getType($column)
    {
        $result = "";
        $array = explode(" ", $column->Type);
        $typeName = $this->getTypeName($array[0]);
        $length = $this->getLength($array[0], $typeName);

        if ($typeName == "int" && $column->Extra == "auto_increment") {
            $result = "->increments('" . $column->Field . "')";
        } else if ($typeName == "varchar") {
            $result = "->string('" . $column->Field . "'" . $length . ")";
        } else if($typeName == "char"){
            $result = "->char('" . $column->Field . "'".$length.")";
        } else {
            if ($typeName == 'decimal' || $typeName == 'double' || $typeName == 'float' ) { //decimal check
                preg_match('#\((.*?)\)#', $array[0], $length);
                $length = ", ".str_replace(',',', ',$length[1]);
                $result = "->" . $this->morph[$typeName] . "('" . $column->Field . "'".$length.")";
            }
            else{
                    $result = "->" . $this->morph[$typeName] . "('" . $column->Field . "')";
            }

        }
        $result .= $this->getUnsigned($array);
        $result .= $this->getUnique($column->Key);
        return $result;

    }

    /**
     * Get length of a column.
     *
     * @param $value
     * @return string
     */
    private function getLength($value)
    {
        $length = "";
        preg_match('/\(([0-9]+?)\)/', $value, $length);
        if (isset($length[1])) {
            $length = ', ' . $length[1];
        }
        return $length;
    }

    /**
     * Get type name of a column.
     *
     * @param $value
     * @return string
     */
    private function getTypeName($value)
    {
        $typeName = preg_replace("/\([^)]+\)/", "", $value);
        return $typeName;
    }

    /**
     * is column unique.
     *
     * @param $value
     * @return string
     */
    private function getUnique($value)
    {
        $unique = ($value == "UNI") ? '->unique()' : '';
        return $unique;
    }

    /**
     * is unsigned.
     *
     * @param $value
     * @return string
     */
    private function getUnsigned($value)
    {
        $unsigned = (isset($value[1]) && (trim($value[1]) == 'unsigned')) ? '->unsigned()' : '';
        return $unsigned;
    }

    /**
     * Replace stub with variables.
     *
     * @param string $columns
     * @param string $string
     * @param $string
     */
    private function replaceStub($tableName, $string, $keys)
    {
        $stub = $this->getStub();
        $className = 'Create' . studly_case($tableName) . 'Table';
        $stub = str_replace(['{CLASS_NAME}', '{TABLE_NAME}', '{FIELDS}', '{KEYS}'],
            [$className, $tableName, $string, $keys], $stub);
        return $stub;
    }

    /**
     * Type to string.
     *
     * @param string $tableName
     * @return string
     */
    private function getFields($tableName)
    {
        $columns = $this->getColumns($tableName);
        $string = "";
        foreach ($columns as $column) {
            if (!in_array($column->Field, $this->ignoredColumns)) {
                $string .= "\n\t\t\t\t";
                $type = $this->getType($column);
                $null = ($column->Null == "NO") ? "" : "->nullable()";
                $default = ($column->Default == null) ? "" : "->default('" . $column->Default . "')";
                $key = $column->Key;
                if ($key == 'MUL') {
                    $indexes[] = $column->Field;
                }
                $string .= '$table' . $type . $null . $default;
                $string .= ';';
            }
        }
        return $string;
    }

    /**
     * TODO : should handle this function better.
     *
     * @param $tableName
     * @return string
     */
    private function getKeys($tableName)
    {
        $string = "\n";
        $queryLines = $this->getCreateQuery($tableName);

        foreach ($queryLines as $queryLine) {
            if (str_contains($queryLine, 'CONSTRAINT')) {
                $string .= "\n\t\t\t\t";

                preg_match("/FOREIGN(.*?)REFERENCES/", $queryLine, $columns);
                preg_match("/REFERENCES .*/", $queryLine, $results);
                $a = explode('`', $results[0]);
                $foreignTable = $a[1];
                $foreignColumn = $a[3];
                $key = explode('`', $columns[1])[1];
                $string .= '$table->foreign(\'' . $key . '\')->references(\'' . $foreignColumn . '\')->on(\'' . $foreignTable . '\');';
            }
        }
        return $string;
    }

    /**
     * Set table column names.
     *
     * @param string $tableName
     * @return array
     */
    private function getColumns($tableName)
    {
        $columns = \DB::select('SHOW COLUMNS FROM ' . $tableName);
        return $columns;
    }

    /**
     * Get create query of a table.
     *
     * @param string $tableName
     * @return array
     */
    private function getCreateQuery($tableName)
    {
        $createStatement = \DB::select('SHOW CREATE TABLE ' . $tableName)[0]->{'Create Table'};
        //dd(explode("\n", $createStatement));
        //preg_match_all("/`(.+)` (\w+)\(? ?(\d*) ?\)?/", $createStatement, $_matches, PREG_SET_ORDER);
        return explode(',', $createStatement);
    }

    /**
     * Get stub.
     *
     * @return string
     */
    private function getStub()
    {
        $stub = file_get_contents(__DIR__ . '/Migration.stub') or die("Unable to open file!");
        return $stub;
    }

    /**
     * Save file.
     *
     * @param string $path
     * @param string $fileName
     * @param string $txt
     */
    private function saveFile($path, $fileName, $txt)
    {
        $path = database_path($path . '/' . $fileName . '.php');
        $file = fopen($path, "w") or die("Unable to open file!");
        fwrite($file, $txt);
        fclose($file);
        $this->info($fileName . ' is created in ' . $path . ' folder.');
    }

    /**
     * Get migration name
     *
     * @param sting $tableName
     * @return string
     */
    private function getMigrationName($tableName)
    {
        return date('Y_m_d_His') . '_create_' . $tableName . '_table';
    }

    private function getAllTables()
    {
        $tables = \DB::select('SHOW TABLES');
        return $tables;

    }
}
