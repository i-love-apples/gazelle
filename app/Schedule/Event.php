<?php

namespace Gazelle\Schedule;

class Event {
    public function __construct(
        public readonly string $severity,
        public readonly string $event,
        public readonly int $reference,
        public string|null $timestamp,
    ) {}
}
