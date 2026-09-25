<?php

declare(strict_types=1);

namespace Ephpicman\ChessEngine\Chess;

/**
 * Represents an atomic change to a chess position.
 *
 * A position change describes one individual modification to the
 * placement or identity of a chess piece within a {@see Position}.
 *
 * The type of change is represented by {@see PositionChangeType}.
 * The three supported change types are:
 *
 * - MOVE   — moves a piece from one square to another.
 * - REMOVE — removes a piece from the position.
 * - CHANGE — replaces one piece with another piece.
 *
 * A complete chess {@see Move} may consist of one or more position
 * changes.
 *
 * Examples:
 *
 * A normal move:
 *
 *     e2 → e4
 *
 * is represented by one MOVE change.
 *
 * A capture:
 *
 *     White pawn e4 → d5
 *     Black piece d5 → removed
 *
 * is represented by one MOVE change and one REMOVE change.
 *
 * Castling:
 *
 *     King e1 → g1
 *     Rook h1 → f1
 *
 * is represented by two MOVE changes.
 *
 * En passant:
 *
 *     Pawn e5 → d6
 *     Captured pawn d5 → removed
 *
 * is represented by one MOVE change and one REMOVE change.
 *
 * Promotion:
 *
 *     Pawn e7 → e8
 *     Pawn → Queen
 *
 * is represented by one MOVE change and one CHANGE change.
 *
 * The CHANGE operation represents replacement rather than mutation.
 * This is consistent with {@see Piece} being immutable. The original
 * piece remains unchanged and is replaced in the position by the new
 * piece.
 *
 * This class only describes a position change. It does not apply the
 * change to a {@see Position} and does not determine whether the
 * change is legal according to the rules of chess.
 *
 * The class is immutable. Once created, its type and associated
 * pieces and squares cannot be changed.
 *
 * @package   EphpicMan\ChessEngine
 * @author    EphpicMan <sinakuhestani@gmail.com>
 * @since     1.0.0
 * @copyright 2026 Sina Kuhestani
 */
final readonly class PositionChange
{
    /**
     * Creates a new position change.
     *
     * The properties used by a change depend on its type:
     *
     * MOVE:
     *     - piece
     *     - from
     *     - to
     *
     * REMOVE:
     *     - piece
     *
     * CHANGE:
     *     - piece
     *     - replacement
     *
     * Unused properties are represented by null.
     *
     * For CHANGE, {@see $piece} is the original piece and
     * {@see $replacement} is the new piece that replaces it.
     *
     * @param PositionChangeType $type
     *        The type of position change.
     *
     * @param Piece $piece
     *        The piece affected by the change.
     *
     * @param Square|null $from
     *        The square from which the piece moves.
     *
     * @param Square|null $to
     *        The square to which the piece moves.
     *
     * @param Piece|null $replacement
     *        The piece that replaces the original piece when the
     *        change type is CHANGE.
     */
    public function __construct(
        public PositionChangeType $type,
        public Piece $piece,
        public ?Square $from = null,
        public ?Square $to = null,
        public ?Piece $replacement = null,
    ) {
    }
}
