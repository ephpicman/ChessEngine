<?php

declare(strict_types=1);

namespace Ephpicman\ChessEngine\Tests\Unit\Chess;

use Ephpicman\ChessEngine\Chess\Rank;
use PHPUnit\Framework\TestCase;

final class RankTest extends TestCase
{
    public function testAllRanksHaveExpectedBackingValues(): void
    {
        self::assertSame(1, Rank::ONE->value);
        self::assertSame(2, Rank::TWO->value);
        self::assertSame(3, Rank::THREE->value);
        self::assertSame(4, Rank::FOUR->value);
        self::assertSame(5, Rank::FIVE->value);
        self::assertSame(6, Rank::SIX->value);
        self::assertSame(7, Rank::SEVEN->value);
        self::assertSame(8, Rank::EIGHT->value);
    }

    public function testDigitReturnsNumericNotation(): void
    {
        self::assertSame('1', Rank::ONE->digit());
        self::assertSame('4', Rank::FOUR->digit());
        self::assertSame('8', Rank::EIGHT->digit());
    }

    public function testFromDigitReturnsCorrespondingRank(): void
    {
        self::assertSame(Rank::ONE, Rank::fromDigit('1'));
        self::assertSame(Rank::FOUR, Rank::fromDigit('4'));
        self::assertSame(Rank::EIGHT, Rank::fromDigit('8'));
    }

    public function testFromDigitThrowsValueErrorForInvalidRank(): void
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('Invalid chess rank: 9');

        Rank::fromDigit('9');
    }
}
