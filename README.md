## rackspace-downloader
Quick and dirty tool for downloading objects from a Rackspace Object Storage container.

###### Use Case
This tool was developed to assist in migrating over 350,000 assets from Rackspace to Google Cloud. Resources such as Cyberduck enjoyed crashing when dealing with a large number of objects so I decided to create a CLI application instead.

###### Installation
Run `composer install`

###### Downloading Rackspace Files
`php application app:download-assets "<LOCAL_DESTINATION>" "<START_MARKER>`
Start marker is an argument to resume the download if an error occurs. Use '' as a default to start from the beginning.

Example: `php application app:download-assets "C:/CDN Assets/" ''`

###### Uploading directory to Google Cloud Storage
`php application app:upload "<UPLOAD_DIR>"`
<UPLOAD_DIR> is the absolute path for the directory you want to upload to your GCP storage bucket.

Example: `php application app:upload "C:/Rackspace Files"`

###### Example Usage
cacert.pem may need to be replaced in vendor/guzzle/guzzle/src/Guzzle/Http/Resources for Curl to work with Rackspace.
You can manually do this or set `curl.cainfo` in your PHP.ini
