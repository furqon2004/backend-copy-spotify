<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeRepositoryCommand extends Command
{
    protected $signature = 'make:repository {name}';
    protected $description = 'Create a new repository class and interface';

    public function handle()
    {
        $name = $this->argument('name');
        $this->createInterface($name);
        $this->createRepository($name);

        $this->info("Repository $name created successfully!");
    }

    protected function createInterface($name)
    {
        $path = app_path("Repositories/Interfaces/{$name}Interface.php");
        File::ensureDirectoryExists(app_path('Repositories/Interfaces'));

        $content = "<?php\n\nnamespace App\Repositories\Interfaces;\n\ninterface {$name}Interface\n{\n}";
        File::put($path, $content);
    }

    protected function createRepository($name)
    {
        $path = app_path("Repositories/{$name}.php");
        File::ensureDirectoryExists(app_path('Repositories'));

        $content = "<?php\n\nnamespace App\Repositories;\n\nuse App\Repositories\Interfaces\\{$name}Interface;\n\nclass {$name} implements {$name}Interface\n{\n}";
        File::put($path, $content);
    }
}