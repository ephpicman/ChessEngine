<?php

declare(strict_types=1);

namespace Ephpicman\Chess\Tests\Unit\Chess;

use Ephpicman\ChessEngine\Chess\Color;
use Ephpicman\ChessEngine\Chess\DecisionHistory;
use Ephpicman\ChessEngine\Chess\InitialPosition;
use Ephpicman\ChessEngine\Chess\LegalMoveGenerator;
use Ephpicman\ChessEngine\Chess\MoveValidator;
use Ephpicman\ChessEngine\Chess\Piece;
use Ephpicman\ChessEngine\Chess\PieceType;
use Ephpicman\ChessEngine\Chess\PositionDescriptor;
use PHPUnit\Framework\TestCase;

final class PositionDescriptorTest extends TestCase
{
    public function testCalculatesMaterialScoreForInitialPosition(): void
    {
        $descriptor = new PositionDescriptor(InitialPosition::create());

        self::assertEqualsWithDelta(39 / 103, $descriptor->getMaterialScore(Color::WHITE), 0.000000001);
        self::assertEqualsWithDelta(39 / 103, $descriptor->getMaterialScore(Color::BLACK), 0.000000001);
    }

    public function testCalculatesZeroMaterialScoreForPositionWithoutMaterial(): void
    {
        $position = InitialPosition::create();

        foreach ($position->getPieces()->all() as $piece) {
            if ($position->hasPiece($piece)) {
                $position->remove($position->getSquareOf($piece));
            }
        }

        $descriptor = new PositionDescriptor($position);

        self::assertSame(0.0, $descriptor->getMaterialScore(Color::WHITE));
        self::assertSame(0.0, $descriptor->getMaterialScore(Color::BLACK));
    }

    public function testCalculatesMaximumMaterialScore(): void
    {
        $position = InitialPosition::create();

        foreach ($position->getPieces()->all() as $piece) {
            if ($position->hasPiece($piece)) {
                $position->remove($position->getSquareOf($piece));
            }
        }

        $pieces = [];

        for ($index = 0; $index < 9; $index++) {
            $pieces[] = new Piece('maximum-q' . $index, Color::WHITE, PieceType::QUEEN);
        }

        for ($index = 0; $index < 2; $index++) {
            $pieces[] = new Piece('maximum-r' . $index, Color::WHITE, PieceType::ROOK);
        }

        for ($index = 0; $index < 2; $index++) {
            $pieces[] = new Piece('maximum-b' . $index, Color::WHITE, PieceType::BISHOP);
            $pieces[] = new Piece('maximum-n' . $index, Color::WHITE, PieceType::KNIGHT);
        }

        foreach ($pieces as $index => $piece) {
            $position->getPieces()->add($piece);
            $position->place($position->getBoard()->getSquare($index), $piece);
        }

        $descriptor = new PositionDescriptor($position);

        self::assertSame(1.0, $descriptor->getMaterialScore(Color::WHITE));
    }

    public function testSupportsPartialMaterialConfiguration(): void
    {
        $position = InitialPosition::create();

        $descriptor = new PositionDescriptor($position, [
            'material_score' => [
                'queen' => 10.0,
            ],
        ]);

        self::assertEqualsWithDelta(40 / 103, $descriptor->getMaterialScore(Color::WHITE), 0.000000001);
    }

    public function testCalculatesPieceCountScoreForInitialPosition(): void
    {
        $descriptor = new PositionDescriptor(InitialPosition::create());

        self::assertSame(1.0, $descriptor->getPieceCountScore(Color::WHITE));
        self::assertSame(1.0, $descriptor->getPieceCountScore(Color::BLACK));
    }

    public function testCalculatesPieceCountScoreUsingSixteenAsTheMaximum(): void
    {
        $position = InitialPosition::create();

        foreach ($position->getPieces()->all() as $piece) {
            if ($position->hasPiece($piece)) {
                $position->remove($position->getSquareOf($piece));
            }
        }

        $pieces = [
            new Piece('count-king', Color::WHITE, PieceType::KING),
        ];

        for ($index = 0; $index < 15; $index++) {
            $pieces[] = new Piece('count-q' . $index, Color::WHITE, PieceType::QUEEN);
        }

        foreach ($pieces as $index => $piece) {
            $position->getPieces()->add($piece);
            $position->place($position->getBoard()->getSquare($index), $piece);
        }

        $descriptor = new PositionDescriptor($position);

        self::assertSame(1.0, $descriptor->getPieceCountScore(Color::WHITE));
    }

    public function testCalculatesUniqueLegalDestinationScoreForInitialPosition(): void
    {
        $position = InitialPosition::create();
        $generator = new LegalMoveGenerator(new MoveValidator());
        self::assertCount(20, $generator->generate($position, Color::WHITE, new DecisionHistory()));

        $descriptor = new PositionDescriptor($position);

        self::assertEqualsWithDelta(16 / 48, $descriptor->getLegalDestinationScore(Color::WHITE), 0.000000001);
        self::assertEqualsWithDelta(16 / 48, $descriptor->getLegalDestinationScore(Color::BLACK), 0.000000001);
    }

    public function testCountsASharedLegalDestinationOnlyOnce(): void
    {
        $position = InitialPosition::create();

        foreach ($position->getPieces()->all() as $piece) {
            if ($position->hasPiece($piece)) {
                $position->remove($position->getSquareOf($piece));
            }
        }

        $pieces = [
            new Piece('shared-white-king', Color::WHITE, PieceType::KING),
            new Piece('shared-white-knight-a', Color::WHITE, PieceType::KNIGHT),
            new Piece('shared-white-knight-b', Color::WHITE, PieceType::KNIGHT),
            new Piece('shared-black-king', Color::BLACK, PieceType::KING),
        ];

        $squares = ['e1', 'b1', 'f1', 'e8'];

        foreach ($pieces as $index => $piece) {
            $position->getPieces()->add($piece);
            $position->place($position->getBoard()->getSquareByNotation($squares[$index]), $piece);
        }

        $descriptor = new PositionDescriptor($position);

        self::assertEqualsWithDelta(9 / 61, $descriptor->getLegalDestinationScore(Color::WHITE), 0.000000001);
    }

    public function testDescriptorKeepsCalculatedValuesAfterPositionChanges(): void
    {
        $position = InitialPosition::create();

        foreach ($position->getPieces()->all() as $piece) {
            if ($position->hasPiece($piece)) {
                $position->remove($position->getSquareOf($piece));
            }
        }

        $board = $position->getBoard();

        $piece = new Piece('snapshot-q1', Color::WHITE, PieceType::QUEEN);
        $position->getPieces()->add($piece);
        $position->place($board->getSquareByNotation('a1'), $piece);

        $descriptor = new PositionDescriptor($position);
        $initialScore = $descriptor->getMaterialScore(Color::WHITE);
        $initialPieceCountScore = $descriptor->getPieceCountScore(Color::WHITE);
        $initialLegalDestinationScore = $descriptor->getLegalDestinationScore(Color::WHITE);

        $secondPiece = new Piece('snapshot-q2', Color::WHITE, PieceType::QUEEN);
        $position->getPieces()->add($secondPiece);
        $position->place($board->getSquareByNotation('b1'), $secondPiece);

        self::assertSame($initialScore, $descriptor->getMaterialScore(Color::WHITE));
        self::assertSame($initialPieceCountScore, $descriptor->getPieceCountScore(Color::WHITE));
        self::assertSame($initialLegalDestinationScore, $descriptor->getLegalDestinationScore(Color::WHITE));
        self::assertEqualsWithDelta(18 / 103, (new PositionDescriptor($position))->getMaterialScore(Color::WHITE), 0.000000001);
        self::assertEqualsWithDelta(2 / 16, (new PositionDescriptor($position))->getPieceCountScore(Color::WHITE), 0.000000001);
    }
}
