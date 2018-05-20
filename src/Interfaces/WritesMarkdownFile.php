<?php declare(strict_types=1);

namespace hollodotme\PHPUnit\TestListeners\TestDox\Interfaces;

interface WritesMarkdownFile
{
	public function writeLegend( array $legend ) : void;

	public function writeHeadline( string $title, int $level ) : void;

	public function writeCheckbox( string $label, bool $ticked ) : void;

	public function writeBulletListing( array $elements, int $indentLevel = 0 ) : void;

	public function writeBlockQuoteListing( array $elements, int $indentLevel = 0 ) : void;

	public function writeHorizontalRule() : void;

	public function getFilePath() : string;

	public function close() : void;
}