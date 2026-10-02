<?php

declare(strict_types=1);

namespace Ephpicman\ChessEngine\Tests\Integration\Chess;

use Ephpicman\ChessEngine\Chess\Color;
use Ephpicman\ChessEngine\Chess\ConsoleBoardRenderer;
use Ephpicman\ChessEngine\Chess\DecisionType;
use Ephpicman\ChessEngine\Chess\Game;
use Ephpicman\ChessEngine\Chess\HumanPlayer;
use Ephpicman\ChessEngine\Chess\InitialPosition;
use PHPUnit\Framework\TestCase;

final class ConsoleGameFlowTest extends TestCase
{
    public function testHumanPlayersCanDriveGameThroughGameOrchestration(): void
    {
        $position = InitialPosition::create();
        $renderer = new ConsoleBoardRenderer();
        $whiteInputs = ['e2e4 draw', 'g1f3', 'resign'];
        $blackInputs = ['e7e5', 'b8c6'];
        $playerOutput = [];
        $gameOutput = [];

        $white = new HumanPlayer(
            input: static function () use (&$whiteInputs): string {
                return array_shift($whiteInputs);
            },
            output: static function (string $message) use (&$playerOutput): void {
                $playerOutput[] = $message;
            },
            renderer: $renderer,
        );
        $black = new HumanPlayer(
            input: static function () use (&$blackInputs): string {
                return array_shift($blackInputs);
            },
            output: static function (string $message) use (&$playerOutput): void {
                $playerOutput[] = $message;
            },
            renderer: $renderer,
        );

        $game = new Game(
            $position,
            $white,
            $black,
            output: static function (string $message) use (&$gameOutput): void {
                $gameOutput[] = $message;
            },
        );

        $history = $game->play();

        self::assertSame(5, $history->count());
        self::assertSame(4, $history->moveCount());
        self::assertSame(Color::WHITE, $history->turn());
        self::assertSame(DecisionType::OFFER_DRAW, $history->all()[0]['decision']->type);
        self::assertSame(DecisionType::MOVE, $history->all()[1]['decision']->type);
        self::assertSame(DecisionType::MOVE, $history->all()[2]['decision']->type);
        self::assertSame(DecisionType::MOVE, $history->all()[3]['decision']->type);
        self::assertSame(DecisionType::RESIGN, $history->all()[4]['decision']->type);

        self::assertSame(
            'e4',
            $position->getSquareOf(
                $position->getPieceAt($position->getBoard()->getSquareByNotation('e4')),
            )->notation(),
        );
        self::assertSame(
            'f3',
            $position->getSquareOf(
                $position->getPieceAt($position->getBoard()->getSquareByNotation('f3')),
            )->notation(),
        );
        self::assertSame(
            'e5',
            $position->getSquareOf(
                $position->getPieceAt($position->getBoard()->getSquareByNotation('e5')),
            )->notation(),
        );
        self::assertSame(
            'c6',
            $position->getSquareOf(
                $position->getPieceAt($position->getBoard()->getSquareByNotation('c6')),
            )->notation(),
        );

        self::assertTrue(
            count(array_filter(
                $playerOutput,
                static fn (string $message): bool => str_contains($message, 'White to move'),
            )) > 0
        );
        self::assertTrue(
            count(array_filter(
                $playerOutput,
                static fn (string $message): bool => str_contains($message, 'Black to move'),
            )) > 0
        );
        self::assertContains('White played e2e4 and offered a draw.', $gameOutput);
        self::assertContains('Black played e7e5.', $gameOutput);
        self::assertContains('White played g1f3.', $gameOutput);
        self::assertContains('Black played b8c6.', $gameOutput);
        self::assertContains('White resigned.', $gameOutput);
    }
}
