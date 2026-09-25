<?php

declare(strict_types=1);

namespace Ephpicman\ChessEngine\Chess;

/**
 * Represents a rank in the chess domain.
 *
 * A rank is one of the eight horizontal rows of a chessboard.
 * Ranks are numbered from 1 to 8, with each rank identified by
 * its corresponding integer value.
 *
 * This enum provides a type-safe representation of chess ranks
 * and utilities for converting between a rank and its numeric
 * notation.
 *
 * Each rank has a unique integer backing value corresponding to
 * its position on the chessboard:
 *
 * - {@see self::ONE}   = 1
 * - {@see self::TWO}   = 2
 * - {@see self::THREE} = 3
 * - {@see self::FOUR}  = 4
 * - {@see self::FIVE}  = 5
 * - {@see self::SIX}   = 6
 * - {@see self::SEVEN} = 7
 * - {@see self::EIGHT} = 8
 *
 * @package   EphpicMan\ChessEngine
 * @author    EphpicMan <sinakuhestani@gmail.com>
 * @since     1.0.0
 * @copyright 2026 Sina Kuhestani
 */
enum Rank: int
{
    /**
     * Represents the first rank of the chessboard.
     */
    case ONE = 1;

    /**
     * Represents the second rank of the chessboard.
     */
    case TWO = 2;

    /**
     * Represents the third rank of the chessboard.
     */
    case THREE = 3;

    /**
     * Represents the fourth rank of the chessboard.
     */
    case FOUR = 4;

    /**
     * Represents the fifth rank of the chessboard.
     */
    case FIVE = 5;

    /**
     * Represents the sixth rank of the chessboard.
     */
    case SIX = 6;

    /**
     * Represents the seventh rank of the chessboard.
     */
    case SEVEN = 7;

    /**
     * Represents the eighth rank of the chessboard.
     */
    case EIGHT = 8;

    /**
     * Returns the numeric notation of the rank as a string.
     *
     * The returned value corresponds to the rank's position on
     * the chessboard and is represented using standard chess
     * rank notation.
     *
     * @return string The numeric representation of the rank.
     *
     * @example
     * Rank::ONE->digit();   // '1'
     * Rank::FOUR->digit();  // '4'
     * Rank::EIGHT->digit(); // '8'
     */
    public function digit(): string
    {
        return (string) $this->value;
    }

    /**
     * Creates a rank from its numeric notation.
     *
     * The supplied digit must represent a valid chess rank,
     * ranging from 1 to 8.
     *
     * The input is converted to an integer before it is matched
     * against the available ranks.
     *
     * @param string $digit The numeric representation of the rank.
     *
     * @return self The corresponding chess rank.
     *
     * @throws \ValueError If the supplied digit does not represent
     *                     a valid chess rank.
     *
     * @example
     * Rank::fromDigit('1'); // Rank::ONE
     * Rank::fromDigit('4'); // Rank::FOUR
     * Rank::fromDigit('8'); // Rank::EIGHT
     *
     * @example
     * Rank::fromDigit('9'); // throws \ValueError
     */
    public static function fromDigit(string $digit): self
    {
        $digit = (int) $digit;

        foreach (self::cases() as $rank) {
            if ($rank->value === $digit) {
                return $rank;
            }
        }

        throw new \ValueError("Invalid chess rank: {$digit}");
    }
}
