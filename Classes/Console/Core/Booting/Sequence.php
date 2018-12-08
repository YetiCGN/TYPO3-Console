<?php
declare(strict_types=1);
namespace Helhum\Typo3Console\Core\Booting;

/*
 * This file is part of the TYPO3 Console project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read
 * LICENSE file that was distributed with this source code.
 *
 */

use Helhum\Typo3Console\Exception;
use TYPO3\CMS\Core\Core\Bootstrap;

/**
 * A boot sequence, consisting of individual steps, each of them initializing a
 * specific part of the application.
 *
 * @api
 */
class Sequence
{
    /**
     * @var string
     */
    protected $identifier;

    /**
     * @var array
     */
    protected $steps = [];

    /**
     * @param string $identifier
     */
    public function __construct($identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * Adds the given step to this sequence, to be executed after then step specified
     * by $previousStepIdentifier. If no previous step is specified, the new step
     * is added to the list of steps executed right at the start of the sequence.
     *
     * @param \Helhum\Typo3Console\Core\Booting\Step $step The new step to add
     * @param string $previousStepIdentifier The preceding step
     * @return void
     */
    public function addStep(Step $step, $previousStepIdentifier = 'start')
    {
        $this->steps[$previousStepIdentifier][] = $step;
    }

    /**
     * Removes all occurrences of the specified step from this sequence
     *
     * @param string $stepIdentifier
     * @throws Exception
     * @return void
     */
    public function removeStep($stepIdentifier)
    {
        $removedOccurrences = 0;
        foreach ($this->steps as $previousStepIdentifier => $steps) {
            foreach ($steps as $index => $step) {
                if ($step->getIdentifier() === $stepIdentifier) {
                    unset($this->steps[$previousStepIdentifier][$index]);
                    $removedOccurrences ++;
                }
            }
        }
        if ($removedOccurrences === 0) {
            throw new Exception(sprintf('Cannot remove sequence step with identifier "%s" because no such step exists in the given sequence.', $stepIdentifier), 1322591669);
        }
    }

    /**
     * Executes all steps of this sequence
     *
     * @param Bootstrap $bootstrap
     * @throws StepFailedException
     * @return void
     */
    public function invoke(Bootstrap $bootstrap)
    {
        if (isset($this->steps['start'])) {
            foreach ($this->steps['start'] as $step) {
                $this->invokeStep($step, $bootstrap);
            }
        }
    }

    /**
     * Invokes a single step of this sequence and also invokes all steps registered
     * to be executed after the given step.
     *
     * @param Step $step The step to invoke
     * @param Bootstrap $bootstrap
     * @throws StepFailedException
     * @return void
     */
    protected function invokeStep(Step $step, Bootstrap $bootstrap)
    {
        $identifier = $step->getIdentifier();
        try {
            $step($bootstrap);
        } catch (\Throwable $e) {
            throw new StepFailedException($step, $e);
        }
        if (isset($this->steps[$identifier])) {
            foreach ($this->steps[$identifier] as $followingStep) {
                $this->invokeStep($followingStep, $bootstrap);
            }
        }
    }
}
