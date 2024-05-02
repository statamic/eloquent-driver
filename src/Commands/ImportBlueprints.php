<?php

namespace Statamic\Eloquent\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Facade;
use Statamic\Console\RunsInPlease;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Fieldset;
use Statamic\Facades\File;
use Statamic\Facades\YAML;
use Statamic\Support\Arr;
use Statamic\Support\Str;

class ImportBlueprints extends Command
{
    use RunsInPlease;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statamic:eloquent:import-blueprints';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports file-based blueprints & fieldsets into the database.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->useDefaultRepositories();

        $this->importBlueprints();
        $this->importFieldsets();

        return 0;
    }

    private function useDefaultRepositories(): void
    {
        Facade::clearResolvedInstance(\Statamic\Fields\BlueprintRepository::class);
        Facade::clearResolvedInstance(\Statamic\Fields\FieldsetRepository::class);

        app()->singleton(
            'Statamic\Fields\BlueprintRepository',
            'Statamic\Fields\BlueprintRepository'
        );

        app()->singleton(
            'Statamic\Fields\FieldsetRepository',
            'Statamic\Fields\FieldsetRepository'
        );
    }

    private function importBlueprints(): void
    {
        $directory = resource_path('blueprints');

        $files = File::withAbsolutePaths()
            ->getFilesByTypeRecursively($directory, 'yaml');

        $this->withProgressBar($files, function ($path) use ($directory) {
            [$namespace, $handle] = $this->getNamespaceAndHandle(
                Str::after(Str::before($path, '.yaml'), $directory.'/')
            );

            $contents = YAML::file($path)->parse();
            // Ensure sections are ordered correctly.
            if (isset($contents['tabs']) && is_array($contents['tabs'])) {
                $count = 0;
                $contents['tabs'] = collect($contents['tabs'])
                    ->map(function ($tab) use (&$count) {
                        $tab['__count'] = $count++;

                        if (isset($tab['sections']) && is_array($tab['sections'])) {
                            $sectionCount = 0;
                            $tab['sections'] = collect($tab['sections'])
                                ->map(function ($section) use (&$sectionCount) {
                                    $section['__count'] = $sectionCount++;

                                    return $section;
                                });
                        }

                        return $tab;
                    })
                    ->toArray();
            }

            $blueprint = Blueprint::make()
                ->setHidden(Arr::pull($contents, 'hide'))
                ->setOrder(Arr::pull($contents, 'order'))
                ->setInitialPath($path)
                ->setHandle($handle)
                ->setNamespace($namespace ?? null)
                ->setContents($contents);

            $lastModified = Carbon::createFromTimestamp(File::lastModified($path));

            app('statamic.eloquent.blueprints.blueprint_model')::firstOrNew([
                'handle' => $blueprint->handle(),
                'namespace' => $blueprint->namespace() ?? null,
            ])
                ->fill(['data' => $blueprint->contents(), 'created_at' => $lastModified, 'updated_at' => $lastModified])
                ->save();
        });

        $this->components->info('Blueprints imported successfully.');
    }

    private function importFieldsets(): void
    {
        $directory = resource_path('fieldsets');

        $files = File::withAbsolutePaths()
            ->getFilesByTypeRecursively($directory, 'yaml');

        $this->withProgressBar($files, function ($path) use ($directory) {
            $basename = Str::after($path, Str::finish($directory, '/'));
            $handle = Str::before($basename, '.yaml');
            $handle = str_replace('/', '.', $handle);

            $fieldset = Fieldset::make($handle)->setContents(YAML::file($path)->parse());

            $lastModified = Carbon::createFromTimestamp(File::lastModified($path));

            app('statamic.eloquent.blueprints.fieldset_model')::firstOrNew([
                'handle' => $fieldset->handle(),
            ])
                ->fill(['data' => $fieldset->contents(), 'created_at' => $lastModified, 'updated_at' => $lastModified])
                ->save();
        });

        $this->components->info('Fieldsets imported successfully.');
    }

    private function getNamespaceAndHandle(string $blueprint): array
    {
        $blueprint = str_replace('/', '.', $blueprint);
        $parts = explode('.', $blueprint);
        $handle = array_pop($parts);
        $namespace = implode('.', $parts);
        $namespace = empty($namespace) ? null : $namespace;

        return [$namespace, $handle];
    }
}
