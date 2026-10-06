<?php

declare(strict_types=1);

namespace Ephpicman\ChessEngine\Chess;

/**
 * Describes a chess position through deterministic numeric features.
 *
 * A descriptor is a snapshot of the supplied position and, where required,
 * the supplied decision history. All configured features are calculated
 * during construction and the resulting values are immutable afterwards.
 *
 * Position-only calculations depend only on the supplied position.
 * Legal-destination calculations additionally depend on DecisionHistory
 * because castling and en-passant legality cannot be determined from piece
 * placement alone.
 *
 * Every descriptor value is represented as a float in the inclusive
 * range [0, 1].
 *
 * @package   Ephpicman\ChessEngine
 * @author    Ephpicman <sinakuhestani@gmail.com>
 * @since     1.0.0
 * @copyright 2026 Sina Kuhestani
 */
final readonly class PositionDescriptor
{
    /**
     * The calculated descriptor values.
     *
     * @var array<string, mixed>
     */
    private array $values;

    /**
     * Creates a position descriptor snapshot.
     *
     * The descriptor uses an empty history by default. Callers analysing a
     * position from an actual game should supply that game's DecisionHistory
     * so history-dependent legal moves such as castling and en-passant are
     * represented correctly.
     *
     * @param Position $position The position to describe.
     * @param array<string, mixed> $config Calculation configuration.
     * @param DecisionHistory|null $history History required for exact legal
     *                                    destination calculation.
     */
    public function __construct(
        Position $position,
        array $config = [],
        ?DecisionHistory $history = null,
    ) {
        $this->values = $this->calculateValues(
            $position,
            $config,
            $history ?? new DecisionHistory(),
        );
    }

    /**
     * Returns the material score for a colour.
     *
     * The default calculation is:
     *
     *     (queens × 9) + (rooks × 5) +
     *     ((bishops + knights) × 3) + pawns
     *     ------------------------------------------------
     *                         103
     *
     * Kings contribute zero and only pieces currently placed on the board
     * are counted.
     *
     * @param Color $color The colour whose material is measured.
     * @return float The material score in the range [0, 1].
     */
    public function getMaterialScore(Color $color): float
    {
        return $this->values['material_score'][$color->value];
    }

    /**
     * Returns the proportion of the maximum possible number of pieces
     * currently present for a colour.
     *
     * The metric is:
     *
     *     piece count / 16
     *
     * A colour can have at most 16 pieces on the board: one king and fifteen
     * non-king pieces. Promotion changes a pawn's type but does not increase
     * the number of pieces, so promotion cannot raise this upper bound.
     *
     * @param Color $color The colour whose piece count is measured.
     * @return float The piece-count score in the range [0, 1].
     */
    public function getPieceCountScore(Color $color): float
    {
        return $this->values['piece_count_score'][$color->value];
    }

    /**
     * Returns the proportion of currently available board squares that are
     * legal destinations for at least one piece of a colour.
     *
     * A colour cannot move to a square already occupied by one of its own
     * pieces. Therefore the theoretical destination-space maximum depends
     * on that colour's current piece count:
     *
     *     64 - own piece count
     *
     * The metric is:
     *
     *     unique legal destination squares / (64 - own piece count)
     *
     * Multiple legal moves ending on the same square count only once.
     * The supplied DecisionHistory is used during construction so that
     * history-dependent legal moves are handled correctly.
     *
     * @param Color $color The colour whose legal destinations are measured.
     * @return float The legal-destination score in the range [0, 1].
     */
    public function getLegalDestinationScore(Color $color): float
    {
        return $this->values['legal_destination_score'][$color->value];
    }

    /**
     * Calculates all currently supported descriptor values.
     *
     * @param Position $position The position to describe.
     * @param array<string, mixed> $config Calculation configuration.
     * @param DecisionHistory $history History used by legal move generation.
     * @return array<string, mixed> The calculated descriptor values.
     */
    private function calculateValues(
        Position $position,
        array $config,
        DecisionHistory $history,
    ): array {
        return [
            'material_score' => $this->calculateMaterialScores($position, $config),
            'piece_count_score' => $this->calculatePieceCountScores($position),
            'legal_destination_score' => $this->calculateLegalDestinationScores(
                $position,
                $history,
            ),
        ];
    }

    /**
     * Calculates the material score for both colours.
     *
     * @param Position $position The position to describe.
     * @param array<string, mixed> $config Calculation configuration.
     * @return array<int, float> Scores indexed by Color backing value.
     * @throws \InvalidArgumentException For invalid material configuration.
     */
    private function calculateMaterialScores(Position $position, array $config): array
    {
        $parameters = $this->getMaterialScoreParameters($config);
        $scores = [
            Color::WHITE->value => 0.0,
            Color::BLACK->value => 0.0,
        ];

        for ($index = 0; $index < 64; $index++) {
            $piece = $position->getPieceAt($position->getBoard()->getSquare($index));

            if ($piece === null) {
                continue;
            }

            $value = match ($piece->type) {
                PieceType::QUEEN => $parameters['queen'],
                PieceType::ROOK => $parameters['rook'],
                PieceType::BISHOP,
                PieceType::KNIGHT => $parameters['minor_piece'],
                PieceType::PAWN => $parameters['pawn'],
                PieceType::KING => 0.0,
            };

            $scores[$piece->color->value] += $value;
        }

        return [
            Color::WHITE->value => $this->normaliseScore(
                $scores[Color::WHITE->value],
                $parameters['maximum'],
            ),
            Color::BLACK->value => $this->normaliseScore(
                $scores[Color::BLACK->value],
                $parameters['maximum'],
            ),
        ];
    }

    /**
     * Calculates piece-count scores for both colours.
     *
     * The maximum of 16 is a structural chess constraint: each colour has
     * one king and fifteen non-king pieces. Promotions replace pawns and do
     * not create additional pieces.
     *
     * @param Position $position The position to inspect.
     * @return array<int, float> Scores indexed by Color backing value.
     */
    private function calculatePieceCountScores(Position $position): array
    {
        $counts = [
            Color::WHITE->value => 0,
            Color::BLACK->value => 0,
        ];

        for ($index = 0; $index < 64; $index++) {
            $piece = $position->getPieceAt($position->getBoard()->getSquare($index));

            if ($piece !== null) {
                $counts[$piece->color->value]++;
            }
        }

        return [
            Color::WHITE->value => $counts[Color::WHITE->value] / 16.0,
            Color::BLACK->value => $counts[Color::BLACK->value] / 16.0,
        ];
    }

    /**
     * Calculates unique legal destination-square scores for both colours.
     *
     * @param Position $position The position to inspect.
     * @param DecisionHistory $history History required for exact legality.
     * @return array<int, float> Scores indexed by Color backing value.
     */
    private function calculateLegalDestinationScores(
        Position $position,
        DecisionHistory $history,
    ): array {
        $generator = new LegalMoveGenerator();
        $scores = [];

        foreach ([Color::WHITE, Color::BLACK] as $color) {
            $destinations = [];

            foreach ($generator->generate($position, $color, $history) as $move) {
                foreach ($move->changes as $change) {
                    if (
                        $change->type === PositionChangeType::MOVE
                        && $change->to !== null
                    ) {
                        $destinations[$change->to->notation()] = true;
                    }
                }
            }

            $pieceCount = $this->countPieces($position, $color);
            $maximumDestinations = 64 - $pieceCount;

            $scores[$color->value] = $maximumDestinations > 0
                ? count($destinations) / $maximumDestinations
                : 0.0;
        }

        return $scores;
    }

    /**
     * Counts the pieces currently belonging to a colour.
     *
     * @param Position $position The position to inspect.
     * @param Color $color The colour to count.
     * @return int The current piece count.
     */
    private function countPieces(Position $position, Color $color): int
    {
        $count = 0;

        for ($index = 0; $index < 64; $index++) {
            $piece = $position->getPieceAt($position->getBoard()->getSquare($index));

            if ($piece !== null && $piece->color === $color) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Returns material-score parameters with defaults applied.
     *
     * @param array<string, mixed> $config Calculation configuration.
     * @return array{queen: float, rook: float, minor_piece: float, pawn: float, maximum: float}
     * @throws \InvalidArgumentException For invalid material configuration.
     */
    private function getMaterialScoreParameters(array $config): array
    {
        $configured = $config['material_score'] ?? [];

        if (!is_array($configured)) {
            throw new \InvalidArgumentException(
                'The material_score configuration must be an array.'
            );
        }

        $parameters = [
            'queen' => 9.0,
            'rook' => 5.0,
            'minor_piece' => 3.0,
            'pawn' => 1.0,
            'maximum' => 103.0,
        ];

        foreach ($parameters as $name => $default) {
            if (!array_key_exists($name, $configured)) {
                continue;
            }

            $value = $configured[$name];

            if (!is_int($value) && !is_float($value)) {
                throw new \InvalidArgumentException(
                    "The material_score parameter '{$name}' must be numeric."
                );
            }

            if (!is_finite((float) $value) || $value < 0) {
                throw new \InvalidArgumentException(
                    "The material_score parameter '{$name}' must be finite and non-negative."
                );
            }

            $parameters[$name] = (float) $value;
        }

        if ($parameters['maximum'] <= 0.0) {
            throw new \InvalidArgumentException(
                'The material_score maximum must be greater than zero.'
            );
        }

        return $parameters;
    }

    /**
     * Normalises a raw descriptor value to the required [0, 1] range.
     *
     * @param float $value The raw value.
     * @param float $maximum The normalisation maximum.
     * @return float The normalised value in the range [0, 1].
     */
    private function normaliseScore(float $value, float $maximum): float
    {
        return min(1.0, max(0.0, $value / $maximum));
    }
}
