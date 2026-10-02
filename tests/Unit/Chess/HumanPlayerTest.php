<?php

declare(strict_types=1);

namespace Ephpicman\Chess\Tests\Unit\Chess;

use Ephpicman\ChessEngine\Chess\Color;
use Ephpicman\ChessEngine\Chess\DecisionHistory;
use Ephpicman\ChessEngine\Chess\DecisionType;
use Ephpicman\ChessEngine\Chess\HumanPlayer;
use Ephpicman\ChessEngine\Chess\InitialPosition;
use PHPUnit\Framework\TestCase;

final class HumanPlayerTest extends TestCase
{
    public function testParsesNormalMove(): void
    {
        $inputs = ['e2e4'];
        $player = new HumanPlayer(
            input: static function () use (&$inputs): string {
                return array_shift($inputs);
            },
            output: static function (): void {},
        );

        $decision = $player->decide(new DecisionHistory(), InitialPosition::create());

        self::assertSame(DecisionType::MOVE, $decision->type);
        self::assertNotNull($decision->move);
        self::assertSame('e2', $decision->move->changes[0]->from->notation());
        self::assertSame('e4', $decision->move->changes[0]->to->notation());
    }

    public function testRetriesInvalidInputWithoutReturningAnInvalidDecision(): void
    {
        $inputs = ['invalid', 'e2e4'];
        $messages = [];
        $player = new HumanPlayer(
            input: static function () use (&$inputs): string {
                return array_shift($inputs);
            },
            output: static function (string $message) use (&$messages): void {
                $messages[] = $message;
            },
        );

        $decision = $player->decide(new DecisionHistory(), InitialPosition::create());

        self::assertSame(DecisionType::MOVE, $decision->type);
        self::assertTrue(
            count(array_filter(
                $messages,
                static fn (string $message): bool => str_contains($message, 'Invalid input'),
            )) > 0
        );
    }

    public function testParsesResignation(): void
    {
        $player = new HumanPlayer(
            input: static fn (): string => 'resign',
            output: static function (): void {},
        );

        $decision = $player->decide(new DecisionHistory(), InitialPosition::create());

        self::assertSame(DecisionType::RESIGN, $decision->type);
        self::assertNull($decision->move);
    }

    public function testParsesMoveWithDrawOffer(): void
    {
        $player = new HumanPlayer(
            input: static fn (): string => 'e2e4 draw',
            output: static function (): void {},
        );

        $decision = $player->decide(new DecisionHistory(), InitialPosition::create());

        self::assertSame(Color::WHITE, (new DecisionHistory())->turn());
        self::assertSame(DecisionType::OFFER_DRAW, $decision->type);
        self::assertNotNull($decision->move);
    }
}
