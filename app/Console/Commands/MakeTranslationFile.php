<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeTranslationFile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:translation {names*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create multiple translation files in both English and Arabic';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get the list of file names from the argument
        $names = $this->argument('names');

        foreach ($names as $name) {
            // Define the paths for English and Arabic translation files
            $enPath = resource_path("lang/en/{$name}.php");
            $arPath = resource_path("lang/ar/{$name}.php");

            // Default content for the translation files
            $defaultContent = "<?php\n\nreturn [\n    // Add your translations here\n];\n";

            // Check if the file already exists
            if (File::exists($enPath) || File::exists($arPath)) {
                $this->warn("⚠️ Translation file '{$name}.php' already exists in one of the languages!");
                continue;
            }

            // Create directories if they don’t exist
            File::ensureDirectoryExists(dirname($enPath));
            File::ensureDirectoryExists(dirname($arPath));

            // Create the translation files
            File::put($enPath, $defaultContent);
            File::put($arPath, $defaultContent);

            // Success message for each file
            $this->info("✅ Translation files created for '{$name}':");
            $this->info("   - {$enPath}");
            $this->info("   - {$arPath}");
        }

        $this->info("\n🎉 All requested translation files have been created successfully!");
    }
}
