<?php

declare(strict_types=1);

namespace Ephpicman\ChessEngine\Tests\Unit\Chess;

use Ephpicman\ChessEngine\Chess\Board;
use Ephpicman\ChessEngine\Chess\Color;
use Ephpicman\ChessEngine\Chess\Decision;
use Ephpicman\ChessEngine\Chess\DecisionHistory;
use Ephpicman\ChessEngine\Chess\DecisionType;
use Ephpicman\ChessEngine\Chess\File;
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

final class MoveValidatorTest extends TestCase
{
    private Board $board;

    private MoveValidator $validator;

    protected function setUp(): void
    {
        $this->board = new Board();
        $this->validator = new MoveValidator();
    }

    public function testValidPawnSingleMove(): void
    {
        $pawn = $this->piece('white-pawn', Color::WHITE, PieceType::PAWN);

        $position = $this->position(
            [
                ['e2', $pawn],
                ['e1', $this->king(Color::WHITE)],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $history = new DecisionHistory();

        $move = $this->move(
            $this->moveChange($pawn, 'e2', 'e3'),
        );

        self::assertTrue(
            $this->validator->validate($position, $history, $move)
        );
    }

    public function testValidPawnDoubleMove(): void
    {
        $pawn = $this->piece('white-pawn', Color::WHITE, PieceType::PAWN);

        $position = $this->position(
            [
                ['e2', $pawn],
                ['e1', $this->king(Color::WHITE)],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($pawn, 'e2', 'e4'),
        );

        self::assertTrue(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            )
        );
    }

    public function testPawnDoubleMoveIsRejectedWhenMiddleSquareIsOccupied(): void
    {
        $pawn = $this->piece('white-pawn', Color::WHITE, PieceType::PAWN);
        $blocker = $this->piece(
            'white-knight',
            Color::WHITE,
            PieceType::KNIGHT,
        );

        $position = $this->position(
            [
                ['e2', $pawn],
                ['e3', $blocker],
                ['e1', $this->king(Color::WHITE)],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($pawn, 'e2', 'e4'),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            )
        );
    }

    public function testPawnCannotMoveForwardIntoOccupiedSquare(): void
    {
        $pawn = $this->piece('white-pawn', Color::WHITE, PieceType::PAWN);
        $blocker = $this->piece(
            'black-knight',
            Color::BLACK,
            PieceType::KNIGHT,
        );

        $position = $this->position(
            [
                ['e2', $pawn],
                ['e3', $blocker],
                ['e1', $this->king(Color::WHITE)],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($pawn, 'e2', 'e3'),
            $this->removeChange($blocker),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            )
        );
    }

    public function testValidPawnCapture(): void
    {
        $pawn = $this->piece('white-pawn', Color::WHITE, PieceType::PAWN);
        $enemy = $this->piece(
            'black-knight',
            Color::BLACK,
            PieceType::KNIGHT,
        );

        $position = $this->position(
            [
                ['e4', $pawn],
                ['d5', $enemy],
                ['e1', $this->king(Color::WHITE)],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($pawn, 'e4', 'd5'),
            $this->removeChange($enemy),
        );

        self::assertTrue(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            )
        );
    }

    public function testPawnCannotCaptureEmptySquareExceptEnPassant(): void
    {
        $pawn = $this->piece('white-pawn', Color::WHITE, PieceType::PAWN);

        $position = $this->position(
            [
                ['e4', $pawn],
                ['e1', $this->king(Color::WHITE)],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($pawn, 'e4', 'd5'),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            )
        );
    }

    public function testValidKnightMove(): void
    {
        $knight = $this->piece(
            'white-knight',
            Color::WHITE,
            PieceType::KNIGHT,
        );

        $position = $this->position(
            [
                ['b1', $knight],
                ['e1', $this->king(Color::WHITE)],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($knight, 'b1', 'c3'),
        );

        self::assertTrue(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            )
        );
    }

    public function testInvalidKnightMove(): void
    {
        $knight = $this->piece(
            'white-knight',
            Color::WHITE,
            PieceType::KNIGHT,
        );

        $position = $this->position(
            [
                ['b1', $knight],
                ['e1', $this->king(Color::WHITE)],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($knight, 'b1', 'b3'),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            )
        );
    }

    public function testValidBishopMove(): void
    {
        $bishop = $this->piece(
            'white-bishop',
            Color::WHITE,
            PieceType::BISHOP,
        );

        $position = $this->position(
            [
                ['c1', $bishop],
                ['e1', $this->king(Color::WHITE)],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($bishop, 'c1', 'f4'),
        );

        self::assertTrue(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            )
        );
    }

    public function testBishopCannotMoveStraight(): void
    {
        $bishop = $this->piece(
            'white-bishop',
            Color::WHITE,
            PieceType::BISHOP,
        );

        $position = $this->position(
            [
                ['c1', $bishop],
                ['e1', $this->king(Color::WHITE)],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($bishop, 'c1', 'c4'),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            )
        );
    }

    public function testBishopCannotJumpOverPiece(): void
    {
        $bishop = $this->piece(
            'white-bishop',
            Color::WHITE,
            PieceType::BISHOP,
        );

        $blocker = $this->piece(
            'white-pawn',
            Color::WHITE,
            PieceType::PAWN,
        );

        $position = $this->position(
            [
                ['c1', $bishop],
                ['d2', $blocker],
                ['e1', $this->king(Color::WHITE)],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($bishop, 'c1', 'f4'),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            )
        );
    }

    public function testValidRookMove(): void
    {
        $rook = $this->piece(
            'white-rook',
            Color::WHITE,
            PieceType::ROOK,
        );

        $position = $this->position(
            [
                ['a1', $rook],
                ['e1', $this->king(Color::WHITE)],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($rook, 'a1', 'a5'),
        );

        self::assertTrue(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            )
        );
    }

    public function testRookCannotMoveDiagonally(): void
    {
        $rook = $this->piece(
            'white-rook',
            Color::WHITE,
            PieceType::ROOK,
        );

        $position = $this->position(
            [
                ['a1', $rook],
                ['e1', $this->king(Color::WHITE)],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($rook, 'a1', 'b2'),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            )
        );
    }

    public function testValidQueenMove(): void
    {
        $queen = $this->piece(
            'white-queen',
            Color::WHITE,
            PieceType::QUEEN,
        );

        $position = $this->position(
            [
                ['d1', $queen],
                ['e1', $this->king(Color::WHITE)],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($queen, 'd1', 'h5'),
        );

        self::assertTrue(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            )
        );
    }

    public function testValidKingMove(): void
    {
        $king = $this->king(Color::WHITE);

        $position = $this->position(
            [
                ['e1', $king],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($king, 'e1', 'f2'),
        );

        self::assertTrue(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            )
        );
    }

    public function testKingCannotMoveMoreThanOneSquare(): void
    {
        $king = $this->king(Color::WHITE);

        $position = $this->position(
            [
                ['e1', $king],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($king, 'e1', 'e3'),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            )
        );
    }

    public function testMoveByWrongColorIsRejected(): void
    {
        $blackPawn = $this->piece(
            'black-pawn',
            Color::BLACK,
            PieceType::PAWN,
        );

        $position = $this->position(
            [
                ['e7', $blackPawn],
                ['e1', $this->king(Color::WHITE)],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($blackPawn, 'e7', 'e6'),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            )
        );
    }

    public function testPieceNotBelongingToPositionIsRejected(): void
    {
        $piece = $this->piece(
            'white-knight',
            Color::WHITE,
            PieceType::KNIGHT,
        );

        $position = $this->position(
            [
                ['e1', $this->king(Color::WHITE)],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($piece, 'b1', 'c3'),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            )
        );
    }

    public function testMoveFromSquareMustMatchActualPieceSquare(): void
    {
        $knight = $this->piece(
            'white-knight',
            Color::WHITE,
            PieceType::KNIGHT,
        );

        $position = $this->position(
            [
                ['b1', $knight],
                ['e1', $this->king(Color::WHITE)],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($knight, 'a1', 'c3'),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            )
        );
    }

    public function testCannotCaptureOwnPiece(): void
    {
        $knight = $this->piece(
            'white-knight',
            Color::WHITE,
            PieceType::KNIGHT,
        );

        $ownPiece = $this->piece(
            'white-pawn',
            Color::WHITE,
            PieceType::PAWN,
        );

        $position = $this->position(
            [
                ['b1', $knight],
                ['c3', $ownPiece],
                ['e1', $this->king(Color::WHITE)],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($knight, 'b1', 'c3'),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            )
        );
    }

    public function testCaptureMustContainMatchingRemoveChange(): void
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

        $wrongPiece = $this->piece(
            'black-bishop',
            Color::BLACK,
            PieceType::BISHOP,
        );

        $position = $this->position(
            [
                ['b1', $knight],
                ['c3', $enemy],
                ['e1', $this->king(Color::WHITE)],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($knight, 'b1', 'c3'),
            $this->removeChange($wrongPiece),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            )
        );
    }

    public function testKingCannotMoveIntoCheck(): void
    {
        $king = $this->king(Color::WHITE);

        $enemyRook = $this->piece(
            'black-rook',
            Color::BLACK,
            PieceType::ROOK,
        );

        $position = $this->position(
            [
                ['e1', $king],
                ['f8', $enemyRook],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($king, 'e1', 'f2'),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            )
        );
    }

    public function testPinnedPieceCannotExposeKingToCheck(): void
    {
        $king = $this->king(Color::WHITE);

        $rook = $this->piece(
            'white-rook',
            Color::WHITE,
            PieceType::ROOK,
        );

        $enemyRook = $this->piece(
            'black-rook',
            Color::BLACK,
            PieceType::ROOK,
        );

        $position = $this->position(
            [
                ['e1', $king],
                ['e2', $rook],
                ['e8', $enemyRook],
                ['a8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($rook, 'e2', 'f2'),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            )
        );
    }

    public function testValidPromotionToQueen(): void
    {
        $pawn = $this->piece(
            'white-pawn',
            Color::WHITE,
            PieceType::PAWN,
        );

        $queen = $this->piece(
            'white-queen',
            Color::WHITE,
            PieceType::QUEEN,
        );

        $position = $this->position(
            [
                ['e7', $pawn],
                ['e1', $this->king(Color::WHITE)],
                ['a8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($pawn, 'e7', 'e8'),
            new PositionChange(
                PositionChangeType::CHANGE,
                $pawn,
                replacement: $queen,
            ),
        );

        self::assertTrue(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            )
        );
    }

    public function testPromotionToKingIsRejected(): void
    {
        $pawn = $this->piece(
            'white-pawn',
            Color::WHITE,
            PieceType::PAWN,
        );

        $kingReplacement = $this->piece(
            'white-king-replacement',
            Color::WHITE,
            PieceType::KING,
        );

        $position = $this->position(
            [
                ['e7', $pawn],
                ['e1', $this->king(Color::WHITE)],
                ['a8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($pawn, 'e7', 'e8'),
            new PositionChange(
                PositionChangeType::CHANGE,
                $pawn,
                replacement: $kingReplacement,
            ),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            )
        );
    }

    public function testPawnReachingPromotionRankMustBePromoted(): void
    {
        $pawn = $this->piece(
            'white-pawn',
            Color::WHITE,
            PieceType::PAWN,
        );

        $position = $this->position(
            [
                ['e7', $pawn],
                ['e1', $this->king(Color::WHITE)],
                ['a8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($pawn, 'e7', 'e8'),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            )
        );
    }

    public function testBlackPawnPromotesOnRankOne(): void
    {
        $pawn = $this->piece(
            'black-pawn',
            Color::BLACK,
            PieceType::PAWN,
        );

        $queen = $this->piece(
            'black-queen',
            Color::BLACK,
            PieceType::QUEEN,
        );

        $position = $this->position(
            [
                ['e2', $pawn],
                ['e8', $this->king(Color::BLACK)],
                ['a1', $this->king(Color::WHITE)],
            ],
        );

        $history = new DecisionHistory();

        $whiteMove = $this->move(
            $this->moveChange(
                $this->piece(
                    'white-pawn',
                    Color::WHITE,
                    PieceType::PAWN,
                ),
                'a2',
                'a3',
            ),
        );

        /*
         * The black pawn needs Black's turn.
         */
        $history->add(
            Color::WHITE,
            new Decision(DecisionType::MOVE, $whiteMove),
        );

        $move = $this->move(
            $this->moveChange($pawn, 'e2', 'e1'),
            new PositionChange(
                PositionChangeType::CHANGE,
                $pawn,
                replacement: $queen,
            ),
        );

        self::assertTrue(
            $this->validator->validate($position, $history, $move)
        );
    }

public function testValidEnPassant(): void
{
    $whitePawn = $this->piece(
        'white-pawn-e5',
        Color::WHITE,
        PieceType::PAWN,
    );

    $whiteHistoryPawn = $this->piece(
        'white-pawn-a2',
        Color::WHITE,
        PieceType::PAWN,
    );

    $blackPawn = $this->piece(
        'black-pawn-d7',
        Color::BLACK,
        PieceType::PAWN,
    );

    $position = $this->position(
        [
            ['e5', $whitePawn],
            ['a2', $whiteHistoryPawn],
            ['d5', $blackPawn],
            ['e1', $this->king(Color::WHITE)],
            ['e8', $this->king(Color::BLACK)],
        ],
    );

    $history = new DecisionHistory();

    $whitePreviousMove = $this->move(
        $this->moveChange(
            $whiteHistoryPawn,
            'a2',
            'a3',
        ),
    );

    $history->add(
        Color::WHITE,
        new Decision(
            DecisionType::MOVE,
            $whitePreviousMove,
        ),
    );

    $blackDoubleMove = $this->move(
        $this->moveChange(
            $blackPawn,
            'd7',
            'd5',
        ),
    );

    $history->add(
        Color::BLACK,
        new Decision(
            DecisionType::MOVE,
            $blackDoubleMove,
        ),
    );

    $move = $this->move(
        $this->moveChange(
            $whitePawn,
            'e5',
            'd6',
        ),
        $this->removeChange($blackPawn),
    );

    self::assertTrue(
        $this->validator->validate(
            $position,
            $history,
            $move,
        ),
    );
}

    public function testEnPassantRequiresPreviousDoublePawnMove(): void
    {
        $whitePawn = $this->piece(
            'white-pawn-e5',
            Color::WHITE,
            PieceType::PAWN,
        );

        $blackPawn = $this->piece(
            'black-pawn-d5',
            Color::BLACK,
            PieceType::PAWN,
        );

        $position = $this->position(
            [
                ['e5', $whitePawn],
                ['d5', $blackPawn],
                ['e1', $this->king(Color::WHITE)],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($whitePawn, 'e5', 'd6'),
            $this->removeChange($blackPawn),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            )
        );
    }

    public function testValidKingsideCastling(): void
    {
        $king = $this->king(Color::WHITE);

        $rook = $this->piece(
            'white-rook-h1',
            Color::WHITE,
            PieceType::ROOK,
        );

        $position = $this->position(
            [
                ['e1', $king],
                ['h1', $rook],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($king, 'e1', 'g1'),
            $this->moveChange($rook, 'h1', 'f1'),
        );

        self::assertTrue(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            )
        );
    }

    public function testValidQueensideCastling(): void
    {
        $king = $this->king(Color::WHITE);

        $rook = $this->piece(
            'white-rook-a1',
            Color::WHITE,
            PieceType::ROOK,
        );

        $position = $this->position(
            [
                ['e1', $king],
                ['a1', $rook],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($king, 'e1', 'c1'),
            $this->moveChange($rook, 'a1', 'd1'),
        );

        self::assertTrue(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            )
        );
    }

    public function testCastlingIsRejectedAfterKingHasMoved(): void
    {
        $king = $this->king(Color::WHITE);

        $rook = $this->piece(
            'white-rook-h1',
            Color::WHITE,
            PieceType::ROOK,
        );

        $position = $this->position(
            [
                ['e1', $king],
                ['h1', $rook],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $history = new DecisionHistory();

        $previousKingMove = $this->move(
            $this->moveChange($king, 'e1', 'f1'),
        );

        $history->add(
            Color::WHITE,
            new Decision(
                DecisionType::MOVE,
                $previousKingMove,
            ),
        );

        $move = $this->move(
            $this->moveChange($king, 'e1', 'g1'),
            $this->moveChange($rook, 'h1', 'f1'),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                $history,
                $move,
            )
        );
    }

    public function testCastlingIsRejectedWhenPathIsBlocked(): void
    {
        $king = $this->king(Color::WHITE);

        $rook = $this->piece(
            'white-rook-h1',
            Color::WHITE,
            PieceType::ROOK,
        );

        $bishop = $this->piece(
            'white-bishop',
            Color::WHITE,
            PieceType::BISHOP,
        );

        $position = $this->position(
            [
                ['e1', $king],
                ['h1', $rook],
                ['f1', $bishop],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($king, 'e1', 'g1'),
            $this->moveChange($rook, 'h1', 'f1'),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            )
        );
    }

    public function testCastlingIsRejectedThroughAttackedSquare(): void
    {
        $king = $this->king(Color::WHITE);

        $rook = $this->piece(
            'white-rook-h1',
            Color::WHITE,
            PieceType::ROOK,
        );

        $enemyRook = $this->piece(
            'black-rook',
            Color::BLACK,
            PieceType::ROOK,
        );

        $position = $this->position(
            [
                ['e1', $king],
                ['h1', $rook],
                ['f8', $enemyRook],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($king, 'e1', 'g1'),
            $this->moveChange($rook, 'h1', 'f1'),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            )
        );
    }

    public function testTwoMoveChangesWithExtraRemoveAreRejected(): void
    {
        $king = $this->king(Color::WHITE);

        $rook = $this->piece(
            'white-rook-h1',
            Color::WHITE,
            PieceType::ROOK,
        );

        $piece = $this->piece(
            'black-pawn',
            Color::BLACK,
            PieceType::PAWN,
        );

        $position = $this->position(
            [
                ['e1', $king],
                ['h1', $rook],
                ['e8', $this->king(Color::BLACK)],
                ['a7', $piece],
            ],
        );

        $move = $this->move(
            $this->moveChange($king, 'e1', 'g1'),
            $this->moveChange($rook, 'h1', 'f1'),
            $this->removeChange($piece),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            )
        );
    }

    public function testMoreThanTwoMoveChangesAreRejected(): void
    {
        $king = $this->king(Color::WHITE);

        $rook = $this->piece(
            'white-rook-h1',
            Color::WHITE,
            PieceType::ROOK,
        );

        $bishop = $this->piece(
            'white-bishop',
            Color::WHITE,
            PieceType::BISHOP,
        );

        $position = $this->position(
            [
                ['e1', $king],
                ['h1', $rook],
                ['c1', $bishop],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($king, 'e1', 'g1'),
            $this->moveChange($rook, 'h1', 'f1'),
            $this->moveChange($bishop, 'c1', 'd2'),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            )
        );
    }

    public function testSecondMoveChangeMustBeCastling(): void
    {
        $king = $this->king(Color::WHITE);

        $knight = $this->piece(
            'white-knight',
            Color::WHITE,
            PieceType::KNIGHT,
        );

        $position = $this->position(
            [
                ['e1', $king],
                ['b1', $knight],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($king, 'e1', 'f1'),
            $this->moveChange($knight, 'b1', 'c3'),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            )
        );
    }

    public function testMoveWithMissingFromSquareIsRejected(): void
    {
        $knight = $this->piece(
            'white-knight',
            Color::WHITE,
            PieceType::KNIGHT,
        );

        $position = $this->position(
            [
                ['b1', $knight],
                ['e1', $this->king(Color::WHITE)],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $change = new PositionChange(
            PositionChangeType::MOVE,
            $knight,
            null,
            $this->square('c3'),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $this->move($change),
            )
        );
    }

    public function testMoveWithMissingToSquareIsRejected(): void
    {
        $knight = $this->piece(
            'white-knight',
            Color::WHITE,
            PieceType::KNIGHT,
        );

        $position = $this->position(
            [
                ['b1', $knight],
                ['e1', $this->king(Color::WHITE)],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $change = new PositionChange(
            PositionChangeType::MOVE,
            $knight,
            $this->square('b1'),
            null,
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $this->move($change),
            )
        );
    }

    public function testMoveWithInvalidPromotionChangeIsRejected(): void
    {
        $pawn = $this->piece(
            'white-pawn',
            Color::WHITE,
            PieceType::PAWN,
        );

        $blackPawn = $this->piece(
            'black-pawn',
            Color::BLACK,
            PieceType::PAWN,
        );

        $position = $this->position(
            [
                ['e7', $pawn],
                ['e8', $blackPawn],
                ['e1', $this->king(Color::WHITE)],
                ['a8', $this->king(Color::BLACK)],
            ],
        );

        $replacement = $this->piece(
            'white-king-replacement',
            Color::WHITE,
            PieceType::KING,
        );

        $move = $this->move(
            $this->moveChange($pawn, 'e7', 'e8'),
            $this->removeChange($blackPawn),
            new PositionChange(
                PositionChangeType::CHANGE,
                $pawn,
                replacement: $replacement,
            ),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            )
        );
    }

    public function testChangeWithMoreThanOneReplacementIsRejected(): void
    {
        $pawn = $this->piece(
            'white-pawn',
            Color::WHITE,
            PieceType::PAWN,
        );

        $queen = $this->piece(
            'white-queen',
            Color::WHITE,
            PieceType::QUEEN,
        );

        $rook = $this->piece(
            'white-rook',
            Color::WHITE,
            PieceType::ROOK,
        );

        $position = $this->position(
            [
                ['e7', $pawn],
                ['e1', $this->king(Color::WHITE)],
                ['a8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($pawn, 'e7', 'e8'),
            new PositionChange(
                PositionChangeType::CHANGE,
                $pawn,
                replacement: $queen,
            ),
            new PositionChange(
                PositionChangeType::CHANGE,
                $pawn,
                replacement: $rook,
            ),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            )
        );
    }

    public function testPositionIsNotModifiedByValidation(): void
    {
        $pawn = $this->piece(
            'white-pawn',
            Color::WHITE,
            PieceType::PAWN,
        );

        $position = $this->position(
            [
                ['e2', $pawn],
                ['e1', $this->king(Color::WHITE)],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($pawn, 'e2', 'e4'),
        );

        $this->validator->validate(
            $position,
            new DecisionHistory(),
            $move,
        );

        self::assertSame($pawn, $position->getPieceAt($this->square('e2')));
        self::assertNull($position->getPieceAt($this->square('e4')));
    }

    public function testHistoryIsNotModifiedByValidation(): void
    {
        $history = new DecisionHistory();

        $pawn = $this->piece(
            'white-pawn',
            Color::WHITE,
            PieceType::PAWN,
        );

        $move = $this->move(
            $this->moveChange($pawn, 'e2', 'e4'),
        );

        $history->add(
            Color::WHITE,
            new Decision(DecisionType::MOVE, $move),
        );

        $count = $history->count();

        $position = $this->position(
            [
                ['e2', $pawn],
                ['e1', $this->king(Color::WHITE)],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $candidate = $this->move(
            $this->moveChange($pawn, 'e2', 'e3'),
        );

        $this->validator->validate(
            $position,
            $history,
            $candidate,
        );

        self::assertSame($count, $history->count());
        self::assertSame($move, $history->lastMove());
    }

    public function testQueenCannotMoveThroughPiece(): void
    {
        $queen = $this->piece(
            'white-queen',
            Color::WHITE,
            PieceType::QUEEN,
        );

        $blocker = $this->piece(
            'white-pawn',
            Color::WHITE,
            PieceType::PAWN,
        );

        $position = $this->position(
            [
                ['d1', $queen],
                ['d2', $blocker],
                ['e1', $this->king(Color::WHITE)],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($queen, 'd1', 'd5'),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            )
        );
    }

    public function testKingCaptureIsRejectedWhenCapturedPieceIsRemovedIncorrectly(): void
    {
        $king = $this->king(Color::WHITE);

        $enemy = $this->piece(
            'black-knight',
            Color::BLACK,
            PieceType::KNIGHT,
        );

        $position = $this->position(
            [
                ['e1', $king],
                ['f2', $enemy],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($king, 'e1', 'f2'),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            )
        );
    }


    public function testPawnDoubleMoveIsValidForBlack(): void
    {
        $pawn = $this->piece(
            'black-pawn',
            Color::BLACK,
            PieceType::PAWN,
        );

        $position = $this->position(
            [
                ['e7', $pawn],
                ['e8', $this->king(Color::BLACK)],
                ['e1', $this->king(Color::WHITE)],
            ],
        );

        $history = new DecisionHistory();

        $whiteMove = $this->move(
            $this->moveChange(
                $this->piece(
                    'white-pawn',
                    Color::WHITE,
                    PieceType::PAWN,
                ),
                'a2',
                'a3',
            ),
        );

        $history->add(
            Color::WHITE,
            new Decision(DecisionType::MOVE, $whiteMove),
        );

        $move = $this->move(
            $this->moveChange($pawn, 'e7', 'e5'),
        );

        self::assertTrue(
            $this->validator->validate(
                $position,
                $history,
                $move,
            ),
        );
    }

    public function testEmptyDestinationCannotContainMultipleRemoveChanges(): void
    {
        $knight = $this->piece(
            'white-knight',
            Color::WHITE,
            PieceType::KNIGHT,
        );

        $first = $this->piece(
            'black-pawn-a',
            Color::BLACK,
            PieceType::PAWN,
        );

        $second = $this->piece(
            'black-pawn-b',
            Color::BLACK,
            PieceType::PAWN,
        );

        $position = $this->position(
            [
                ['b1', $knight],
                ['a7', $first],
                ['b7', $second],
                ['e1', $this->king(Color::WHITE)],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($knight, 'b1', 'c3'),
            $this->removeChange($first),
            $this->removeChange($second),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            ),
        );
    }

    public function testNonPawnCannotContainPromotionChange(): void
    {
        $knight = $this->piece(
            'white-knight',
            Color::WHITE,
            PieceType::KNIGHT,
        );

        $queen = $this->piece(
            'white-queen',
            Color::WHITE,
            PieceType::QUEEN,
        );

        $position = $this->position(
            [
                ['b1', $knight],
                ['e1', $this->king(Color::WHITE)],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($knight, 'b1', 'c3'),
            new PositionChange(
                PositionChangeType::CHANGE,
                $knight,
                replacement: $queen,
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

    public function testPromotionChangeAwayFromFinalRankIsRejected(): void
    {
        $pawn = $this->piece(
            'white-pawn',
            Color::WHITE,
            PieceType::PAWN,
        );

        $queen = $this->piece(
            'white-queen',
            Color::WHITE,
            PieceType::QUEEN,
        );

        $position = $this->position(
            [
                ['e6', $pawn],
                ['e1', $this->king(Color::WHITE)],
                ['a8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($pawn, 'e6', 'e7'),
            new PositionChange(
                PositionChangeType::CHANGE,
                $pawn,
                replacement: $queen,
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

    public function testPromotionChangeMustReferToMovingPawn(): void
    {
        $pawn = $this->piece(
            'white-pawn',
            Color::WHITE,
            PieceType::PAWN,
        );

        $otherPawn = $this->piece(
            'other-white-pawn',
            Color::WHITE,
            PieceType::PAWN,
        );

        $queen = $this->piece(
            'white-queen',
            Color::WHITE,
            PieceType::QUEEN,
        );

        $position = $this->position(
            [
                ['e7', $pawn],
                ['a2', $otherPawn],
                ['e1', $this->king(Color::WHITE)],
                ['a8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($pawn, 'e7', 'e8'),
            new PositionChange(
                PositionChangeType::CHANGE,
                $otherPawn,
                replacement: $queen,
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

    public function testPromotionReplacementIsRequired(): void
    {
        $pawn = $this->piece(
            'white-pawn',
            Color::WHITE,
            PieceType::PAWN,
        );

        $position = $this->position(
            [
                ['e7', $pawn],
                ['e1', $this->king(Color::WHITE)],
                ['a8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($pawn, 'e7', 'e8'),
            new PositionChange(
                PositionChangeType::CHANGE,
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

    public function testPromotionReplacementMustHaveSameColor(): void
    {
        $pawn = $this->piece(
            'white-pawn',
            Color::WHITE,
            PieceType::PAWN,
        );

        $blackQueen = $this->piece(
            'black-queen',
            Color::BLACK,
            PieceType::QUEEN,
        );

        $position = $this->position(
            [
                ['e7', $pawn],
                ['e1', $this->king(Color::WHITE)],
                ['a8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($pawn, 'e7', 'e8'),
            new PositionChange(
                PositionChangeType::CHANGE,
                $pawn,
                replacement: $blackQueen,
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

    public function testValidPromotionToRook(): void
    {
        $pawn = $this->piece(
            'white-pawn',
            Color::WHITE,
            PieceType::PAWN,
        );

        $rook = $this->piece(
            'white-rook',
            Color::WHITE,
            PieceType::ROOK,
        );

        $position = $this->position(
            [
                ['e7', $pawn],
                ['e1', $this->king(Color::WHITE)],
                ['a8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($pawn, 'e7', 'e8'),
            new PositionChange(
                PositionChangeType::CHANGE,
                $pawn,
                replacement: $rook,
            ),
        );

        self::assertTrue(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            ),
        );
    }

    public function testValidPromotionToBishop(): void
    {
        $pawn = $this->piece(
            'white-pawn',
            Color::WHITE,
            PieceType::PAWN,
        );

        $bishop = $this->piece(
            'white-bishop',
            Color::WHITE,
            PieceType::BISHOP,
        );

        $position = $this->position(
            [
                ['e7', $pawn],
                ['e1', $this->king(Color::WHITE)],
                ['a8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($pawn, 'e7', 'e8'),
            new PositionChange(
                PositionChangeType::CHANGE,
                $pawn,
                replacement: $bishop,
            ),
        );

        self::assertTrue(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            ),
        );
    }

    public function testValidPromotionToKnight(): void
    {
        $pawn = $this->piece(
            'white-pawn',
            Color::WHITE,
            PieceType::PAWN,
        );

        $knight = $this->piece(
            'white-knight',
            Color::WHITE,
            PieceType::KNIGHT,
        );

        $position = $this->position(
            [
                ['e7', $pawn],
                ['e1', $this->king(Color::WHITE)],
                ['a8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($pawn, 'e7', 'e8'),
            new PositionChange(
                PositionChangeType::CHANGE,
                $pawn,
                replacement: $knight,
            ),
        );

        self::assertTrue(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            ),
        );
    }

    public function testEnPassantRequiresExactlyOnePreviousMoveChange(): void
    {
        $whitePawn = $this->piece(
            'white-pawn-e5',
            Color::WHITE,
            PieceType::PAWN,
        );

        $blackPawn = $this->piece(
            'black-pawn-d5',
            Color::BLACK,
            PieceType::PAWN,
        );

        $blackRook = $this->piece(
            'black-rook-h8',
            Color::BLACK,
            PieceType::ROOK,
        );

        $position = $this->position(
            [
                ['e5', $whitePawn],
                ['d5', $blackPawn],
                ['h8', $blackRook],
                ['e1', $this->king(Color::WHITE)],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $history = new DecisionHistory();

        $whiteMove = $this->move(
            $this->moveChange(
                $this->piece(
                    'white-pawn-a2',
                    Color::WHITE,
                    PieceType::PAWN,
                ),
                'a2',
                'a3',
            ),
        );

        $history->add(
            Color::WHITE,
            new Decision(DecisionType::MOVE, $whiteMove),
        );

        $blackCastlingLikeMove = $this->move(
            $this->moveChange($this->king(Color::BLACK), 'e8', 'g8'),
            $this->moveChange($blackRook, 'h8', 'f8'),
        );

        $history->add(
            Color::BLACK,
            new Decision(
                DecisionType::MOVE,
                $blackCastlingLikeMove,
            ),
        );

        $move = $this->move(
            $this->moveChange($whitePawn, 'e5', 'd6'),
            $this->removeChange($blackPawn),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                $history,
                $move,
            ),
        );
    }

    public function testEnPassantRequiresPreviousMoveToBeByPawn(): void
    {
        $whitePawn = $this->piece(
            'white-pawn-e5',
            Color::WHITE,
            PieceType::PAWN,
        );

        $blackKnight = $this->piece(
            'black-knight-d7',
            Color::BLACK,
            PieceType::KNIGHT,
        );

        $position = $this->position(
            [
                ['e5', $whitePawn],
                ['d5', $blackKnight],
                ['e1', $this->king(Color::WHITE)],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $history = new DecisionHistory();

        $whiteMove = $this->move(
            $this->moveChange(
                $this->piece(
                    'white-pawn-a2',
                    Color::WHITE,
                    PieceType::PAWN,
                ),
                'a2',
                'a3',
            ),
        );

        $history->add(
            Color::WHITE,
            new Decision(DecisionType::MOVE, $whiteMove),
        );

        $blackMove = $this->move(
            $this->moveChange($blackKnight, 'd7', 'd5'),
        );

        $history->add(
            Color::BLACK,
            new Decision(DecisionType::MOVE, $blackMove),
        );

        $move = $this->move(
            $this->moveChange($whitePawn, 'e5', 'd6'),
            $this->removeChange($blackKnight),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                $history,
                $move,
            ),
        );
    }

    public function testEnPassantCannotCaptureSameColorPawn(): void
    {
        $whitePawn = $this->piece(
            'white-pawn-e5',
            Color::WHITE,
            PieceType::PAWN,
        );

        $otherWhitePawn = $this->piece(
            'white-pawn-d7',
            Color::WHITE,
            PieceType::PAWN,
        );

        $position = $this->position(
            [
                ['e5', $whitePawn],
                ['d5', $otherWhitePawn],
                ['e1', $this->king(Color::WHITE)],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $history = new DecisionHistory();

        $whitePreviousMove = $this->move(
            $this->moveChange(
                $this->piece(
                    'white-pawn-a2',
                    Color::WHITE,
                    PieceType::PAWN,
                ),
                'a2',
                'a3',
            ),
        );

        $history->add(
            Color::WHITE,
            new Decision(DecisionType::MOVE, $whitePreviousMove),
        );

        $blackMove = $this->move(
            $this->moveChange($otherWhitePawn, 'd7', 'd5'),
        );

        $history->add(
            Color::BLACK,
            new Decision(DecisionType::MOVE, $blackMove),
        );

        $move = $this->move(
            $this->moveChange($whitePawn, 'e5', 'd6'),
            $this->removeChange($otherWhitePawn),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                $history,
                $move,
            ),
        );
    }

    public function testEnPassantPreviousMoveMustHaveFromAndToSquares(): void
    {
        $whitePawn = $this->piece(
            'white-pawn-e5',
            Color::WHITE,
            PieceType::PAWN,
        );

        $blackPawn = $this->piece(
            'black-pawn-d7',
            Color::BLACK,
            PieceType::PAWN,
        );

        $position = $this->position(
            [
                ['e5', $whitePawn],
                ['d5', $blackPawn],
                ['e1', $this->king(Color::WHITE)],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $history = new DecisionHistory();

        $history->add(
            Color::WHITE,
            new Decision(
                DecisionType::MOVE,
                $this->move(
                    $this->moveChange(
                        $this->piece(
                            'white-pawn-a2',
                            Color::WHITE,
                            PieceType::PAWN,
                        ),
                        'a2',
                        'a3',
                    ),
                ),
            ),
        );

        $history->add(
            Color::BLACK,
            new Decision(
                DecisionType::MOVE,
                $this->move(
                    new PositionChange(
                        PositionChangeType::MOVE,
                        $blackPawn,
                        null,
                        $this->square('d5'),
                    ),
                ),
            ),
        );

        $move = $this->move(
            $this->moveChange($whitePawn, 'e5', 'd6'),
            $this->removeChange($blackPawn),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                $history,
                $move,
            ),
        );
    }

    public function testEnPassantPreviousPawnMustMoveTwoRanks(): void
    {
        $whitePawn = $this->piece(
            'white-pawn-e5',
            Color::WHITE,
            PieceType::PAWN,
        );

        $blackPawn = $this->piece(
            'black-pawn-d5',
            Color::BLACK,
            PieceType::PAWN,
        );

        $position = $this->position(
            [
                ['e5', $whitePawn],
                ['d5', $blackPawn],
                ['e1', $this->king(Color::WHITE)],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $history = new DecisionHistory();

        $history->add(
            Color::WHITE,
            new Decision(
                DecisionType::MOVE,
                $this->move(
                    $this->moveChange(
                        $this->piece(
                            'white-pawn-a2',
                            Color::WHITE,
                            PieceType::PAWN,
                        ),
                        'a2',
                        'a3',
                    ),
                ),
            ),
        );

        $history->add(
            Color::BLACK,
            new Decision(
                DecisionType::MOVE,
                $this->move(
                    $this->moveChange($blackPawn, 'd6', 'd5'),
                ),
            ),
        );

        $move = $this->move(
            $this->moveChange($whitePawn, 'e5', 'd6'),
            $this->removeChange($blackPawn),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                $history,
                $move,
            ),
        );
    }

    public function testEnPassantPreviousPawnMustEndOnCaptureFileAndPawnRank(): void
    {
        $whitePawn = $this->piece(
            'white-pawn-e5',
            Color::WHITE,
            PieceType::PAWN,
        );

        $blackPawn = $this->piece(
            'black-pawn-c7',
            Color::BLACK,
            PieceType::PAWN,
        );

        $position = $this->position(
            [
                ['e5', $whitePawn],
                ['d5', $blackPawn],
                ['e1', $this->king(Color::WHITE)],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $history = new DecisionHistory();

        $history->add(
            Color::WHITE,
            new Decision(
                DecisionType::MOVE,
                $this->move(
                    $this->moveChange(
                        $this->piece(
                            'white-pawn-a2',
                            Color::WHITE,
                            PieceType::PAWN,
                        ),
                        'a2',
                        'a3',
                    ),
                ),
            ),
        );

        $history->add(
            Color::BLACK,
            new Decision(
                DecisionType::MOVE,
                $this->move(
                    $this->moveChange($blackPawn, 'c7', 'c5'),
                ),
            ),
        );

        $move = $this->move(
            $this->moveChange($whitePawn, 'e5', 'd6'),
            $this->removeChange($blackPawn),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                $history,
                $move,
            ),
        );
    }

    public function testEnPassantRemoveChangeMustReferToLastPawn(): void
    {
        $whitePawn = $this->piece(
            'white-pawn-e5',
            Color::WHITE,
            PieceType::PAWN,
        );

        $blackPawn = $this->piece(
            'black-pawn-d7',
            Color::BLACK,
            PieceType::PAWN,
        );

        $wrongPawn = $this->piece(
            'black-pawn-c7',
            Color::BLACK,
            PieceType::PAWN,
        );

        $position = $this->position(
            [
                ['e5', $whitePawn],
                ['d5', $blackPawn],
                ['c5', $wrongPawn],
                ['e1', $this->king(Color::WHITE)],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $history = new DecisionHistory();

        $history->add(
            Color::WHITE,
            new Decision(
                DecisionType::MOVE,
                $this->move(
                    $this->moveChange(
                        $this->piece(
                            'white-pawn-a2',
                            Color::WHITE,
                            PieceType::PAWN,
                        ),
                        'a2',
                        'a3',
                    ),
                ),
            ),
        );

        $history->add(
            Color::BLACK,
            new Decision(
                DecisionType::MOVE,
                $this->move(
                    $this->moveChange($blackPawn, 'd7', 'd5'),
                ),
            ),
        );

        $move = $this->move(
            $this->moveChange($whitePawn, 'e5', 'd6'),
            $this->removeChange($wrongPawn),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                $history,
                $move,
            ),
        );
    }

    public function testEnPassantCapturedPawnMustStillBeOnBoard(): void
    {
        $whitePawn = $this->piece(
            'white-pawn-e5',
            Color::WHITE,
            PieceType::PAWN,
        );

        $blackPawn = $this->piece(
            'black-pawn-d7',
            Color::BLACK,
            PieceType::PAWN,
        );

        $position = $this->position(
            [
                ['e5', $whitePawn],
                ['e1', $this->king(Color::WHITE)],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $history = new DecisionHistory();

        $history->add(
            Color::WHITE,
            new Decision(
                DecisionType::MOVE,
                $this->move(
                    $this->moveChange(
                        $this->piece(
                            'white-pawn-a2',
                            Color::WHITE,
                            PieceType::PAWN,
                        ),
                        'a2',
                        'a3',
                    ),
                ),
            ),
        );

        $history->add(
            Color::BLACK,
            new Decision(
                DecisionType::MOVE,
                $this->move(
                    $this->moveChange($blackPawn, 'd7', 'd5'),
                ),
            ),
        );

        $move = $this->move(
            $this->moveChange($whitePawn, 'e5', 'd6'),
            $this->removeChange($blackPawn),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                $history,
                $move,
            ),
        );
    }

    public function testCastlingRequiresKingAndRookChanges(): void
    {
        $rookOne = $this->piece(
            'white-rook-h1',
            Color::WHITE,
            PieceType::ROOK,
        );

        $rookTwo = $this->piece(
            'white-rook-a1',
            Color::WHITE,
            PieceType::ROOK,
        );

        $position = $this->position(
            [
                ['h1', $rookOne],
                ['a1', $rookTwo],
                ['e1', $this->king(Color::WHITE)],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($rookOne, 'h1', 'f1'),
            $this->moveChange($rookTwo, 'a1', 'd1'),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            ),
        );
    }

    public function testCastlingRequiresCompleteKingAndRookChanges(): void
    {
        $king = $this->king(Color::WHITE);

        $rook = $this->piece(
            'white-rook-h1',
            Color::WHITE,
            PieceType::ROOK,
        );

        $position = $this->position(
            [
                ['e1', $king],
                ['h1', $rook],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            new PositionChange(
                PositionChangeType::MOVE,
                $king,
                null,
                $this->square('g1'),
            ),
            $this->moveChange($rook, 'h1', 'f1'),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            ),
        );
    }

    public function testCastlingRequiresKingAndRookToHaveSameColor(): void
    {
        $king = $this->king(Color::WHITE);

        $rook = $this->piece(
            'black-rook-h8',
            Color::BLACK,
            PieceType::ROOK,
        );

        $position = $this->position(
            [
                ['e1', $king],
                ['h8', $rook],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($king, 'e1', 'g1'),
            $this->moveChange($rook, 'h8', 'f8'),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            ),
        );
    }

    public function testCastlingKingMustStartOnEFile(): void
    {
        $king = $this->king(Color::WHITE);

        $rook = $this->piece(
            'white-rook-h1',
            Color::WHITE,
            PieceType::ROOK,
        );

        $position = $this->position(
            [
                ['d1', $king],
                ['h1', $rook],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($king, 'd1', 'g1'),
            $this->moveChange($rook, 'h1', 'f1'),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            ),
        );
    }

    public function testCastlingKingMustRemainOnOriginalRank(): void
    {
        $king = $this->king(Color::WHITE);

        $rook = $this->piece(
            'white-rook-h1',
            Color::WHITE,
            PieceType::ROOK,
        );

        $position = $this->position(
            [
                ['e1', $king],
                ['h1', $rook],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($king, 'e1', 'g2'),
            $this->moveChange($rook, 'h1', 'f1'),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            ),
        );
    }

    public function testCastlingKingMustMoveToCOrGFile(): void
    {
        $king = $this->king(Color::WHITE);

        $rook = $this->piece(
            'white-rook-h1',
            Color::WHITE,
            PieceType::ROOK,
        );

        $position = $this->position(
            [
                ['e1', $king],
                ['h1', $rook],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($king, 'e1', 'f1'),
            $this->moveChange($rook, 'h1', 'f1'),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            ),
        );
    }

    public function testCastlingRookMustUseCorrectSquares(): void
    {
        $king = $this->king(Color::WHITE);

        $rook = $this->piece(
            'white-rook-h1',
            Color::WHITE,
            PieceType::ROOK,
        );

        $position = $this->position(
            [
                ['e1', $king],
                ['h1', $rook],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($king, 'e1', 'g1'),
            $this->moveChange($rook, 'h1', 'e1'),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            ),
        );
    }

    public function testCastlingIsRejectedAfterRookHasMoved(): void
    {
        $king = $this->king(Color::WHITE);

        $rook = $this->piece(
            'white-rook-h1',
            Color::WHITE,
            PieceType::ROOK,
        );

        $blackPawn = $this->piece(
            'black-pawn',
            Color::BLACK,
            PieceType::PAWN,
        );

        $position = $this->position(
            [
                ['e1', $king],
                ['h1', $rook],
                ['a7', $blackPawn],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $history = new DecisionHistory();

        $whiteRookMove = $this->move(
            $this->moveChange($rook, 'h1', 'h2'),
        );

        $history->add(
            Color::WHITE,
            new Decision(DecisionType::MOVE, $whiteRookMove),
        );

        $blackMove = $this->move(
            $this->moveChange($blackPawn, 'a7', 'a6'),
        );

        $history->add(
            Color::BLACK,
            new Decision(DecisionType::MOVE, $blackMove),
        );

        $move = $this->move(
            $this->moveChange($king, 'e1', 'g1'),
            $this->moveChange($rook, 'h1', 'f1'),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                $history,
                $move,
            ),
        );
    }

    public function testCastlingIsRejectedWhenRookIsNotOnOriginalSquare(): void
    {
        $king = $this->king(Color::WHITE);

        $rook = $this->piece(
            'white-rook-h1',
            Color::WHITE,
            PieceType::ROOK,
        );

        $position = $this->position(
            [
                ['e1', $king],
                ['g1', $rook],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($king, 'e1', 'g1'),
            $this->moveChange($rook, 'h1', 'f1'),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            ),
        );
    }

    public function testCastlingIsRejectedWhenKingIsAlreadyInCheck(): void
    {
        $king = $this->king(Color::WHITE);

        $rook = $this->piece(
            'white-rook-h1',
            Color::WHITE,
            PieceType::ROOK,
        );

        $enemyRook = $this->piece(
            'black-rook-e8',
            Color::BLACK,
            PieceType::ROOK,
        );

        $position = $this->position(
            [
                ['e1', $king],
                ['h1', $rook],
                ['e8', $enemyRook],
                ['a8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($king, 'e1', 'g1'),
            $this->moveChange($rook, 'h1', 'f1'),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            ),
        );
    }

    public function testCastlingIsRejectedWhenDestinationSquareIsAttacked(): void
    {
        $king = $this->king(Color::WHITE);

        $rook = $this->piece(
            'white-rook-h1',
            Color::WHITE,
            PieceType::ROOK,
        );

        $enemyRook = $this->piece(
            'black-rook-g8',
            Color::BLACK,
            PieceType::ROOK,
        );

        $position = $this->position(
            [
                ['e1', $king],
                ['h1', $rook],
                ['g8', $enemyRook],
                ['a8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($king, 'e1', 'g1'),
            $this->moveChange($rook, 'h1', 'f1'),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            ),
        );
    }

    public function testKingInCheckIsDetectedWhenOwnKingIsRemovedByCandidateMove(): void
    {
        $knight = $this->piece(
            'white-knight',
            Color::WHITE,
            PieceType::KNIGHT,
        );

        $king = $this->king(Color::WHITE);

        $position = $this->position(
            [
                ['b1', $knight],
                ['e1', $king],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($knight, 'b1', 'c3'),
            $this->removeChange($king),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            ),
        );
    }

    public function testBlackKnightAttackIsDetected(): void
    {
        $knight = $this->piece(
            'black-knight',
            Color::BLACK,
            PieceType::KNIGHT,
        );

        $rook = $this->piece(
            'white-rook',
            Color::WHITE,
            PieceType::ROOK,
        );

        $position = $this->position(
            [
                ['f3', $knight],
                ['a1', $rook],
                ['e1', $this->king(Color::WHITE)],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($rook, 'a1', 'a2'),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            ),
        );
    }

    public function testBlackPawnAttackIsDetected(): void
    {
        $pawn = $this->piece(
            'black-pawn',
            Color::BLACK,
            PieceType::PAWN,
        );

        $rook = $this->piece(
            'white-rook',
            Color::WHITE,
            PieceType::ROOK,
        );

        $position = $this->position(
            [
                ['d2', $pawn],
                ['a1', $rook],
                ['e1', $this->king(Color::WHITE)],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($rook, 'a1', 'a2'),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            ),
        );
    }

    public function testWhitePawnAttackIsDetected(): void
    {
        $pawn = $this->piece(
            'white-pawn',
            Color::WHITE,
            PieceType::PAWN,
        );

        $rook = $this->piece(
            'black-rook',
            Color::BLACK,
            PieceType::ROOK,
        );

        $position = $this->position(
            [
                ['d7', $pawn],
                ['a8', $rook],
                ['e8', $this->king(Color::BLACK)],
                ['h8', $this->king(Color::WHITE)],
            ],
        );

        $history = new DecisionHistory();

        $whiteMove = $this->move(
            $this->moveChange($pawn, 'd7', 'd6'),
        );

        $history->add(
            Color::WHITE,
            new Decision(DecisionType::MOVE, $whiteMove),
        );

        $move = $this->move(
            $this->moveChange($rook, 'a8', 'a7'),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                $history,
                $move,
            ),
        );
    }

    public function testBlackBishopAttackIsDetected(): void
    {
        $bishop = $this->piece(
            'black-bishop',
            Color::BLACK,
            PieceType::BISHOP,
        );

        $rook = $this->piece(
            'white-rook',
            Color::WHITE,
            PieceType::ROOK,
        );

        $position = $this->position(
            [
                ['b4', $bishop],
                ['a1', $rook],
                ['e1', $this->king(Color::WHITE)],
                ['e8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($rook, 'a1', 'a2'),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            ),
        );
    }

    public function testBlackQueenAttackIsDetected(): void
    {
        $queen = $this->piece(
            'black-queen',
            Color::BLACK,
            PieceType::QUEEN,
        );

        $rook = $this->piece(
            'white-rook',
            Color::WHITE,
            PieceType::ROOK,
        );

        $position = $this->position(
            [
                ['e8', $queen],
                ['a1', $rook],
                ['e1', $this->king(Color::WHITE)],
                ['a8', $this->king(Color::BLACK)],
            ],
        );

        $move = $this->move(
            $this->moveChange($rook, 'a1', 'a2'),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            ),
        );
    }

    public function testBlackKingAttackIsDetected(): void
    {
        $enemyKing = $this->king(Color::BLACK);

        $rook = $this->piece(
            'white-rook',
            Color::WHITE,
            PieceType::ROOK,
        );

        $position = $this->position(
            [
                ['d2', $enemyKing],
                ['a1', $rook],
                ['e1', $this->king(Color::WHITE)],
            ],
        );

        $move = $this->move(
            $this->moveChange($rook, 'a1', 'a2'),
        );

        self::assertFalse(
            $this->validator->validate(
                $position,
                new DecisionHistory(),
                $move,
            ),
        );
    }

    private function position(array $placements): Position
    {
        $pieces = new Pieces();

        foreach ($placements as [$notation, $piece]) {
            $pieces->add($piece);
        }

        $position = new Position($this->board, $pieces);

        foreach ($placements as [$notation, $piece]) {
            $position->place(
                $this->square($notation),
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
            $color === Color::WHITE
                ? 'white-king'
                : 'black-king',
            $color,
            PieceType::KING,
        );
    }

    private function square(string $notation): Square
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

    private function removeChange(Piece $piece): PositionChange
    {
        return new PositionChange(
            PositionChangeType::REMOVE,
            $piece,
        );
    }

    private function move(PositionChange ...$changes): Move
    {
        return new Move(...$changes);
    }


}
