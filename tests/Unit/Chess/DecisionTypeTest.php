<?php

declare(strict_types=1);

namespace Ephpicman\ChessEngine\Tests\Unit\Chess;

use Ephpicman\ChessEngine\Chess\DecisionType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DecisionTypeTest extends TestCase
{
    #[DataProvider('decisionTypesProvider')]
    public function testDecisionTypeHasExpectedBackingValue(
        DecisionType $decisionType,
        string $expectedValue,
    ): void {
        self::assertSame($expectedValue, $decisionType->value);
    }

    #[DataProvider('decisionTypesProvider')]
    public function testDecisionTypeCanBeCreatedFromItsBackingValue(
        DecisionType $expectedDecisionType,
        string $value,
    ): void {
        self::assertSame($expectedDecisionType, DecisionType::from($value));
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
            DecisionType::cases(),
        );
    }

    public function testInvalidBackingValueThrowsValueError(): void
    {
        $this->expectException(\ValueError::class);

        DecisionType::from('invalid');
    }

    public function testTryFromReturnsNullForInvalidBackingValue(): void
    {
        self::assertNull(DecisionType::tryFrom('invalid'));
    }

    public static function decisionTypesProvider(): array
    {
        return [
            'move' => [DecisionType::MOVE, 'move'],
            'offer draw' => [DecisionType::OFFER_DRAW, 'offer_draw'],
            'accept draw' => [DecisionType::ACCEPT_DRAW, 'accept_draw'],
            'resign' => [DecisionType::RESIGN, 'resign'],
        ];
    }
}
