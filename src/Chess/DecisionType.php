<?php

declare(strict_types=1);

namespace Ephpicman\ChessEngine\Chess;

/**
 * Represents a type of decision or action made by a player during a
 * chess game.
 *
 * The decision types correspond to player actions defined by the
 * Laws of Chess.
 *
 * The supported actions are:
 *
 * - MOVE
 *   The player makes a chess move.
 *
 * - OFFER_DRAW
 *   The player offers a draw to the opponent after making a move.
 *
 * - ACCEPT_DRAW
 *   The player accepts a valid draw offer from the opponent.
 *
 * - RESIGN
 *   The player resigns the game.
 *
 * A draw rejection is not represented by a separate decision type.
 * Under the Laws of Chess, a player may reject a draw offer by
 * continuing play and making a move. The subsequent MOVE therefore
 * represents the continuation of the game rather than a separate
 * "reject draw" action.
 *
 * The enum represents the type of player action only. The objects
 * required to describe the action, such as a {@see Move}, are
 * represented separately by the domain model.
 *
 * @package   Ephpicman\ChessEngine
 * @author    Ephpicman <sinakuhestani@gmail.com>
 * @since     1.0.0
 * @copyright 2026 Sina Kuhestani
 */
enum DecisionType: string
{
    /**
     * The player makes a chess move.
     */
    case MOVE = 'move';

    /**
     * The player offers a draw after making a move.
     */
    case OFFER_DRAW = 'offer_draw';

    /**
     * The player accepts a valid draw offer.
     */
    case ACCEPT_DRAW = 'accept_draw';

    /**
     * The player resigns the game.
     */
    case RESIGN = 'resign';
}
