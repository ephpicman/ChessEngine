<?php

declare(strict_types=1);

namespace Ephpicman\ChessEngine\Tests\Unit\Chess;

use Ephpicman\ChessEngine\Chess\Board;
use Ephpicman\ChessEngine\Chess\Color;
use Ephpicman\ChessEngine\Chess\Decision;
use Ephpicman\ChessEngine\Chess\DecisionHistory;
use Ephpicman\ChessEngine\Chess\DecisionType;
use Ephpicman\ChessEngine\Chess\InitialPosition;
use Ephpicman\ChessEngine\Chess\LegalMoveGenerator;
use Ephpicman\ChessEngine\Chess\Move;
use Ephpicman\ChessEngine\Chess\MoveValidator;
use Ephpicman\ChessEngine\Chess\Piece;
use Ephpicman\ChessEngine\Chess\PieceType;
use Ephpicman\ChessEngine\Chess\Pieces;
use Ephpicman\ChessEngine\Chess\Position;
use Ephpicman\ChessEngine\Chess\PositionChange;
use Ephpicman\ChessEngine\Chess\PositionChangeType;
use Ephpicman\ChessEngine\Chess\Square;
use PHPUnit\Framework\TestCase;

final class LegalMoveGeneratorTest extends TestCase
{
    private Board $board;
    private LegalMoveGenerator $generator;

    protected function setUp(): void
    {
        $this->board = new Board();
        $this->generator = new LegalMoveGenerator(new MoveValidator());
    }

    public function testInitialPositionHasTwentyWhiteLegalMoves(): void
    {
        self::assertCount(20, $this->generator->generate(InitialPosition::create(), Color::WHITE, new DecisionHistory()));
    }

    public function testInitialPositionHasTwentyBlackLegalMovesAfterWhiteMove(): void
    {
        $position = InitialPosition::create();
        $pawn = $position->getPieceAt($this->square('e2'));
        self::assertNotNull($pawn);
        $history = new DecisionHistory();
        $history->add(Color::WHITE, new Decision(DecisionType::MOVE, $this->move($this->moveChange($pawn, 'e2', 'e4'))));
        $position->remove($this->square('e2'));
        $position->place($this->square('e4'), $pawn);
        self::assertCount(20, $this->generator->generate($position, Color::BLACK, $history));
    }

    public function testOnlyRequestedColourIsGenerated(): void
    {
        $white = $this->piece('wn', Color::WHITE, PieceType::KNIGHT);
        $black = $this->piece('bn', Color::BLACK, PieceType::KNIGHT);
        $position = $this->position([
            ['d4', $white], ['d5', $black],
            ['e1', $this->king(Color::WHITE)], ['e8', $this->king(Color::BLACK)],
        ]);
        $moves = $this->generator->generate($position, Color::WHITE, new DecisionHistory());
        foreach ($moves as $move) self::assertSame(Color::WHITE, $move->changes[0]->piece->color);
        self::assertCount(8, $this->movesByPiece($moves, $white));
    }

    public function testKnightGeneratesMovesAndCapture(): void
    {
        $knight = $this->piece('wn', Color::WHITE, PieceType::KNIGHT);
        $enemy = $this->piece('bp', Color::BLACK, PieceType::PAWN);
        $position = $this->position([
            ['d4', $knight], ['f5', $enemy],
            ['e1', $this->king(Color::WHITE)], ['e8', $this->king(Color::BLACK)],
        ]);
        $moves = $this->movesByPiece($this->generator->generate($position, Color::WHITE, new DecisionHistory()), $knight);
        $keys = $this->keys($moves);
        self::assertCount(8, $moves);
        self::assertArrayHasKey('d4-f5', $keys);
        self::assertSame(PositionChangeType::REMOVE, $keys['d4-f5']->changes[1]->type);
    }

    public function testKnightCannotCaptureOwnPiece(): void
    {
        $knight = $this->piece('wn', Color::WHITE, PieceType::KNIGHT);
        $friendly = $this->piece('wp', Color::WHITE, PieceType::PAWN);
        $position = $this->position([
            ['d4', $knight], ['f5', $friendly],
            ['e1', $this->king(Color::WHITE)], ['e8', $this->king(Color::BLACK)],
        ]);
        $moves = $this->movesByPiece($this->generator->generate($position, Color::WHITE, new DecisionHistory()), $knight);
        self::assertCount(7, $moves);
        self::assertArrayNotHasKey('d4-f5', $this->keys($moves));
    }

    public function testSlidingPiecesStopAtFirstOccupiedSquare(): void
    {
        $bishop = $this->piece('b', Color::WHITE, PieceType::BISHOP);
        $rook = $this->piece('r', Color::WHITE, PieceType::ROOK);
        $queen = $this->piece('q', Color::WHITE, PieceType::QUEEN);
        $bishopTarget = $this->piece('bt', Color::BLACK, PieceType::PAWN);
        $rookTarget = $this->piece('rt', Color::BLACK, PieceType::PAWN);
        $queenTarget = $this->piece('qt', Color::BLACK, PieceType::PAWN);
        $position = $this->position([
            ['d4', $bishop], ['f6', $bishopTarget],
            ['a1', $rook], ['a6', $rookTarget],
            ['h4', $queen], ['f4', $queenTarget],
            ['e1', $this->king(Color::WHITE)], ['e8', $this->king(Color::BLACK)],
        ]);
        $keys = $this->keys($this->generator->generate($position, Color::WHITE, new DecisionHistory()));
        self::assertArrayHasKey('d4-f6', $keys);
        self::assertArrayNotHasKey('d4-g7', $keys);
        self::assertArrayHasKey('a1-a6', $keys);
        self::assertArrayNotHasKey('a1-a7', $keys);
        self::assertArrayHasKey('h4-f4', $keys);
        self::assertArrayNotHasKey('h4-e4', $keys);
    }

    public function testPawnSingleDoubleAndBlockedDoubleMove(): void
    {
        $pawn = $this->piece('p', Color::WHITE, PieceType::PAWN);
        $position = $this->position([['e2', $pawn], ['e1', $this->king(Color::WHITE)], ['e8', $this->king(Color::BLACK)]]);
        $keys = $this->keys($this->movesByPiece($this->generator->generate($position, Color::WHITE, new DecisionHistory()), $pawn));
        self::assertArrayHasKey('e2-e3', $keys);
        self::assertArrayHasKey('e2-e4', $keys);
        self::assertCount(2, $keys);

        $blocker = $this->piece('blocker', Color::BLACK, PieceType::KNIGHT);
        $blocked = $this->position([['e2', $pawn], ['e3', $blocker], ['e1', $this->king(Color::WHITE)], ['e8', $this->king(Color::BLACK)]]);
        self::assertCount(0, $this->movesByPiece($this->generator->generate($blocked, Color::WHITE, new DecisionHistory()), $pawn));
    }

    public function testPawnCaptureAndForwardBlock(): void
    {
        $pawn = $this->piece('p', Color::WHITE, PieceType::PAWN);
        $enemy = $this->piece('enemy', Color::BLACK, PieceType::KNIGHT);
        $forward = $this->piece('forward', Color::BLACK, PieceType::KNIGHT);
        $position = $this->position([
            ['e4', $pawn], ['d5', $enemy], ['e5', $forward],
            ['e1', $this->king(Color::WHITE)], ['e8', $this->king(Color::BLACK)],
        ]);
        $keys = $this->keys($this->movesByPiece($this->generator->generate($position, Color::WHITE, new DecisionHistory()), $pawn));
        self::assertArrayHasKey('e4-d5', $keys);
        self::assertArrayNotHasKey('e4-e5', $keys);
    }

    public function testKingSafetyFiltersIllegalKingAndPinnedMoves(): void
    {
        $king = $this->king(Color::WHITE);
        $enemyRook = $this->piece('er', Color::BLACK, PieceType::ROOK);
        $position = $this->position([['e4', $king], ['e6', $enemyRook], ['e8', $this->king(Color::BLACK)]]);
        $keys = $this->keys($this->movesByPiece($this->generator->generate($position, Color::WHITE, new DecisionHistory()), $king));
        self::assertArrayNotHasKey('e4-e5', $keys);
        self::assertArrayHasKey('e4-d4', $keys);

        $whiteRook = $this->piece('wr', Color::WHITE, PieceType::ROOK);
        $blackRook = $this->piece('br', Color::BLACK, PieceType::ROOK);
        $pinned = $this->position([['e1', $king], ['e2', $whiteRook], ['e7', $blackRook], ['e8', $this->king(Color::BLACK)]]);
        $pinnedKeys = $this->keys($this->movesByPiece($this->generator->generate($pinned, Color::WHITE, new DecisionHistory()), $whiteRook));
        self::assertArrayNotHasKey('e2-d2', $pinnedKeys);
        self::assertArrayNotHasKey('e2-f2', $pinnedKeys);
    }

    public function testKingCannotCaptureProtectedPiece(): void
    {
        $king = $this->king(Color::WHITE);
        $enemy = $this->piece('enemy', Color::BLACK, PieceType::ROOK);
        $guard = $this->piece('guard', Color::BLACK, PieceType::ROOK);
        $position = $this->position([['e1', $king], ['e2', $enemy], ['e7', $guard], ['e8', $this->king(Color::BLACK)]]);
        self::assertArrayNotHasKey('e1-e2', $this->keys($this->movesByPiece($this->generator->generate($position, Color::WHITE, new DecisionHistory()), $king)));
    }

    public function testCastlingBothSidesAndMovedRook(): void
    {
        $king = $this->king(Color::WHITE);
        $rookA = $this->piece('ra', Color::WHITE, PieceType::ROOK);
        $rookH = $this->piece('rh', Color::WHITE, PieceType::ROOK);
        $position = $this->position([['e1', $king], ['a1', $rookA], ['h1', $rookH], ['e8', $this->king(Color::BLACK)]]);
        $keys = $this->keys($this->generator->generate($position, Color::WHITE, new DecisionHistory()));
        self::assertArrayHasKey('e1-g1', $keys);
        self::assertArrayHasKey('e1-c1', $keys);

        $history = new DecisionHistory();
        $history->add(Color::WHITE, new Decision(DecisionType::MOVE, $this->move($this->moveChange($rookH, 'h1', 'g1'))));
        self::assertArrayNotHasKey('e1-g1', $this->keys($this->generator->generate($position, Color::WHITE, $history)));
    }

    public function testEnPassantRequiresImmediateDoublePawnMove(): void
    {
        $white = $this->piece('wp', Color::WHITE, PieceType::PAWN);
        $historyWhite = $this->piece('history-wp', Color::WHITE, PieceType::PAWN);
        $black = $this->piece('bp', Color::BLACK, PieceType::PAWN);
        $position = $this->position([
            ['e5', $white], ['a3', $historyWhite], ['d5', $black],
            ['e1', $this->king(Color::WHITE)], ['e8', $this->king(Color::BLACK)],
        ]);
        $history = new DecisionHistory();
        $history->add(Color::WHITE, new Decision(DecisionType::MOVE, $this->move($this->moveChange($historyWhite, 'a2', 'a3'))));
        $history->add(Color::BLACK, new Decision(DecisionType::MOVE, $this->move($this->moveChange($black, 'd7', 'd5'))));
        $keys = $this->keys($this->movesByPiece($this->generator->generate($position, Color::WHITE, $history), $white));
        self::assertArrayHasKey('e5-d6', $keys);
        self::assertSame(PositionChangeType::REMOVE, $keys['e5-d6']->changes[1]->type);

        $history2 = new DecisionHistory();
        $history2->add(Color::WHITE, new Decision(DecisionType::MOVE, $this->move($this->moveChange($historyWhite, 'a2', 'a3'))));
        $history2->add(Color::BLACK, new Decision(DecisionType::MOVE, $this->move($this->moveChange($black, 'd6', 'd5'))));
        self::assertArrayNotHasKey('e5-d6', $this->keys($this->movesByPiece($this->generator->generate($position, Color::WHITE, $history2), $white)));
    }

    public function testPromotionGeneratesFourQuietChoices(): void
    {
        $pawn = $this->piece('pawn', Color::WHITE, PieceType::PAWN);
        $position = $this->promotionPosition($pawn, null);
        $moves = $this->movesByPiece($this->generator->generate($position, Color::WHITE, new DecisionHistory()), $pawn);
        self::assertCount(4, $moves);
        self::assertSame([PieceType::BISHOP, PieceType::KNIGHT, PieceType::QUEEN, PieceType::ROOK], $this->promotionTypes($moves));
    }

    public function testPromotionCaptureGeneratesFourChoices(): void
    {
        $pawn = $this->piece('pawn', Color::WHITE, PieceType::PAWN);
        $enemy = $this->piece('enemy', Color::BLACK, PieceType::ROOK);
        $position = $this->promotionPosition($pawn, $enemy);
        $moves = array_values(array_filter(
            $this->movesByPiece($this->generator->generate($position, Color::WHITE, new DecisionHistory()), $pawn),
            static fn(Move $move): bool => $move->changes[0]->to?->notation() === 'd8',
        ));
        self::assertCount(4, $moves);
        foreach ($moves as $move) {
            self::assertSame(PositionChangeType::REMOVE, $move->changes[1]->type);
            self::assertSame(PositionChangeType::CHANGE, $move->changes[2]->type);
        }
        self::assertSame([PieceType::BISHOP, PieceType::KNIGHT, PieceType::QUEEN, PieceType::ROOK], $this->promotionTypes($moves));
    }

    public function testGeneratorDoesNotMutatePositionOrHistory(): void
    {
        $position = InitialPosition::create();
        $history = new DecisionHistory();
        $pieceCount = $position->getPieces()->count();
        $historyCount = $history->count();
        $this->generator->generate($position, Color::WHITE, $history);
        self::assertSame($pieceCount, $position->getPieces()->count());
        self::assertSame($historyCount, $history->count());
        self::assertSame(Color::WHITE, $position->getPieceAt($this->square('e2'))?->color);
    }

    private function keys(array $moves): array
    {
        $keys = [];
        foreach ($moves as $move) {
            $change = $move->changes[0];
            self::assertNotNull($change->from);
            self::assertNotNull($change->to);
            $key = $change->from->notation() . '-' . $change->to->notation();
            foreach ($move->changes as $positionChange) {
                if ($positionChange->type === PositionChangeType::CHANGE && $positionChange->replacement !== null) {
                    $key .= '=' . $positionChange->replacement->type->value;
                    break;
                }
            }
            $keys[$key] = $move;
        }
        return $keys;
    }

    private function movesByPiece(array $moves, Piece $piece): array
    {
        return array_values(array_filter($moves, static fn(Move $move): bool => $move->changes[0]->piece === $piece));
    }

    private function promotionPosition(Piece $pawn, ?Piece $capture): Position
    {
        $pieces = new Pieces();
        $whiteKing = $this->king(Color::WHITE);
        $blackKing = $this->king(Color::BLACK);
        $pieces->add($pawn);
        $pieces->add($whiteKing);
        $pieces->add($blackKing);
        if ($capture !== null) $pieces->add($capture);
        foreach ([PieceType::QUEEN, PieceType::ROOK, PieceType::BISHOP, PieceType::KNIGHT] as $type) {
            $pieces->add($this->piece('reserve-' . $type->value, Color::WHITE, $type));
        }
        $position = new Position($this->board, $pieces);
        $position->place($this->square('e7'), $pawn);
        $position->place($this->square('e1'), $whiteKing);
        $position->place($this->square('a8'), $blackKing);
        if ($capture !== null) $position->place($this->square('d8'), $capture);
        return $position;
    }

    private function promotionTypes(array $moves): array
    {
        $types = [];
        foreach ($moves as $move) {
            foreach ($move->changes as $change) {
                if ($change->type === PositionChangeType::CHANGE && $change->replacement !== null) $types[] = $change->replacement->type;
            }
        }
        usort($types, static fn(PieceType $a, PieceType $b): int => $a->value <=> $b->value);
        return $types;
    }

    private function position(array $placements): Position
    {
        $pieces = new Pieces();
        foreach ($placements as [$notation, $piece]) $pieces->add($piece);
        $position = new Position($this->board, $pieces);
        foreach ($placements as [$notation, $piece]) $position->place($this->square($notation), $piece);
        return $position;
    }

    private function move(PositionChange ...$changes): Move
    {
        return new Move(...$changes);
    }

    private function moveChange(Piece $piece, string $from, string $to): PositionChange
    {
        return new PositionChange(PositionChangeType::MOVE, $piece, $this->square($from), $this->square($to));
    }

    private function piece(string $id, Color $color, PieceType $type): Piece
    {
        return new Piece($id, $color, $type);
    }

    private function king(Color $color): Piece
    {
        return $this->piece($color === Color::WHITE ? 'white-king' : 'black-king', $color, PieceType::KING);
    }

    private function square(string $notation): Square
    {
        return $this->board->getSquareByNotation($notation);
    }
}
