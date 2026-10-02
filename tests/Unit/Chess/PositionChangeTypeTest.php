<?php

declare(strict_types=1);

namespace Ephpicman\ChessEngine\Tests\Unit\Chess;

use Ephpicman\ChessEngine\Chess\PositionChangeType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PositionChangeTypeTest extends TestCase
{
    #[DataProvider('positionChangeTypesProvider')]
    public function testPositionChangeTypeHasExpectedBackingValue(
        PositionChangeType $changeType,
        string $expectedValue,
    ): void {
        self::assertSame($expectedValue, $changeType->value);
    }

    #[DataProvider('positionChangeTypesProvider')]
    public function testPositionChangeTypeCanBeCreatedFromItsBackingValue(
        PositionChangeType $expectedChangeType,
        string $value,
    ): void {
        self::assertSame($expectedChangeType, PositionChangeType::from($value));
    }

    public function testAllPositionChangeTypesAreDefined(): void
    {
        self::assertSame(
            [
                PositionChangeType::MOVE,
                PositionChangeType::REMOVE,
                PositionChangeType::CHANGE,
            ],
            PositionChangeType::cases(),
        );
    }

    public function testInvalidBackingValueThrowsValueError(): void
    {
        $this->expectException(\ValueError::class);

        PositionChangeType::from('invalid');
    }

    public function testTryFromReturnsNullForInvalidBackingValue(): void
    {
        self::assertNull(PositionChangeType::tryFrom('invalid'));
    }

    public static function positionChangeTypesProvider(): array
    {
        return [
            'move' => [PositionChangeType::MOVE, 'move'],
            'remove' => [PositionChangeType::REMOVE, 'remove'],
            'change' => [PositionChangeType::CHANGE, 'change'],
        ];
    }
}
