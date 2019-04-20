@TODO 

- poller module
- add interceptors for consumer lifecycle
- aggregate root as a service
- simple types for aggregate commands/queries
- allow inject all headers


# Integration Messaging

## What is this about

This is implementation of [Enterprise Integration Patterns](enterpriseintegrationpatterns.com) in PHP.  
Implementation is based on [EIP book](https://www.amazon.com/Enterprise-Integration-Patterns-Designing-Deploying/dp/0321200683) 
and [Spring Integration](https://projects.spring.io/spring-integration/)   

#### What it should help me with

It will provide pipe and filters architecture for your system where components are loosely coupled and easily replaceable.  
There are a lot of benefits, which will be described in later stage of the project. 

#### How far in development is this framework

This is early stage. A lot of concepts are going to be added and changed.   
Keep in my mind, that API may change.

### Messaging High Level

@TODO

### Messaging Components

### Enricher

A Enricher defines an endpoint that passes a Message to the exposed request channel and then expects a reply message. The reply message then becomes the root object for evaluation of expressions to enrich the target payload.  
Basically by defining the request channel, the Payload Enricher acts as a Gateway, waiting for the message that were sent to the request channel to return, and the Enricher then augments the message’s payload with the data provided by the reply message.  
When sending messages to the request channel you also have the option to only send a subset of the original payload using the `request-payload-expression` attribute.  

#### Example


### Messaging Configuration

Messaging Configuration is divined in three forms.  

##### Configuration 
The main one `SimplyCodedSoftware\Messaging\Config\Configuration`, which expect 
registration of all messaging components. From Configuration we build messaging system, that 
has all messaging component defined. What it means it consumer are constructed and connected via 
message channels.  
This is main and the only entry for registering messaging components within messaging system.


##### Module
On to of `Configuration`, we have `SimplyCodedSoftware\Messaging\Config\Module`, which
registers using `Configuration` his own messaging components. 
It works as plug in system for new features. 
Because `SimplyCodedSoftware\Messaging\MessageHandler` and `SimplyCodedSoftware\Messaging\MessageChannel`
are interfaces, modules can extend them in any shape they want and provide within understandable language for lower
layer components. 

##### ModuleExtension

As `Module` may come with big features, it may be wise to split it into, next separate modules.  
Here `ModuleExtension` comes to play as extension for a module. 
`Module` does provide plug in system by it own, by defining interface that need to fulfilled by it's
extensions.


##### How Configuration, Module, ModuleExtension work together

Configuration during initialization gets list of all modules and module extensions.  
This system is flexible and do not any client configuration, because messaging system search for 
modules and extensions in all of your autoloaded classes.   
What does it mean in practice? When you need to do add new feature from `module`/`moduleExtension` all you need  
to do is add package to your `composer.json`