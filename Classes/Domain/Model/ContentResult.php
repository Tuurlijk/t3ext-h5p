<?php
namespace MichielRoos\H5p\Domain\Model;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Class ContentResult
 * @package MichielRoos\H5p\Domain\Model
 */
class ContentResult extends AbstractEntity
{

    /**
     * @var int
     */
    protected $content;

    /**
     * int
     */
    protected $user;

    /**
     * @var int
     */
    protected $score;

    /**
     * @var int
     */
    protected $maxScore;

    /**
     * @var int
     */
    protected $opened;

    /**
     * @var int
     */
    protected $finished;

    /**
     * @var int
     */
    protected $time;

    /**
     * ContentResult constructor.
     * @param int $content
     * @param int $user
     * @param int $score
     * @param int $maxScore
     * @param int $opened
     * @param int $finished
     * @param int $time
     */
    public function __construct(int $content, int $user, int $score, int $maxScore, int $opened, int $finished, int $time = 0)
    {
        $this->setContent($content);
        $this->setUser($user);
        $this->setScore($score);
        $this->setMaxScore($maxScore);
        $this->setOpened($opened);
        $this->setFinished($finished);
        $this->setTime($time);
    }

    /**
     * @return int
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param int $content
     */
    public function setContent(int $content)
    {
        $this->content = $content;
    }

    /**
     * @return int
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param int
     */
    public function setUser(int $user)
    {
        $this->user = $user;
    }

    /**
     * @return int
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * @param int $score
     */
    public function setScore(int $score)
    {
        $this->score = $score;
    }

    /**
     * @return int
     */
    public function getMaxScore()
    {
        return $this->maxScore;
    }

    /**
     * @param int $maxScore
     */
    public function setMaxScore(int $maxScore)
    {
        $this->maxScore = $maxScore;
    }

    /**
     * @return int
     */
    public function getOpened()
    {
        return $this->opened;
    }

    /**
     * @param int $opened
     */
    public function setOpened(int $opened)
    {
        $this->opened = $opened;
    }

    /**
     * @return int
     */
    public function getFinished()
    {
        return $this->finished;
    }

    /**
     * @param int $finished
     */
    public function setFinished(int $finished)
    {
        $this->finished = $finished;
    }

    /**
     * @return int
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @param int $time
     */
    public function setTime(int $time)
    {
        $this->time = $time;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getFormattedTimeInterval()
    {
        return $this->getFinishedDateTime()->diff($this->getOpenedDateTime())->format("%H:%M:%S");
    }

    /**
     * @return \DateTime
     * @throws \Exception
     */
    public function getFinishedDateTime()
    {
        $datetime = new \DateTime();
        $datetime->setTimestamp($this->finished);
        return $datetime;
    }

    /**
     * @return \DateTime
     * @throws \Exception
     */
    public function getOpenedDateTime()
    {
        $datetime = new \DateTime();
        $datetime->setTimestamp($this->opened);
        return $datetime;
    }
}
