<?php

/*
 * This file is part of the Behat.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Behat\Output\Node\Printer\Pretty;

use Behat\Gherkin\Node\ExampleNode;
use Behat\Gherkin\Node\ScenarioLikeInterface as Scenario;
use Behat\Gherkin\Node\StepNode;

/**
 * Behat scenario width calculator.
 *
 * Calculates width of scenario. Width of scenario = max width of scenario
 * title and scenario step texts.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class WidthCalculator
{
    /**
     * Calculates scenario width.
     *
     * @param Scenario $scenario
     * @param integer  $indentation
     * @param integer  $subIndentation
     *
     * @return integer
     */
    public function calculateScenarioWidth(Scenario $scenario, $indentation = 2, $subIndentation = 2)
    {
        $length = $this->calculateScenarioHeaderWidth($scenario, $indentation);

        foreach ($scenario->getSteps() as $step) {
            $stepLength = $this->calculateStepWidth($step, $indentation + $subIndentation);
            $length = max($length, $stepLength);
        }

        return $length;
    }

    /**
     * Calculates outline examples width.
     *
     * @param ExampleNode $example
     * @param integer     $indentation
     * @param integer     $subIndentation
     *
     * @return integer
     */
    public function calculateExampleWidth(ExampleNode $example, $indentation = 4, $subIndentation = 6)
    {
        $length = $this->calculateScenarioHeaderWidth($example, $indentation);

        foreach ($example->getSteps() as $step) {
            $stepLength = $this->calculateStepWidth($step, $indentation + $subIndentation);
            $length = max($length, $stepLength);
        }

        return $length;
    }

    /**
     * Calculates scenario header width.
     *
     * @param Scenario $scenario
     * @param integer  $indentation
     *
     * @return integer
     */
    public function calculateScenarioHeaderWidth(Scenario $scenario, $indentation = 2)
    {
        $indentText = str_repeat(' ', intval($indentation));

        if ($scenario instanceof ExampleNode) {
            $header = sprintf('%s%s', $indentText, $scenario->getTitle());
        } else {
            $title = $scenario->getTitle();
            $lines = explode("\n", $title);
            $header = sprintf('%s%s: %s', $indentText, $scenario->getKeyword(), array_shift($lines));
        }

        return mb_strlen($header, 'utf8');
    }

    /**
     * Calculates step width.
     *
     * @param StepNode $step
     * @param integer  $indentation
     *
     * @return integer
     */
    public function calculateStepWidth(StepNode $step, $indentation = 4)
    {
        $indentText = str_repeat(' ', intval($indentation));

        $text = sprintf('%s%s %s', $indentText, $step->getType(), $step->getText());

        return mb_strlen($text, 'utf8');
    }
}
