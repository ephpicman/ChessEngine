<?php

declare(strict_types=1);

namespace Ephpicman\ChessEngine\Chess;

/**
 * Represents the type of change that can be applied to a chess position.
 *
 * A position change describes one atomic modification to the current
 * placement of chess pieces on the board.
 *
 * The three supported types are:
 *
 * - {@see self::MOVE}   — moves a piece from one square to another.
 * - {@see self::REMOVE} — removes a piece from the position.
 * - {@see self::CHANGE} — changes a property of an existing piece.
 *
 * These atomic changes can be combined to represent more complex
 * chess moves.
 *
 * For example, castling consists of two move changes:
 *
 * - King: e1 → g1
 * - Rook: h1 → f1
 *
 * En passant consists of a move and a removal:
 *
 * - Pawn: e5 → d6
 * - Captured pawn: removed
 *
 * Promotion consists of a move and a change:
 *
 * - Pawn: e7 → e8
 * - Piece type: Pawn → Queen
 *
 * The enum describes only the kind of position change. The data
 * required to perform or describe the change is represented by
 * {@see PositionChange}.
 *
 * @package   EphpicMan\ChessEngine
 * @author    EphpicMan <sinakuhestani@gmail.com>
 * @since     1.0.0
 * @copyright 2026 Sina Kuhestani
 */
enum PositionChangeType: string
{
    /**
     * Moves a piece from one square to another.
     */
    case MOVE = 'move';

    /**
     * Removes a piece from the position.
     */
    case REMOVE = 'remove';

    /**
     * Changes a property of an existing piece.
     */
    case CHANGE = 'change';
}
