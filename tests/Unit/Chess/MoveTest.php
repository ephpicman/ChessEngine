<?php

declare(strict_types=1);

namespace Ephpicman\ChessEngine\Tests\Unit\Chess;

use Ephpicman\ChessEngine\Chess\Color;
use Ephpicman\ChessEngine\Chess\File;
use Ephpicman\ChessEngine\Chess\Move;
use Ephpicman\ChessEngine\Chess\Piece;
use Ephpicman\ChessEngine\Chess\PieceType;
use Ephpicman\ChessEngine\Chess\PositionChange;
use Ephpicman\ChessEngine\Chess\PositionChangeType;
use Ephpicman\ChessEngine\Chess\Rank;
use Ephpicman\ChessEngine\Chess\Square;
use PHPUnit\Framework\TestCase;

final class MoveTest extends TestCase
{
    public function testMoveStoresSinglePositionChange(): void
    {
        $piece = new Piece(
            'white-pawn-e2',
            Color::WHITE,
            PieceType::PAWN,
        );

        $change = new PositionChange(
            PositionChangeType::MOVE,
            $piece,
            new Square(File::E, Rank::TWO),
            new Square(File::E, Rank::FOUR),
        );

        $move = new Move($change);

        self::assertCount(1, $move->changes);
        self::assertSame($change, $move->changes[0]);
    }

    public function testMoveStoresMultiplePositionChangesInOrder(): void
    {
        $whitePawn = new Piece(
            'white-pawn-e4',
            Color::WHITE,
            PieceType::PAWN,
        );

        $blackPawn = new Piece(
            'black-pawn-d5',
            Color::BLACK,
            PieceType::PAWN,
        );

        $moveChange = new PositionChange(
            PositionChangeType::MOVE,
            $whitePawn,
            new Square(File::E, Rank::FOUR),
            new Square(File::D, Rank::FIVE),
        );

        $removeChange = new PositionChange(
            PositionChangeType::REMOVE,
            $blackPawn,
        );

        $move = new Move($moveChange, $removeChange);

        self::assertCount(2, $move->changes);
        self::assertSame($moveChange, $move->changes[0]);
        self::assertSame($removeChange, $move->changes[1]);
    }

    public function testMoveCanContainThreeOrMorePositionChanges(): void
    {
        $king = new Piece(
            'white-king',
            Color::WHITE,
            PieceType::KING,
        );

        $rook = new Piece(
            'white-rook-h1',
            Color::WHITE,
            PieceType::ROOK,
        );

        $kingMove = new PositionChange(
            PositionChangeType::MOVE,
            $king,
            new Square(File::E, Rank::ONE),
            new Square(File::G, Rank::ONE),
        );

        $rookMove = new PositionChange(
            PositionChangeType::MOVE,
            $rook,
            new Square(File::H, Rank::ONE),
            new Square(File::F, Rank::ONE),
        );

        $additionalChange = new PositionChange(
            PositionChangeType::REMOVE,
            $rook,
        );

        $move = new Move(
            $kingMove,
            $rookMove,
            $additionalChange,
        );

        self::assertCount(3, $move->changes);
        self::assertSame($kingMove, $move->changes[0]);
        self::assertSame($rookMove, $move->changes[1]);
        self::assertSame($additionalChange, $move->changes[2]);
    }

    public function testMoveWithNoChangesThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'A move must contain at least one position change.'
        );

        new Move();
    }

    public function testMoveKeepsOriginalChangeObjects(): void
    {
        $piece = new Piece(
            'white-pawn-e7',
            Color::WHITE,
            PieceType::PAWN,
        );

        $replacement = new Piece(
            'white-queen-e8',
            Color::WHITE,
            PieceType::QUEEN,
        );

        $change = new PositionChange(
            PositionChangeType::CHANGE,
            $piece,
            replacement: $replacement,
        );

        $move = new Move($change);

        self::assertSame($change, $move->changes[0]);
        self::assertSame($piece, $move->changes[0]->piece);
        self::assertSame($replacement, $move->changes[0]->replacement);
    }

    public function testMoveIsReadonly(): void
    {
        $piece = new Piece(
            'white-pawn-e2',
            Color::WHITE,
            PieceType::PAWN,
        );

        $change = new PositionChange(
            PositionChangeType::MOVE,
            $piece,
            new Square(File::E, Rank::TWO),
            new Square(File::E, Rank::FOUR),
        );

        $move = new Move($change);

        $this->expectException(\Error::class);

        $move->changes = [];
    }
}
