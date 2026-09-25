<?php

declare(strict_types=1);

namespace Ephpicman\ChessEngine\Chess;

/**
 * Represents a type of chess piece.
 *
 * A piece type identifies the kind of chess piece independently
 * of its color and position on the board.
 *
 * The six types of chess pieces are:
 *
 * - {@see self::KING}   = King
 * - {@see self::QUEEN}  = Queen
 * - {@see self::ROOK}   = Rook
 * - {@see self::BISHOP} = Bishop
 * - {@see self::KNIGHT} = Knight
 * - {@see self::PAWN}   = Pawn
 *
 * The enum uses the standard lowercase piece letters commonly
 * used in chess notation:
 *
 * - k = king
 * - q = queen
 * - r = rook
 * - b = bishop
 * - n = knight
 * - p = pawn
 *
 * The backing values represent the identity of the piece types.
 * They are not intended to describe how a piece is displayed in
 * every chess notation format.
 *
 * In particular, the pawn uses {@see self::PAWN} = 'p'. The fact
 * that a pawn is normally represented without a piece letter in
 * Standard Algebraic Notation (SAN) is a notation concern and
 * should not be part of the piece type's identity.
 *
 * Piece color is represented separately by {@see Color}, while
 * the position of a piece is represented separately by {@see Square}.
 *
 * @package   Ephpicman\ChessEngine
 * @author    EphpicMan <sinakuhestani@gmail.com>
 * @since     1.0.0
 * @copyright 2026 Sina Kuhestani
 */
enum PieceType: string
{
    /**
     * Represents a king.
     */
    case KING = 'k';

    /**
     * Represents a queen.
     */
    case QUEEN = 'q';

    /**
     * Represents a rook.
     */
    case ROOK = 'r';

    /**
     * Represents a bishop.
     */
    case BISHOP = 'b';

    /**
     * Represents a knight.
     */
    case KNIGHT = 'n';

    /**
     * Represents a pawn.
     */
    case PAWN = 'p';
}
