<?php

declare(strict_types=1);

namespace Ephpicman\ChessEngine\Tests\Unit\Chess;

use Ephpicman\ChessEngine\Chess\Color;
use Ephpicman\ChessEngine\Chess\Piece;
use Ephpicman\ChessEngine\Chess\PieceType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PieceTest extends TestCase
{
    #[DataProvider('pieceProvider')]
    public function testConstructorStoresPieceData(
        string $id,
        Color $color,
        PieceType $type,
    ): void {
        $piece = new Piece($id, $color, $type);

        self::assertSame($id, $piece->id);
        self::assertSame($color, $piece->color);
        self::assertSame($type, $piece->type);
    }

    public function testPiecesWithSameColorAndTypeCanHaveDifferentIdentities(): void
    {
        $first = new Piece(
            'white-pawn-1',
            Color::WHITE,
            PieceType::PAWN
        );

        $second = new Piece(
            'white-pawn-2',
            Color::WHITE,
            PieceType::PAWN
        );

        self::assertNotSame($first, $second);
        self::assertNotSame($first->id, $second->id);
        self::assertSame(Color::WHITE, $first->color);
        self::assertSame(Color::WHITE, $second->color);
        self::assertSame(PieceType::PAWN, $first->type);
        self::assertSame(PieceType::PAWN, $second->type);
    }

    public function testReadonlyPropertiesCannotBeModified(): void
    {
        $piece = new Piece(
            'white-king',
            Color::WHITE,
            PieceType::KING
        );

        $this->expectException(\Error::class);

        $piece->id = 'black-king';
    }

    public function testDifferentPiecesRemainIndependent(): void
    {
        $whiteKing = new Piece(
            'white-king',
            Color::WHITE,
            PieceType::KING
        );

        $blackKing = new Piece(
            'black-king',
            Color::BLACK,
            PieceType::KING
        );

        self::assertSame('white-king', $whiteKing->id);
        self::assertSame(Color::WHITE, $whiteKing->color);
        self::assertSame(PieceType::KING, $whiteKing->type);

        self::assertSame('black-king', $blackKing->id);
        self::assertSame(Color::BLACK, $blackKing->color);
        self::assertSame(PieceType::KING, $blackKing->type);

        self::assertNotSame($whiteKing, $blackKing);
    }

    public static function pieceProvider(): array
    {
        return [
            'white king' => [
                'white-king',
                Color::WHITE,
                PieceType::KING,
            ],
            'black queen' => [
                'black-queen',
                Color::BLACK,
                PieceType::QUEEN,
            ],
            'white rook' => [
                'white-rook-a',
                Color::WHITE,
                PieceType::ROOK,
            ],
            'black bishop' => [
                'black-bishop-c',
                Color::BLACK,
                PieceType::BISHOP,
            ],
            'white knight' => [
                'white-knight-b',
                Color::WHITE,
                PieceType::KNIGHT,
            ],
            'black pawn' => [
                'black-pawn-8',
                Color::BLACK,
                PieceType::PAWN,
            ],
        ];
    }
}
