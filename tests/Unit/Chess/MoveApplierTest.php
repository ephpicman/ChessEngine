<?php

declare(strict_types=1);

namespace Ephpicman\ChessEngine\Tests\Unit\Chess;

use Ephpicman\ChessEngine\Chess\Board;
use Ephpicman\ChessEngine\Chess\Color;
use Ephpicman\ChessEngine\Chess\Move;
use Ephpicman\ChessEngine\Chess\MoveApplier;
use Ephpicman\ChessEngine\Chess\Piece;
use Ephpicman\ChessEngine\Chess\PieceType;
use Ephpicman\ChessEngine\Chess\Pieces;
use Ephpicman\ChessEngine\Chess\Position;
use Ephpicman\ChessEngine\Chess\PositionChange;
use Ephpicman\ChessEngine\Chess\PositionChangeType;
use PHPUnit\Framework\TestCase;

final class MoveApplierTest extends TestCase
{
    private Board $board;

    private MoveApplier $applier;

    protected function setUp(): void
    {
        $this->board = new Board();
        $this->applier = new MoveApplier();
    }

    public function testAppliesMove(): void
    {
        $piece = $this->piece('white-knight', Color::WHITE, PieceType::KNIGHT);
        $position = $this->position([$piece]);

        $move = new Move(
            $this->moveChange($piece, 'b1', 'c3'),
        );

        $this->place($position, 'b1', $piece);
        $this->applier->apply($position, $move);

        self::assertNull($position->getPieceAt($this->square('b1')));
        self::assertSame($piece, $position->getPieceAt($this->square('c3')));
        self::assertSame($this->square('c3'), $position->getSquareOf($piece));
    }

    public function testAppliesRemove(): void
    {
        $piece = $this->piece('black-bishop', Color::BLACK, PieceType::BISHOP);
        $position = $this->position([$piece]);
        $this->place($position, 'c8', $piece);

        $move = new Move(
            new PositionChange(PositionChangeType::REMOVE, $piece),
        );

        $this->applier->apply($position, $move);

        self::assertNull($position->getPieceAt($this->square('c8')));
        self::assertNull($position->getSquareOf($piece));
    }

    public function testAppliesChange(): void
    {
        $pawn = $this->piece('white-pawn', Color::WHITE, PieceType::PAWN);
        $queen = $this->piece('white-queen', Color::WHITE, PieceType::QUEEN);
        $position = $this->position([$pawn, $queen]);
        $this->place($position, 'e8', $pawn);

        $move = new Move(
            new PositionChange(
                PositionChangeType::CHANGE,
                $pawn,
                replacement: $queen,
            ),
        );

        $this->applier->apply($position, $move);

        self::assertSame($queen, $position->getPieceAt($this->square('e8')));
        self::assertNull($position->getSquareOf($pawn));
        self::assertSame($this->square('e8'), $position->getSquareOf($queen));
    }

    public function testAppliesMultipleChangesInOrder(): void
    {
        $king = $this->piece('white-king', Color::WHITE, PieceType::KING);
        $rook = $this->piece('white-rook', Color::WHITE, PieceType::ROOK);
        $position = $this->position([$king, $rook]);
        $this->place($position, 'e1', $king);
        $this->place($position, 'h1', $rook);

        $move = new Move(
            $this->moveChange($king, 'e1', 'g1'),
            $this->moveChange($rook, 'h1', 'f1'),
        );

        $this->applier->apply($position, $move);

        self::assertSame($king, $position->getPieceAt($this->square('g1')));
        self::assertSame($rook, $position->getPieceAt($this->square('f1')));
        self::assertNull($position->getPieceAt($this->square('e1')));
        self::assertNull($position->getPieceAt($this->square('h1')));
    }

    public function testRejectsMoveWithoutFromSquare(): void
    {
        $piece = $this->piece('white-pawn', Color::WHITE, PieceType::PAWN);
        $position = $this->position([$piece]);
        $change = new PositionChange(
            PositionChangeType::MOVE,
            $piece,
            to: $this->square('e4'),
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'A MOVE position change requires both from and to squares.'
        );

        $this->applier->apply($position, new Move($change));
    }

    public function testRejectsMoveWithoutToSquare(): void
    {
        $piece = $this->piece('white-pawn', Color::WHITE, PieceType::PAWN);
        $position = $this->position([$piece]);
        $change = new PositionChange(
            PositionChangeType::MOVE,
            $piece,
            from: $this->square('e2'),
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'A MOVE position change requires both from and to squares.'
        );

        $this->applier->apply($position, new Move($change));
    }

    public function testRejectsMoveWhenPieceIsNotOnFromSquare(): void
    {
        $piece = $this->piece('white-knight', Color::WHITE, PieceType::KNIGHT);
        $position = $this->position([$piece]);
        $this->place($position, 'c3', $piece);

        $move = new Move(
            $this->moveChange($piece, 'b1', 'd2'),
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Piece 'white-knight' is not located on b1.");

        $this->applier->apply($position, $move);
    }

    public function testRejectsMoveToOccupiedSquare(): void
    {
        $piece = $this->piece('white-knight', Color::WHITE, PieceType::KNIGHT);
        $blocker = $this->piece('white-pawn', Color::WHITE, PieceType::PAWN);
        $position = $this->position([$piece, $blocker]);
        $this->place($position, 'b1', $piece);
        $this->place($position, 'c3', $blocker);

        $move = new Move(
            $this->moveChange($piece, 'b1', 'c3'),
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Square c3 is already occupied.');

        $this->applier->apply($position, $move);
    }

    public function testRejectsRemoveWhenPieceIsAbsent(): void
    {
        $piece = $this->piece('black-rook', Color::BLACK, PieceType::ROOK);
        $position = $this->position([$piece]);

        $move = new Move(
            new PositionChange(PositionChangeType::REMOVE, $piece),
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            "Piece 'black-rook' is not present in the position."
        );

        $this->applier->apply($position, $move);
    }

    public function testRejectsChangeWithoutReplacement(): void
    {
        $piece = $this->piece('white-pawn', Color::WHITE, PieceType::PAWN);
        $position = $this->position([$piece]);
        $this->place($position, 'e8', $piece);

        $move = new Move(
            new PositionChange(PositionChangeType::CHANGE, $piece),
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'A CHANGE position change requires a replacement piece.'
        );

        $this->applier->apply($position, $move);
    }

    public function testRejectsChangeWhenPieceIsAbsent(): void
    {
        $piece = $this->piece('white-pawn', Color::WHITE, PieceType::PAWN);
        $queen = $this->piece('white-queen', Color::WHITE, PieceType::QUEEN);
        $position = $this->position([$piece, $queen]);

        $move = new Move(
            new PositionChange(
                PositionChangeType::CHANGE,
                $piece,
                replacement: $queen,
            ),
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            "Piece 'white-pawn' is not present in the position."
        );

        $this->applier->apply($position, $move);
    }

    /**
     * @param array<int, Piece> $pieces
     */
    private function position(array $pieces): Position
    {
        $collection = new Pieces();

        foreach ($pieces as $piece) {
            $collection->add($piece);
        }

        return new Position($this->board, $collection);
    }

    private function place(Position $position, string $notation, Piece $piece): void
    {
        $position->place($this->square($notation), $piece);
    }

    private function square(string $notation): \Ephpicman\ChessEngine\Chess\Square
    {
        return $this->board->getSquareByNotation($notation);
    }

    private function moveChange(
        Piece $piece,
        string $from,
        string $to,
    ): PositionChange {
        return new PositionChange(
            PositionChangeType::MOVE,
            $piece,
            $this->square($from),
            $this->square($to),
        );
    }

    private function piece(string $id, Color $color, PieceType $type): Piece
    {
        return new Piece($id, $color, $type);
    }
}
