<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ProjectInfo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'project:info';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        return $this->info($this->projectInfo());
    }

    public function projectInfo()
    {
        $content = file_get_contents('composer.json');
        $content = json_decode($content, true);
        foreach ($content['require'] as $key => $value) {
            echo $key . ' => ' . $value . PHP_EOL;
        }
    }
}
