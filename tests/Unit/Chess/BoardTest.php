<?php

declare(strict_types=1);

namespace Ephpicman\ChessEngine\Tests\Unit\Chess;

use Ephpicman\ChessEngine\Chess\Board;
use Ephpicman\ChessEngine\Chess\File;
use Ephpicman\ChessEngine\Chess\Rank;
use Ephpicman\ChessEngine\Chess\Square;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BoardTest extends TestCase
{
    private Board $board;

    protected function setUp(): void
    {
        $this->board = new Board();
    }

    public function testBoardContainsExactly64Squares(): void
    {
        $squares = $this->board->getSquaresFromIndices(
            range(0, 63)
        );

        self::assertCount(64, $squares);
    }

    #[DataProvider('squareIndexProvider')]
    public function testGetSquareReturnsExpectedSquare(
        int $index,
        File $expectedFile,
        Rank $expectedRank,
    ): void {
        $square = $this->board->getSquare($index);

        self::assertSame($expectedFile, $square->file);
        self::assertSame($expectedRank, $square->rank);
        self::assertSame($index, $square->getIndex());
    }

    #[DataProvider('invalidIndexProvider')]
    public function testGetSquareThrowsOutOfBoundsExceptionForInvalidIndex(
        int $index,
    ): void {
        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage(
            "Square index {$index} is out of bounds."
        );

        $this->board->getSquare($index);
    }

    public function testGetSquareReturnsSameInstanceForRepeatedRequests(): void
    {
        $first = $this->board->getSquare(28);
        $second = $this->board->getSquare(28);

        self::assertSame($first, $second);
    }

    #[DataProvider('notationProvider')]
    public function testGetSquareByNotationReturnsExpectedSquare(
        string $notation,
        int $expectedIndex,
        File $expectedFile,
        Rank $expectedRank,
    ): void {
        $square = $this->board->getSquareByNotation($notation);

        self::assertSame($expectedIndex, $square->getIndex());
        self::assertSame($expectedFile, $square->file);
        self::assertSame($expectedRank, $square->rank);
    }

    public function testGetSquareByNotationAcceptsUppercaseFile(): void
    {
        $square = $this->board->getSquareByNotation('E4');

        self::assertSame(28, $square->getIndex());
        self::assertSame(File::E, $square->file);
        self::assertSame(Rank::FOUR, $square->rank);
    }

    public function testGetSquareByNotationReturnsSameInstanceAsGetSquare(): void
    {
        $byIndex = $this->board->getSquare(28);
        $byNotation = $this->board->getSquareByNotation('e4');

        self::assertSame($byIndex, $byNotation);
    }

    public function testGetSquareByNotationPropagatesInvalidLengthException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Notation must be exactly 2 characters (e.g., 'e4')"
        );

        $this->board->getSquareByNotation('e44');
    }

    public function testGetSquareByNotationPropagatesInvalidFileValueError(): void
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('Invalid chess file: x');

        $this->board->getSquareByNotation('x4');
    }

    public function testGetSquareByNotationPropagatesInvalidRankValueError(): void
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('Invalid chess rank: 9');

        $this->board->getSquareByNotation('e9');
    }

    public function testGetSquaresFromIndicesReturnsSquaresInGivenOrder(): void
    {
        $squares = $this->board->getSquaresFromIndices([
            28,
            0,
            63,
            7,
        ]);

        self::assertSame(
            [
                'e4',
                'a1',
                'h8',
                'h1',
            ],
            array_map(
                static fn (Square $square): string => $square->notation(),
                $squares
            )
        );
    }

    public function testGetSquaresFromIndicesReturnsEmptyArrayForEmptyInput(): void
    {
        self::assertSame(
            [],
            $this->board->getSquaresFromIndices([])
        );
    }

    public function testGetSquaresFromIndicesReturnsSameBoardInstances(): void
    {
        $squares = $this->board->getSquaresFromIndices([
            0,
            28,
            63,
        ]);

        self::assertSame(
            $this->board->getSquare(0),
            $squares[0]
        );

        self::assertSame(
            $this->board->getSquare(28),
            $squares[1]
        );

        self::assertSame(
            $this->board->getSquare(63),
            $squares[2]
        );
    }

    #[DataProvider('invalidIndexProvider')]
    public function testGetSquaresFromIndicesThrowsForInvalidIndex(
        int $invalidIndex,
    ): void {
        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage(
            "Square index {$invalidIndex} is out of bounds."
        );

        $this->board->getSquaresFromIndices([
            0,
            28,
            $invalidIndex,
        ]);
    }

    public function testGetAdjacentSquaresReturnsThreeSquaresFromCorner(): void
    {
        $square = $this->board->getSquareByNotation('a1');

        $adjacent = $this->board->getAdjacentSquares($square);

        self::assertSame(
            [
                'a2',
                'b1',
                'b2',
            ],
            array_map(
                static fn (Square $square): string => $square->notation(),
                $adjacent
            )
        );
    }

    public function testGetAdjacentSquaresReturnsFiveSquaresFromEdge(): void
    {
        $square = $this->board->getSquareByNotation('a4');

        $adjacent = $this->board->getAdjacentSquares($square);

        self::assertSame(
            [
                'a5',
                'a3',
                'b4',
                'b5',
                'b3',
            ],
            array_map(
                static fn (Square $square): string => $square->notation(),
                $adjacent
            )
        );
    }

    public function testGetAdjacentSquaresReturnsEightSquaresFromCenter(): void
    {
        $square = $this->board->getSquareByNotation('e4');

        $adjacent = $this->board->getAdjacentSquares($square);

        self::assertSame(
            [
                'e5',
                'e3',
                'f4',
                'd4',
                'f5',
                'f3',
                'd5',
                'd3'
            ],
            array_map(
                static fn (Square $square): string => $square->notation(),
                $adjacent
            )
        );
    }

    public static function squareIndexProvider(): array
    {
        return [
            'a1' => [0, File::A, Rank::ONE],
            'h1' => [7, File::H, Rank::ONE],
            'a2' => [8, File::A, Rank::TWO],
            'e4' => [28, File::E, Rank::FOUR],
            'h8' => [63, File::H, Rank::EIGHT],
        ];
    }

    public static function invalidIndexProvider(): array
    {
        return [
            'negative index' => [-1],
            'index above maximum' => [64],
        ];
    }

    public static function notationProvider(): array
    {
        return [
            'a1' => ['a1', 0, File::A, Rank::ONE],
            'e4' => ['e4', 28, File::E, Rank::FOUR],
            'h8' => ['h8', 63, File::H, Rank::EIGHT],
        ];
    }
}
