<?php

declare(strict_types=1);

namespace Ephpicman\ChessEngine\Chess;

/**
 * Represents a color in the chess domain.
 *
 * A color identifies one of the two sides of a chess game:
 * white or black. It can be used to represent the color of a
 * player or a chess piece.
 *
 * Each color has a unique integer backing value:
 *
 * - {@see self::WHITE} = 1
 * - {@see self::BLACK} = 0
 *
 * The enum also provides convenience methods for determining a
 * color and obtaining its opposite.
 *
 * @package   EphpicMan\ChessEngine
 * @author    EphpicMan <sinakuhestani@gmail.com>
 * @since     1.0.0
 * @copyright 2026 Sina Kuhestani
 */
enum Color: int
{
    /**
     * Represents the white side of a chess game.
     */
    case WHITE = 1;

    /**
     * Represents the black side of a chess game.
     */
    case BLACK = 0;

    /**
     * Returns the opposite color.
     *
     * The opposite of white is black, and the opposite of black
     * is white.
     *
     * @return self The opposite color of the current color.
     *
     * @example
     * Color::WHITE->opposite(); // Color::BLACK
     * Color::BLACK->opposite(); // Color::WHITE
     */
    public function opposite(): self
    {
        return $this === self::WHITE ? self::BLACK : self::WHITE;
    }

    /**
     * Determines whether the current color is white.
     *
     * @return bool True when the current color is {@see self::WHITE};
     *              otherwise, false.
     *
     * @example
     * Color::WHITE->isWhite(); // true
     * Color::BLACK->isWhite(); // false
     */
    public function isWhite(): bool
    {
        return $this === self::WHITE;
    }

    /**
     * Determines whether the current color is black.
     *
     * @return bool True when the current color is {@see self::BLACK};
     *              otherwise, false.
     *
     * @example
     * Color::BLACK->isBlack(); // true
     * Color::WHITE->isBlack(); // false
     */
    public function isBlack(): bool
    {
        return $this === self::BLACK;
    }
}
