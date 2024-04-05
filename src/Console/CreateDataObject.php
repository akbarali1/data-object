<?php
declare(strict_types=1);

namespace Akbarali\DataObject\Console;

use Illuminate\Console\GeneratorCommand;

class CreateDataObject extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'do:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create new Data Object';
    protected $type        = 'DataObject';

    protected string $folder;
    protected        $name;
    protected string $rules;
    protected        $rulesArray = null;
    protected string $fullPath;
    protected        $nameSpace;
    protected string $tmpFile;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->folder    = ucfirst($this->ask('New Folder Name. Default:Default', 'default'));
        $this->name      = ucfirst($this->ask('Action Data Name.', 'store'));
        $this->rules     = str_replace("'", '"', $this->ask('Action Data Rules.', '[]'));
        $this->nameSpace = $this->qualifyClass($this->folder);
        $this->fullPath  = $this->getPath($this->qualifyClass($this->folder)."\\".$this->name.$this->folder.$this->type); # faylning yaratilishi kerak bo`lgan full path
        /*$this->table(
            ["Folder", 'New File', "NameSpace"],
            [[dirname($this->fullPath), $this->fullPath, $this->nameSpace]]
        );*/
        //        if ($this->confirm("Is this information correct?")) {
        $this->writeActionData();
        //            return true;
        //        }
        //$this->info("Bekor qilindi");
        return true;
    }

    protected function writeActionData(): void
    {
        $path = $this->fullPath;
        if (!$this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }

        if ($this->files->exists($path)) {
            if ($this->confirm("ActionData already exists. Dellete ?")) {
                $this->unlinkFile($path);
            } else {
                return;
            }
        }
        $class = $this->buildClass($path);
        $this->createFile($path, $class);
        $this->unlinkFile($this->tmpFile);
        sleep(1);
        $this->info('Folder: '.dirname($this->fullPath));
        $this->info('File: '.$path);

        if ($this->confirm("Check the files. Select \"Yes\" to delete the file if there are errors ?")) {
            $this->unlinkFile($this->fullPath);
            $this->info('File deleted');

            return;
        }
        $this->info('All saved');
    }

    /**
     * Build the class with the given name.
     *
     * @param string $name
     * @return string
     */
    protected function buildClass($name): string
    {
        $this->stringToArray();
        $property = '';
        if (is_array($this->rulesArray)) {
            foreach ($this->rulesArray as $key => $item) {
                $property .= "public $".$key.";\n    ";
            }
        }
        $stub = $this->files->get($this->getStub());

        return $this->replaceNamespace($stub, $this->nameSpace)
            ->replaceProperty($stub, $property)
            ->replaceRules($stub, $this->rules)
            ->replaceClass($stub, pathinfo($this->fullPath)['filename']);
    }

    public function stringToArray(): void
    {
        $rulesTmpName  = dirname($this->fullPath)."/rulesTmp.php";
        $this->tmpFile = $rulesTmpName;
        $this->unlinkFile($this->tmpFile);
        $stubTmp = $this->files->get($this->getStubTmp());
        $stubTmp = $this->replaceRules($stubTmp, $this->rules, true);
        $this->createFile($rulesTmpName, $stubTmp);
        $this->rulesArray = $this->files->exists($rulesTmpName) ? require $rulesTmpName : false;
        if ($this->rulesArray === false) {
            $this->error("Rules Tmp Not Found");
        }
        //        $this->rulesArray = json_decode(json_encode($this->rulesArray, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
    }

    public function unlinkFile($fileName): void
    {
        if ($this->files->exists($fileName)) {
            unlink($fileName);
        }
    }

    public function createFile($path, $name): void
    {
        file_put_contents($path, $name);
    }

    protected function replaceNamespace(&$stub, $name): CreateActionData|static
    {
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

    protected function replaceProperty(&$stub, $name): CreateActionData|static
    {
        $stub = str_replace(
            ['{{ property }}', '{{property}}'],
            $name,
            $stub
        );


        return $this;
    }

    protected function replaceRules(&$stub, $name, bool $string = false): array|CreateActionData|string|static
    {
        $stub = str_replace(
            ['{{ rules }}', '{{rules}}'],
            $name,
            $stub
        );

        return $string ? $stub : $this;
    }


    /**
     * Get the default namespace for the class.
     *
     * @param string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\ActionData';
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub(): string
    {
        return $this->resolveStubPath('/stubs/action-data.stub');
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStubTmp(): string
    {
        return $this->resolveStubPath('/stubs/rules-tmp.stub');
    }

    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param string $stub
     * @return string
     */
    protected function resolveStubPath($stub): string
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }
}
