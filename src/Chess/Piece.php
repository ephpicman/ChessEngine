<?php

declare(strict_types=1);

namespace Ephpicman\ChessEngine\Chess;

/**
 * Represents a chess piece.
 *
 * A piece is an individual chess object identified by a unique
 * identifier, a color, and a piece type.
 *
 * The identifier distinguishes one piece from another, even when
 * multiple pieces have the same color and type. This is necessary
 * because a chess game can contain multiple pieces of the same type
 * and color, such as the eight white pawns.
 *
 * A piece is immutable. Its identifier, color, and type cannot be
 * changed after construction.
 *
 * The position of a piece on the chessboard is intentionally not
 * stored by the piece itself. The relationship between a piece and
 * a {@see Square} is managed by the position model.
 *
 * @package   EphpicMan\ChessEngine
 * @author    EphpicMan <sinakuhestani@gmail.com>
 * @since     1.0.0
 * @copyright 2026 Sina Kuhestani
 */
final readonly class Piece
{
    /**
     * The unique identifier of the piece.
     *
     * The identifier represents the identity of this particular piece
     * and remains unchanged throughout its lifetime.
     *
     * @var string
     */
    public string $id;

    /**
     * The color of the piece.
     *
     * @var Color
     */
    public Color $color;

    /**
     * The type of the piece.
     *
     * @var PieceType
     */
    public PieceType $type;

    /**
     * Creates a new chess piece.
     *
     * @param string    $id    The unique identifier of the piece.
     * @param Color     $color The color of the piece.
     * @param PieceType $type  The type of the piece.
     */
    public function __construct(
        string $id,
        Color $color,
        PieceType $type
    ) {
        $this->id = $id;
        $this->color = $color;
        $this->type = $type;
    }
}
