<?php

declare(strict_types=1);

namespace Ephpicman\ChessEngine\Tests\Unit\Chess;

use Ephpicman\ChessEngine\Chess\Color;
use PHPUnit\Framework\TestCase;

final class ColorTest extends TestCase
{
    public function testWhiteHasExpectedBackingValue(): void
    {
        self::assertSame(1, Color::WHITE->value);
    }

    public function testBlackHasExpectedBackingValue(): void
    {
        self::assertSame(0, Color::BLACK->value);
    }

    public function testWhiteOppositeIsBlack(): void
    {
        self::assertSame(Color::BLACK, Color::WHITE->opposite());
    }

    public function testBlackOppositeIsWhite(): void
    {
        self::assertSame(Color::WHITE, Color::BLACK->opposite());
    }

    public function testWhiteIsWhite(): void
    {
        self::assertTrue(Color::WHITE->isWhite());
    }

    public function testBlackIsNotWhite(): void
    {
        self::assertFalse(Color::BLACK->isWhite());
    }

    public function testBlackIsBlack(): void
    {
        self::assertTrue(Color::BLACK->isBlack());
    }

    public function testWhiteIsNotBlack(): void
    {
        self::assertFalse(Color::WHITE->isBlack());
    }
}
