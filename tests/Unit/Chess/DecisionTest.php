<?php

declare(strict_types=1);

namespace Ephpicman\ChessEngine\Tests\Unit\Chess;

use Ephpicman\ChessEngine\Chess\Color;
use Ephpicman\ChessEngine\Chess\Decision;
use Ephpicman\ChessEngine\Chess\DecisionType;
use Ephpicman\ChessEngine\Chess\File;
use Ephpicman\ChessEngine\Chess\Move;
use Ephpicman\ChessEngine\Chess\Piece;
use Ephpicman\ChessEngine\Chess\PieceType;
use Ephpicman\ChessEngine\Chess\PositionChange;
use Ephpicman\ChessEngine\Chess\PositionChangeType;
use Ephpicman\ChessEngine\Chess\Rank;
use Ephpicman\ChessEngine\Chess\Square;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DecisionTest extends TestCase
{
    #[DataProvider('decisionTypesRequiringMoveProvider')]
    public function testDecisionTypesRequiringMoveAcceptMove(
        DecisionType $type,
    ): void {
        $move = $this->createMove();

        $decision = new Decision($type, $move);

        self::assertSame($type, $decision->type);
        self::assertSame($move, $decision->move);
    }

    #[DataProvider('decisionTypesNotRequiringMoveProvider')]
    public function testDecisionTypesNotRequiringMoveAcceptNullMove(
        DecisionType $type,
    ): void {
        $decision = new Decision($type);

        self::assertSame($type, $decision->type);
        self::assertNull($decision->move);
    }

    #[DataProvider('decisionTypesRequiringMoveProvider')]
    public function testDecisionTypesRequiringMoveRejectNullMove(
        DecisionType $type,
    ): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Decision type '{$type->value}' requires a move."
        );

        new Decision($type);
    }

    #[DataProvider('decisionTypesNotRequiringMoveProvider')]
    public function testDecisionTypesNotRequiringMoveRejectMove(
        DecisionType $type,
    ): void {
        $move = $this->createMove();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Decision type '{$type->value}' must not contain a move."
        );

        new Decision($type, $move);
    }

    public function testMoveDecisionKeepsOriginalMoveObject(): void
    {
        $move = $this->createMove();

        $decision = new Decision(
            DecisionType::MOVE,
            $move,
        );

        self::assertSame($move, $decision->move);
    }

    public function testOfferDrawDecisionKeepsOriginalMoveObject(): void
    {
        $move = $this->createMove();

        $decision = new Decision(
            DecisionType::OFFER_DRAW,
            $move,
        );

        self::assertSame($move, $decision->move);
    }

    public function testReadonlyPropertiesCannotBeModified(): void
    {
        $decision = new Decision(DecisionType::RESIGN);

        $this->expectException(\Error::class);

        $decision->type = DecisionType::MOVE;
    }

    public static function decisionTypesRequiringMoveProvider(): array
    {
        return [
            'move' => [DecisionType::MOVE],
            'offer draw' => [DecisionType::OFFER_DRAW],
        ];
    }

    public static function decisionTypesNotRequiringMoveProvider(): array
    {
        return [
            'accept draw' => [DecisionType::ACCEPT_DRAW],
            'resign' => [DecisionType::RESIGN],
        ];
    }

    private function createMove(): Move
    {
        $piece = new Piece(
            'white-pawn-e2',
            Color::WHITE,
            PieceType::PAWN,
        );

        $change = new PositionChange(
            PositionChangeType::MOVE,
            $piece,
            new Square(File::E, Rank::TWO),
            new Square(File::E, Rank::FOUR),
        );

        return new Move($change);
    }
}
