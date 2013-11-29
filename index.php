<!DOCTYPE html>
<html>
<head>
<title>Opening Page</title>
</head>

<body>
<?php
ini_set('display_errors',1);
error_reporting(E_ALL);
// Include the SDK using the Composer autoloader
require 'vendor/autoload.php';

use Aws\Sns\SnsClient;
use Aws\Sqs\SqsClient;
use Aws\Common\Aws;

//aws factory
$aws = Aws::factory('/var/www/vendor/aws/aws-sdk-php/src/Aws/Common/Resources/custom-config.php');
$snsclient = $aws->get('Sns'); 
$sqsclient = $aws->get('Sqs');

$topicName="mp1tuto";

$snsresult = $snsclient->createTopic(array(
    // Name is required
    'Name' => $topicName,
));

$topicArn = $snsresult['TopicArn'];

#echo $topicArn ."\n";
#echo $phone ."\n";
$snsresult = $snsclient->setTopicAttributes(array(
    // TopicArn is required
    'TopicArn' => $topicArn,
    // AttributeName is required
    'AttributeName' => 'DisplayName',
    'AttributeValue' => '544',
));

$sqsresult = $sqsclient->createQueue(array('QueueName' => 'photo_q2',));
$qurl=$sqsresult['QueueUrl'];
?>

<h2>Picture Uploader</h2>
<form action="process.php" method="post" enctype="multipart/form-data">
 Email: <input type="text" name="email" > <br />
 Cell Number: <input type="text" name="phone" > <br />
 Choose Image: <input type="file" name="uploaded_file" id="uploaded_file"> <br />  
 <input type="hidden" name="topicArn" value="<? echo $topicArn ?>" >
 <input type="hidden" name="qurl" value="<? echo $qurl ?>" > 
 <input type="submit"  value="submit it!" >
</form>
</body>

</html>
