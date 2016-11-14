<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class AssetUpdate extends Command {
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'asset:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update image assets.';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'asset:update';

    /**
     * Create a new command instance.
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
    public function fire()
    {
        $config = config('evedata.assets');

        $client = new \GuzzleHttp\Client();

        $res = $client->request('GET', $config['web_url']);

        $matches = array();
        preg_match_all('/' . $config['pattern'] . '/', $res->getBody(), $matches);

        var_dump($matches);

        if (!Storage::exists('eve/assets')) {
            Storage::makeDirectory('eve/assets', 0755, true);
        }

        foreach($matches[1] as $match) {
            $urlParts = parse_url($match);

            $savePath = $this->getLocalStoragePath('eve/assets/' . basename($urlParts['path']));

            $this->info('Downloading ' . basename($urlParts['path']));
            $res = $client->request('GET', $match, array(
                'sink' => $savePath,
                'progress' => function($dlTotal, $dlCurrent, $ulTotal, $ulCurrent) {
                    static $progressBar = null;

                    if (!$dlTotal) {
                        return;
                    }

                    if ($progressBar === false) {
                        return;
                    }

                    if ($progressBar === null) {
                        $progressBar = $this->output->createProgressBar();
                        $progressBar->setFormat("[%bar%] %current%MB/%max%MB %percent:3s%% %elapsed:6s%/%estimated:-6s%");
                        $progressBar->start($dlTotal / 1024 / 1024);
                    }

                    if ($dlCurrent >= $dlTotal) {
                        $progressBar->finish();
                        $this->info('');
                        $progressBar = false;
                    } else {
                        $progressBar->setProgress($dlCurrent / 1024 / 1024);
                    }
                }
            ));

            if (($res->getStatusCode() !== 200) || !File::exists($savePath)) {
                $this->error('Failed to download dump file');
            }

            $this->info('Extracting ' . basename($savePath));


            $zippy = \Alchemy\Zippy\Zippy::load();
            $archive = $zippy->open($savePath);
            $archive->extract($this->getLocalStoragePath('eve/assets'));

            File::delete($savePath);
        }
    }

    /**
     * Return base path used by Storage class for local disk methods
     *
     * @param string $path Relative path to generate.
     * @return string
     */
    private function getLocalStoragePath($path)
    {
        return config('filesystems.disks.local.root') . '/' . $path;
    }
}
