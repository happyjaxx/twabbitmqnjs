; Sample Configuration File for the 3 strike... err, 3 Step Twitter to Browser Streaming Example

[amqp]
host = "localhost"
port = 5672;
user = "guest"
pass = "guest"
vhost = "/"

[consumer]
tw_username = "Jackson"
tw_password = "m1ch34l"
amqp_exchange_raw = "twabbit_raw"
amqp_queue_raw = "raw_msgs"
; RabbitMQ specific argument on queue (TTL on messages in milliseconds)
amqp_queue_args[] = "x-message-ttl,I,60000"
amqp_topic_prefix_raw = "twabbit.stream.raw"

; Let's eat some Circle spotted streams (actually, phirehose makes a square from it)
stream_type = "LocationsByRect"

; Paris
locationbycircle[]="48.861,3.34,6"
; São Paulo
locationbycircle[]="-23.551,-46.672,25"
; LA
locationbycircle[]="-118.242,34.055,15"

; San Fran
locationbyrect[]="-122.75,36.8,-121.75,37.8"
; NYC
locationbyrect[]="-74,40,-73,41"

[processor]
; Where to put the message once processed, we can put the same since we use topic subscription anyways, but well...
amqp_exchange_processed = "twabbit_edible"
amqp_topic_prefix_processed = "twabbit.stream.processed"

[server]
; hum, too bad I don't know how to read this file from node.js but here are some values anyways:

sio_transports=""