<?php

namespace App\Models;

/**
 * Interface to implement for Models that need their changes to be logged
 * in the change_log table.
 */
interface ChangeLogable
{
    public function getSection(): string;

    public function getSectionId(): int;

    public function getSectionName(): string;

    public function getSubSection(): string;

    public function getSubSectionId(): int;

    public function getSubSectionName(): string;
}
