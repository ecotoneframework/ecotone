<?php

require __DIR__ . "/vendor/autoload.php";

/** @var \Composer\Autoload\ClassLoader $loader */
$loader = require __DIR__.'/vendor/autoload.php';

\Doctrine\Common\Annotations\AnnotationRegistry::registerLoader([$loader, 'loadClass']);

$reader = new \Doctrine\Common\Annotations\AnnotationReader();

$start = microtime(true);
$fileLocator = new \SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\FileSystemClassLocator([__DIR__ . '/src', __DIR__ . '/vendor'], [
    'IntegrationMessaging',
    'SimplyCodedSoftware'
]);
$end = microtime(true);


$start = microtime(true);
$reflectionClasses = [];
foreach ($fileLocator->getAllClasses() as $class) {
    $reflectionClasses[] = new \ReflectionClass($class);
}
$end = microtime(true);

//die($end - $start);

class Test {
    private $number = 0;
    /**
     * @var Test|null
     */
    private $test;

    /**
     * Test constructor.
     * @param int $number
     * @param Test $test
     */
    public function __construct(int $number, Test $test = null)
    {
        $this->number = $number;
        $this->test = $test;
    }
}

$file = '/tmp/container.php';
//if (file_exists($file)) {
//    require_once $file;
//    $container = new MessagingSystemContainer();
//} else {
    $container = new \Symfony\Component\DependencyInjection\ContainerBuilder();

    $definition = new \Symfony\Component\DependencyInjection\Definition();
    $definition->setPrivate(false);
    $definition->setClass(Test::class);
    $definition->setArgument(0, 3);
    $definition->setArgument(1, new \Symfony\Component\DependencyInjection\Reference('test'));
    $container->setDefinition('test2', $definition);
    $container->set('test', new Test(4));

    $container->compile();

    var_dump($container->get('test2'));

    $dumper = new \Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
    file_put_contents(
        $file,
        $dumper->dump(array('class' => 'MessagingSystemContainer'))
    );
//}

//var_dump($container->get('test2'));