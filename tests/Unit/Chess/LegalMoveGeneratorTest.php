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
        $moves = $this->generator->generate(
            InitialPosition::create(),
            Color::WHITE,
            new DecisionHistory(),
        );

        self::assertCount(20, $moves);
    }

    public function testInitialPositionHasTwentyBlackLegalMovesAfterWhiteMove(): void
    {
        $position = InitialPosition::create();
        $pawn = $position->getPieceAt($this->square('e2'));
        self::assertNotNull($pawn);

        $history = new DecisionHistory();
        $history->add(
            Color::WHITE,
            new Decision(
                DecisionType::MOVE,
                $this->move($this->moveChange($pawn, 'e2', 'e4')),
            ),
        );
        $position->remove($this->square('e2'));
        $position->place($this->square('e4'), $pawn);

        self::assertCount(
            20,
            $this->generator->generate($position, Color::BLACK, $history),
        );
    }

    public function testOnlyRequestedColourIsGenerated(): void
    {
        $whiteKnight = $this->piece('white-knight', Color::WHITE, PieceType::KNIGHT);
        $blackKnight = $this->piece('black-knight', Color::BLACK, PieceType::KNIGHT);
        $position = $this->position([
            ['d4', $whiteKnight],
            ['d5', $blackKnight],
            ['e1', $this->king(Color::WHITE)],
            ['e8', $this->king(Color::BLACK)],
        ]);

        $moves = $this->generator->generate($position, Color::WHITE, new DecisionHistory());

        foreach ($moves as $move) {
            self::assertSame(Color::WHITE, $move->changes[0]->piece->color);
        }

        self::assertCount(8, $this->movesByPiece($moves, $whiteKnight));
    }

    public function testKnightGeneratesAllEightMovesAndCapturesEnemy(): void
    {
        $knight = $this->piece('white-knight', Color::WHITE, PieceType::KNIGHT);
        $enemy = $this->piece('black-pawn', Color::BLACK, PieceType::PAWN);
        $position = $this->position([
            ['d4', $knight],
            ['f5', $enemy],
            ['e1', $this->king(Color::WHITE)],
            ['e8', $this->king(Color::BLACK)],
        ]);

        $moves = $this->movesByPiece(
            $this->generator->generate($position, Color::WHITE, new DecisionHistory()),
            $knight,
        );
        $keys = $this->keys($moves);

        self::assertCount(8, $moves);
        self::assertArrayHasKey('d4-f5', $keys);
        self::assertSame(PositionChangeType::REMOVE, $keys['d4-f5']->changes[1]->type);
    }

    public function testKnightCannotCaptureOwnPiece(): void
    {
        $knight = $this->piece('white-knight', Color::WHITE, PieceType::KNIGHT);
        $friendly = $this->piece('white-pawn', Color::WHITE, PieceType::PAWN);
        $position = $this->position([
            ['d4', $knight],
            ['f5', $friendly],
            ['e1', $this->king(Color::WHITE)],
            ['e8', $this->king(Color::BLACK)],
        ]);

        $moves = $this->movesByPiece(
            $this->generator->generate($position, Color::WHITE, new DecisionHistory()),
            $knight,
        );

        self::assertCount(7, $moves);
        self::assertArrayNotHasKey('d4-f5', $this->keys($moves));
    }

    public function testSlidingPiecesStopAtFirstOccupiedSquare(): void
    {
        $bishop = $this->piece('bishop', Color::WHITE, PieceType::BISHOP);
        $rook = $this->piece('rook', Color::WHITE, PieceType::ROOK);
        $queen = $this->piece('queen', Color::WHITE, PieceType::QUEEN);
        $bishopTarget = $this->piece('bishop-target', Color::BLACK, PieceType::PAWN);
        $rookBlocker = $this->piece('rook-blocker', Color::BLACK, PieceType::PAWN);
        $queenBlocker = $this->piece('queen-blocker', Color::BLACK, PieceType::PAWN);

        $position = $this->position([
            ['d4', $bishop],
            ['f6', $bishopTarget],
            ['a1', $rook],
            ['a6', $rookBlocker],
            ['h4', $queen],
            ['f4', $queenBlocker],
            ['e1', $this->king(Color::WHITE)],
            ['e8', $this->king(Color::BLACK)],
        ]);

        $keys = $this->keys(
            $this->generator->generate($position, Color::WHITE, new DecisionHistory()),
        );

        self::assertArrayHasKey('d4-f6', $keys);
        self::assertArrayNotHasKey('d4-g7', $keys);
        self::assertArrayHasKey('a1-a2', $keys);
        self::assertArrayHasKey('a1-a6', $keys);
        self::assertArrayNotHasKey('a1-a7', $keys);
        self::assertArrayHasKey('h4-f4', $keys);
        self::assertArrayNotHasKey('h4-e4', $keys);
    }

    public function testPawnGeneratesSingleAndDoubleMove(): void
    {
        $pawn = $this->piece('pawn', Color::WHITE, PieceType::PAWN);
        $position = $this->position([
            ['e2', $pawn],
            ['e1', $this->king(Color::WHITE)],
            ['e8', $this->king(Color::BLACK)],
        ]);

        $keys = $this->keys($this->movesByPiece(
            $this->generator->generate($position, Color::WHITE, new DecisionHistory()),
            $pawn,
        ));

        self::assertArrayHasKey('e2-e3', $keys);
        self::assertArrayHasKey('e2-e4', $keys);
        self::assertCount(2, $keys);
    }

    public function testPawnDoesNotDoubleMoveThroughOccupiedSquare(): void
    {
        $pawn = $this->piece('pawn', Color::WHITE, PieceType::PAWN);
        $blocker = $this->piece('blocker', Color::BLACK, PieceType::KNIGHT);
        $position = $this->position([
            ['e2', $pawn],
            ['e3', $blocker],
            ['e1', $this->king(Color::WHITE)],
            ['e8', $this->king(Color::BLACK)],
        ]);

        self::assertCount(
            0,
            $this->movesByPiece(
                $this->generator->generate($position, Color::WHITE, new DecisionHistory()),
                $pawn,
            ),
        );
    }

    public function testPawnCapturesDiagonallyButNotForward(): void
    {
        $pawn = $this->piece('pawn', Color::WHITE, PieceType::PAWN);
        $enemy = $this->piece('enemy', Color::BLACK, PieceType::KNIGHT);
        $forward = $this->piece('forward', Color::BLACK, PieceType::KNIGHT);
        $position = $this->position([
            ['e4', $pawn],
            ['d5', $enemy],
            ['e5', $forward],
            ['e1', $this->king(Color::WHITE)],
            ['e8', $this->king(Color::BLACK)],
        ]);

        $keys = $this->keys($this->movesByPiece(
            $this->generator->generate($position, Color::WHITE, new DecisionHistory()),
            $pawn,
        ));

        self::assertArrayHasKey('e4-d5', $keys);
        self::assertArrayNotHasKey('e4-e5', $keys);
    }

    public function testKingGeneratesOnlyLegalSquares(): void
    {
        $king = $this->king(Color::WHITE);
        $enemyRook = $this->piece('enemy-rook', Color::BLACK, PieceType::ROOK);
        $position = $this->position([
            ['e4', $king],
            ['e8', $this->king(Color::BLACK)],
            ['e6', $enemyRook],
        ]);

        $keys = $this->keys($this->movesByPiece(
            $this->generator->generate($position, Color::WHITE, new DecisionHistory()),
            $king,
        ));

        self::assertArrayNotHasKey('e4-e5', $keys);
        self::assertArrayHasKey('e4-d4', $keys);
    }

    public function testPinnedPieceMovesAreFilteredByKingSafety(): void
    {
        $king = $this->king(Color::WHITE);
        $rook = $this->piece('white-rook', Color::WHITE, PieceType::ROOK);
        $enemyRook = $this->piece('black-rook', Color::BLACK, PieceType::ROOK);
        $position = $this->position([
            ['e1', $king],
            ['e2', $rook],
            ['e8', $this->king(Color::BLACK)],
            ['e7', $enemyRook],
        ]);

        $keys = $this->keys($this->movesByPiece(
            $this->generator->generate($position, Color::WHITE, new DecisionHistory()),
            $rook,
        ));

        self::assertArrayNotHasKey('e2-d2', $keys);
        self::assertArrayNotHasKey('e2-f2', $keys);
    }

    public function testKingCaptureThatLeavesKingInCheckIsExcluded(): void
    {
        $king = $this->king(Color::WHITE);
        $enemy = $this->piece('enemy', Color::BLACK, PieceType::ROOK);
        $guard = $this->piece('guard', Color::BLACK, PieceType::ROOK);
        $position = $this->position([
            ['e1', $king],
            ['e2', $enemy],
            ['e8', $this->king(Color::BLACK)],
            ['e7', $guard],
        ]);

        self::assertArrayNotHasKey(
            'e1-e2',
            $this->keys($this->movesByPiece(
                $this->generator->generate($position, Color::WHITE, new DecisionHistory()),
                $king,
            )),
        );
    }

    public function testCastlingGeneratesBothSidesWhenAvailable(): void
    {
        $king = $this->king(Color::WHITE);
        $rookA = $this->piece('rook-a', Color::WHITE, PieceType::ROOK);
        $rookH = $this->piece('rook-h', Color::WHITE, PieceType::ROOK);
        $position = $this->position([
            ['e1', $king],
            ['a1', $rookA],
            ['h1', $rookH],
            ['e8', $this->king(Color::BLACK)],
        ]);

        $keys = $this->keys($this->generator->generate($position, Color::WHITE, new DecisionHistory()));

        self::assertArrayHasKey('e1-g1', $keys);
        self::assertArrayHasKey('e1-c1', $keys);
        self::assertSame(2, count($this->movesWithTwoMoveChanges($keys)));
    }

    public function testCastlingIsNotGeneratedAfterRookHasMoved(): void
    {
        $king = $this->king(Color::WHITE);
        $rook = $this->piece('rook-h', Color::WHITE, PieceType::ROOK);
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
                $this->move($this->moveChange($rook, 'h1', 'g1')),
            ),
        );

        self::assertArrayNotHasKey(
            'e1-g1',
            $this->keys($this->generator->generate($position, Color::WHITE, $history)),
        );
    }

    public function testEnPassantIsGeneratedImmediatelyAfterDoublePawnMove(): void
    {
        $whitePawn = $this->piece('white-pawn', Color::WHITE, PieceType::PAWN);
        $historyPawn = $this->piece('history-white-pawn', Color::WHITE, PieceType::PAWN);
        $blackPawn = $this->piece('black-pawn', Color::BLACK, PieceType::PAWN);
        $position = $this->position([
            ['e5', $whitePawn],
            ['a3', $historyPawn],
            ['d5', $blackPawn],
            ['e1', $this->king(Color::WHITE)],
            ['e8', $this->king(Color::BLACK)],
        ]);

        $history = new DecisionHistory();
        $history->add(
            Color::WHITE,
            new Decision(
                DecisionType::MOVE,
                $this->move($this->moveChange($historyPawn, 'a2', 'a3')),
            ),
        );
        $history->add(
            Color::BLACK,
            new Decision(
                DecisionType::MOVE,
                $this->move($this->moveChange($blackPawn, 'd7', 'd5')),
            ),
        );

        $moves = $this->movesByPiece(
            $this->generator->generate($position, Color::WHITE, $history),
            $whitePawn,
        );
        $keys = $this->keys($moves);

        self::assertArrayHasKey('e5-d6', $keys);
        self::assertCount(2, $keys['e5-d6']->changes);
        self::assertSame(PositionChangeType::REMOVE, $keys['e5-d6']->changes[1]->type);
    }

    public function testEnPassantIsNotGeneratedWithoutImmediateDoublePawnMove(): void
    {
        $whitePawn = $this->piece('white-pawn', Color::WHITE, PieceType::PAWN);
        $blackPawn = $this->piece('black-pawn', Color::BLACK, PieceType::PAWN);
        $historyPawn = $this->piece('history-white-pawn', Color::WHITE, PieceType::PAWN);
        $position = $this->position([
            ['e5', $whitePawn],
            ['a3', $historyPawn],
            ['d5', $blackPawn],
            ['e1', $this->king(Color::WHITE)],
            ['e8', $this->king(Color::BLACK)],
        ]);

        $history = new DecisionHistory();
        $history->add(
            Color::WHITE,
            new Decision(
                DecisionType::MOVE,
                $this->move($this->moveChange($historyPawn, 'a2', 'a3')),
            ),
        );
        $history->add(
            Color::BLACK,
            new Decision(
                DecisionType::MOVE,
                $this->move($this->moveChange($blackPawn, 'd6', 'd5')),
            ),
        );

        self::assertArrayNotHasKey(
            'e5-d6',
            $this->keys($this->movesByPiece(
                $this->generator->generate($position, Color::WHITE, $history),
                $whitePawn,
            )),
        );
    }

    public function testPromotionGeneratesFourQuietChoices(): void
    {
        $pawn = $this->piece('pawn', Color::WHITE, PieceType::PAWN);
        $position = $this->promotionPosition($pawn, null);

        $moves = $this->movesByPiece(
            $this->generator->generate($position, Color::WHITE, new DecisionHistory()),
            $pawn,
        );
        $keys = $this->keys($moves);

        self::assertCount(4, $moves);
        self::assertCount(4, $keys);
        self::assertSame(
            [PieceType::BISHOP, PieceType::KNIGHT, PieceType::QUEEN, PieceType::ROOK],
            $this->promotionTypes($moves),
        );
    }

    public function testPromotionCaptureGeneratesFourChoices(): void
    {
        $pawn = $this->piece('pawn', Color::WHITE, PieceType::PAWN);
        $enemy = $this->piece('enemy', Color::BLACK, PieceType::ROOK);
        $position = $this->promotionPosition($pawn, $enemy);

        $moves = $this->movesByPiece(
            $this->generator->generate($position, Color::WHITE, new DecisionHistory()),
            $pawn,
        );

        self::assertCount(4, $moves);
        foreach ($moves as $move) {
            self::assertSame(PositionChangeType::REMOVE, $move->changes[1]->type);
            self::assertSame(PositionChangeType::CHANGE, $move->changes[2]->type);
        }
        self::assertSame(
            [PieceType::BISHOP, PieceType::KNIGHT, PieceType::QUEEN, PieceType::ROOK],
            $this->promotionTypes($moves),
        );
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

    /** @param array<int, Move> $moves */
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

    /** @param array<int, Move> $moves */
    private function movesByPiece(array $moves, Piece $piece): array
    {
        return array_values(array_filter(
            $moves,
            static fn(Move $move): bool => $move->changes[0]->piece === $piece,
        ));
    }

    /** @param array<string, Move> $moves */
    private function movesWithTwoMoveChanges(array $moves): array
    {
        return array_values(array_filter(
            $moves,
            static fn(Move $move): bool => count(array_filter(
                $move->changes,
                static fn(PositionChange $change): bool => $change->type === PositionChangeType::MOVE,
            )) === 2,
        ));
    }

    private function promotionPosition(Piece $pawn, ?Piece $capture): Position
    {
        $pieces = new Pieces();
        $pieces->add($pawn);
        $pieces->add($this->king(Color::WHITE));
        $pieces->add($this->king(Color::BLACK));
        if ($capture !== null) {
            $pieces->add($capture);
        }

        foreach ([PieceType::QUEEN, PieceType::ROOK, PieceType::BISHOP, PieceType::KNIGHT] as $type) {
            $pieces->add($this->piece('reserve-' . $type->value, Color::WHITE, $type));
        }

        $position = new Position($this->board, $pieces);
        $position->place($this->square('e7'), $pawn);
        $position->place($this->square('e1'), $this->king(Color::WHITE));
        $position->place($this->square('a8'), $this->king(Color::BLACK));
        if ($capture !== null) {
            $position->place($this->square('d8'), $capture);
        }
        return $position;
    }

    /** @param array<int, Move> $moves @return array<int, PieceType> */
    private function promotionTypes(array $moves): array
    {
        $types = [];
        foreach ($moves as $move) {
            foreach ($move->changes as $change) {
                if ($change->type === PositionChangeType::CHANGE && $change->replacement !== null) {
                    $types[] = $change->replacement->type;
                }
            }
        }
        usort($types, static fn(PieceType $a, PieceType $b): int => $a->value <=> $b->value);
        return $types;
    }

    private function position(array $placements): Position
    {
        $pieces = new Pieces();
        foreach ($placements as [$notation, $piece]) {
            $pieces->add($piece);
        }
        $position = new Position($this->board, $pieces);
        foreach ($placements as [$notation, $piece]) {
            $position->place($this->square($notation), $piece);
        }
        return $position;
    }

    private function move(PositionChange ...$changes): Move
    {
        return new Move(...$changes);
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

    private function piece(string $id, Color $color, PieceType $type): Piece
    {
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

    private function square(string $notation): Square
    {
        return $this->board->getSquareByNotation($notation);
    }
}
