<?php

/**
 * Twitter Stream Sucker -> MQ Injector
 * As wanted by Twitter's API doc, the deamon connecting to the stream service shall not process anything,
 * just consume for later use, so let's do as they say !
 * @author JaXX.org
 */

require_once('php-amqplib/amqp.inc');
require_once('phirehose/lib/Phirehose.php');

// define('AMQP_DEBUG', true);
$conf = parse_ini_file('conf.php',TRUE);


class consumeTwitter extends Phirehose
{
	/**
	 * Phirehose needs us to extend the class and reimplement enqueueStatus
	 *
	 * @param string $status
	 */
	
	
	
	static private $mqconnection;
	static private $mqexchange;
	static private $mqchannel;
	static private $ex;
	static private $qu;
	
	public function __construct($username, $password, $method = Phirehose::METHOD_SAMPLE, $format = self::FORMAT_JSON)
	{
		global $conf;
		self::$ex=$conf['consumer']['amqp_exchange_raw'];
		self::$qu=$conf['consumer']['amqp_queue_raw'];
		// Let's connect to AMQP too
		self::$mqconnection = new AMQPConnection($conf['amqp']['host'],$conf['amqp']['port'],$conf['amqp']['user'],$conf['amqp']['pass'],$conf['amqp']['vhost']);
		self::$mqchannel = self::$mqconnection->channel();
		
		$args=array();
		foreach($conf['consumer']['amqp_queue_args'] as $amqpargs){
			// echo $amqpargs."\n";
			$_args=split(',',$amqpargs);
			$args=array_merge($args,array($_args[0]=> array($_args[1],$_args[2])));
		}
		
		print_r($args); // die();
		
		// queue_declare ( $queue="", $passive=false, $durable=false, $exclusive=false, $auto_delete=true, $nowait=false, $arguments=NULL, $ticket=NULL )
		self::$mqchannel->queue_declare(
			self::$qu,	// QueueName string
			false,
			true,
			false,
			false,
			false,
			$args,
			NULL);
		self::$mqchannel->exchange_declare(self::$ex, 'topic', false, true, false);
		self::$mqchannel->queue_bind(self::$qu, self::$ex);
		
		$msg = new AMQPMessage("Connected to AMQP Broker, jusqu'ici, tout va bien", array('content_type' => 'text/plain'));
		self::$mqchannel->basic_publish($msg, self::$ex);
		
		parent::__construct($username, $password, $method = Phirehose::METHOD_SAMPLE, $format = self::FORMAT_JSON);
	}

	
	
	public function enqueueStatus($status)
	{
		/*
		 * In this simple example, we will just display to STDOUT rather than enqueue.
		 * NOTE: You should NOT be processing tweets at this point in a real application, instead they should be being
		 * enqueued and processed asyncronously from the collection process.
		 */
		$data = json_decode($status, true);
		if (is_array($data) && isset($data['user']['screen_name'])) {
			print $data['user']['screen_name'] . ': ' . urldecode($data['text']) . "\n";
		}
		// print_r($data);
		
		$msg = new AMQPMessage($status, array('content_type' => 'application/json'));
		self::$mqchannel->basic_publish($msg, self::$ex);
	}
	
	public function __destruct()
	{
		self::$mqconnection->close();
		self::$mqchannel->close();
	}
}

// Start streaming

//echo $conf['consumer']['tw_username'].$conf['consumer']['tw_password']; exit();

$str = new consumeTwitter($conf['consumer']['tw_username'], $conf['consumer']['tw_password'], Phirehose::METHOD_FILTER);

if($conf['consumer']['stream_type'] == "LocationsByCircle"){
	$locations=array();
	foreach($conf['consumer']['locationbycircle'] as $confloc){
		$locations[]=split(',',$confloc);
	}
	print_r($locations);
	$str->setLocationsByCircle($locations);
}
if($conf['consumer']['stream_type'] == "LocationsByRect"){
	$locations=array();
	foreach($conf['consumer']['locationbyrect'] as $confloc){
		$locations[]=split(',',$confloc);
	}
	print_r($locations);
	$str->setLocations($locations);
}

$str->consume();