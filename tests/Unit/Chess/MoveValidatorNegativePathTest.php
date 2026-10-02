<?php

declare(strict_types=1);

namespace Ephpicman\ChessEngine\Tests\Unit\Chess;

use Ephpicman\ChessEngine\Chess\Board;
use Ephpicman\ChessEngine\Chess\Color;
use Ephpicman\ChessEngine\Chess\Decision;
use Ephpicman\ChessEngine\Chess\DecisionHistory;
use Ephpicman\ChessEngine\Chess\DecisionType;
use Ephpicman\ChessEngine\Chess\Move;
use Ephpicman\ChessEngine\Chess\MoveValidator;
use Ephpicman\ChessEngine\Chess\Piece;
use Ephpicman\ChessEngine\Chess\PieceType;
use Ephpicman\ChessEngine\Chess\Pieces;
use Ephpicman\ChessEngine\Chess\Position;
use Ephpicman\ChessEngine\Chess\PositionChange;
use Ephpicman\ChessEngine\Chess\PositionChangeType;
use PHPUnit\Framework\TestCase;

final class MoveValidatorNegativePathTest extends TestCase
{
    private Board $board;

    private MoveValidator $validator;

    protected function setUp(): void
    {
        $this->board = new Board();
        $this->validator = new MoveValidator();
    }

    public function testMoveWithoutMoveChangeIsRejected(): void
    {
        $pawn = $this->piece('black-pawn', Color::BLACK, PieceType::PAWN);
        $position = $this->position([
            ['a7', $pawn],
            ['e1', $this->king(Color::WHITE)],
            ['e8', $this->king(Color::BLACK)],
        ]);

        $move = new Move(
            new PositionChange(
                PositionChangeType::REMOVE,
                $pawn,
            ),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            ),
        );
    }

    public function testCastlingIsRejectedAfterKingHasMovedWhileItRemainsWhiteTurn(): void
    {
        $king = $this->king(Color::WHITE);
        $rook = $this->piece('white-rook-h1', Color::WHITE, PieceType::ROOK);
        $blackPawn = $this->piece('black-pawn-a7', Color::BLACK, PieceType::PAWN);

        $position = $this->position([
            ['e1', $king],
            ['h1', $rook],
            ['e8', $this->king(Color::BLACK)],
        ]);

        $history = new DecisionHistory();
        $history->add(
            Color::WHITE,
            new Decision(
                DecisionType::MOVE,
                new Move(
                    $this->moveChange($king, 'e1', 'f1'),
                ),
            ),
        );
        $history->add(
            Color::BLACK,
            new Decision(
                DecisionType::MOVE,
                new Move(
                    $this->moveChange($blackPawn, 'a7', 'a6'),
                ),
            ),
        );

        $move = new Move(
            $this->moveChange($king, 'e1', 'g1'),
            $this->moveChange($rook, 'h1', 'f1'),
        );

        self::assertFalse(
            $this->validator->validate($position, $history, $move),
        );
    }

    public function testBlockedSlidingAttackDoesNotAttackKingSquare(): void
    {
        $king = $this->king(Color::WHITE);
        $blackRook = $this->piece('black-rook', Color::BLACK, PieceType::ROOK);
        $blackBlocker = $this->piece(
            'black-bishop',
            Color::BLACK,
            PieceType::BISHOP,
        );

        $position = $this->position([
            ['e1', $king],
            ['e8', $blackRook],
            ['e5', $blackBlocker],
            ['a8', $this->king(Color::BLACK)],
        ]);

        $move = new Move(
            $this->moveChange($king, 'e1', 'e2'),
        );

        self::assertTrue(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            ),
        );
    }

    /**
     * @param array<int, array{0: string, 1: Piece}> $placements
     */
    private function position(array $placements): Position
    {
        $pieces = new Pieces();

        foreach ($placements as [, $piece]) {
            $pieces->add($piece);
        }

        $position = new Position($this->board, $pieces);

        foreach ($placements as [$notation, $piece]) {
            $position->place(
                $this->board->getSquareByNotation($notation),
                $piece,
            );
        }

        return $position;
    }

    private function piece(
        string $id,
        Color $color,
        PieceType $type,
    ): Piece {
        return new Piece($id, $color, $type);
    }

    private function king(Color $color): Piece
    {
        return $this->piece(
            $color === Color::WHITE ? 'white-king' : 'black-king',
            $color,
            PieceType::KING,
        );
    }

    private function moveChange(
        Piece $piece,
        string $from,
        string $to,
    ): PositionChange {
        return new PositionChange(
            PositionChangeType::MOVE,
            $piece,
            $this->board->getSquareByNotation($from),
            $this->board->getSquareByNotation($to),
        );
    }
}
