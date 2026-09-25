<?php

declare(strict_types=1);

namespace Ephpicman\ChessEngine\Tests\Unit\Chess;

use Ephpicman\ChessEngine\Chess\DecisionType;
use PHPUnit\Framework\TestCase;

final class DecisionTypeTest extends TestCase
{
    public function testMoveValue(): void
    {
        self::assertSame('move', DecisionType::MOVE->value);
    }

    public function testOfferDrawValue(): void
    {
        self::assertSame('offer_draw', DecisionType::OFFER_DRAW->value);
    }

    public function testAcceptDrawValue(): void
    {
        self::assertSame('accept_draw', DecisionType::ACCEPT_DRAW->value);
    }

    public function testResignValue(): void
    {
        self::assertSame('resign', DecisionType::RESIGN->value);
    }

    public function testAllDecisionTypesAreDefined(): void
    {
        self::assertSame(
            [
                DecisionType::MOVE,
                DecisionType::OFFER_DRAW,
                DecisionType::ACCEPT_DRAW,
                DecisionType::RESIGN,
            ],
            DecisionType::cases()
        );
    }
}
