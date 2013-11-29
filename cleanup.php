<!DOCTYPE html>
<?php
session_start();

ini_set('display_errors',1); 
error_reporting(E_ALL);


$finishedurl = $_SESSION['finishedURL'];
$urlBefore = $_SESSION['url2'];
$queueURL = $_SESSION['queueurl'];
$topicArn = $_SESSION['topicArn'];
$domain = $_SESSION['domain'];
$bucketCleanUp = $_SESSION['bucket'];
$SourceFile = $_SESSION['SourceFile'];

$prefix = basename(preg_replace("/\\.[^.\\s]{3,4}$/", "", $SourceFile)).'.png';


// Include the SDK using the Composer autoloader
require 'vendor/autoload.php';

use Aws\SimpleDb\SimpleDbClient;
use Aws\S3\S3Client;
use Aws\Sqs\SqsClient;
use Aws\Common\Aws;
use Aws\SimpleDb\Exception\InvalidQueryExpressionException;

//aws factory
$aws = Aws::factory('/var/www/vendor/aws/aws-sdk-php/src/Aws/Common/Resources/custom-config.php');

// Instantiate the S3 client with your AWS credentials and desired AWS region

$client = $aws->get('S3');
$sdbclient = $aws->get('SimpleDb');
$sqsclient = $aws->get('Sqs');
$snsclient = $aws->get('Sns'); 


// -------------------------------------------add code to consume the Queue to make sure the job is done

$result = $sqsclient->deleteQueue(array(
    // QueueUrl is required
    'QueueUrl' => $queueURL,
));

// -------------------------------------------add code to send the SMS message of the finished S3 URL

$exp="select * from  $domain";

$result = $sdbclient->select(array(
    'SelectExpression' => $exp 
));

foreach ($result['Items'] as $item) {
    //echo $item['Name'] . "\n";
    var_export($item['Attributes'],true);
}
#####################################################
# SNS publishing of message to topic - which will be sent via SMS
#####################################################
$result = $snsclient->publish(array(
    'TopicArn' => $topicArn,
    'TargetArn' => $topicArn,
    // Message is required
    'Message' => $finishedurl,
    ////'Subject' => $finishedurl,
    'MessageStructure' => 'sms',
));


// ------------------------------------------Set object expire to remove the image in one day
//bool date_default_timezone_set ('America/Chicago'));
//$date = new DateTime("2012-07-05 16:43:21", new DateTimeZone('America/Chicago')); 

$date = date('Y-m-d H:i:s'); 
$currentDate = strtotime($date);
$futureDate = $currentDate+(60*10);
$formatDate = date("Y-m-d H:i:s", $futureDate);


$result = $client->putBucketLifecycle(array(
    // Bucket is required
    'Bucket' => $bucketCleanUp,
    // Rules is required
    'Rules' => array(
        array(
            'Expiration' => array(
			    //'Date' => 'Y-m-d H:i:s' | $formatDate,
                'Days' => 1,
            ),
            'ID' => 'string',
            // Prefix is required
            'Prefix' => $prefix,
            // Status is required
            'Status' => 'Enabled',
        ),
        // ... repeated
    ),
));
?> 
<html>
<head><title>clean up</title></head>
<body>
<h2> The image before</h2>
<img src = "<?php echo $urlBefore ?>">
<h2> The image after</h2>
<img src = "<?php echo $finishedurl ?>">
</body>
</html>