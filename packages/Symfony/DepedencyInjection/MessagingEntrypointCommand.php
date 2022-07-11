<?php

namespace Ecotone\SymfonyBundle\DepedencyInjection;

use Ecotone\Messaging\Config\ConsoleCommandParameter;
use Ecotone\Messaging\Config\ConsoleCommandResultSet;
use Ecotone\Messaging\Gateway\ConsoleCommandRunner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MessagingEntrypointCommand extends Command
{
    private string $name;
    /** @var ConsoleCommandParameter[] */
    private array $parameters;
    private ConsoleCommandRunner $consoleCommandRunner;

    /**
     * @var ConsoleCommandParameter[] $parameters
     */
    public function __construct(string $name, string $parameters, ConsoleCommandRunner $consoleCommandRunner)
    {
        $this->name = $name;
        $this->parameters = unserialize($parameters);
        $this->consoleCommandRunner = $consoleCommandRunner;

        parent::__construct();
    }

    protected function configure()
    {
        foreach ($this->parameters as $parameter) {
            if ($parameter->isOption()) {
                $this->addOption($parameter->getName(), null, InputOption::VALUE_OPTIONAL, '', $parameter->getDefaultValue());
            } else {
                if ($parameter->hasDefaultValue()) {
                    $this->addArgument($parameter->getName(), InputArgument::OPTIONAL, '', $parameter->getDefaultValue());
                } else {
                    $this->addArgument($parameter->getName(), InputArgument::REQUIRED);
                }
            }
        }

        $this
            ->setName($this->name);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ConsoleCommandResultSet $result */
        $result = $this->consoleCommandRunner->execute($this->name, $input->getArguments());

        if ($result) {
            $table = new Table($output);
            $table
                ->setHeaders($result->getColumnHeaders())
                ->setRows($result->getRows());

            $table->render();
        }

        return 0;
    }
}
