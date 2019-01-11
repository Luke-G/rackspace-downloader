<?php

namespace App\Command;

use OpenCloud\Rackspace;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

use Google\Cloud\Storage\StorageClient;

class Upload extends Command
{
    private function getDirContents($dir, &$results = array()){
        $files = scandir($dir);
    
        foreach($files as $key => $value){
            $path = realpath($dir.DIRECTORY_SEPARATOR.$value);
            if(!is_dir($path)) {
                $results[] = $path;
            } else if($value != "." && $value != "..") {
                $this->getDirContents($path, $results);
                $results[] = $path;
            }
        }
    
        return $results;
    }

    protected function configure()
    {
        $this->setName('app:upload')
             ->addArgument('dir-to-upload');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {           
        if (! getenv('GOOGLE_AUTH_KEY') || ! getenv('UPLOAD_BUCKET'))
            $output->writeln('<error>No auth key or upload bucket set in your .env file</error>');

        // Initialise a GC Storage Client, authenticating with our stored JSON key
        $storage = new StorageClient([
            'keyFilePath' => 'authKeys/' . getenv('GOOGLE_AUTH_KEY'),
        ]);

        $bucket = $storage->bucket(getenv('UPLOAD_BUCKET'));

        // Scan all files in given directory and upload to GC bucket
        $paths = $this->getDirContents($input->getArgument('dir-to-upload'));

        $fileCount = count($paths);

        $output->writeln("Files to upload: " . $fileCount);

        $progressBar = new ProgressBar($output, $fileCount);
        $progressBar->start();

        foreach ($paths as $path) {                      
            // Get just the filename and relative directories          
            $filename = strtolower(str_replace('\\', '/', $path));
            $filename = str_replace($input->getArgument('dir-to-upload'), '', $filename);

            // Remove the preceding '/' which will create a new folder
            $filename = ltrim($filename, "/");

            if (! is_dir($path)) {
                $output->writeln(' Uploading: ' . $path);

                try {
                    $bucket->upload(fopen($path, 'r'), [
                        'name' => $filename
                    ]);   
                } catch (\Exception $e) {
                    $output->writeln('<error>' . $e->getMessage() . '</error>');
                }
                $output->writeln(' Uploaded: ' . $filename);
            } else {
                $output->writeln('Skipped ' . $filename . ' as it is a directory');
            }

            $progressBar->advance();            
        }
        
        $output->writeln(" Done");

    }


}