<?php
declare(strict_types=1);

namespace Akbarali\DataObject\Console;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Console\DatabaseInspectionCommand;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;
use Illuminate\Support\Str;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\suggest;
use function Laravel\Prompts\search;

class CreateDataObject extends DatabaseInspectionCommand
{
    #region Properties
    protected $signature = 'do:create
                            {type? : The type of the data object}
                            {--database= : The database connection}
                            {--model : The name of the model}
                            {--table : Output the table information as JSON}';


    protected        $description = 'Create new Data Object';
    protected string $type        = 'DataObject';

    protected int $scrollSize = 15;
    protected     $name;

    protected            $connection;
    protected            $schema;
    protected string     $fullPath;
    protected string     $fileName;
    protected string     $case;
    protected array      $cases                 = [
        'camel' => "camelCase",
        'snake' => 'snake_case',
        //'kebab'  => 'kebab-case',
        //'studly' => 'StudlyCase',
    ];
    private array        $createDataObjectTypes = [
        'tables'        => 'Tables',
        'models'        => 'Models',
        'tables_search' => 'Tables Search',
        'models_search' => 'Models Search',
    ];
    protected Filesystem $files;

    #endregion

    public function __construct(Filesystem $files, Composer $composer = null)
    {
        parent::__construct($composer);
        $this->files = $files;
    }

    /**
     * @throws FileNotFoundException
     */
    public function handle(ConnectionResolverInterface $connections): int
    {
        if (!$this->ensureDependenciesExist()) {
            return 1;
        }

        $this->connection = $connections->connection($this->input->getOption('database'));
        $this->schema     = $this->connection->getDoctrineSchemaManager();

        $this->registerTypeMappings($this->connection->getDoctrineConnection()->getDatabasePlatform());
        $name = $this->argument('type') ?: select('Data Object yaratish turini tanlang', $this->createDataObjectTypes);

        if (!array_key_exists($name, $this->createDataObjectTypes)) {
            $this->components->warn("Invalid type [$name]");

            return 0;
        }

        $tableName = match ($name) {
            'tables'        => $this->tables(),
            'models'        => $this->models(),
            'tables_search' => $this->searchTables(),
            'models_search' => $this->searchModels(),
        };

        $this->createDataObject($tableName);

        return 0;
    }

    protected function models(): string
    {
        $models    = $this->getAllModels();
        $select    = select(
            'Modelni tanlang',
            $models,
            scroll  : $this->scrollSize * 2,
            required: true
        );
        $modelName = 'App\Models\\'.$select;

        if (is_dir(app_path('Models/'.$select))) {
            $this->components->warn(sprintf("Model [%s] is a directory.", $modelName));

            return $this->models();
        }

        if (!class_exists($modelName)) {
            $this->components->warn(sprintf("Model [%s] not found.", $modelName));

            return $this->models();
        }

        return (new $modelName())->getTable();
    }

    protected function searchModels()
    {
        $collection = collect($this->getAllModels())->filter(fn($model) => !str_contains($model, 'Folder'));
        $select     = search(
            'Search for a model',
            options: fn($search) => $collection->filter(fn($model) => str_contains(strtolower($model), strtolower($search)))->toArray(),
            scroll : $this->scrollSize,
        );

        $modelName = 'App\Models\\'.$select;

        if (is_dir(app_path('Models/'.$select))) {
            $this->components->warn(sprintf("Model [%s] is a directory.", $modelName));

            return $this->models();
        }
        if (!class_exists($modelName)) {
            $this->components->warn(sprintf("Model [%s] not found.", $modelName));

            return $this->models();
        }

        return (new $modelName())->getTable();
    }

    protected function tables(): string
    {
        return select(
            'Tableni tanlang',
            collect($this->schema->listTables())->flatMap(fn(Table $table) => [$table->getName()])->toArray(),
            scroll  : $this->scrollSize,
            required: true
        );
    }

    protected function searchTables(): string
    {
        return search(
            'Search for a table',
            options: fn($search) => collect($this->schema->listTables())
                ->flatMap(fn(Table $table) => [$table->getName()])
                ->filter(fn($table) => str_contains($table, $search))->values()->toArray(),
            scroll : $this->scrollSize,
        );
    }

    private function getAllModels(): array
    {
        $models = [];
        $path   = app_path('Models');
        $files  = scandir($path);
        foreach ($files as $file) {
            if (is_file($path.'/'.$file)) {
                $file          = str_replace('.php', '', $file);
                $models[$file] = $file;
            }
            if ($file !== '.' && $file !== '..' && is_dir($path.'/'.$file)) {
                $subFiles      = scandir($path.'/'.$file);
                $models[$file] = $file." (Folder)";
                foreach ($subFiles as $subFile) {
                    if (is_file($path.'/'.$file.'/'.$subFile)) {
                        $subFile                     = str_replace('.php', '', $subFile);
                        $models[$file.'\\'.$subFile] = '-- '.$subFile;
                    }
                }
            }
        }

        return $models;
    }

    private function getFolder(): array
    {
        $path   = app_path('DataObjects');
        $files  = scandir($path);
        $models = [];
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..' && is_dir($path.'/'.$file)) {
                $models[$file] = $file;
            }
        }

        return $models;
    }

    /**
     * @throws FileNotFoundException
     */
    protected function createDataObject($table): void
    {
        if (!$this->schema->tablesExist([$table])) {
            $this->components->warn(sprintf("Table [%s] doesn't exist.", $table));

            return;
        }

        /** @var Table $table */
        $table                 = $this->schema->introspectTable($table);
        $defaultDataObjectName = Str::studly($table->getName());
        $defaultDataObjectName = Str::endsWith($defaultDataObjectName, 's') ? Str::substr($defaultDataObjectName, 0, -1) : $defaultDataObjectName;
        $defaultDataObjectName = Str::singular($defaultDataObjectName);
        $defaultDataObjectName = Str::of($defaultDataObjectName)->append('Data')->__toString();

        $this->fullPath = suggest(
            label      : 'DataObject Papkani kiriting:',
            options    : $this->getFolder(),
            placeholder: "DataObject saqlanishi kerak bo'lgan papka nomi (optional)",
        );

        $this->fullPath = 'DataObjects/'.$this->fullPath;
        $this->fileName = text(
            'DataObject nomini kiriting:',
            default : $defaultDataObjectName,
            required: 'DataObject nomi majburiy'
        );
        $this->case     = select("DataObject Property case turini tanlang", $this->cases, default: 'camel');
        $modelColumns   = $this->columns($table);

        $this->createDataObjectFile($modelColumns);
    }

    /**
     * @throws FileNotFoundException
     */
    private function createDataObjectFile(&$modelColumns): void
    {
        $path = app_path(Str::finish($this->fullPath, '/').$this->fileName.'.php');

        if ($this->files->exists($path)) {
            $this->components->error('DataObject allaqachon mavjud');
            if (!confirm("O'chirib yangi yaratishni hohlaysizmi?", false, "Ha", "Yo'q")) {
                $this->components->warn('Bekor qilindi');

                return;
            }

            $this->deleteFile($path);
        }

        $class = $this->buildClass($modelColumns);
        $this->createFile($path, $class);
        $this->components->info('DataObject yaratildi');
        $this->components->twoColumnDetail('<fg=green;options=bold>Folder:</>', dirname($path));
        $this->components->twoColumnDetail('<fg=green;options=bold>File:</>', $path);
        $this->components->twoColumnDetail('<fg=green;options=bold>Size:</>', $this->formatSize($this->files->size($path)));

        if (confirm('Agar xato yaratilgan deb hisoblasangiz "Ha" ni tanlang ?', false, 'Ha', 'Yo`q')) {
            $this->deleteFile($path);
        }
    }

    private function formatSize(int $bytes): string
    {
        if ($bytes < 1000 * 1024) {
            return number_format($bytes / 1024, 2).' KB';
        }

        if ($bytes < 1000 * 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }

        if ($bytes < 1000 * 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        }

        return number_format($bytes / 1099511627776, 2).' TB';
    }

    private function createFile($path, $name): void
    {
        if (!$this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }

        file_put_contents($path, $name);
    }

    private function deleteFile($fileName): void
    {
        if ($this->files->exists($fileName)) {
            unlink($fileName);
            $this->components->warn('File : '.$fileName.' deleted');

            //folder empty delete folder
            $folder = dirname($fileName);
            if (count(scandir($folder)) === 2) {
                rmdir($folder);
                $this->components->warn('Folder : '.$folder.' deleted');
            }
        }
    }

    /**
     * @throws FileNotFoundException
     */
    private function buildClass(&$columns): string
    {
        $properties = '';
        foreach ($columns as $column) {
            $properties .= "    ".$this->propertyGenerate($column);
        }

        $stub = $this->files->get($this->getStub());

        return $this->replaceNamespace($stub, $this->fullPath)
            ->replaceProperty($stub, $properties)
            ->replaceClass($stub, $this->fileName);
    }

    private function propertyGenerate($column): string
    {
        $type = match ($column['type']) {
            'integer', 'bigint'      => 'int',
            "decimal", "float"       => "float",
            'string', 'text', 'uuid' => 'string',
            'boolean'                => 'bool',
            'datetime', 'date'       => "\Carbon\Carbon",
            'json', 'jsonb'          => 'array',
            default                  => '?string',
        };

        $readOnly = false;
        if (str_contains($column['column'], 'id')) {
            $type     = 'readonly int';
            $readOnly = true;
        }

        $key     = $this->convertCase($column['column']);
        $default = $column['default'] ? ' = '.$column['default'] : '';
        $default = $readOnly ? '' : $default;

        return 'public '.$type.' $'.$key.$default.';'.PHP_EOL;
    }

    protected function convertCase($string): string
    {
        return match ($this->case) {
            'camel' => Str::camel($string),
            'snake' => Str::snake($string),
            //'kebab'  => Str::kebab(Str::snake($string)),
            //'studly' => Str::studly(Str::snake($string)),
            default => $string,
        };
    }

    private function replaceNamespace(&$stub, $name): static
    {
        $name     = str_replace('/', '\\', $name);
        $name     = trim($name, '\\');
        $name     = 'App\\'.$name;
        $searches = [
            ['{{ namespace }}', '{{ rootNamespace }}', '{{ namespacedUserModel }}'],
            ['{{namespace}}', '{{rootNamespace}}', '{{namespacedUserModel}}'],
        ];

        foreach ($searches as $search) {
            $stub = str_replace(
                $search,
                $name,
                $stub
            );
        }

        return $this;
    }

    private function replaceProperty(&$stub, $name): static
    {
        $stub = str_replace(
            ['{{ properties }}', '{{properties}}'],
            $name,
            $stub
        );

        return $this;
    }

    private function replaceClass($stub, $name): array|string
    {
        $class = str_replace($this->getNamespace($name).'\\', '', $name);

        return str_replace(['DummyClass', '{{ class }}', '{{class}}'], $class, $stub);
    }

    private function getNamespace($name): string
    {
        return trim(implode('\\', array_slice(explode('\\', $name), 0, -1)), '\\');
    }

    public function getStub(): string
    {
        return $this->resolveStubPath('/stubs/data-object.stub');
    }

    protected function resolveStubPath($stub): string
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }

    protected function columns(Table $table): array
    {
        return collect($table->getColumns())->map(fn(Column $column) => [
            'column'  => $column->getName(),
            'default' => $column->getDefault(),
            'type'    => $column->getType()->getName(),
        ])->toArray();
    }

}
