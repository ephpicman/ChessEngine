<?php

declare(strict_types=1);

namespace Ephpicman\ChessEngine\Tests\Integration\Chess;

use Ephpicman\ChessEngine\Chess\Board;
use Ephpicman\ChessEngine\Chess\Color;
use Ephpicman\ChessEngine\Chess\Decision;
use Ephpicman\ChessEngine\Chess\DecisionHistory;
use Ephpicman\ChessEngine\Chess\DecisionType;
use Ephpicman\ChessEngine\Chess\Move;
use Ephpicman\ChessEngine\Chess\MoveApplier;
use Ephpicman\ChessEngine\Chess\MoveValidator;
use Ephpicman\ChessEngine\Chess\Piece;
use Ephpicman\ChessEngine\Chess\PieceType;
use Ephpicman\ChessEngine\Chess\Pieces;
use Ephpicman\ChessEngine\Chess\Position;
use Ephpicman\ChessEngine\Chess\PositionChange;
use Ephpicman\ChessEngine\Chess\PositionChangeType;
use Ephpicman\ChessEngine\Chess\Square;
use PHPUnit\Framework\TestCase;

final class GameFlowTest extends TestCase
{
    private Board $board;
    private MoveValidator $validator;
    private MoveApplier $applier;
    private Position $position;
    private Pieces $pieces;
    private DecisionHistory $history;

    protected function setUp(): void
    {
        $this->board = new Board();
        $this->validator = new MoveValidator();
        $this->applier = new MoveApplier();
        $this->pieces = new Pieces();
        $this->position = new Position($this->board, $this->pieces);
        $this->history = new DecisionHistory();

        $this->setupInitialPosition();
    }

    public function testGameAllowsThreeInvalidAttemptsBeforeEachValidMove(): void
    {
        // White: three invalid attempts, then 1. e4.
        $this->assertInvalid($this->move($this->moveChange($this->piece('black-pawn-a7'), 'a7', 'a6')));
        $this->assertInvalid($this->move($this->moveChange($this->piece('white-pawn-a2'), 'a2', 'a5')));
        $this->assertInvalid($this->move($this->moveChange($this->piece('white-knight-b1'), 'b1', 'b3')));
        $this->playValid($this->move($this->moveChange($this->piece('white-pawn-e2'), 'e2', 'e4')));

        // Black: three invalid attempts, then 1... e5.
        $this->assertInvalid($this->move($this->moveChange($this->piece('white-pawn-a2'), 'a2', 'a3')));
        $this->assertInvalid($this->move($this->moveChange($this->piece('black-pawn-a7'), 'a7', 'a4')));
        $this->assertInvalid($this->move($this->moveChange($this->piece('black-knight-b8'), 'b8', 'b6')));
        $this->playValid($this->move($this->moveChange($this->piece('black-pawn-e7'), 'e7', 'e5')));

        // White: three invalid attempts, then 2. Nf3.
        $this->assertInvalid($this->move($this->moveChange($this->piece('black-pawn-a7'), 'a7', 'a6')));
        $this->assertInvalid($this->move($this->moveChange($this->piece('white-pawn-a2'), 'a2', 'a5')));
        $this->assertInvalid($this->move($this->moveChange($this->piece('white-bishop-c1'), 'c1', 'c3')));
        $this->playValid($this->move($this->moveChange($this->piece('white-knight-g1'), 'g1', 'f3')));

        // Black: three invalid attempts, then 2... Nc6.
        $this->assertInvalid($this->move($this->moveChange($this->piece('white-pawn-a2'), 'a2', 'a3')));
        $this->assertInvalid($this->move($this->moveChange($this->piece('black-pawn-h7'), 'h7', 'h4')));
        $this->assertInvalid($this->move($this->moveChange($this->piece('black-knight-b8'), 'b8', 'b6')));
        $this->playValid($this->move($this->moveChange($this->piece('black-knight-b8'), 'b8', 'c6')));

        // White: three invalid attempts, then 3. Bc4.
        $this->assertInvalid($this->move($this->moveChange($this->piece('black-pawn-a7'), 'a7', 'a6')));
        $this->assertInvalid($this->move($this->moveChange($this->piece('white-pawn-a2'), 'a2', 'a5')));
        $this->assertInvalid($this->move($this->moveChange($this->piece('white-bishop-c1'), 'c1', 'h6')));
        $this->playValid($this->move($this->moveChange($this->piece('white-bishop-f1'), 'f1', 'c4')));

        // Black: three invalid attempts, then 3... Nf6.
        $this->assertInvalid($this->move($this->moveChange($this->piece('white-pawn-a2'), 'a2', 'a3')));
        $this->assertInvalid($this->move($this->moveChange($this->piece('black-pawn-h7'), 'h7', 'h4')));
        $this->assertInvalid($this->move($this->moveChange($this->piece('black-knight-f6'), 'f6', 'f4')));
        $this->playValid($this->move($this->moveChange($this->piece('black-knight-g8'), 'g8', 'f6')));

        // White: three invalid attempts, then 4. d3.
        $this->assertInvalid($this->move($this->moveChange($this->piece('black-pawn-a7'), 'a7', 'a6')));
        $this->assertInvalid($this->move($this->moveChange($this->piece('white-pawn-a2'), 'a2', 'a5')));
        $this->assertInvalid($this->move($this->moveChange($this->piece('white-pawn-d2'), 'd2', 'd5')));
        $this->playValid($this->move($this->moveChange($this->piece('white-pawn-d2'), 'd2', 'd3')));

        // Black: three invalid attempts, then 4... Bc5.
        $this->assertInvalid($this->move($this->moveChange($this->piece('white-pawn-a2'), 'a2', 'a3')));
        $this->assertInvalid($this->move($this->moveChange($this->piece('black-pawn-h7'), 'h7', 'h4')));
        $this->assertInvalid($this->move($this->moveChange($this->piece('black-bishop-c8'), 'c8', 'c6')));
        $this->playValid($this->move($this->moveChange($this->piece('black-bishop-f8'), 'f8', 'c5')));

        self::assertSame(8, $this->history->count());
        self::assertSame(8, $this->history->moveCount());
        self::assertSame(Color::WHITE, $this->history->turn());
    }

    private function assertInvalid(Move $move): void
    {
        $positionBefore = $this->snapshotPosition();
        $historyCountBefore = $this->history->count();
        $moveCountBefore = $this->history->moveCount();
        $turnBefore = $this->history->turn();

        self::assertFalse($this->validator->validate($this->position, $this->history, $move));
        self::assertSame($positionBefore, $this->snapshotPosition());
        self::assertSame($historyCountBefore, $this->history->count());
        self::assertSame($moveCountBefore, $this->history->moveCount());
        self::assertSame($turnBefore, $this->history->turn());
    }

    private function playValid(Move $move): void
    {
        $turn = $this->history->turn();
        $moveCountBefore = $this->history->moveCount();

        self::assertTrue($this->validator->validate($this->position, $this->history, $move));
        $this->applier->apply($this->position, $move);
        $this->history->add($turn, new Decision(DecisionType::MOVE, $move));

        self::assertSame($moveCountBefore + 1, $this->history->moveCount());
        self::assertNotSame($turn, $this->history->turn());
    }

    /** @return array<string, string|null> */
    private function snapshotPosition(): array
    {
        $snapshot = [];

        foreach ($this->pieces->all() as $piece) {
            $square = $this->position->getSquareOf($piece);
            $snapshot[$piece->id] = $square?->notation();
        }

        ksort($snapshot);

        return $snapshot;
    }

    private function setupInitialPosition(): void
    {
        $backRank = [
            PieceType::ROOK,
            PieceType::KNIGHT,
            PieceType::BISHOP,
            PieceType::QUEEN,
            PieceType::KING,
            PieceType::BISHOP,
            PieceType::KNIGHT,
            PieceType::ROOK,
        ];

        $files = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'];

        foreach ($files as $index => $file) {
            $this->addPiece("white-{$this->typeName($backRank[$index])}-{$file}1", Color::WHITE, $backRank[$index], "{$file}1");
            $this->addPiece("black-{$this->typeName($backRank[$index])}-{$file}8", Color::BLACK, $backRank[$index], "{$file}8");
            $this->addPiece("white-pawn-{$file}2", Color::WHITE, PieceType::PAWN, "{$file}2");
            $this->addPiece("black-pawn-{$file}7", Color::BLACK, PieceType::PAWN, "{$file}7");
        }
    }

    private function typeName(PieceType $type): string
    {
        return match ($type) {
            PieceType::PAWN => 'pawn',
            PieceType::KNIGHT => 'knight',
            PieceType::BISHOP => 'bishop',
            PieceType::ROOK => 'rook',
            PieceType::QUEEN => 'queen',
            PieceType::KING => 'king',
        };
    }

    private function addPiece(string $id, Color $color, PieceType $type, string $notation): void
    {
        $piece = new Piece($id, $color, $type);
        $this->pieces->add($piece);
        $this->position->place($this->square($notation), $piece);
    }

    private function piece(string $id): Piece
    {
        return $this->pieces->get($id);
    }

    private function moveChange(Piece $piece, string $from, string $to): PositionChange
    {
        return new PositionChange(
            PositionChangeType::MOVE,
            $piece,
            $this->square($from),
            $this->square($to),
        );
    }

    private function move(PositionChange ...$changes): Move
    {
        return new Move(...$changes);
    }

    private function square(string $notation): Square
    {
        return $this->board->getSquareByNotation($notation);
    }
}
