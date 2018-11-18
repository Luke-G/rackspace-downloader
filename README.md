## rackspace-downloader
Quick and dity tool for downloading objects from a Rackspace Object Storage container.

###### Installation
1. `composer install`
2. `php application "<LOCAL_DESTINATION>" <USERNAME> <PASSWORD> <CONTAINER_NAME>`
3. Wait and see your files appear in the specified path.

###### Example Usage
`php application "C:/CDN Assets/" lukegorman password assets`

cacert.pem may need to be replaced in vendor/guzzle/guzzle/src/Guzzle/Http/Resources for Curl to work with Rackspace.
