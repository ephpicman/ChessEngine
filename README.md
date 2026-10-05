# Chess Engine

A chess engine and decision system being developed from the ground up.

The long-term objective is to build a genuinely strong chess-playing system, with the eventual ambition of reaching a level capable of competing with very strong established engines.

The project is **not based on a fixed classical engine architecture**. Algorithms and abstractions are introduced only when they demonstrably help the actual objective: making better decisions and ultimately winning more games.

## Project Direction

The project is being developed in stages:

1. Build a correct and reliable chess domain.
2. Establish a clean game and decision architecture.
3. Validate the rules and game flow through automated tests.
4. Build measurable position information that machine decision-making can use.
5. Introduce the first generation of machine decision-making.
6. Progressively improve decision quality through controlled experiments.
7. Benchmark strength and performance and preserve behavioural compatibility between bot generations.

The current codebase has moved beyond the basic rules foundation. The current work is focused on the information and decision-making layers that will support progressively stronger bots.

## Current Implementation

### Chess domain

The project has explicit domain objects for the main chess concepts, including:

- Board
- Position
- Square
- Piece
- Pieces
- Color
- PieceType
- File
- Rank
- Move
- PositionChange

The domain is represented explicitly rather than as a collection of procedural helpers.

### Position and board

The project separates the board from the complete game position.

`InitialPosition` provides the standard starting position. The position model also supports deterministic hypothetical positions, which are important for analysing possible future moves without changing the real game state.

### Pieces

The domain explicitly represents:

- Pawn
- Knight
- Bishop
- Rook
- Queen
- King

Piece ownership is represented through `Color`.

### Moves

Moves are represented as domain objects.

Console notation is only an input representation. Examples:

```text
e2e4
g1f3
e7e8q
```

Promotion supports:

```text
q
r
b
n
```

### Move validation and legal move generation

`MoveValidator` determines whether a requested move is legal.

`LegalMoveGenerator` provides the legal moves available from a position, including the relevant promotion possibilities.

Validation and move generation are separated from:

- input parsing;
- game orchestration;
- move application;
- board rendering;
- bot decision-making.

This keeps the chess rules layer reusable by both human and machine players.

### Move application

`MoveApplier` applies an accepted move to a position.

The distinction is intentional:

```text
Is the move legal?
        ↓
MoveValidator / LegalMoveGenerator

Apply the move
        ↓
MoveApplier
```

The same mechanism can be used to construct hypothetical positions when a bot needs to reason about a possible move.

## Position Descriptor

`PositionDescriptor` is the current foundation for extracting measurable information from a chess position.

Its purpose is deliberately narrow:

> **PositionDescriptor describes what is true about a position. It does not decide what the bot should do.**

A descriptor can be created from either the real current position or a hypothetical position produced while analysing a possible move. The input mechanism is the same in both cases.

### Design principles

`PositionDescriptor` is:

- deterministic;
- independent of game history;
- side-effect free;
- effectively immutable after construction;
- usable for real and hypothetical positions;
- independent of bot strategy.

The constructor receives a `Position` and a configuration array. The configuration array contains **calculation parameters**, not a list of enabled features. Missing parameters use their defined defaults. This allows future descriptor versions to introduce additional parameters without forcing older bots to know about them.

Descriptor values are exposed as decimal floating-point values normalised to `[0,1]`. The endpoints must have meaningful definitions rather than being artificial scaling boundaries.

### Current descriptor feature

The current descriptor provides material scores per colour.

The material score uses configurable values for:

- queen;
- rook;
- minor piece (bishop or knight);
- pawn;
- maximum possible material.

Kings contribute zero to this particular metric, and unplaced promotion reserves are not counted.

The current default material formula is:

```text
(queens × 9)
+ (rooks × 5)
+ ((bishops + knights) × 3)
+ pawns
--------------------------------
              103
```

where `103` is the defined maximum for the metric.

More descriptor features can be added as the decision system requires them. They should remain measurements of position state rather than becoming hidden decision logic.

## Player and Decision Architecture

### Player abstraction

The project contains a `Player` abstraction.

A player produces a `Decision` from the current game state.

The current implementation is:

- `HumanPlayer`

The architecture is intended to support machine players without requiring the game loop to know how a decision was produced.

### Human player

`HumanPlayer` currently provides console interaction.

It:

1. receives the current position;
2. renders the board when a renderer is available;
3. prompts the side to move;
4. reads console input;
5. parses the command;
6. creates the corresponding decision;
7. repeats when input is invalid.

### Decision model

Player actions are represented through the `Decision` abstraction rather than being handled as arbitrary strings inside the game loop.

The associated `DecisionType` classifies actions such as:

- moves;
- resignation;
- draw acceptance;
- draw offers.

### Decision history

`DecisionHistory` stores game decisions and related state, including:

- decisions;
- colours;
- move count;
- last move;
- whether a player has moved;
- turn information.

This provides the game with a central source for history-dependent information. The descriptor itself deliberately does not depend on this history.

## Game Orchestration

`Game` coordinates the game flow:

```text
Player
  ↓
Decision
  ↓
Move validation
  ↓
Move application
  ↓
Position / history update
  ↓
Next player
```

The game continues until a terminal game decision is reached, such as resignation or accepted draw.

The important architectural boundary is that `Game` does not need to know how a player arrived at a decision.

## Console Interface

The console entry point is:

```text
bin/chess.php
```

It creates the initial position, renderer, players, and game, then starts the game.

The console currently exists primarily for:

- exercising the domain;
- manually playing games;
- validating game flow;
- debugging rules;
- testing future players.

The UI is not the core architectural concern.

## Rendering

`ConsoleBoardRenderer` is responsible for presentation only.

It does not own:

- move validation;
- move decisions;
- move application;
- game state.

## Testing

The project contains automated unit and integration tests covering the chess domain, move handling, legal move generation, position analysis, and game flow.

Existing tests are part of the safety boundary for future decision-making work. New engine functionality should not be used as a reason to weaken existing correctness guarantees.

## Current Time-Control Status

Timed games are **not currently implemented**.

The current human-player input is synchronous and can wait indefinitely for console input.

There is currently no:

- chess clock;
- remaining time;
- increment;
- move elapsed-time tracking;
- timeout / flag fall;
- time-based game termination.

Time control is therefore a future game-level capability, not something currently provided by `HumanPlayer`.

## Current Engine Status

The project should not yet be described as a strong chess engine.

The current foundation is:

```text
Chess Domain
      ↓
Position
      ↓
Legal Moves
      ↓
Hypothetical Positions
      ↓
PositionDescriptor
      ↓
Machine Decision Logic
```

The important distinction is between **information** and **decision-making**:

```text
PositionDescriptor
    = measurable facts about a position

Bot logic
    = what to do with those facts
```

`PositionDescriptor` does not filter moves, choose moves, predict opponents, or contain a bot personality. Those are decision-system concerns.

## Decision-System Direction

The intended bot architecture is based on **local and iterative optimisation** rather than attempting to construct a globally optimal plan from the current position to the end of the game.

The core idea can be described as a flashlight model:

> You do not need to see the entire road to drive from one destination to another at night. You need enough visibility to choose a good direction, move forward, then analyse the new situation again.

Applied to the engine:

```text
Current Position
       ↓
Local Analysis
       ↓
Limited Scenario Tree
       ↓
Best Local Direction
       ↓
Actual Opponent Move
       ↓
New Position
       ↓
Local Analysis again
       ↓
...
```

The scenario tree is therefore a **bounded set of plausible futures**, not an attempt to enumerate the entire game.

### Bot control versus opponent prediction

The bot controls only its own moves.

Opponent moves can be predicted, but they cannot be controlled. A predicted opponent move is therefore a hypothesis about what may happen, not a commitment to what the opponent will actually do.

If the opponent makes an unexpected move, the bot does not continue blindly along the old scenario. On its next turn it analyses the new real position and builds its decision from the new information.

This gives the decision system the following loop:

```text
Observe reality
      ↓
Build limited hypotheses
      ↓
Filter unsuitable directions
      ↓
Choose the best remaining direction
      ↓
Act
      ↓
Observe the new reality
      ↓
Repeat
```

### Filtering and scenario expansion

The bot should not necessarily fully construct every possible future from every legal move.

Instead, decision logic can use measured position features to discard unsuitable directions and only expand scenarios when there is a reason to do so.

A possible flow is:

```text
LegalMoveGenerator
        ↓
Possible move
        ↓
Hypothetical Position
        ↓
PositionDescriptor
        ↓
Filter / Decision Logic
        ↓
Retain promising direction
        ↓
Expand only as required
```

The exact filtering and expansion strategy is an engineering question to be tested, not a predetermined algorithm.

### Limited horizon

A bot may analyse a limited number of moves into a scenario. If it finds a route that clearly leads toward victory, that information can dominate the decision. If no winning route is established within the available horizon, the bot can choose the strongest remaining direction according to its available information.

Failure to find a win within a limited horizon must **not** be interpreted as proof that no win exists. It only means that the current analysis did not establish one within its available information and depth.

## Bot Generations

The bot should evolve through measurable generations rather than through an assumption that one fixed architecture must remain forever.

The following are development concepts, not a locked implementation roadmap:

### First generation

The first generation should establish a complete machine-decision loop using:

- legal move generation;
- hypothetical positions;
- measurable position descriptors;
- filtering of unsuitable directions;
- limited scenario analysis;
- prediction of a limited set of plausible opponent moves;
- local decision-making.

Its primary value is establishing a **measurable baseline** from which stronger generations can be compared.

### Later generations

Future generations may improve different parts of the system, for example:

- better prediction of opponent behaviour;
- learning an opponent's actual behaviour during a game;
- deeper or better-filtered local scenario analysis;
- adaptive decision parameters during a game;
- stronger position features;
- better allocation of computational effort.

These are possibilities rather than commitments. A feature should be introduced because experiments show that it improves the engine's objective.

## Behavioural Compatibility

A core engineering constraint is that improvements to the engine must not silently change the behaviour of existing bots.

If an existing bot uses a particular configuration and the underlying system is upgraded, that bot should continue to behave according to its established rules unless the bot itself is explicitly upgraded or its configuration changes.

This matters for two reasons:

1. **Backward compatibility** — existing bots remain valid implementations.
2. **Meaningful benchmarking** — generations can be compared because the behaviour of the baseline has not silently changed underneath the experiment.

The same principle applies to configuration-driven components such as `PositionDescriptor`: new capabilities should have sensible defaults so that older consumers can continue to operate without being rewritten merely because the engine gained new features.

## Personality

A dedicated `Personality` abstraction is **not currently part of the committed architecture**.

Personality-like behaviour may eventually prove useful for modelling preferences, filtering decisions, predicting an opponent, or adapting decision parameters. However, creating a `Personality` class before its value is demonstrated would add an abstraction without a proven requirement.

If such a concept is introduced later, it must serve the engine objective and preserve the behavioural compatibility of existing bots.

## Engineering Principles

### Winning is the objective

Algorithms, heuristics, parameters, and abstractions are means to an end.

The project should not adopt a technique merely because it is common in chess engines. A technique is valuable when it improves decision quality, correctness, performance, or another measurable property that contributes to the engine objective.

### Correctness before strength

The chess rules layer must remain reliable while decision-making becomes more sophisticated.

### Measurement over intuition

Changes to engine strength should be measured whenever practical.

A useful development cycle is:

```text
Establish baseline
      ↓
Make one controlled change
      ↓
Run benchmark / games
      ↓
Compare results
      ↓
Check correctness and regressions
      ↓
Keep, reject, or revise the change
```

### No human-computation constraint

The engine should not be designed around human calculation limits.

Human-oriented heuristics are a secondary research output. The engine itself should use algorithms justified by strength, correctness, and measured performance.

### No premature architecture

The project should not commit to a search algorithm, evaluation architecture, personality model, or other major abstraction merely because it is conventional.

Architecture should follow demonstrated engine requirements.

## Upcoming Updates

The immediate development direction is:

## **1. Timed Games**

Add proper chess time controls as a game-level capability.

The intended model should support:

- initial clock time;
- remaining time for each player;
- move start time;
- move elapsed time;
- increment;
- timeout / flag fall;
- game termination on time.

For example:

```text
10+5
```

means:

```text
10 minutes initial time
+
5 seconds increment after each move
```

The timing infrastructure should work for both human and machine players. Later, the bot can use the available time as an input to its decision process.

## **2. First Generation Bot**

Introduce the **first generation of the chess bot** using the decision-system principles described above.

The initial bot is not intended to be the final engine. It is a measurable baseline for future generations.

The initial target is approximately:

```text
Current Position
       ↓
Legal Moves
       ↓
Local / Limited Analysis
       ↓
PositionDescriptor
       ↓
Filtering
       ↓
Limited Scenario Expansion
       ↓
Opponent Move Prediction
       ↓
Local Decision
       ↓
Decision
       ↓
Game
```

The exact algorithms inside this pipeline will be determined through implementation and measurement rather than assumed in advance.

# Research and Benchmarking

Engine development should be treated as an experimental process.

For significant changes, the project should record:

```text
What changed?
Why was it changed?
What hypothesis does it test?
What baseline is being compared?
How was it measured?
What happened to playing strength?
What happened to performance?
Did anything regress?
Did existing bot behaviour remain compatible?
```

Useful experiments may include:

- bot-vs-bot matches;
- fixed-position decision tests;
- scenario-selection benchmarks;
- descriptor performance measurements;
- regression suites;
- controlled comparisons between bot generations.

The goal is not to optimise numbers in isolation. The goal is to identify changes that produce better decisions and stronger play.

# Current Milestone

The project currently provides:

- explicit chess domain modelling;
- board and position representation;
- piece modelling;
- move representation;
- move validation;
- legal move generation;
- move application;
- player abstraction;
- human console player;
- decision model;
- decision history;
- game orchestration;
- console rendering;
- automated tests;
- integration-level game-flow validation;
- `PositionDescriptor` for measurable position features.

The two immediate milestones are:

1. **Timed Games**
2. **First Generation Bot**

These move the project from a validated chess-game implementation toward a real chess decision system.

# Long-Term Direction

```text
Correct Chess Domain
        ↓
Stable Game Architecture
        ↓
Legal Move Generation
        ↓
Position Description
        ↓
Timed Game Infrastructure
        ↓
First Generation Bot
        ↓
Measured Bot Generations
        ↓
Opponent Modelling
        ↓
Adaptive Decision-Making
        ↓
Stronger Local Scenario Analysis
        ↓
Automated Strength Testing
        ↓
Progressive Strength Improvements
        ↓
High-Strength Chess Decision System
```

This roadmap is intentionally open to change. The engine should evolve according to measured results and actual requirements rather than being forced into a predetermined classical architecture.

The ultimate goal is a strong, testable, measurable chess decision system.
