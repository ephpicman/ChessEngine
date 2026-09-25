<?php

declare(strict_types=1);

namespace Ephpicman\ChessEngine\Chess;

/**
 * Represents a standard chessboard.
 *
 * A board is a fixed geometric structure consisting of exactly
 * 64 {@see Square} instances arranged in eight files and eight ranks.
 *
 * The board is immutable. Once constructed, its collection of
 * squares cannot be changed, added to, or removed from.
 *
 * A board represents only the geometry of a chessboard. It does not
 * contain chess pieces and does not represent the current state of
 * a chess game. The relationship between pieces and squares belongs
 * to the position model.
 *
 * Each square is created once during board initialization and is
 * retained by the board. Repeated requests for the same square
 * return the same {@see Square} instance.
 *
 * Squares use the zero-based index defined by {@see Square::getIndex()}:
 *
 * - a1 = 0
 * - b1 = 1
 * - ...
 * - h1 = 7
 * - a2 = 8
 * - ...
 * - h8 = 63
 *
 * The board provides access to squares through:
 *
 * - Their zero-based board index.
 * - Their standard algebraic notation.
 * - Collections of zero-based square indices.
 * - Geometric relationships such as adjacent squares.
 *
 * The board does not implement chess movement rules. It provides
 * the static board structure required by higher-level domain objects.
 *
 * @package   EphpicMan\ChessEngine
 * @author    EphpicMan <sinakuhestani@gmail.com>
 * @since     1.0.0
 * @copyright 2026 Sina Kuhestani
 */
final class Board
{
    /**
     * The number of squares on a standard chessboard.
     */
    private const SQUARE_COUNT = 64;

    /**
     * The immutable collection of squares belonging to the board.
     *
     * The array key is the square's zero-based board index.
     *
     * @var array<int, Square>
     */
    private array $squares;

    /**
     * Creates a standard chessboard.
     *
     * All 64 squares are created during construction and retained
     * by the board for its entire lifetime.
     */
    public function __construct()
    {
        $squares = [];

        for ($index = 0; $index < self::SQUARE_COUNT; $index++) {
            $squares[$index] = Square::fromIndex($index);
        }

        $this->squares = $squares;
    }

    /**
     * Returns a square by its zero-based board index.
     *
     * Valid indices range from 0 to 63.
     *
     * @param int $index The zero-based index of the square.
     *
     * @return Square The square corresponding to the supplied index.
     *
     * @throws \OutOfBoundsException If the index is outside the
     *                               valid board range.
     *
     * @example
     * $board->getSquare(0);  // a1
     * $board->getSquare(28); // e4
     * $board->getSquare(63); // h8
     */
    public function getSquare(int $index): Square
    {
        if (!isset($this->squares[$index])) {
            throw new \OutOfBoundsException(
                "Square index {$index} is out of bounds."
            );
        }

        return $this->squares[$index];
    }

    /**
     * Returns a square by its standard algebraic notation.
     *
     * The notation consists of a file letter followed by a rank
     * digit, for example "e4".
     *
     * File matching is case-insensitive.
     *
     * @param string $notation The algebraic notation of the square.
     *
     * @return Square The square corresponding to the supplied notation.
     *
     * @throws \InvalidArgumentException If the notation does not
     *                                   contain exactly two characters.
     * @throws \ValueError If the file or rank is invalid.
     *
     * @example
     * $board->getSquareByNotation('e4');
     * $board->getSquareByNotation('A1');
     */
    public function getSquareByNotation(string $notation): Square
    {
        $square = Square::fromNotation($notation);

        return $this->getSquare($square->getIndex());
    }

    /**
     * Converts square indices into their corresponding square objects.
     *
     * This method provides a bridge between operations that work with
     * the integer representation of squares and operations that work
     * with {@see Square} objects.
     *
     * The order of the returned squares is preserved from the supplied
     * indices.
     *
     * @param array<int, int> $indices Zero-based square indices.
     *
     * @return array<int, Square> The corresponding square objects.
     *
     * @throws \OutOfBoundsException If any supplied index is invalid.
     *
     * @example
     * $squares = $board->getSquaresFromIndices([0, 1, 2]);
     *
     * // [a1, b1, c1]
     */
    public function getSquaresFromIndices(array $indices): array
    {
        $squares = [];

        foreach ($indices as $index) {
            $squares[] = $this->getSquare($index);
        }

        return $squares;
    }

    /**
     * Returns the squares immediately adjacent to a given square.
     *
     * Adjacent squares are the squares located one step away from
     * the supplied square in any of the eight possible directions:
     *
     * - Up
     * - Down
     * - Left
     * - Right
     * - Up-left
     * - Up-right
     * - Down-left
     * - Down-right
     *
     * The supplied square itself is not included.
     *
     * A square can have between three and eight adjacent squares,
     * depending on its location on the board.
     *
     * @param Square $square The square whose adjacent squares are requested.
     *
     * @return array<int, Square> The adjacent squares.
     *
     * @example
     * $e4 = $board->getSquareByNotation('e4');
     * $adjacent = $board->getAdjacentSquares($e4);
     */
    public function getAdjacentSquares(Square $square): array
    {
        return $this->getSquaresFromIndices(
            $square->getAdjacentIndices()
        );
    }
}
