<?php
namespace MichielRoos\H5p\Domain\Model;

use TYPO3\CMS\Extbase\Domain\Model\FrontendUser;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Class ContentResult
 * @package MichielRoos\H5p\Domain\Model
 */
class ContentResult extends AbstractEntity
{

    /**
     * @var Content
     */
    protected $content;

    /**
     * @var FrontendUser
     */
    protected $user;

    /**
     * @var integer
     */
    protected $score;

    /**
     * @var integer
     */
    protected $maxScore;

    /**
     * @var integer
     */
    protected $opened;

    /**
     * @var integer
     */
    protected $finished;

    /**
     * @var integer
     */
    protected $time;

    /**
     * ContentResult constructor.
     * @param Content $content
     * @param FrontendUser $user
     * @param int $score
     * @param int $maxScore
     * @param int $opened
     * @param int $finished
     * @param int $time
     */
    public function __construct(Content $content, FrontendUser $user, int $score, int $maxScore, int $opened, int $finished, int $time = 0)
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
     * @return Content
     */
    public function getContent(): Content
    {
        return $this->content;
    }

    /**
     * @param Content $content
     */
    public function setContent(Content $content)
    {
        $this->content = $content;
    }

    /**
     * @return FrontendUser
     */
    public function getUser(): FrontendUser
    {
        return $this->user;
    }

    /**
     * @param FrontendUser $user
     */
    public function setUser(FrontendUser $user)
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
