General Concept
http://docs.spring.io/spring-integration/reference/htmlsingle/#overview-components

Message Content Types:
http://docs.spring.io/spring-cloud-stream/docs/1.0.0.BUILD-SNAPSHOT/reference/html/contenttypemanagement.html


# example
https://www.javacodegeeks.com/2014/12/message-processing-with-spring-integration.html

# TODO

1. Add transformer before service activator, so it transforms reply channel (string), to MessageChannel
2. Check how replyChannel works in spring
3. create builder for service activator
4. create pollable consumer and event driven consumer
5. gateway proxy throw exception if calling not described method
6. throw exception, if gateway reply channel returned null, but interface expects value


interface 

@Payload amount
@Header(name="message_id") name
public function send(int $amount, string $name)

$this->send(5, 'some');

MessageBuilder::start()
    ->withHeader("message_id", 2)
    ->

convert to message
send to channel


@Payload amount
@Header(name="message_id") name
public function send(int $amount, string $name)
{
//code
}

receive message
convert to method call


