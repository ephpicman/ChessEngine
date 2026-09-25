<?php

declare(strict_types=1);

namespace Ephpicman\ChessEngine\Tests\Unit\Chess;

use Ephpicman\ChessEngine\Chess\Color;
use Ephpicman\ChessEngine\Chess\File;
use Ephpicman\ChessEngine\Chess\Rank;
use Ephpicman\ChessEngine\Chess\Square;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SquareTest extends TestCase
{
    public function testConstructorStoresFileAndRank(): void
    {
        $square = new Square(File::E, Rank::FOUR);

        self::assertSame(File::E, $square->file);
        self::assertSame(Rank::FOUR, $square->rank);
    }

    public function testNotationReturnsAlgebraicNotation(): void
    {
        $square = new Square(File::E, Rank::FOUR);

        self::assertSame('e4', $square->notation());
    }

    #[DataProvider('notationProvider')]
    public function testFromNotationReturnsExpectedSquare(
        string $notation,
        File $expectedFile,
        Rank $expectedRank,
    ): void {
        $square = Square::fromNotation($notation);

        self::assertSame($expectedFile, $square->file);
        self::assertSame($expectedRank, $square->rank);
        self::assertSame($notation, strtolower($square->notation()));
    }

    public function testFromNotationAcceptsUppercaseFile(): void
    {
        $square = Square::fromNotation('A1');

        self::assertSame(File::A, $square->file);
        self::assertSame(Rank::ONE, $square->rank);
        self::assertSame('a1', $square->notation());
    }

    #[DataProvider('invalidNotationLengthProvider')]
    public function testFromNotationThrowsInvalidArgumentExceptionForInvalidLength(
        string $notation,
    ): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Notation must be exactly 2 characters (e.g., 'e4')"
        );

        Square::fromNotation($notation);
    }

    public function testFromNotationPropagatesInvalidFileValueError(): void
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('Invalid chess file: x');

        Square::fromNotation('x4');
    }

    public function testFromNotationPropagatesInvalidRankValueError(): void
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('Invalid chess rank: 9');

        Square::fromNotation('e9');
    }

    #[DataProvider('indexProvider')]
    public function testGetIndexReturnsExpectedBoardIndex(
        File $file,
        Rank $rank,
        int $expectedIndex,
    ): void {
        $square = new Square($file, $rank);

        self::assertSame($expectedIndex, $square->getIndex());
    }

    #[DataProvider('indexProvider')]
    public function testFromIndexReturnsExpectedSquare(
        File $expectedFile,
        Rank $expectedRank,
        int $index,
    ): void {
        $square = Square::fromIndex($index);

        self::assertSame($expectedFile, $square->file);
        self::assertSame($expectedRank, $square->rank);
        self::assertSame($index, $square->getIndex());
    }

    #[DataProvider('invalidIndexProvider')]
    public function testFromIndexThrowsValueErrorForInvalidIndex(
        int $index,
        string $message,
    ): void {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage($message);

        Square::fromIndex($index);
    }

    public function testGetColorReturnsBlackForBlackSquare(): void
    {
        $square = new Square(File::A, Rank::ONE);

        self::assertSame(Color::BLACK, $square->getColor());
    }

    public function testGetColorReturnsWhiteForWhiteSquare(): void
    {
        $square = new Square(File::A, Rank::TWO);

        self::assertSame(Color::WHITE, $square->getColor());
    }

    public function testIsWhiteReturnsTrueForWhiteSquare(): void
    {
        $square = new Square(File::A, Rank::TWO);

        self::assertTrue($square->isWhite());
    }

    public function testIsWhiteReturnsFalseForBlackSquare(): void
    {
        $square = new Square(File::A, Rank::ONE);

        self::assertFalse($square->isWhite());
    }

    public function testIsBlackReturnsTrueForBlackSquare(): void
    {
        $square = new Square(File::A, Rank::ONE);

        self::assertTrue($square->isBlack());
    }

    public function testIsBlackReturnsFalseForWhiteSquare(): void
    {
        $square = new Square(File::A, Rank::TWO);

        self::assertFalse($square->isBlack());
    }

    public function testGetAdjacentIndicesReturnsThreeSquaresFromCorner(): void
    {
        $square = new Square(File::A, Rank::ONE);

        self::assertSame(
            [8, 1, 9],
            $square->getAdjacentIndices()
        );
    }

    public function testGetAdjacentIndicesReturnsFiveSquaresFromEdge(): void
    {
        $square = new Square(File::A, Rank::FOUR);

        self::assertSame(
            [32, 16, 25, 33, 17],
            $square->getAdjacentIndices()
        );
    }

    public function testGetAdjacentIndicesReturnsEightSquaresFromCenter(): void
    {
        $square = new Square(File::E, Rank::FOUR);

        self::assertSame(
            [36, 20, 29, 27, 37, 21, 35, 19],
            $square->getAdjacentIndices()
        );
    }

    public function testGetSameRankIndicesReturnsOtherSquaresOnSameRank(): void
    {
        $square = new Square(File::E, Rank::FOUR);

        self::assertSame(
            [24, 25, 26, 27, 29, 30, 31],
            $square->getSameRankIndices()
        );
    }

    public function testGetSameFileIndicesReturnsOtherSquaresOnSameFile(): void
    {
        $square = new Square(File::E, Rank::FOUR);

        self::assertSame(
            [4, 12, 20, 36, 44, 52, 60],
            $square->getSameFileIndices()
        );
    }

    public function testGetIndicesByDirectionReturnsValidDestination(): void
    {
        $square = new Square(File::E, Rank::FOUR);

        self::assertSame(
            [29],
            $square->getIndicesByDirection([
                [1, 0],
            ], 1)
        );
    }

    public function testGetIndicesByDirectionSkipsInvalidDirection(): void
    {
        $square = new Square(File::E, Rank::FOUR);

        self::assertSame(
            [29],
            $square->getIndicesByDirection([
                [1],
                'invalid',
                [1, 0],
            ], 1)
        );
    }

    public function testGetIndicesByDirectionSkipsDestinationOutsideBoard(): void
    {
        $square = new Square(File::A, Rank::ONE);

        self::assertSame(
            [9],
            $square->getIndicesByDirection([
                [-1, 0],
                [0, -1],
                [1, 1],
            ], 1)
        );
    }

    public function testGetIndicesByDirectionSupportsDistance(): void
    {
        $square = new Square(File::E, Rank::FOUR);

        self::assertSame(
            [30, 26],
            $square->getIndicesByDirection([
                [1, 0],
                [-1, 0],
            ], 2)
        );
    }

    public function testGetIndicesAtDistanceUsesDefaultDirections(): void
    {
        $square = new Square(File::E, Rank::FOUR);

        self::assertSame(
            [36, 20, 29, 27, 37, 21, 35, 19],
            $square->getIndicesAtDistance(1)
        );
    }

    public function testGetIndicesAtDistanceAcceptsCustomDirections(): void
    {
        $square = new Square(File::E, Rank::FOUR);

        self::assertSame(
            [30, 26],
            $square->getIndicesAtDistance(
                2,
                [
                    [1, 0],
                    [-1, 0],
                ]
            )
        );
    }

    public static function notationProvider(): array
    {
        return [
            'a1' => ['a1', File::A, Rank::ONE],
            'e4' => ['e4', File::E, Rank::FOUR],
            'h8' => ['h8', File::H, Rank::EIGHT],
        ];
    }

    public static function invalidNotationLengthProvider(): array
    {
        return [
            'empty notation' => [''],
            'one character' => ['e'],
            'three characters' => ['e44'],
            'four characters' => ['abcd'],
        ];
    }

    public static function indexProvider(): array
    {
        return [
            'a1' => [File::A, Rank::ONE, 0],
            'h1' => [File::H, Rank::ONE, 7],
            'a2' => [File::A, Rank::TWO, 8],
            'e4' => [File::E, Rank::FOUR, 28],
            'h8' => [File::H, Rank::EIGHT, 63],
        ];
    }

    public static function invalidIndexProvider(): array
    {
        return [
            'negative index' => [
                -1,
                'Invalid chess square index: -1',
            ],
            'index above maximum' => [
                64,
                'Invalid chess square index: 64',
            ],
        ];
    }
}
