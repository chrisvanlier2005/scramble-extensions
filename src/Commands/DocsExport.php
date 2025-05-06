<?php

namespace Lier\ScrambleExtensions\Commands;

use cebe\openapi\Reader;
use cebe\openapi\Writer;
use Dedoc\Scramble\Generator;
use Dedoc\Scramble\Scramble;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Webmozart\Assert\Assert;

final class DocsExport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'docs:export
        {--path= : The path to save the exported YAML file}
        {--api=default : The API to export a documentation for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export the OpenAPI documentation to a YAML file';

    /**
     * Execute the console command.
     *
     * @param \Dedoc\Scramble\Generator $generator
     * @return int
     * @throws \cebe\openapi\exceptions\TypeErrorException
     * @throws \cebe\openapi\exceptions\IOException
     */
    public function handle(Generator $generator): int
    {
        $api = $this->option('api');
        $path = $this->option('path') ?? sprintf('api-%s.yml', $api);

        Assert::string($api);
        Assert::string($path);

        $config = Scramble::getGeneratorConfig($api);

        $spec = Reader::readFromJson(
            json_encode($generator($config), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        );

        Writer::writeToYamlFile($spec, $path);

        $this->info("Exported specification to {$path}.");

        $this->info('Formatting...');

        $process = new Process(['npx', 'openapi-format', $path, '-o', $path]);
        $process->setTimeout(60);
        $process->run();

        $this->info("{$path} formatted.");

        return 0;
    }
}
