# Integration Messaging

## What is this about

This is implementation of [Enterprise Integration Patterns](enterpriseintegrationpatterns.com) in PHP.  
Implementation is based on [EIP book](https://www.amazon.com/Enterprise-Integration-Patterns-Designing-Deploying/dp/0321200683) 
and [Spring Integration](https://projects.spring.io/spring-integration/)   

#### What it should help me with

It will provide pipe and filters architecture for your system where components are loosely coupled and easily replaceable.  
There are a lot of benefits, which will be described in later stage of the project. 

#### How far in development is this framework

This is early stage. A lot of concepts are still waiting to add.  

#### General Concept
http://docs.spring.io/spring-integration/reference/htmlsingle/#overview-components

#### TODO before stable release
* aggregator
* command and query handlers
* scheduling
    a) pollers
    b) executors (systemctl, supervisor)
* content management (http://docs.spring.io/spring-cloud-stream/docs/1.0.0.BUILD-SNAPSHOT/reference/html/contenttypemanagement.html)   

# example
https://www.javacodegeeks.com/2014/12/message-processing-with-spring-integration.html