@TODO 

- fix issue with reading annotations from src
- change namespace to configuration instead of parameter
- register transaction interceptor as direct object
- amqp admin not as a service, but configuration
- inbound adapter as annotation
- @inheritdoc + extra annotations
- aggregate root as a service
- allow simple types for aggregate commands/queries
- write tests for bus routers
- check possible to debug with endpoint and channel names
- allow to intercept part of namespace
- define how event handler subscribing should work in context of classes and channel names
- presend interceptor. So it can be called before sending to channel
- amqp add possibility to define point to point or publish subscribe amqp backend channel