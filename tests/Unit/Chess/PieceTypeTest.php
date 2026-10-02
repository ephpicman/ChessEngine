<?php

declare(strict_types=1);

namespace Ephpicman\ChessEngine\Tests\Unit\Chess;

use Ephpicman\ChessEngine\Chess\PieceType;
use PHPUnit\Framework\TestCase;

final class PieceTypeTest extends TestCase
{
    /**
     * @dataProvider pieceTypesProvider
     */
    public function testPieceTypeHasExpectedBackingValue(
        PieceType $pieceType,
        string $expectedValue,
    ): void {
        self::assertSame($expectedValue, $pieceType->value);
    }

    /**
     * @dataProvider pieceTypesProvider
     */
    public function testPieceTypeCanBeCreatedFromItsBackingValue(
        PieceType $expectedPieceType,
        string $value,
    ): void {
        self::assertSame($expectedPieceType, PieceType::from($value));
    }

    public function testAllPieceTypesAreRepresented(): void
    {
        self::assertSame(
            [
                PieceType::KING,
                PieceType::QUEEN,
                PieceType::ROOK,
                PieceType::BISHOP,
                PieceType::KNIGHT,
                PieceType::PAWN,
            ],
            PieceType::cases(),
        );
    }

    public function testInvalidBackingValueThrowsValueError(): void
    {
        $this->expectException(\ValueError::class);

        PieceType::from('x');
    }

    public function testTryFromReturnsNullForInvalidBackingValue(): void
    {
        self::assertNull(PieceType::tryFrom('x'));
    }

    public static function pieceTypesProvider(): array
    {
        return [
            'king' => [PieceType::KING, 'k'],
            'queen' => [PieceType::QUEEN, 'q'],
            'rook' => [PieceType::ROOK, 'r'],
            'bishop' => [PieceType::BISHOP, 'b'],
            'knight' => [PieceType::KNIGHT, 'n'],
            'pawn' => [PieceType::PAWN, 'p'],
        ];
    }
}
