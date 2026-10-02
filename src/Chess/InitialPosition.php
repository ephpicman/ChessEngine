<?php

declare(strict_types=1);

namespace Ephpicman\ChessEngine\Chess;

/**
 * Creates the standard initial chess position.
 *
 * The piece collection also contains enough reserve pieces to represent
 * every possible promotion without requiring a player to mutate the
 * position while deciding a move.
 */
final class InitialPosition
{
    public static function create(): Position
    {
        $board = new Board();
        $pieces = new Pieces();
        $position = new Position($board, $pieces);

        foreach ([Color::WHITE, Color::BLACK] as $color) {
            self::addInitialPieces($pieces, $position, $color);
            self::addPromotionReserves($pieces, $color);
        }

        return $position;
    }

    private static function addInitialPieces(
        Pieces $pieces,
        Position $position,
        Color $color,
    ): void {
        $prefix = $color === Color::WHITE ? 'w' : 'b';
        $backRank = $color === Color::WHITE ? Rank::ONE : Rank::EIGHT;
        $pawnRank = $color === Color::WHITE ? Rank::TWO : Rank::SEVEN;

        $backRankTypes = [
            PieceType::ROOK,
            PieceType::KNIGHT,
            PieceType::BISHOP,
            PieceType::QUEEN,
            PieceType::KING,
            PieceType::BISHOP,
            PieceType::KNIGHT,
            PieceType::ROOK,
        ];

        foreach ($backRankTypes as $index => $type) {
            $piece = new Piece(
                $prefix . $type->value . ($index + 1),
                $color,
                $type,
            );
            $pieces->add($piece);
            $position->place(
                $boardSquare = $position->getBoard()->getSquare(
                    ($backRank->value - 1) * 8 + $index
                ),
                $piece,
            );
        }

        for ($file = 0; $file < 8; $file++) {
            $piece = new Piece(
                $prefix . 'p' . ($file + 1),
                $color,
                PieceType::PAWN,
            );
            $pieces->add($piece);
            $position->place(
                $position->getBoard()->getSquare(
                    ($pawnRank->value - 1) * 8 + $file
                ),
                $piece,
            );
        }
    }

    private static function addPromotionReserves(
        Pieces $pieces,
        Color $color,
    ): void {
        $prefix = $color === Color::WHITE ? 'w' : 'b';

        foreach ([PieceType::QUEEN, PieceType::ROOK, PieceType::BISHOP, PieceType::KNIGHT] as $type) {
            for ($index = 1; $index <= 8; $index++) {
                $pieces->add(new Piece(
                    $prefix . 'reserve-' . $type->value . $index,
                    $color,
                    $type,
                ));
            }
        }
    }
}
