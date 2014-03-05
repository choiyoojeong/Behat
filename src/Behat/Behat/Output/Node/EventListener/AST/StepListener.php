<?php

/*
 * This file is part of the Behat.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Behat\Output\Node\EventListener\AST;

use Behat\Behat\EventDispatcher\Event\BackgroundTested;
use Behat\Behat\EventDispatcher\Event\ExampleTested;
use Behat\Behat\EventDispatcher\Event\ScenarioLikeTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Behat\Behat\EventDispatcher\Event\StepTested;
use Behat\Behat\Output\Node\Printer\StepPrinter;
use Behat\Gherkin\Node\ScenarioLikeInterface;
use Behat\Testwork\Output\Formatter;
use Behat\Testwork\Output\Node\EventListener\EventListener;
use Symfony\Component\EventDispatcher\Event;

/**
 * Behat step listener.
 *
 * Listens to step events and call appropriate printers.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class StepListener implements EventListener
{
    /**
     * @var StepPrinter
     */
    private $stepPrinter;
    /**
     * @var ScenarioLikeInterface
     */
    private $scenario;

    /**
     * Initializes listener.
     *
     * @param StepPrinter $stepPrinter
     */
    public function __construct(StepPrinter $stepPrinter)
    {
        $this->stepPrinter = $stepPrinter;
    }

    /**
     * {@inheritdoc}
     */
    public function listenEvent(Formatter $formatter, Event $event, $eventName)
    {
        $this->captureScenarioOnScenarioEvent($event);
        $this->forgetScenarioOnAfterEvent($eventName);
        $this->printStepOnAfterEvent($formatter, $event, $eventName);
    }

    /**
     * Captures scenario into the ivar on scenario/background/example BEFORE event.
     *
     * @param Event $event
     */
    private function captureScenarioOnScenarioEvent(Event $event)
    {
        if (!$event instanceof ScenarioLikeTested) {
            return;
        }

        $this->scenario = $event->getScenario();
    }

    /**
     * Removes scenario from the ivar on scenario/background/example AFTER event.
     *
     * @param string $eventName
     */
    private function forgetScenarioOnAfterEvent($eventName)
    {
        if (!in_array($eventName, array(BackgroundTested::AFTER, ScenarioTested::AFTER, ExampleTested::AFTER))) {
            return;
        }

        $this->scenario = null;
    }

    /**
     * Prints step on AFTER event.
     *
     * @param Formatter $formatter
     * @param Event     $event
     * @param string    $eventName
     */
    private function printStepOnAfterEvent(Formatter $formatter, Event $event, $eventName)
    {
        if (!$event instanceof StepTested || StepTested::AFTER !== $eventName) {
            return;
        }

        $this->stepPrinter->printStep($formatter, $this->scenario, $event->getStep(), $event->getTestResult());
    }
}
