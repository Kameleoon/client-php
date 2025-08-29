<?php

declare(strict_types=1);

namespace Kameleoon\Data\Manager;

use Kameleoon\Data\BaseData;
use Kameleoon\Data\PageView;
use Kameleoon\Helpers\TimeHelper;

/** @internal */
class PageViewVisit implements BaseData
{
    private PageView $pageView;
    private int $count;
    private int $lastTimestamp; // in milliseconds (server returns in ms as well)

    public function __construct(
        PageView $pageView,
        int $count = 1,
        ?int $timestamp = null)
    {
        $this->pageView = $pageView;
        $this->count = $count;
        $this->lastTimestamp = $timestamp ?? TimeHelper::nowInMilliseconds();
    }

    public function getPageView(): PageView
    {
        return $this->pageView;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function getLastTimestamp(): int
    {
        return $this->lastTimestamp;
    }

    public function overwrite(PageView $pageView): PageViewVisit
    {
        return new PageViewVisit($pageView, $this->count + 1);
    }

    public function merge(PageViewVisit $other): PageViewVisit
    {
        $this->count += $other->count;
        $this->lastTimestamp = max($this->lastTimestamp, $other->lastTimestamp);
        return $this;
    }

    public function increasePageVisits(): void
    {
        $this->count++;
    }

    public function __toString(): string {
        return "PageViewVisit{" .
            "lastTimestamp:" . $this->lastTimestamp .
            ",count:" . $this->count .
            ",pageView:" . $this->pageView .
            "}";
    }
}
