<?php

declare(strict_types=1);

namespace Ephpicman\ChessEngine\Tests\Unit\Chess;

use Ephpicman\ChessEngine\Chess\PieceType;
use PHPUnit\Framework\TestCase;

final class PieceTypeTest extends TestCase
{
    public function testKingHasExpectedBackingValue(): void
    {
        self::assertSame('k', PieceType::KING->value);
    }

    public function testQueenHasExpectedBackingValue(): void
    {
        self::assertSame('q', PieceType::QUEEN->value);
    }

    public function testRookHasExpectedBackingValue(): void
    {
        self::assertSame('r', PieceType::ROOK->value);
    }

    public function testBishopHasExpectedBackingValue(): void
    {
        self::assertSame('b', PieceType::BISHOP->value);
    }

    public function testKnightHasExpectedBackingValue(): void
    {
        self::assertSame('n', PieceType::KNIGHT->value);
    }

    public function testPawnHasExpectedBackingValue(): void
    {
        self::assertSame('p', PieceType::PAWN->value);
    }
}
