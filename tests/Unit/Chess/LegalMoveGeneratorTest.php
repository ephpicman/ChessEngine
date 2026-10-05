<?php

declare(strict_types=1);

namespace Ephpicman\ChessEngine\Tests\Unit\Chess;

use Ephpicman\ChessEngine\Chess\Board;
use Ephpicman\ChessEngine\Chess\Color;
use Ephpicman\ChessEngine\Chess\Decision;
use Ephpicman\ChessEngine\Chess\DecisionHistory;
use Ephpicman\ChessEngine\Chess\DecisionType;
use Ephpicman\ChessEngine\Chess\File;
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
use Ephpicman\ChessEngine\Chess\Rank;
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
        self::assertSame(20, count($this->keys($moves)));
    }

    public function testInitialPositionHasTwentyBlackLegalMoves(): void
    {
        $history = new DecisionHistory();

        $position = InitialPosition::create();
        $whitePawn = $position->getPieceAt($this->square('e2'));
        self::assertNotNull($whitePawn);

        $whiteMove = $this->move(
            $this->moveChange($whitePawn, 'e2', 'e4'),
        );

        $history->add(
            Color::WHITE,
            new Decision(DecisionType::MOVE, $whiteMove),
        );

        $position->remove($this->square('e2'));
        $position->place($this->square('e4'), $whitePawn);

        $moves = $this->generator->generate(
            $position,
            Color::BLACK,
            $history,
        );

        self::assertCount(20, $moves);
    }

    public function testOnlyRequestedColourIsGenerated(): void
    {
        $whiteKnight = $this->piece(
            'white-knight',
            Color::WHITE,
            PieceType::KNIGHT,
        );
        $blackKnight = $this->piece(
            'black-knight',
            Color::BLACK,
            PieceType::KNIGHT,
        );

        $position = $this->position([
            ['d4', $whiteKnight],
            ['d5', $blackKnight],
            ['e1', $this->king(Color::WHITE)],
            ['e8', $this->king(Color::BLACK)],
        ]);

        $moves = $this->generator->generate(
            $position,
            Color::WHITE,
            new DecisionHistory(),
        );

        foreach ($moves as $move) {
            self::assertSame(Color::WHITE, $move->changes[0]->piece->color);
        }

        self::assertCount(8, $moves);
    }

    public function testKnightGeneratesAllEightMovesFromCentreAndCapturesEnemy(): void
    {
        $knight = $this->piece(
            'white-knight',
            Color::WHITE,
            PieceType::KNIGHT,
        );
        $enemy = $this->piece(
            'black-pawn',
            Color::BLACK,
            PieceType::PAWN,
        );

        $position = $this->position([
            ['d4', $knight],
            ['f5', $enemy],
            ['e1', $this->king(Color::WHITE)],
            ['e8', $this->king(Color::BLACK)],
        ]);

        $keys = $this->keys(
            $this->generator->generate(
                $position,
                Color::WHITE,
                new DecisionHistory(),
            ),
        );

        self::assertCount(8, $keys);
        self::assertArrayHasKey('d4-f5', $keys);
        self::assertTrue($keys['d4-f5']->changes[1]->type === PositionChangeType::REMOVE);
    }

    public function testKnightCannotCaptureOwnPiece(): void
    {
        $knight = $this->piece(
            'white-knight',
            Color::WHITE,
            PieceType::KNIGHT,
        );
        $friendly = $this->piece(
            'white-pawn',
            Color::WHITE,
            PieceType::PAWN,
        );

        $position = $this->position([
            ['d4', $knight],
            ['f5', $friendly],
            ['e1', $this->king(Color::WHITE)],
            ['e8', $this->king(Color::BLACK)],
        ]);

        $keys = $this->keys(
            $this->generator->generate(
                $position,
                Color::WHITE,
                new DecisionHistory(),
            ),
        );

        self::assertCount(7, $keys);
        self::assertArrayNotHasKey('d4-f5', $keys);
    }

    public function testBishopRookAndQueenStopAtFirstOccupiedSquare(): void
    {
        $bishop = $this->piece('bishop', Color::WHITE, PieceType::BISHOP);
        $rook = $this->piece('rook', Color::WHITE, PieceType::ROOK);
        $queen = $this->piece('queen', Color::WHITE, PieceType::QUEEN);
        $enemy = $this->piece('enemy', Color::BLACK, PieceType::PAWN);

        $position = $this->position([
            ['d4', $bishop],
            ['a1', $rook],
            ['h4', $queen],
            ['f6', $enemy],
            ['e1', $this->king(Color::WHITE)],
            ['e8', $this->king(Color::BLACK)],
        ]);

        $keys = $this->keys(
            $this->generator->generate(
                $position,
                Color::WHITE,
                new DecisionHistory(),
            ),
        );

        self::assertArrayHasKey('d4-f6', $keys);
        self::assertArrayNotHasKey('d4-g7', $keys);
        self::assertArrayNotHasKey('a1-a6', $keys);
        self::assertArrayNotHasKey('h4-g4', $keys);
    }

    public function testPawnGeneratesSingleAndDoubleMove(): void
    {
        $pawn = $this->piece('pawn', Color::WHITE, PieceType::PAWN);

        $position = $this->position([
            ['e2', $pawn],
            ['e1', $this->king(Color::WHITE)],
            ['e8', $this->king(Color::BLACK)],
        ]);

        $keys = $this->keys(
            $this->generator->generate(
                $position,
                Color::WHITE,
                new DecisionHistory(),
            ),
        );

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

        $keys = $this->keys(
            $this->generator->generate(
                $position,
                Color::WHITE,
                new DecisionHistory(),
            ),
        );

        self::assertCount(0, $keys);
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

        $keys = $this->keys(
            $this->generator->generate(
                $position,
                Color::WHITE,
                new DecisionHistory(),
            ),
        );

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

        $keys = $this->keys(
            $this->generator->generate(
                $position,
                Color::WHITE,
                new DecisionHistory(),
            ),
        );

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

        $keys = $this->keys(
            $this->generator->generate(
                $position,
                Color::WHITE,
                new DecisionHistory(),
            ),
        );

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

        $keys = $this->keys(
            $this->generator->generate(
                $position,
                Color::WHITE,
                new DecisionHistory(),
            ),
        );

        self::assertArrayNotHasKey('e1-e2', $keys);
    }

    public function testCastlingGeneratesBothSidesWhenAvailable(): void
    {
        $whiteKing = $this->king(Color::WHITE);
        $whiteRookA = $this->piece('rook-a', Color::WHITE, PieceType::ROOK);
        $whiteRookH = $this->piece('rook-h', Color::WHITE, PieceType::ROOK);

        $position = $this->position([
            ['e1', $whiteKing],
            ['a1', $whiteRookA],
            ['h1', $whiteRookH],
            ['e8', $this->king(Color::BLACK)],
        ]);

        $keys = $this->keys(
            $this->generator->generate(
                $position,
                Color::WHITE,
                new DecisionHistory(),
            ),
        );

        self::assertArrayHasKey('e1-g1', $keys);
        self::assertArrayHasKey('e1-c1', $keys);
        self::assertSame(2, count($this->movesWithTwoMoveChanges($keys)));
    }

    public function testCastlingIsNotGeneratedAfterKingOrRookHasMoved(): void
    {
        $king = $this->king(Color::WHITE);
        $rook = $this->piece('rook-h', Color::WHITE, PieceType::ROOK);
        $dummy = $this->piece('dummy', Color::BLACK, PieceType::KNIGHT);

        $position = $this->position([
            ['e1', $king],
            ['h1', $rook],
            ['e8', $this->king(Color::BLACK)],
            ['a8', $dummy],
        ]);

        $history = new DecisionHistory();
        $history->add(
            Color::WHITE,
            new Decision(
                DecisionType::MOVE,
                $this->move($this->moveChange($rook, 'h1', 'g1')),
            ),
        );

        $keys = $this->keys(
            $this->generator->generate($position, Color::WHITE, $history),
        );

        self::assertArrayNotHasKey('e1-g1', $keys);
    }

    public function testEnPassantIsGeneratedImmediatelyAfterDoublePawnMove(): void
    {
        $whitePawn = $this->piece('white-pawn', Color::WHITE, PieceType::PAWN);
        $blackPawn = $this->piece('black-pawn', Color::BLACK, PieceType::PAWN);

        $position = $this->position([
            ['e5', $whitePawn],
            ['d5', $blackPawn],
            ['e1', $this->king(Color::WHITE)],
            ['e8', $this->king(Color::BLACK)],
        ]);

        $history = new DecisionHistory();
        $lastMove = $this->move(
            $this->moveChange($blackPawn, 'd7', 'd5'),
        );
        $history->add(
            Color::BLACK,
            new Decision(DecisionType::MOVE, $lastMove),
        );

        $keys = $this->keys(
            $this->generator->generate($position, Color::WHITE, $history),
        );

        self::assertArrayHasKey('e5-d6', $keys);
        self::assertCount(2, $keys['e5-d6']->changes);
        self::assertSame(PositionChangeType::REMOVE, $keys['e5-d6']->changes[1]->type);
    }

    public function testEnPassantIsNotGeneratedWithoutImmediateDoublePawnMove(): void
    {
        $whitePawn = $this->piece('white-pawn', Color::WHITE, PieceType::PAWN);
        $blackPawn = $this->piece('black-pawn', Color::BLACK, PieceType::PAWN);

        $position = $this->position([
            ['e5', $whitePawn],
            ['d5', $blackPawn],
            ['e1', $this->king(Color::WHITE)],
            ['e8', $this->king(Color::BLACK)],
        ]);

        $history = new DecisionHistory();
        $history->add(
            Color::BLACK,
            new Decision(
                DecisionType::MOVE,
                $this->move($this->moveChange($blackPawn, 'd6', 'd5')),
            ),
        );

        $keys = $this->keys(
            $this->generator->generate($position, Color::WHITE, $history),
        );

        self::assertArrayNotHasKey('e5-d6', $keys);
    }

    public function testPromotionGeneratesFourQuietChoices(): void
    {
        $pawn = $this->piece('pawn', Color::WHITE, PieceType::PAWN);
        $this->addPromotionReserves();

        $position = $this->position([
            ['e7', $pawn],
            ['e1', $this->king(Color::WHITE)],
            ['e8', $this->king(Color::BLACK)],
        ], false);

        $keys = $this->keys(
            $this->generator->generate(
                $position,
                Color::WHITE,
                new DecisionHistory(),
            ),
        );

        self::assertCount(4, $keys);

        foreach ($keys as $move) {
            self::assertSame(2, count($move->changes));
            self::assertSame(PositionChangeType::CHANGE, $move->changes[1]->type);
        }
    }

    public function testPromotionCaptureGeneratesFourChoices(): void
    {
        $pawn = $this->piece('pawn', Color::WHITE, PieceType::PAWN);
        $enemy = $this->piece('enemy', Color::BLACK, PieceType::ROOK);
        $this->addPromotionReserves();

        $position = $this->position([
            ['e7', $pawn],
            ['d8', $enemy],
            ['e1', $this->king(Color::WHITE)],
            ['a8', $this->king(Color::BLACK)],
        ], false);

        $keys = $this->keys(
            $this->generator->generate(
                $position,
                Color::WHITE,
                new DecisionHistory(),
            ),
        );

        self::assertCount(4, $keys);

        foreach ($keys as $move) {
            self::assertSame(PositionChangeType::REMOVE, $move->changes[1]->type);
            self::assertSame(PositionChangeType::CHANGE, $move->changes[2]->type);
        }
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
        self::assertSame(
            'w',
            $position->getPieceAt($this->square('e2'))?->color->value,
        );
    }

    /**
     * @param array<int, Move> $moves
     * @return array<string, Move>
     */
    private function keys(array $moves): array
    {
        $keys = [];

        foreach ($moves as $move) {
            $moveChange = $move->changes[0];

            self::assertNotNull($moveChange->from);
            self::assertNotNull($moveChange->to);

            $key = $moveChange->from->notation()
                . '-'
                . $moveChange->to->notation();

            $keys[$key] = $move;
        }

        return $keys;
    }

    /**
     * @param array<string, Move> $moves
     * @return array<int, Move>
     */
    private function movesWithTwoMoveChanges(array $moves): array
    {
        return array_values(array_filter(
            $moves,
            static fn (Move $move): bool =>
                count(array_filter(
                    $move->changes,
                    static fn (PositionChange $change): bool =>
                        $change->type === PositionChangeType::MOVE,
                )) === 2,
        ));
    }

    private function position(
        array $placements,
        bool $includeAllPieces = true,
    ): Position {
        $pieces = new Pieces();

        if ($includeAllPieces) {
            foreach ($placements as [$notation, $piece]) {
                $pieces->add($piece);
            }
        } else {
            foreach ($placements as [$notation, $piece]) {
                $pieces->add($piece);
            }
        }

        $position = new Position($this->board, $pieces);

        foreach ($placements as [$notation, $piece]) {
            $position->place($this->square($notation), $piece);
        }

        return $position;
    }

    private function addPromotionReserves(): void
    {
        foreach ([
            PieceType::QUEEN,
            PieceType::ROOK,
            PieceType::BISHOP,
            PieceType::KNIGHT,
        ] as $type) {
            $this->currentPromotionPieces[] = $this->piece(
                'reserve-' . $type->value,
                Color::WHITE,
                $type,
            );
        }
    }

    /**
     * @var array<int, Piece>
     */
    private array $currentPromotionPieces = [];

    private function move(PositionChange ...$changes): Move
    {
        return new Move(...$changes);
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

    private function square(string $notation): Square
    {
        return $this->board->getSquareByNotation($notation);
    }
}
