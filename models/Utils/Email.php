<?php

class Email {

    /**
     * This function is for enable channel connection
     *
     * @param : $config
     *            contains list of environment variables of rabbitMQ,
     * @param : $jsonObj
     * @author Malini Chandrasekar
     * @throws exception $ex Throws sql syntax error
     *         return $error
     */
    public function channelMQConnection($config, $jsonObj) {
        $error = '';
        try {
            $connection = new PhpAmqpLib\Connection\AMQPStreamConnection($config [APPLICATION_ENV]->rabbitmq->host, $config [APPLICATION_ENV]->rabbitmq->port, $config [APPLICATION_ENV]->rabbitmq->username, $config [APPLICATION_ENV]->rabbitmq->password);
            $channel = $connection->channel();
            $channel->queue_declare($config [APPLICATION_ENV]->rabbitmq->emailQueue, false, true, false, false);
            $channel->exchange_declare($config [APPLICATION_ENV]->rabbitmq->emailExchange, 'fanout', false, true, false);

            $msg = new PhpAmqpLib\Message\AMQPMessage($jsonObj, array(
                'content_type'  => 'text/html charset=UTF-8',
                'delivery_mode' => PhpAmqpLib\Message\AMQPMessage::DELIVERY_MODE_PERSISTENT
            ));
            $channel->queue_bind($config [APPLICATION_ENV]->rabbitmq->emailQueue, $config [APPLICATION_ENV]->rabbitmq->emailExchange);
            $channel->basic_publish($msg, $config [APPLICATION_ENV]->rabbitmq->emailExchange);
            $channel->close();
            $connection->close();
        } catch (exception $ex) {
            echo $ex->getMessage();
            $error = $ex->getMessage();
        }
        return $error;
    }

    /**
     * This function is used to send the welcome email
     * @data array $data contains list of input query params.
     * email
     *
     * @author Malini Chandrasekar
     * @return $channel returns channel
     */
    public function sendWelcomeEmail($data, $config) {
        $messageArr [CommonConstants::TEMPLATE_NAME] = 'WELCOME EMAIL';
        $messageArr [CommonConstants::TEMPLATE_CONTENT] = array();
        $varArr [0] [CommonConstants::EMAIL_NAME] = FieldConstants::DEPENDENT_EMAIL;
        $varArr [0] [CommonConstants::EMAIL_CONTENT] = $data [FieldConstants::EMAIL];
        $messageArr [CommonConstants::EMAIL_MESSAGE] = $this->getMessageContent($config->mandrill->fromEmail, $data [FieldConstants::EMAIL], $varArr);
        return $this->channelMQConnection($config, json_encode($messageArr));
    }

}
