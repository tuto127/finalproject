<?php
    // Include the SDK using the Composer autoloader
    require 'vendor/autoload.php';

    use Aws\Common\Aws;

    //The bucket's name you want to keep at your S3.
    $dontDelete = 'pm1tuto';

    // Instantiate the S3 client with your AWS credentials and desired AWS region Aws\Common\Aws;

    //aws factory
    $aws = Aws::factory('/var/www/vendor/aws/aws-sdk-php/src/Aws/Common/Resources/custom-config.php');

    $client = $aws->get('S3'); 

    //List every existing bucket.
    $result = $client->listBuckets();

    //It iterates over every bucket listed.
    foreach ($result['Buckets'] as $bucket) {

        //If the bucket is not the one we want to keep.
        if ($bucket['Name'] != $dontDelete){
            //List every existing object in the bucket.
            $objects = $client->getIterator('ListObjects', array(
                'Bucket' => $bucket['Name']
            ));
            //Deletes every object in the bucket.
            foreach ($objects as $object) {
                $deleteObject = $client->deleteObject(array(
                    // Bucket is required
                    'Bucket' => $bucket['Name'],
                    //Key of the object in the bucket
                    'Key' => $object['Key'],
                ));
            }
            // Once every object in the bucket have been deleted 
            // it's possible to delete the bucket.
            $deleteBucket = $client->deleteBucket(array(
                'Bucket' => $bucket['Name'],
            ));
        }
    }

?>
