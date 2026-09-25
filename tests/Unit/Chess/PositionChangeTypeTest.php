<?php

declare(strict_types=1);

namespace Ephpicman\ChessEngine\Tests\Unit\Chess;

use Ephpicman\ChessEngine\Chess\PositionChangeType;
use PHPUnit\Framework\TestCase;

final class PositionChangeTypeTest extends TestCase
{
    public function testMoveHasExpectedBackingValue(): void
    {
        self::assertSame('move', PositionChangeType::MOVE->value);
    }

    public function testRemoveHasExpectedBackingValue(): void
    {
        self::assertSame('remove', PositionChangeType::REMOVE->value);
    }

    public function testChangeHasExpectedBackingValue(): void
    {
        self::assertSame('change', PositionChangeType::CHANGE->value);
    }
}
