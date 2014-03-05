<?php

/*
 * This file is part of the Behat.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Behat\Output\Node\Printer\Pretty;

use Behat\Behat\EventDispatcher\Event\StepTested;
use Behat\Behat\Output\Node\Printer\ExampleRowPrinter;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Tester\Result\StepTestResult;
use Behat\Gherkin\Node\ExampleNode;
use Behat\Gherkin\Node\OutlineNode;
use Behat\Testwork\Exception\ExceptionPresenter;
use Behat\Testwork\Output\Formatter;
use Behat\Testwork\Output\Printer\OutputPrinter;
use Behat\Testwork\Tester\Result\TestResult;

/**
 * Behat pretty example row printer.
 *
 * Prints example results in form of a table row.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class PrettyExampleRowPrinter implements ExampleRowPrinter
{
    /**
     * @var ExceptionPresenter
     */
    private $exceptionPresenter;
    /**
     * @var string
     */
    private $indentText;
    /**
     * @var string
     */
    private $subIndentText;

    /**
     * Initializes printer.
     *
     * @param ExceptionPresenter $exceptionPresenter
     * @param integer            $indentation
     * @param integer            $subIndentation
     */
    public function __construct(ExceptionPresenter $exceptionPresenter, $indentation = 6, $subIndentation = 2)
    {
        $this->exceptionPresenter = $exceptionPresenter;
        $this->indentText = str_repeat(' ', intval($indentation));
        $this->subIndentText = $this->indentText . str_repeat(' ', intval($subIndentation));
    }

    /**
     * {@inheritdoc}
     */
    public function printExampleRow(Formatter $formatter, OutlineNode $outline, ExampleNode $example, array $events)
    {
        $rowNum = array_search($example, $outline->getExamples()) + 1;
        $wrapper = $this->getWrapperClosure($outline, $example, $events);
        $row = $outline->getExampleTable()->getRowAsStringWithWrappedValues($rowNum, $wrapper);

        $formatter->getOutputPrinter()->writeln(sprintf('%s%s', $this->indentText, $row));
        $this->printStepExceptions($formatter->getOutputPrinter(), $events);
    }

    /**
     * Creates wrapper-closure for the example table.
     *
     * @param OutlineNode  $outline
     * @param ExampleNode  $example
     * @param StepTested[] $stepEvents
     *
     * @return callable
     */
    private function getWrapperClosure(OutlineNode $outline, ExampleNode $example, array $stepEvents)
    {
        return function ($value, $column) use ($outline, $example, $stepEvents) {
            $resultCode = StepTestResult::PASSED;

            foreach ($stepEvents as $event) {
                $index = array_search($event->getStep(), $example->getSteps());
                $header = $outline->getExampleTable()->getRow(0);
                $steps = $outline->getSteps();
                $outlineStepText = $steps[$index]->getText();

                if (false !== strpos($outlineStepText, '<' . $header[$column] . '>')) {
                    $resultCode = max($resultCode, $event->getResultCode());
                }
            }

            $result = new TestResult($resultCode);

            return sprintf('{+%s}%s{-%s}', $result, $value, $result);
        };
    }

    /**
     * Prints step events exceptions (if has some).
     *
     * @param OutputPrinter $printer
     * @param StepTested[]  $events
     */
    private function printStepExceptions(OutputPrinter $printer, array $events)
    {
        foreach ($events as $event) {
            $this->printStepException($printer, $event->getTestResult());
        }
    }

    /**
     * Prints step exception (if has one).
     *
     * @param OutputPrinter  $printer
     * @param StepTestResult $result
     */
    private function printStepException(OutputPrinter $printer, StepTestResult $result)
    {
        if (!$result->hasException()) {
            return;
        }

        if ($result->getException() instanceof PendingException) {
            $text = $result->getException()->getMessage();
        } else {
            $text = $this->exceptionPresenter->presentException($result->getException());
        }

        $indentedText = implode("\n", array_map(array($this, 'subIndent'), explode("\n", $text)));
        $printer->writeln(sprintf('{+%s}%s{-%s}', $result, $indentedText, $result));
    }

    /**
     * Indents text to the subIndentation level.
     *
     * @param string $text
     *
     * @return string
     */
    private function subIndent($text)
    {
        return $this->subIndentText . $text;
    }
}
