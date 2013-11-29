<!DOCTYPE html>
<?php
ini_set('display_errors',1);
error_reporting(E_ALL);
session_start();

// Include the SDK using the Composer autoloader
require 'vendor/autoload.php';

use Aws\Sns\SnsClient;
use Aws\Sns\Exception\InvalidParameterException;
use Aws\Common\Aws;
use Aws\Sqs\SqsClient;
use Aws\SimpleDb\SimpleDbClient;

// Instantiate the S3 client with your AWS credentials and desired AWS regionws\Common\Aws;

//aws factory
$aws = Aws::factory('/var/www/vendor/aws/aws-sdk-php/src/Aws/Common/Resources/custom-config.php');

$client = $aws->get('S3'); 

$sdbclient = $aws->get('SimpleDb'); 

$snsclient = $aws->get('Sns'); 

$sqsclient = $aws->get('Sqs');

$UUID = uniqid();
$email = str_replace("@","-",$_POST["email"]); 
$bucket = str_replace("@", "-",$_POST["email"]).time(); 
$phone = $_POST["phone"];
$topic = explode("-",$email );
$itemName = 'images-'.$UUID;
$topicArn = $_POST["topicArn"];
$qurl = $_POST["qurl"];
#############################################
# Create SNS Simple Notification Service Topic for subscription
##############################################
#echo $topic[0]."\n";
#############################################
#  Service Topic for subscription
##############################################
try {
$result = $snsclient->subscribe(array(
    // TopicArn is required
    'TopicArn' => $topicArn,
    // Protocol is required
    'Protocol' => 'sms',
    'Endpoint' => $phone,
)); } catch(InvalidParameterException $i) {
 echo 'Invalid parameter: '. $i->getMessage() . " $topicArn". "\n";
} 

# see send for actual sending of text message

###############################################################
# Create S3 bucket
############################################################
$result = $client->createBucket(array(
    'Bucket' => $bucket
));

// Wait until the bucket is created
$client->waitUntil('BucketExists', array('Bucket' => $bucket));

$uploaddir = '/tmp/';
$uploadfile = $uploaddir . basename($_FILES['uploaded_file']['name']);
echo $uploadfile. "\n";
if (move_uploaded_file($_FILES['uploaded_file']['tmp_name'], $uploadfile)) {
    echo "File is valid, and was successfully uploaded.\n";
} else {
    echo "Possible file upload attack!\n";
}
$pathToFile = $uploaddir.$_FILES['uploaded_file']['name'];
#echo 'Here is some more debugging info:';
#print_r($_FILES);

// Upload an object by streaming the contents of a file
// $pathToFile should be absolute path to a file on disk
$result = $client->putObject(array(
    'ACL'        => 'public-read',
    'Bucket'     => $bucket,
    'Key'        => $_FILES['uploaded_file']['name'],
    'SourceFile' => $pathToFile,
    'Metadata'   => array(
    'timestamp' => time(),
    'md5' =>  md5_file($pathToFile),
    )
));
print "#############################\n";
var_export($result->getkeys());
// this gets all the key value pairs and exports them as system variables making our lives nice so we don't have to do this manually. 

$url= $result['ObjectURL'];
####################################################
# SimpleDB create here - note no error checking
###################################################
##################################################
# Set simpleDB Domain here - 
#################################################
$domain = "itm544"; 
$result = $sdbclient->createDomain(array(
    // DomainName is required
    'DomainName' => $domain, 
));

$result = $sdbclient->putAttributes(array(
    // DomainName is required
    'DomainName' => $domain,
   // ItemName is required
    'ItemName' => $itemName,
    // Attributes is required
    'Attributes' => array(
        array(
            // Name is required
           'Name' => 'rawurl',
            // Value is required
            'Value' => $url,
        ),  
        array(    
           'Name' => 'bucket',
            // Value is required
            'Value' => $bucket,
        ),
        array(
           'Name' => 'id',
           'Value' => $UUID,
        ),  
        array(
            'Name' =>  'email',
            'Value' => $_POST['email'],
        ),
	    array(
            'Name' => 'phone',
            'Value' => $phone,
	    ),
        array(
            'Name' => 'finishedurl',
            'Value' => '',
        ),     
         array(
            'Name' => 'filename',
            'Value' => basename($_FILES['uploaded_file']['name']),
        ), 
    ),
));

#$domains = $sdbclient->getIterator('ListDomains')->toArray();
#var_export($domains);
// Lists an array of domain names, including "mydomain"

$exp="select * from  $domain";

$result = $sdbclient->select(array(
    'SelectExpression' => $exp 
));

foreach ($result['Items'] as $item) {
    echo $item['Name'] . "\n";
    var_export($item['Attributes']);
}
#####################################################
# SNS publishing of message to topic - which will be sent via SMS
#####################################################
$result = $snsclient->publish(array(
    'TopicArn' => $topicArn,
    'TargetArn' => $topicArn,
    // Message is required
    'Message' => 'Your image has been uploaded',
    'Subject' => $url,
    'MessageStructure' => 'sms',
));
#####################################################
# Code to add a Message to a queue - queue has been precreated - its just easier
#####################################################
$result = $sqsclient->sendMessage(array(
    // QueueUrl is required
    'QueueUrl' => $qurl,
    // MessageBody is required
    'MessageBody' => $UUID,
    'DelaySeconds' => 0,
));

$_SESSION['domain']=$domain;
$_SESSION['queueurl']=$qurl;
$_SESSION['topicArn']=$topicArn;
$_SESSION['url']=$url;

?>
<html>
<head>
<title>Title of the document</title>
</head>

<body>
Thank you <? echo $bucket ?>
<script>
window.location = 'resize.php';
</script>
</body>
</html>

</html>
