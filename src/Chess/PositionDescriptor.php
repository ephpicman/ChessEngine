<?php

declare(strict_types=1);

namespace Ephpicman\ChessEngine\Chess;

/**
 * Describes a chess position through deterministic numeric features.
 *
 * A descriptor is a snapshot of the supplied position. All configured
 * features are calculated during construction and the resulting values
 * are immutable afterwards.
 *
 * Descriptor calculations depend only on the supplied position and the
 * supplied configuration. No game history, previous moves, clocks, or
 * other historical state is considered.
 *
 * Every descriptor value is represented as a float in the inclusive
 * range [0, 1].
 *
 * Configuration is optional and supports partial overrides. Missing
 * parameters use their documented defaults so that older consumers can
 * continue using newer versions of this class without knowing about
 * newly introduced parameters.
 *
 * @package   EphpicMan\ChessEngine
 * @author    EphpicMan <sinakuhestani@gmail.com>
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
     * All descriptor calculations are performed during construction.
     * The resulting values do not change if the supplied Position is
     * subsequently modified.
     *
     * Configuration may contain only the parameters that need to be
     * overridden. Missing parameters use their defaults.
     *
     * The current supported configuration is:
     *
     * - material_score.queen: value of each queen; default 9.
     * - material_score.rook: value of each rook; default 5.
     * - material_score.minor_piece: value of each bishop or knight;
     *   default 3.
     * - material_score.pawn: value of each pawn; default 1.
     * - material_score.maximum: normalisation maximum; default 103,
     *   calculated as (9 × 9) + (2 × 5) + (3 × 4).
     *
     * Unknown configuration keys are ignored. This allows new descriptor
     * parameters to be introduced without requiring older consumers to
     * provide them.
     *
     * @param Position $position The position to describe.
     * @param array<string, mixed> $config Calculation configuration.
     *
     * @throws \InvalidArgumentException If a configured material
     *                                   parameter is invalid.
     */
    public function __construct(
        Position $position,
        array $config = [],
    ) {
        $this->values = $this->calculateValues($position, $config);
    }

    /**
     * Returns the material score for a colour.
     *
     * The score is calculated as:
     *
     *     (queens × 9) + (rooks × 5) +
     *     ((bishops + knights) × 3) + pawns
     *     ------------------------------------------------
     *                         103
     *
     * The default denominator 103 is the defined normalisation maximum:
     *
     *     (9 × 9) + (2 × 5) + (3 × 4) = 103
     *
     * Only pieces currently placed on the board are counted. Pieces in
     * the position's collection but not currently placed, such as
     * promotion reserves, are not counted.
     *
     * The returned value is always within the inclusive range [0, 1].
     * A score of 0 means that the colour has no counted material, while
     * a score of 1 represents the configured normalisation maximum.
     *
     * @param Color $color The colour whose material is measured.
     *
     * @return float The material score in the range [0, 1].
     */
    public function getMaterialScore(Color $color): float
    {
        return $this->values['material_score'][$color->value];
    }

    /**
     * Calculates all currently supported descriptor values.
     *
     * Each private calculation method is responsible for one logical
     * descriptor and returns only data that belongs to the immutable
     * descriptor snapshot.
     *
     * @param Position $position The position to describe.
     * @param array<string, mixed> $config Calculation configuration.
     *
     * @return array<string, mixed> The calculated descriptor values.
     */
    private function calculateValues(Position $position, array $config): array
    {
        return [
            'material_score' => $this->calculateMaterialScores($position, $config),
        ];
    }

    /**
     * Calculates the material score for both colours.
     *
     * The calculation counts only pieces that currently occupy a square
     * on the supplied position. It deliberately does not inspect game
     * history or the position's unused piece reserves.
     *
     * @param Position $position The position to inspect.
     * @param array<string, mixed> $config Calculation configuration.
     *
     * @return array<int, float> Scores indexed by Color backing value.
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
     * Returns material-score parameters with defaults applied.
     *
     * Partial configuration is supported. Each missing parameter falls
     * back to the current default, which keeps older configurations valid
     * as new parameters are added over time.
     *
     * @param array<string, mixed> $config Calculation configuration.
     *
     * @return array{queen: float, rook: float, minor_piece: float, pawn: float, maximum: float}
     *     The effective material-score parameters.
     *
     * @throws \InvalidArgumentException If a supplied parameter is not a
     *                                   finite non-negative numeric value,
     *                                   or if the maximum is zero.
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
     * A value above the configured maximum is capped at 1.0. This keeps
     * the descriptor contract intact even when a custom configuration
     * assigns values that allow a position to exceed its normalisation
     * maximum.
     *
     * @param float $value The raw value.
     * @param float $maximum The normalisation maximum.
     *
     * @return float The normalised value in the range [0, 1].
     */
    private function normaliseScore(float $value, float $maximum): float
    {
        return min(1.0, max(0.0, $value / $maximum));
    }
}
