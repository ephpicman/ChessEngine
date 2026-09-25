<?php

declare(strict_types=1);

namespace Ephpicman\ChessEngine\Tests\Unit\Chess;

use Ephpicman\ChessEngine\Chess\Color;
use Ephpicman\ChessEngine\Chess\Decision;
use Ephpicman\ChessEngine\Chess\DecisionHistory;
use Ephpicman\ChessEngine\Chess\DecisionType;
use Ephpicman\ChessEngine\Chess\File;
use Ephpicman\ChessEngine\Chess\Move;
use Ephpicman\ChessEngine\Chess\Piece;
use Ephpicman\ChessEngine\Chess\PieceType;
use Ephpicman\ChessEngine\Chess\PositionChange;
use Ephpicman\ChessEngine\Chess\PositionChangeType;
use Ephpicman\ChessEngine\Chess\Rank;
use Ephpicman\ChessEngine\Chess\Square;
use PHPUnit\Framework\TestCase;

final class DecisionHistoryTest extends TestCase
{
    public function testNewHistoryIsEmpty(): void
    {
        $history = new DecisionHistory();

        self::assertSame([], $history->all());
        self::assertSame(0, $history->count());
        self::assertSame(0, $history->moveCount());
        self::assertNull($history->lastMove());
        self::assertSame(Color::WHITE, $history->turn());
    }

    public function testAddStoresColorAndDecision(): void
    {
        $history = new DecisionHistory();
        $decision = new Decision(DecisionType::RESIGN);

        $history->add(Color::WHITE, $decision);

        self::assertSame(
            [
                [
                    'color' => Color::WHITE,
                    'decision' => $decision,
                ],
            ],
            $history->all()
        );

        self::assertSame(1, $history->count());
    }

    public function testAddPreservesInsertionOrder(): void
    {
        $history = new DecisionHistory();

        $first = new Decision(DecisionType::RESIGN);
        $second = new Decision(DecisionType::ACCEPT_DRAW);

        $history->add(Color::WHITE, $first);
        $history->add(Color::BLACK, $second);

        self::assertSame(
            [
                [
                    'color' => Color::WHITE,
                    'decision' => $first,
                ],
                [
                    'color' => Color::BLACK,
                    'decision' => $second,
                ],
            ],
            $history->all()
        );
    }

    public function testCountReturnsNumberOfDecisions(): void
    {
        $history = new DecisionHistory();

        $history->add(
            Color::WHITE,
            new Decision(DecisionType::RESIGN),
        );

        $history->add(
            Color::BLACK,
            new Decision(DecisionType::ACCEPT_DRAW),
        );

        $history->add(
            Color::WHITE,
            new Decision(DecisionType::RESIGN),
        );

        self::assertSame(3, $history->count());
    }

    public function testMoveCountCountsOnlyDecisionsContainingMoves(): void
    {
        $history = new DecisionHistory();

        $move1 = $this->createMove(
            'white-pawn-e2',
            File::E,
            Rank::TWO,
            File::E,
            Rank::FOUR,
        );

        $move2 = $this->createMove(
            'black-pawn-e7',
            File::E,
            Rank::SEVEN,
            File::E,
            Rank::FIVE,
        );

        $history->add(
            Color::WHITE,
            new Decision(DecisionType::MOVE, $move1),
        );

        $history->add(
            Color::BLACK,
            new Decision(DecisionType::RESIGN),
        );

        $history->add(
            Color::WHITE,
            new Decision(DecisionType::MOVE, $move2),
        );

        $history->add(
            Color::BLACK,
            new Decision(DecisionType::ACCEPT_DRAW),
        );

        self::assertSame(4, $history->count());
        self::assertSame(2, $history->moveCount());
    }

    public function testLastMoveReturnsNullWhenNoMoveExists(): void
    {
        $history = new DecisionHistory();

        $history->add(
            Color::WHITE,
            new Decision(DecisionType::RESIGN),
        );

        $history->add(
            Color::BLACK,
            new Decision(DecisionType::ACCEPT_DRAW),
        );

        self::assertNull($history->lastMove());
    }

    public function testLastMoveReturnsMostRecentMove(): void
    {
        $history = new DecisionHistory();

        $firstMove = $this->createMove(
            'white-pawn-e2',
            File::E,
            Rank::TWO,
            File::E,
            Rank::FOUR,
        );

        $secondMove = $this->createMove(
            'black-pawn-e7',
            File::E,
            Rank::SEVEN,
            File::E,
            Rank::FIVE,
        );

        $history->add(
            Color::WHITE,
            new Decision(DecisionType::MOVE, $firstMove),
        );

        $history->add(
            Color::BLACK,
            new Decision(DecisionType::MOVE, $secondMove),
        );

        self::assertSame($secondMove, $history->lastMove());
    }

    public function testLastMoveSkipsDecisionsWithoutMove(): void
    {
        $history = new DecisionHistory();

        $move = $this->createMove(
            'white-pawn-e2',
            File::E,
            Rank::TWO,
            File::E,
            Rank::FOUR,
        );

        $history->add(
            Color::WHITE,
            new Decision(DecisionType::MOVE, $move),
        );

        $history->add(
            Color::BLACK,
            new Decision(DecisionType::RESIGN),
        );

        self::assertSame($move, $history->lastMove());
    }

    public function testHasMovedReturnsTrueForPieceThatMadeMove(): void
    {
        $history = new DecisionHistory();

        $piece = new Piece(
            'white-pawn-e2',
            Color::WHITE,
            PieceType::PAWN,
        );

        $move = $this->createMoveForPiece(
            $piece,
            File::E,
            Rank::TWO,
            File::E,
            Rank::FOUR,
        );

        $history->add(
            Color::WHITE,
            new Decision(DecisionType::MOVE, $move),
        );

        self::assertTrue($history->hasMoved($piece));
    }

    public function testHasMovedReturnsFalseForPieceThatHasNotMoved(): void
    {
        $history = new DecisionHistory();

        $movedPiece = new Piece(
            'white-pawn-e2',
            Color::WHITE,
            PieceType::PAWN,
        );

        $unmovedPiece = new Piece(
            'white-pawn-d2',
            Color::WHITE,
            PieceType::PAWN,
        );

        $move = $this->createMoveForPiece(
            $movedPiece,
            File::E,
            Rank::TWO,
            File::E,
            Rank::FOUR,
        );

        $history->add(
            Color::WHITE,
            new Decision(DecisionType::MOVE, $move),
        );

        self::assertTrue($history->hasMoved($movedPiece));
        self::assertFalse($history->hasMoved($unmovedPiece));
    }

    public function testHasMovedUsesPieceIdentity(): void
    {
        $history = new DecisionHistory();

        $movedPiece = new Piece(
            'white-pawn-e2',
            Color::WHITE,
            PieceType::PAWN,
        );

        $differentPiece = new Piece(
            'white-pawn-e2',
            Color::WHITE,
            PieceType::PAWN,
        );

        $move = $this->createMoveForPiece(
            $movedPiece,
            File::E,
            Rank::TWO,
            File::E,
            Rank::FOUR,
        );

        $history->add(
            Color::WHITE,
            new Decision(DecisionType::MOVE, $move),
        );

        self::assertTrue($history->hasMoved($movedPiece));
        self::assertFalse($history->hasMoved($differentPiece));
    }

    public function testHasMovedIgnoresNonMovePositionChanges(): void
    {
        $history = new DecisionHistory();

        $piece = new Piece(
            'black-pawn-d5',
            Color::BLACK,
            PieceType::PAWN,
        );

        $removeChange = new PositionChange(
            PositionChangeType::REMOVE,
            $piece,
        );

        $move = new Move($removeChange);

        $history->add(
            Color::BLACK,
            new Decision(DecisionType::MOVE, $move),
        );

        self::assertFalse($history->hasMoved($piece));
    }

    public function testHasMovedSkipsDecisionsWithoutMoves(): void
    {
        $history = new DecisionHistory();

        $piece = new Piece(
            'white-pawn-e2',
            Color::WHITE,
            PieceType::PAWN,
        );

        $history->add(
            Color::WHITE,
            new Decision(DecisionType::RESIGN),
        );

        self::assertFalse($history->hasMoved($piece));
    }

    public function testHasMovedDetectsPieceAmongMultipleChanges(): void
    {
        $history = new DecisionHistory();

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

        $move = new Move($kingMove, $rookMove);

        $history->add(
            Color::WHITE,
            new Decision(DecisionType::MOVE, $move),
        );

        self::assertTrue($history->hasMoved($king));
        self::assertTrue($history->hasMoved($rook));
    }

    public function testTurnIsWhiteWhenMoveCountIsEven(): void
    {
        $history = new DecisionHistory();

        self::assertSame(Color::WHITE, $history->turn());

        $move = $this->createMove(
            'white-pawn-e2',
            File::E,
            Rank::TWO,
            File::E,
            Rank::FOUR,
        );

        $history->add(
            Color::WHITE,
            new Decision(DecisionType::MOVE, $move),
        );

        $history->add(
            Color::BLACK,
            new Decision(DecisionType::RESIGN),
        );

        self::assertSame(1, $history->moveCount());
        self::assertSame(Color::BLACK, $history->turn());
    }

    public function testTurnIsBlackWhenMoveCountIsOdd(): void
    {
        $history = new DecisionHistory();

        $firstMove = $this->createMove(
            'white-pawn-e2',
            File::E,
            Rank::TWO,
            File::E,
            Rank::FOUR,
        );

        $secondMove = $this->createMove(
            'black-pawn-e7',
            File::E,
            Rank::SEVEN,
            File::E,
            Rank::FIVE,
        );

        $history->add(
            Color::WHITE,
            new Decision(DecisionType::MOVE, $firstMove),
        );

        self::assertSame(Color::BLACK, $history->turn());

        $history->add(
            Color::BLACK,
            new Decision(DecisionType::MOVE, $secondMove),
        );

        self::assertSame(Color::WHITE, $history->turn());
    }

    private function createMove(
        string $id,
        File $fromFile,
        Rank $fromRank,
        File $toFile,
        Rank $toRank,
    ): Move {
        $piece = new Piece(
            $id,
            str_starts_with($id, 'white-')
                ? Color::WHITE
                : Color::BLACK,
            PieceType::PAWN,
        );

        return $this->createMoveForPiece(
            $piece,
            $fromFile,
            $fromRank,
            $toFile,
            $toRank,
        );
    }

    private function createMoveForPiece(
        Piece $piece,
        File $fromFile,
        Rank $fromRank,
        File $toFile,
        Rank $toRank,
    ): Move {
        $change = new PositionChange(
            PositionChangeType::MOVE,
            $piece,
            new Square($fromFile, $fromRank),
            new Square($toFile, $toRank),
        );

        return new Move($change);
    }
}
