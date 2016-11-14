<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class SDEUpdate extends Command {
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'sde:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update static data export (SDE) data.';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sde:update {--f|force : Force download of SDE dump}';

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
        $config = config('evedata.sde');

        if (!Storage::exists('sde')) {
            Storage::makeDirectory('sde');
        }

        $client = new \GuzzleHttp\Client();

        $dumpPath = $this->getLocalStoragePath('sde/' . $config['dump']);

        // Check for existing dump file and validate if it's out of date or not.
        if (File::exists($dumpPath) && !$this->option('force')) {
            $res = $client->request('GET', $config['web_url'] . $config['check']);

            if ($res->getStatusCode() !== 200) {
                $this->error('Failed to download check file');
            }

            $contents = preg_split('/[\s]+/', trim($res->getBody()));
            $check = $contents[0];
            $hash = md5_file($dumpPath);

            if ($check === $hash) {
                $this->info('No new data available');
                return;
            }
        }

        $webUrl = $config['web_url'] . $config['dump'];
        $this->info("Downloading dump file: $webUrl");
        $res = $client->request('GET', $webUrl, array(
            'sink' => $dumpPath,
            'progress' => function($dlTotal, $dlCurrent, $ulTotal, $ulCurrent) {
                static $progressBar = null;

                if (!$dlTotal) {
                    return;
                }

                if ($progressBar === null) {
                    $progressBar = $this->output->createProgressBar();
                    $progressBar->setFormat("[%bar%] %current%MB/%max%MB %percent:3s%% %elapsed:6s%/%estimated:-6s%");
                    $progressBar->start($dlTotal / 1024 / 1024);
                }

                $progressBar->setProgress($dlCurrent / 1024 / 1024);

                if ($dlCurrent >= $dlTotal) {
                    $progressBar->finish();
                    $this->info('');
                }
            }
        ));
        if (($res->getStatusCode() !== 200) || !File::exists($dumpPath)) {
            $this->error('Failed to download dump file');
        }

        $this->info('Extracting ' . basename($dumpPath));

        Storage::makeDirectory('sde/extracted');

        $zippy = \Alchemy\Zippy\Zippy::load();
        $archive = $zippy->open($dumpPath);
        $archive->extract($this->getLocalStoragePath('sde/extracted'));

        $files = Storage::allFiles('sde/extracted');
        $this->info('Importing');
        $progressBar = $this->output->createProgressBar();
        $progressBar->setFormat("%message%\t[%bar%]");
        foreach($files as $file) {
            $progressBar->setMessage(basename($file));
            $progressBar->start();

            $dumpContents = Storage::get($file);
            $queries = $this->splitSql($dumpContents);

            $progressBar->setFormat("%message%\t[%bar%] %current%/%max% %percent:3s%% %elapsed:6s%/%estimated:-6s%");
            $progressBar->start(count($queries));

            foreach ($queries as $query) {
                DB::unprepared($query);
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->info('');
        }

        Storage::deleteDirectory('sde/extracted');
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

    /**
     * Splits SQL dump into individual queries
     *
     * @param $sql
     * @param $delimiter
     * @return array
     */
    private function splitSql($sql, $delimiter = ';')
    {
        // Split up our string into "possible" SQL statements.
        $tokens = explode($delimiter, $sql);

        // try to save mem.
        $sql = "";
        $output = array();

        // we don't actually care about the matches preg gives us.
        $matches = array();

        // this is faster than calling count($oktens) every time thru the loop.
        $token_count = count($tokens);
        for ($i = 0; $i < $token_count; $i++)
        {
            // Don't wanna add an empty string as the last thing in the array.
            if (($i != ($token_count - 1)) || (strlen($tokens[$i] > 0)))
            {
                // This is the total number of single quotes in the token.
                $total_quotes = preg_match_all("/'/", $tokens[$i], $matches);
                // Counts single quotes that are preceded by an odd number of backslashes,
                // which means they're escaped quotes.
                $escaped_quotes = preg_match_all("/(?<!\\\\)(\\\\\\\\)*\\\\'/", $tokens[$i], $matches);

                $unescaped_quotes = $total_quotes - $escaped_quotes;

                // If the number of unescaped quotes is even, then the delimiter did NOT occur inside a string literal.
                if (($unescaped_quotes % 2) == 0)
                {
                    // It's a complete sql statement.
                    $output[] = $tokens[$i];
                    // save memory.
                    $tokens[$i] = "";
                }
                else
                {
                    // incomplete sql statement. keep adding tokens until we have a complete one.
                    // $temp will hold what we have so far.
                    $temp = $tokens[$i] . $delimiter;
                    // save memory..
                    $tokens[$i] = "";

                    // Do we have a complete statement yet?
                    $complete_stmt = false;

                    for ($j = $i + 1; (!$complete_stmt && ($j < $token_count)); $j++)
                    {
                        // This is the total number of single quotes in the token.
                        $total_quotes = preg_match_all("/'/", $tokens[$j], $matches);
                        // Counts single quotes that are preceded by an odd number of backslashes,
                        // which means they're escaped quotes.
                        $escaped_quotes = preg_match_all("/(?<!\\\\)(\\\\\\\\)*\\\\'/", $tokens[$j], $matches);

                        $unescaped_quotes = $total_quotes - $escaped_quotes;

                        if (($unescaped_quotes % 2) == 1)
                        {
                            // odd number of unescaped quotes. In combination with the previous incomplete
                            // statement(s), we now have a complete statement. (2 odds always make an even)
                            $output[] = $temp . $tokens[$j];

                            // save memory.
                            $tokens[$j] = "";
                            $temp = "";

                            // exit the loop.
                            $complete_stmt = true;
                            // make sure the outer loop continues at the right point.
                            $i = $j;
                        }
                        else
                        {
                            // even number of unescaped quotes. We still don't have a complete statement.
                            // (1 odd and 1 even always make an odd)
                            $temp .= $tokens[$j] . $delimiter;
                            // save memory.
                            $tokens[$j] = "";
                        }

                    } // for..
                } // else
            }
        }

        return $output;
    }
}
