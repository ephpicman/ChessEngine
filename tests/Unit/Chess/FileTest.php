<?php

declare(strict_types=1);

namespace Ephpicman\ChessEngine\Tests\Unit\Chess;

use Ephpicman\ChessEngine\Chess\File;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FileTest extends TestCase
{
    #[DataProvider('fileProvider')]
    public function testFileHasExpectedBackingValue(
        File $file,
        int $expectedValue,
    ): void {
        self::assertSame($expectedValue, $file->value);
    }

    #[DataProvider('fileProvider')]
    public function testLetterReturnsExpectedChessNotation(
        File $file,
        int $expectedValue,
        string $expectedLetter,
    ): void {
        self::assertSame($expectedLetter, $file->letter());
    }

    #[DataProvider('fileProvider')]
    public function testFromLetterReturnsCorrespondingFile(
        File $expectedFile,
        int $expectedValue,
        string $letter,
    ): void {
        self::assertSame($expectedFile, File::fromLetter($letter));
    }

    public function testFromLetterIsCaseInsensitive(): void
    {
        self::assertSame(File::A, File::fromLetter('A'));
        self::assertSame(File::D, File::fromLetter('D'));
        self::assertSame(File::H, File::fromLetter('H'));
    }

    public function testFromLetterThrowsValueErrorForInvalidLetter(): void
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('Invalid chess file: x');

        File::fromLetter('x');
    }

    public static function fileProvider(): array
    {
        return [
            'a-file' => [File::A, 0, 'a'],
            'b-file' => [File::B, 1, 'b'],
            'c-file' => [File::C, 2, 'c'],
            'd-file' => [File::D, 3, 'd'],
            'e-file' => [File::E, 4, 'e'],
            'f-file' => [File::F, 5, 'f'],
            'g-file' => [File::G, 6, 'g'],
            'h-file' => [File::H, 7, 'h'],
        ];
    }
}
