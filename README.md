General Concept
http://docs.spring.io/spring-integration/reference/htmlsingle/#overview-components

Message Content Types:
http://docs.spring.io/spring-cloud-stream/docs/1.0.0.BUILD-SNAPSHOT/reference/html/contenttypemanagement.html


# example
https://www.javacodegeeks.com/2014/12/message-processing-with-spring-integration.html

# TODO

1. Service Activator
- Running service with payload of message
- Running service with passing message headers
2. Method invocator
3. Gateway
 - impement pollableWithTimeout or something that gets poll as argument and polls with timeout 
 - rethink async and sync gateway
 - implement proxy
 - implement trigger



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


