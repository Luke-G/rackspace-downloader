<?php

namespace App\Command;

use OpenCloud\Rackspace;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

class DownloadAssets extends Command
{
    protected function configure()
    {
        $this->setName('app:download-assets')
              ->addArgument('download_destination')
              ->addArgument('rackspace_username')
              ->addArgument('rackspace_apikey')
              ->addArgument('rackspace_container')
              ->addArgument('start-marker');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {        
        $filesStored = 0;

        // Declare path to store files in
        $savePath = $input->getArgument('download_destination');

        // Create client connection
        $client = new Rackspace(Rackspace::US_IDENTITY_ENDPOINT, array(
            'username' => $input->getArgument('rackspace_username'),
            'apiKey'   => $input->getArgument('rackspace_apikey'),
        ));

        // Access the storage file service
        $service = $client->objectStoreService(null, 'LON');

        $account = $service->getAccount();

        // Access the assets container
        $container = $service->getContainer($input->getArgument('rackspace_container'));

        // Access files inside container
        $files = $container->objectList();

        $filesList = [];

        $marker = $input->getArgument('start-marker');
        $output->writeln('Starting from:' . $marker);

        $total = (int) $container->getObjectCount(); 
        $output->writeln($total);

        $count = 0;

        /* Gather list of files */
        while ($marker !== null) {
            $params = [
                'marker' => $marker,
            ];
            
            // $output->writeln($marker);

            $objects = $container->objectList($params);
        
            if ($objects->count() == 0) {
                break;
            }
        
            foreach ($objects as $object) {  
                $objectName = $object->getName();      
                $filesList[] = $objectName;
                $count++;    
        
                $count === $total ? $marker = null : $marker = $objectName;                        
            }        
        }

        $output->writeln(count($filesList) . " total files found");

        $progressBar = new ProgressBar($output, count($filesList));

        /* Begin saving files */
        $containerDir = $savePath . $container->name . '/';

        if (! is_dir($containerDir)) {
            mkdir($containerDir, 0777, true);
        }

        $progressBar->start();

        foreach ($filesList as $file) {
            $file = $container->getObject($file);

            $fileName = $file->getName();
            $localFilepath = $containerDir . $fileName;

            // Check if file exists
            if (file_exists($localFilepath)) {                
                $output->writeln($localFilepath .' already exists');
                $progressBar->advance();
                continue;
            }

            // Check if folder that the file is due to go in exists
            $localDirectory = dirname($localFilepath);

            if (! is_dir($localDirectory)) {
                mkdir($localDirectory, "0777", true);
            }

            $extensions = [
                '.exe',
                '.ogg',
                '.zip',
                '.png',
                '.pdf',
                '.svg',
                '.jpg',
                '.jpeg',
                '.json',
                '.csv',
                '.rar',
                '.nupkg'                
            ];

            // Check the file is not a folder
            foreach ($extensions as $extension) {
                if (strpos($localFilepath, $extension) !== FALSE) {
                    $file = $container->getObject($file->getName());

                    // Check if file handle can be created for writing
                    if (! $fp = @fopen($localFilepath, "wb")) {
                        $output->writeln("* ERROR - Cannot write to file ($localFilepath)");
                    }

                    // Check file has been written correctly
                    if (fwrite($fp, $file->getContent()) === FALSE) {
                        $output->writeln("* ERROR - Cannot write to file ($localFilepath)");
                    } else {
                        $filesStored++;
                        $output->writeln("* " . $file->getName() . " - OK");
                    }

                    $progressBar->advance();
                }                
            }
          
            unset($file);
        }    
        
        $output->writeln("Downloaded $filesStored files successfully");
    }
}