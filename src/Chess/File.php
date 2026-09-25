<?php

declare(strict_types=1);

namespace Ephpicman\ChessEngine\Chess;

/**
 * Represents a file in the chess domain.
 *
 * A file is one of the eight vertical columns of a chessboard.
 * Files are identified by the letters a through h in standard
 * chess notation.
 *
 * This enum provides a type-safe representation of chess files
 * and utilities for converting between a file and its standard
 * algebraic notation.
 *
 * Each file has a unique integer backing value corresponding to
 * its zero-based position on the chessboard:
 *
 * - {@see self::A} = 0
 * - {@see self::B} = 1
 * - {@see self::C} = 2
 * - {@see self::D} = 3
 * - {@see self::E} = 4
 * - {@see self::F} = 5
 * - {@see self::G} = 6
 * - {@see self::H} = 7
 *
 * The integer backing values are implementation details used to
 * represent the files internally. The public chess notation of
 * a file is its corresponding letter.
 *
 * @package   EphpicMan\ChessEngine
 * @author    EphpicMan <sinakuhestani@gmail.com>
 * @since     1.0.0
 * @copyright 2026 Sina Kuhestani
 */
enum File: int
{
    /**
     * Represents the a-file of the chessboard.
     */
    case A = 0;

    /**
     * Represents the b-file of the chessboard.
     */
    case B = 1;

    /**
     * Represents the c-file of the chessboard.
     */
    case C = 2;

    /**
     * Represents the d-file of the chessboard.
     */
    case D = 3;

    /**
     * Represents the e-file of the chessboard.
     */
    case E = 4;

    /**
     * Represents the f-file of the chessboard.
     */
    case F = 5;

    /**
     * Represents the g-file of the chessboard.
     */
    case G = 6;

    /**
     * Represents the h-file of the chessboard.
     */
    case H = 7;

    /**
     * Returns the standard chess notation of the file.
     *
     * The returned letter is always lowercase and corresponds
     * to the file's position on the chessboard.
     *
     * @return string The lowercase letter representing the file.
     *
     * @example
     * File::A->letter(); // 'a'
     * File::D->letter(); // 'd'
     * File::H->letter(); // 'h'
     */
    public function letter(): string
    {
        return chr(ord('a') + $this->value);
    }

    /**
     * Creates a file from its standard chess notation.
     *
     * The comparison is case-insensitive, allowing both uppercase
     * and lowercase file letters to be supplied.
     *
     * @param string $letter The letter representing the chess file.
     *
     * @return self The corresponding chess file.
     *
     * @throws \ValueError If the supplied letter does not represent
     *                     a valid chess file.
     *
     * @example
     * File::fromLetter('a'); // File::A
     * File::fromLetter('d'); // File::D
     * File::fromLetter('H'); // File::H
     *
     * @example
     * File::fromLetter('x'); // throws \ValueError
     */
    public static function fromLetter(string $letter): self
    {
        $letter = strtolower($letter);

        foreach (self::cases() as $file) {
            if ($file->letter() === $letter) {
                return $file;
            }
        }

        throw new \ValueError("Invalid chess file: {$letter}");
    }
}
