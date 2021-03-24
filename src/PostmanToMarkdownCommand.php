<?php

namespace AndPHP\Postman;

use Illuminate\Console\Command;

class PostmanToMarkdownCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'docs:postmanMd';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Data conversion from postman JSON format to markdown format';

    protected $files;
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->files = $this->getDir(app_path().'/ApiDocs/PostmanJson');
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $post = new PostmanToMarkdown();
        foreach ($this->files as $fileName){
            $post->getMarkdownDocument($fileName, app_path().'/ApiDocs/Markdown');
            $this->info($fileName.' created successfully.');
        }

    }

    /**
     * Write a string as information output.
     *
     * @param  string  $string
     * @param  int|string|null  $verbosity
     * @return void
     */
    public function info($string, $verbosity = null)
    {
        $this->line($string, 'info', $verbosity);
    }

    /**
     * Write a string as standard output.
     *
     * @param  string  $string
     * @param  string|null  $style
     * @param  int|string|null  $verbosity
     * @return void
     */
    public function line($string, $style = null, $verbosity = null)
    {
        $styled = $style ? "<$style>$string</$style>" : $string;

        $this->output->writeln($styled, $this->parseVerbosity($verbosity));
    }

    protected function getDir($dir)
    {
        $files = array();
        $this->searchDir($dir, $files);
        return $files;
    }

    protected function searchDir($path, &$files)
    {

        if (is_dir($path)) {

            $opendir = opendir($path);

            while ($file = readdir($opendir)) {
                if ($file != '.' && $file != '..') {
                    $this->searchDir($path . '/' . $file, $files);
                }
            }
            closedir($opendir);
        }
        if (!is_dir($path)) {
            $files[] = $path;
        }
    }
}
