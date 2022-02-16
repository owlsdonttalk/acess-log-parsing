<?php

namespace SolutionOne\Exception;

interface HumanReadableInterface
{
    public function getUserMessage(): string;
}