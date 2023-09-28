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
    protected Content $content;

    /**
     * @var FrontendUser
     */
    protected FrontendUser $user;

    /**
     * @var integer
     */
    protected int $score;

    /**
     * @var integer
     */
    protected int $maxScore;

    /**
     * @var integer
     */
    protected int $opened;

    /**
     * @var integer
     */
    protected int $finished;

    /**
     * @var integer
     */
    protected int $time;

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
    public function setContent(Content $content): void
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
    public function setUser(FrontendUser $user): void
    {
        $this->user = $user;
    }

    /**
     * @return int
     */
    public function getScore(): int
    {
        return $this->score;
    }

    /**
     * @param int $score
     */
    public function setScore(int $score): void
    {
        $this->score = $score;
    }

    /**
     * @return int
     */
    public function getMaxScore(): int
    {
        return $this->maxScore;
    }

    /**
     * @param int $maxScore
     */
    public function setMaxScore(int $maxScore): void
    {
        $this->maxScore = $maxScore;
    }

    /**
     * @return int
     */
    public function getOpened(): int
    {
        return $this->opened;
    }

    /**
     * @param int $opened
     */
    public function setOpened(int $opened): void
    {
        $this->opened = $opened;
    }

    /**
     * @return int
     */
    public function getFinished(): int
    {
        return $this->finished;
    }

    /**
     * @param int $finished
     */
    public function setFinished(int $finished): void
    {
        $this->finished = $finished;
    }

    /**
     * @return int
     */
    public function getTime(): int
    {
        return $this->time;
    }

    /**
     * @param int $time
     */
    public function setTime(int $time): void
    {
        $this->time = $time;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getFormattedTimeInterval(): string
    {
        return $this->getFinishedDateTime()->diff($this->getOpenedDateTime())->format("%H:%M:%S");
    }

    /**
     * @return \DateTime
     * @throws \Exception
     */
    public function getFinishedDateTime(): \DateTime
    {
        $datetime = new \DateTime();
        $datetime->setTimestamp($this->finished);
        return $datetime;
    }

    /**
     * @return \DateTime
     * @throws \Exception
     */
    public function getOpenedDateTime(): \DateTime
    {
        $datetime = new \DateTime();
        $datetime->setTimestamp($this->opened);
        return $datetime;
    }
}
