<?php

declare(strict_types=1);

namespace Ephpicman\Chess\Tests\Unit\Chess;

use Ephpicman\ChessEngine\Chess\Color;
use Ephpicman\ChessEngine\Chess\InitialPosition;
use Ephpicman\ChessEngine\Chess\PieceType;
use PHPUnit\Framework\TestCase;

final class InitialPositionTest extends TestCase
{
    public function testCreatesStandardInitialPlacement(): void
    {
        $position = InitialPosition::create();

        self::assertSame(64, $position->getPieces()->count());
        self::assertSame(32, count(array_filter(
            $position->getPieces()->all(),
            fn ($piece) => $position->hasPiece($piece),
        )));

        $board = $position->getBoard();

        self::assertSame(Color::WHITE, $position->getPieceAt($board->getSquareByNotation('e1'))->color);
        self::assertSame(PieceType::KING, $position->getPieceAt($board->getSquareByNotation('e1'))->type);
        self::assertSame(Color::BLACK, $position->getPieceAt($board->getSquareByNotation('e8'))->color);
        self::assertSame(PieceType::KING, $position->getPieceAt($board->getSquareByNotation('e8'))->type);
        self::assertSame(PieceType::PAWN, $position->getPieceAt($board->getSquareByNotation('e2'))->type);
        self::assertSame(PieceType::PAWN, $position->getPieceAt($board->getSquareByNotation('e7'))->type);
    }

    public function testProvidesUnusedPromotionPieces(): void
    {
        $position = InitialPosition::create();

        $unusedWhiteQueens = array_filter(
            $position->getPieces()->all(),
            fn ($piece) =>
                $piece->color === Color::WHITE
                && $piece->type === PieceType::QUEEN
                && !$position->hasPiece($piece),
        );

        self::assertCount(8, $unusedWhiteQueens);
    }
}
