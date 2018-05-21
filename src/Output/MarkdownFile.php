<?php declare(strict_types=1);

namespace hollodotme\PHPUnit\TestListeners\TestDox\Output;

use hollodotme\PHPUnit\TestListeners\TestDox\Exceptions\RuntimeException;
use hollodotme\PHPUnit\TestListeners\TestDox\Interfaces\WritesMarkdownFile;
use function count;
use function fclose;
use function fwrite;
use function is_resource;
use function sprintf;

final class MarkdownFile implements WritesMarkdownFile
{
	/** @var string */
	private $filePath;

	/** @var resource */
	private $fileHandle;

	public function __construct( string $filePath )
	{
		$this->filePath = $filePath;
	}

	/**
	 * @param array $legend
	 *
	 * @throws RuntimeException
	 */
	public function writeLegend( array $legend ) : void
	{
		$legendStrings = [];
		foreach ( $legend as $status => $output )
		{
			$legendStrings[] = "{$output} {$status}";
		}

		$this->write( "%s\n\n", implode( ' | ', $legendStrings ) );
	}

	/**
	 * @param string $formatOrContents
	 * @param mixed  ...$contextValues
	 *
	 * @throws RuntimeException
	 */
	public function write( string $formatOrContents, ...$contextValues ) : void
	{
		fwrite(
			$this->getFileHandle(),
			sprintf( $formatOrContents, ...$contextValues )
		);
	}

	/**
	 * @throws RuntimeException
	 * @return resource
	 */
	private function getFileHandle()
	{
		if ( null === $this->fileHandle )
		{
			$this->fileHandle = @fopen( $this->filePath, 'wb' );
		}

		if ( false === $this->fileHandle )
		{
			throw new RuntimeException( 'Could not open output file.' );
		}

		return $this->fileHandle;
	}

	/**
	 * @param string $title
	 * @param int    $level
	 *
	 * @throws RuntimeException
	 */
	public function writeHeadline( string $title, int $level ) : void
	{
		$this->write( "%s %s\n\n", str_repeat( '#', $level ), $title );
	}

	/**
	 * @param array $elements
	 * @param int   $indentLevel
	 *
	 * @throws RuntimeException
	 */
	public function writeBulletListing( array $elements, int $indentLevel = 0 ) : void
	{
		$this->writeListing( $elements, $indentLevel, '*' );
	}

	/**
	 * @param array  $elements
	 * @param int    $indentLevel
	 * @param string $lineChar
	 *
	 * @throws RuntimeException
	 */
	private function writeListing( array $elements, int $indentLevel, string $lineChar ) : void
	{
		if ( 0 === count( $elements ) )
		{
			return;
		}

		$indent = str_repeat( ' ', $indentLevel * 2 );

		$this->write(
			"%s%s %s  \n\n",
			$indent,
			$lineChar,
			implode( "  \n{$indent}{$lineChar} ", $elements )
		);
	}

	/**
	 * @param array $elements
	 * @param int   $indentLevel
	 *
	 * @throws RuntimeException
	 */
	public function writeBlockQuoteListing( array $elements, int $indentLevel = 0 ) : void
	{
		$this->writeListing( $elements, $indentLevel, '>' );
	}

	/**
	 * @param string $label
	 * @param bool   $ticked
	 *
	 * @throws RuntimeException
	 */
	public function writeCheckbox( string $label, bool $ticked ) : void
	{
		$this->write(
			"- [%s] %s\n",
			$ticked ? 'x' : ' ',
			$label
		);
	}

	/**
	 * @throws RuntimeException
	 */
	public function writeHorizontalRule() : void
	{
		$this->write( "\n---\n\n" );
	}

	public function getFilePath() : string
	{
		return $this->filePath;
	}

	public function close() : void
	{
		if ( is_resource( $this->fileHandle ) )
		{
			fclose( $this->fileHandle );
		}
	}
}