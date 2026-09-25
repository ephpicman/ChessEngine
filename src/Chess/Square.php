<?php

declare(strict_types=1);

namespace Ephpicman\ChessEngine\Chess;

/**
 * Represents a single square on a chessboard.
 *
 * A square is uniquely identified by a {@see File} and a {@see Rank}.
 * For example, file E combined with rank FOUR represents the e4 square.
 *
 * The class provides functionality for:
 *
 * - Converting a square to and from standard algebraic notation.
 * - Converting a square to and from its zero-based board index.
 * - Determining the square's color.
 * - Finding adjacent squares.
 * - Finding squares on the same rank or file.
 * - Finding squares at a specific distance and direction.
 *
 * A square is a positional concept and does not contain or reference
 * a chess piece. The relationship between squares and pieces is managed
 * separately by the board or position model.
 *
 * The file and rank of a square cannot be changed after construction.
 *
 * Board indices use a zero-based representation from 0 to 63:
 *
 * - a1 = 0
 * - b1 = 1
 * - ...
 * - h1 = 7
 * - a2 = 8
 * - ...
 * - h8 = 63
 *
 * @package   EphpicMan\ChessEngine
 * @author    EphpicMan <sinakuhestani@gmail.com>
 * @since     1.0.0
 * @copyright 2026 Sina Kuhestani
 */
final readonly class Square
{
    /**
     * The file on which the square is located.
     *
     * @var File
     */
    public readonly File $file;

    /**
     * The rank on which the square is located.
     *
     * @var Rank
     */
    public readonly Rank $rank;

    /**
     * Creates a new square.
     *
     * @param File $file The file of the square.
     * @param Rank $rank The rank of the square.
     */
    public function __construct(
        File $file,
        Rank $rank
    ) {
        $this->file = $file;
        $this->rank = $rank;
    }

    /**
     * Returns the standard algebraic notation of the square.
     *
     * The notation consists of the file letter followed by the
     * rank digit.
     *
     * @return string The algebraic notation of the square.
     *
     * @example
     * new Square(File::E, Rank::FOUR)->notation(); // 'e4'
     * new Square(File::A, Rank::ONE)->notation();  // 'a1'
     */
    public function notation(): string
    {
        return $this->file->letter() . $this->rank->digit();
    }

    /**
     * Creates a square from its standard algebraic notation.
     *
     * The notation must consist of exactly two characters:
     * a valid file letter followed by a valid rank digit.
     *
     * File matching is case-insensitive.
     *
     * @param string $notation The algebraic notation of the square.
     *
     * @return self A square represented by the supplied notation.
     *
     * @throws \InvalidArgumentException If the notation is not exactly
     *                                   two characters long.
     * @throws \ValueError If the file or rank is invalid.
     *
     * @example
     * Square::fromNotation('e4'); // Square(File::E, Rank::FOUR)
     * Square::fromNotation('A1'); // Square(File::A, Rank::ONE)
     *
     * @example
     * Square::fromNotation('e');  // throws \InvalidArgumentException
     * Square::fromNotation('x9'); // throws \ValueError
     */
    public static function fromNotation(string $notation): self
    {
        if (strlen($notation) !== 2) {
            throw new \InvalidArgumentException(
                "Notation must be exactly 2 characters (e.g., 'e4')"
            );
        }

        return new self(
            File::fromLetter($notation[0]),
            Rank::fromDigit($notation[1])
        );
    }

    /**
     * Returns the zero-based index of the square on the board.
     *
     * The board is represented as a one-dimensional array of 64
     * squares, ordered by rank from 1 to 8 and file from a to h.
     *
     * The resulting index is calculated as:
     *
     *     ((rank - 1) * 8) + file
     *
     * Therefore:
     *
     * - a1 = 0
     * - h1 = 7
     * - a2 = 8
     * - h8 = 63
     *
     * @return int The zero-based board index.
     *
     * @example
     * new Square(File::A, Rank::ONE)->getIndex();   // 0
     * new Square(File::E, Rank::FOUR)->getIndex();  // 28
     * new Square(File::H, Rank::EIGHT)->getIndex(); // 63
     */
    public function getIndex(): int
    {
        return (($this->rank->value - 1) * 8) + $this->file->value;
    }

    /**
     * Creates a square from its zero-based board index.
     *
     * Valid indices range from 0 to 63.
     *
     * @param int $index The zero-based board index.
     *
     * @return self The square corresponding to the supplied index.
     *
     * @throws \ValueError If the index is outside the valid range.
     *
     * @example
     * Square::fromIndex(0);  // a1
     * Square::fromIndex(28); // e4
     * Square::fromIndex(63); // h8
     */
    public static function fromIndex(int $index): self
    {
        if ($index < 0 || $index > 63) {
            throw new \ValueError("Invalid chess square index: {$index}");
        }

        $fileValue = $index % 8;
        $rankValue = (int) ($index / 8) + 1;

        return new self(
            File::from($fileValue),
            Rank::from($rankValue)
        );
    }

    /**
     * Returns the color of the square.
     *
     * Chessboard squares alternate between black and white.
     * According to the standard chessboard orientation, a1 is a black square.
     *
     * @return Color The color of the square.
     *
     * @example
     * new Square(File::A, Rank::ONE)->getColor();  // Color::BLACK
     * new Square(File::E, Rank::FOUR)->getColor(); // Color::BLACK
     * new Square(File::A, Rank::TWO)->getColor();  // Color::WHITE
     */
    public function getColor(): Color
    {
        if (($this->file->value + ($this->rank->value - 1)) % 2 === 0) {
            return Color::BLACK;
        }

        return Color::WHITE;
    }

    /**
     * Determines whether this square is white.
     *
     * @return bool True if the square is white; otherwise, false.
     */
    public function isWhite(): bool
    {
        return $this->getColor()->isWhite();
    }

    /**
     * Determines whether this square is black.
     *
     * @return bool True if the square is black; otherwise, false.
     */
    public function isBlack(): bool
    {
        return $this->getColor()->isBlack();
    }

    /**
     * Returns the indices of all squares immediately adjacent
     * to this square.
     *
     * Adjacent squares are the squares that can be reached by a
     * single king move, including horizontal, vertical, and
     * diagonal directions.
     *
     * A square can therefore have between 3 and 8 adjacent squares,
     * depending on its position on the board.
     *
     * @return int[] Zero-based indices of all valid adjacent squares.
     *
     * @example
     * $square->getAdjacentIndices();
     */
    public function getAdjacentIndices(): array
    {
        return $this->getIndicesAtDistance(1);
    }

    /**
     * Returns the indices of all other squares on the same rank.
     *
     * The current square is excluded from the result.
     *
     * @return int[] Zero-based indices of the other seven squares
     *              on the same rank.
     *
     * @example
     * // For e4, returns the indices of a4, b4, c4, d4,
     * // f4, g4 and h4.
     */
    public function getSameRankIndices(): array
    {
        $indices = [];
        $rowOffset = ($this->rank->value - 1) * 8;

        for ($f = 0; $f < 8; $f++) {
            if ($f !== $this->file->value) {
                $indices[] = $rowOffset + $f;
            }
        }

        return $indices;
    }

    /**
     * Returns the indices of all other squares on the same file.
     *
     * The current square is excluded from the result.
     *
     * @return int[] Zero-based indices of the other seven squares
     *              on the same file.
     *
     * @example
     * // For e4, returns the indices of e1, e2, e3,
     * // e5, e6, e7 and e8.
     */
    public function getSameFileIndices(): array
    {
        $indices = [];
        $col = $this->file->value;

        for ($r = 1; $r <= 8; $r++) {
            if ($r !== $this->rank->value) {
                $indices[] = (($r - 1) * 8) + $col;
            }
        }

        return $indices;
    }

    /**
     * Returns the indices of squares reached by moving a specified
     * distance in the supplied directions.
     *
     * A direction is represented by a two-element array:
     *
     *     [file offset, rank offset]
     *
     * For example:
     *
     *     [1, 0]  // one file to the right
     *     [-1, 0] // one file to the left
     *     [0, 1]  // one rank upward
     *     [1, 1]  // one file right and one rank upward
     *
     * The method automatically excludes positions outside the
     * boundaries of the 8x8 chessboard.
     *
     * @param array<array<int>> $directions Array of direction vectors.
     * @param int $distance The number of squares to move in each
     *                      specified direction.
     *
     * @return int[] Zero-based indices of all valid destination squares.
     */
    public function getIndicesByDirection(
        array $directions,
        int $distance
    ): array {
        $indices = [];

        foreach ($directions as $direction) {
            if (!is_array($direction) || count($direction) < 2) {
                continue;
            }

            $df = $direction[0];
            $dr = $direction[1];

            $newFile = $this->file->value + ($df * $distance);
            $newRank = $this->rank->value + ($dr * $distance);

            if (
                $newFile >= 0 &&
                $newFile < 8 &&
                $newRank >= 1 &&
                $newRank <= 8
            ) {
                $indices[] = (($newRank - 1) * 8) + $newFile;
            }
        }

        return $indices;
    }

    /**
     * Returns the indices of squares at a specified distance using
     * the supplied or default directions.
     *
     * By default, all eight directions surrounding a square are used:
     *
     * - Up
     * - Down
     * - Right
     * - Left
     * - Up-right
     * - Down-right
     * - Up-left
     * - Down-left
     *
     * This method is useful for calculating movement positions for
     * pieces whose movement is based on a fixed distance and direction.
     *
     * @param int $distance The number of squares to move.
     * @param array<array<int>> $directions The direction vectors to use.
     *
     * @return int[] Zero-based indices of all valid destination squares.
     *
     * @example
     * // Get all squares immediately surrounding the current square.
     * $square->getIndicesAtDistance(1);
     *
     * @example
     * // Get squares two files to the left or right.
     * $square->getIndicesAtDistance(2, [
     *     [1, 0],
     *     [-1, 0],
     * ]);
     */
    public function getIndicesAtDistance(
        int $distance,
        array $directions = [
            [0, 1],
            [0, -1],
            [1, 0],
            [-1, 0],
            [1, 1],
            [1, -1],
            [-1, 1],
            [-1, -1],
        ]
    ): array {
        return $this->getIndicesByDirection($directions, $distance);
    }
}
