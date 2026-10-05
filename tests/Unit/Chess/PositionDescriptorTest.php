<?php

declare(strict_types=1);

namespace Ephpicman\Chess\Tests\Unit\Chess;

use Ephpicman\ChessEngine\Chess\Board;
use Ephpicman\ChessEngine\Chess\Color;
use Ephpicman\ChessEngine\Chess\InitialPosition;
use Ephpicman\ChessEngine\Chess\Piece;
use Ephpicman\ChessEngine\Chess\PieceType;
use Ephpicman\ChessEngine\Chess\Pieces;
use Ephpicman\ChessEngine\Chess\Position;
use Ephpicman\ChessEngine\Chess\PositionDescriptor;
use PHPUnit\Framework\TestCase;

final class PositionDescriptorTest extends TestCase
{
    public function testInitialPositionMaterialScoreUsesDefinedNormalisation(): void
    {
        $descriptor = new PositionDescriptor(InitialPosition::create());

        // 9 + (2 × 5) + (4 × 3) + 8 = 39; 39 / 103 ≈ 0.3786407767.
        self::assertEqualsWithDelta(
            39 / 103,
            $descriptor->getMaterialScore(Color::WHITE),
            0.000000001,
        );
        self::assertEqualsWithDelta(
            39 / 103,
            $descriptor->getMaterialScore(Color::BLACK),
            0.000000001,
        );
    }

    public function testEmptyPositionHasZeroMaterialScore(): void
    {
        $position = new Position(new Board(), new Pieces());
        $descriptor = new PositionDescriptor($position);

        self::assertSame(0.0, $descriptor->getMaterialScore(Color::WHITE));
        self::assertSame(0.0, $descriptor->getMaterialScore(Color::BLACK));
    }

    public function testDefinedMaximumProducesScoreOfOne(): void
    {
        $position = new Position(new Board(), new Pieces());
        $board = $position->getBoard();

        $pieces = [
            [Color::WHITE, PieceType::QUEEN, 'wq1', 'a1'],
            [Color::WHITE, PieceType::QUEEN, 'wq2', 'b1'],
            [Color::WHITE, PieceType::QUEEN, 'wq3', 'c1'],
            [Color::WHITE, PieceType::QUEEN, 'wq4', 'd1'],
            [Color::WHITE, PieceType::QUEEN, 'wq5', 'e1'],
            [Color::WHITE, PieceType::QUEEN, 'wq6', 'f1'],
            [Color::WHITE, PieceType::QUEEN, 'wq7', 'g1'],
            [Color::WHITE, PieceType::QUEEN, 'wq8', 'h1'],
            [Color::WHITE, PieceType::QUEEN, 'wq9', 'a2'],
            [Color::WHITE, PieceType::ROOK, 'wr1', 'b2'],
            [Color::WHITE, PieceType::ROOK, 'wr2', 'c2'],
            [Color::WHITE, PieceType::BISHOP, 'wb1', 'd2'],
            [Color::WHITE, PieceType::BISHOP, 'wb2', 'e2'],
            [Color::WHITE, PieceType::KNIGHT, 'wn1', 'f2'],
            [Color::WHITE, PieceType::KNIGHT, 'wn2', 'g2'],
            [Color::WHITE, PieceType::PAWN, 'wp1', 'h2'],
            [Color::WHITE, PieceType::PAWN, 'wp2', 'a3'],
            [Color::WHITE, PieceType::PAWN, 'wp3', 'b3'],
            [Color::WHITE, PieceType::PAWN, 'wp4', 'c3'],
            [Color::WHITE, PieceType::PAWN, 'wp5', 'd3'],
            [Color::WHITE, PieceType::PAWN, 'wp6', 'e3'],
            [Color::WHITE, PieceType::PAWN, 'wp7', 'f3'],
            [Color::WHITE, PieceType::PAWN, 'wp8', 'g3'],
        ];

        foreach ($pieces as [$color, $type, $id, $square]) {
            $piece = new Piece($id, $color, $type);
            $position->getPieces()->add($piece);
            $position->place($board->getSquareByNotation($square), $piece);
        }

        $descriptor = new PositionDescriptor($position);

        self::assertSame(1.0, $descriptor->getMaterialScore(Color::WHITE));
        self::assertSame(0.0, $descriptor->getMaterialScore(Color::BLACK));
    }

    public function testConfigurationCanOverrideMaterialParametersPartially(): void
    {
        $position = new Position(new Board(), new Pieces());
        $piece = new Piece('wq1', Color::WHITE, PieceType::QUEEN);
        $position->getPieces()->add($piece);
        $position->place($position->getBoard()->getSquareByNotation('a1'), $piece);

        $descriptor = new PositionDescriptor($position, [
            'material_score' => [
                'queen' => 10,
                'maximum' => 100,
            ],
        ]);

        self::assertSame(0.1, $descriptor->getMaterialScore(Color::WHITE));
        self::assertSame(0.0, $descriptor->getMaterialScore(Color::BLACK));
    }

    public function testDescriptorIsSnapshotOfPositionAtConstructionTime(): void
    {
        $position = new Position(new Board(), new Pieces());
        $piece = new Piece('wq1', Color::WHITE, PieceType::QUEEN);
        $position->getPieces()->add($piece);
        $position->place($position->getBoard()->getSquareByNotation('a1'), $piece);

        $descriptor = new PositionDescriptor($position);
        $initialScore = $descriptor->getMaterialScore(Color::WHITE);

        $secondPiece = new Piece('wq2', Color::WHITE, PieceType::QUEEN);
        $position->getPieces()->add($secondPiece);
        $position->place($position->getBoard()->getSquareByNotation('b1'), $secondPiece);

        self::assertSame($initialScore, $descriptor->getMaterialScore(Color::WHITE));
        self::assertEqualsWithDelta(
            18 / 103,
            new PositionDescriptor($position)->getMaterialScore(Color::WHITE),
            0.000000001,
        );
    }
}
