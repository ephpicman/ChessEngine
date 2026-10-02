<?php

declare(strict_types=1);

namespace Ephpicman\Chess\Tests\Unit\Chess;

use Ephpicman\ChessEngine\Chess\Color;
use Ephpicman\ChessEngine\Chess\Decision;
use Ephpicman\ChessEngine\Chess\DecisionHistory;
use Ephpicman\ChessEngine\Chess\DecisionType;
use Ephpicman\ChessEngine\Chess\Game;
use Ephpicman\ChessEngine\Chess\InitialPosition;
use Ephpicman\ChessEngine\Chess\Move;
use Ephpicman\ChessEngine\Chess\Player;
use Ephpicman\ChessEngine\Chess\Position;
use Ephpicman\ChessEngine\Chess\PositionChange;
use Ephpicman\ChessEngine\Chess\PositionChangeType;
use PHPUnit\Framework\TestCase;

final class GameTest extends TestCase
{
    public function testRetriesIllegalMoveAndRecordsOnlyAcceptedDecision(): void
    {
        $position = InitialPosition::create();
        $board = $position->getBoard();
        $pawn = $position->getPieceAt($board->getSquareByNotation('e2'));

        $illegal = new Move(new PositionChange(
            PositionChangeType::MOVE,
            $pawn,
            $board->getSquareByNotation('e2'),
            $board->getSquareByNotation('e5'),
        ));

        $legal = new Move(new PositionChange(
            PositionChangeType::MOVE,
            $pawn,
            $board->getSquareByNotation('e2'),
            $board->getSquareByNotation('e4'),
        ));

        $white = new ScriptedPlayer([
            new Decision(DecisionType::MOVE, $illegal),
            new Decision(DecisionType::MOVE, $legal),
        ]);
        $black = new ScriptedPlayer([
            new Decision(DecisionType::RESIGN),
        ]);
        $messages = [];

        $game = new Game(
            $position,
            $white,
            $black,
            output: static function (string $message) use (&$messages): void {
                $messages[] = $message;
            },
        );

        $history = $game->play();

        self::assertSame(2, $history->count());
        self::assertSame(1, $history->moveCount());
        self::assertSame(Color::BLACK, $history->all()[1]['color']);
        self::assertSame('e4', $position->getSquareOf($pawn)->notation());
        self::assertTrue(
            count(array_filter(
                $messages,
                static fn (string $message): bool => $message === 'Illegal move.',
            )) === 1
        );
    }

    public function testResignationEndsGame(): void
    {
        $position = InitialPosition::create();
        $game = new Game(
            $position,
            new ScriptedPlayer([new Decision(DecisionType::RESIGN)]),
            new ScriptedPlayer([]),
        );

        $history = $game->play();

        self::assertCount(1, $history->all());
        self::assertSame(DecisionType::RESIGN, $history->all()[0]['decision']->type);
        self::assertSame(Color::WHITE, $history->all()[0]['color']);
        self::assertSame(0, $history->moveCount());
    }

    public function testDrawOfferIsAcceptedByOpponent(): void
    {
        $position = InitialPosition::create();
        $board = $position->getBoard();
        $pawn = $position->getPieceAt($board->getSquareByNotation('e2'));
        $move = new Move(new PositionChange(
            PositionChangeType::MOVE,
            $pawn,
            $board->getSquareByNotation('e2'),
            $board->getSquareByNotation('e4'),
        ));

        $game = new Game(
            $position,
            new ScriptedPlayer([new Decision(DecisionType::OFFER_DRAW, $move)]),
            new ScriptedPlayer([new Decision(DecisionType::ACCEPT_DRAW)]),
        );

        $history = $game->play();

        self::assertSame(2, $history->count());
        self::assertSame(1, $history->moveCount());
        self::assertSame(DecisionType::OFFER_DRAW, $history->all()[0]['decision']->type);
        self::assertSame(DecisionType::ACCEPT_DRAW, $history->all()[1]['decision']->type);
        self::assertSame('e4', $position->getSquareOf($pawn)->notation());
    }

    public function testDrawOfferExpiresAfterOpponentMakesAnotherMove(): void
    {
        $position = InitialPosition::create();
        $board = $position->getBoard();
        $whitePawn = $position->getPieceAt($board->getSquareByNotation('e2'));
        $blackPawn = $position->getPieceAt($board->getSquareByNotation('e7'));

        $whiteMove = new Move(new PositionChange(
            PositionChangeType::MOVE,
            $whitePawn,
            $board->getSquareByNotation('e2'),
            $board->getSquareByNotation('e4'),
        ));
        $blackMove = new Move(new PositionChange(
            PositionChangeType::MOVE,
            $blackPawn,
            $board->getSquareByNotation('e7'),
            $board->getSquareByNotation('e5'),
        ));

        $messages = [];
        $game = new Game(
            $position,
            new ScriptedPlayer([
                new Decision(DecisionType::OFFER_DRAW, $whiteMove),
                new Decision(DecisionType::ACCEPT_DRAW),
                new Decision(DecisionType::RESIGN),
            ]),
            new ScriptedPlayer([
                new Decision(DecisionType::MOVE, $blackMove),
            ]),
            output: static function (string $message) use (&$messages): void {
                $messages[] = $message;
            },
        );

        $history = $game->play();

        self::assertSame(3, $history->count());
        self::assertSame(2, $history->moveCount());
        self::assertSame(DecisionType::OFFER_DRAW, $history->all()[0]['decision']->type);
        self::assertSame(DecisionType::MOVE, $history->all()[1]['decision']->type);
        self::assertSame(DecisionType::RESIGN, $history->all()[2]['decision']->type);
        self::assertContains('No draw offer is currently active.', $messages);
    }
}

final class ScriptedPlayer implements Player
{
    /** @var array<int, Decision> */
    private array $decisions;

    /** @param array<int, Decision> $decisions */
    public function __construct(array $decisions)
    {
        $this->decisions = $decisions;
    }

    public function decide(
        DecisionHistory $decisionHistory,
        Position $position,
    ): Decision {
        if ($this->decisions === []) {
            throw new \LogicException('No scripted decision remains.');
        }

        return array_shift($this->decisions);
    }
}
