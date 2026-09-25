<?php

declare(strict_types=1);

namespace Ephpicman\ChessEngine\Tests\Unit\Chess;

use Ephpicman\ChessEngine\Chess\Color;
use Ephpicman\ChessEngine\Chess\File;
use Ephpicman\ChessEngine\Chess\Piece;
use Ephpicman\ChessEngine\Chess\PieceType;
use Ephpicman\ChessEngine\Chess\PositionChange;
use Ephpicman\ChessEngine\Chess\PositionChangeType;
use Ephpicman\ChessEngine\Chess\Rank;
use Ephpicman\ChessEngine\Chess\Square;
use PHPUnit\Framework\TestCase;

final class PositionChangeTest extends TestCase
{
    public function testMoveChangeStoresAllRelevantData(): void
    {
        $piece = new Piece(
            'white-pawn-e2',
            Color::WHITE,
            PieceType::PAWN,
        );

        $from = new Square(File::E, Rank::TWO);
        $to = new Square(File::E, Rank::FOUR);

        $change = new PositionChange(
            PositionChangeType::MOVE,
            $piece,
            $from,
            $to,
        );

        self::assertSame(PositionChangeType::MOVE, $change->type);
        self::assertSame($piece, $change->piece);
        self::assertSame($from, $change->from);
        self::assertSame($to, $change->to);
        self::assertNull($change->replacement);
    }

    public function testRemoveChangeStoresPieceAndLeavesUnusedPropertiesNull(): void
    {
        $piece = new Piece(
            'black-pawn-d5',
            Color::BLACK,
            PieceType::PAWN,
        );

        $change = new PositionChange(
            PositionChangeType::REMOVE,
            $piece,
        );

        self::assertSame(PositionChangeType::REMOVE, $change->type);
        self::assertSame($piece, $change->piece);
        self::assertNull($change->from);
        self::assertNull($change->to);
        self::assertNull($change->replacement);
    }

    public function testChangeStoresOriginalAndReplacementPieces(): void
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

        self::assertSame(PositionChangeType::CHANGE, $change->type);
        self::assertSame($piece, $change->piece);
        self::assertNull($change->from);
        self::assertNull($change->to);
        self::assertSame($replacement, $change->replacement);
    }

    public function testDifferentChangesRemainIndependent(): void
    {
        $piece = new Piece(
            'white-pawn-e2',
            Color::WHITE,
            PieceType::PAWN,
        );

        $from = new Square(File::E, Rank::TWO);
        $to = new Square(File::E, Rank::FOUR);

        $change = new PositionChange(
            PositionChangeType::MOVE,
            $piece,
            $from,
            $to,
        );

        $samePiece = new Piece(
            'white-pawn-e2',
            Color::WHITE,
            PieceType::PAWN,
        );

        $removeChange = new PositionChange(
            PositionChangeType::REMOVE,
            $samePiece,
        );

        self::assertNotSame($change, $removeChange);
        self::assertSame(PositionChangeType::MOVE, $change->type);
        self::assertSame(PositionChangeType::REMOVE, $removeChange->type);
        self::assertSame($piece, $change->piece);
        self::assertSame($samePiece, $removeChange->piece);
    }

    public function testReadonlyPropertiesCannotBeModified(): void
    {
        $piece = new Piece(
            'white-pawn-e2',
            Color::WHITE,
            PieceType::PAWN,
        );

        $change = new PositionChange(
            PositionChangeType::REMOVE,
            $piece,
        );

        $this->expectException(\Error::class);

        $change->type = PositionChangeType::MOVE;
    }
}
