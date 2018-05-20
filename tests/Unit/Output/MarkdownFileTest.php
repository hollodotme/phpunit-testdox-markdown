<?php declare(strict_types=1);

namespace hollodotme\PHPUnit\TestListeners\TestDox\Tests\Unit\Output;

use hollodotme\PHPUnit\TestListeners\TestDox\Exceptions\RuntimeException;
use hollodotme\PHPUnit\TestListeners\TestDox\Interfaces\WritesMarkdownFile;
use hollodotme\PHPUnit\TestListeners\TestDox\Output\MarkdownFile;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use function file_get_contents;
use function is_resource;
use function tempnam;
use function unlink;

final class MarkdownFileTest extends TestCase
{
	/** @var WritesMarkdownFile */
	private $markdownFile;

	protected function setUp() : void
	{
		$this->markdownFile = new MarkdownFile(
			tempnam( __DIR__ . '/_temp', 'MarkDownFileTest_' )
		);
	}

	protected function tearDown() : void
	{
		@unlink( $this->markdownFile->getFilePath() );
		$this->markdownFile = null;
	}

	/**
	 * @throws \PHPUnit\Framework\ExpectationFailedException
	 * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
	 */
	public function testWriteBlockQuoteListing() : void
	{
		$this->markdownFile->writeBlockQuoteListing(
			[
				'Element 1',
				'Element 2',
				'Element 3',
			]
		);

		$this->assertFileEquals(
			__DIR__ . '/_files/BlockQuoteListing.md',
			$this->markdownFile->getFilePath()
		);
	}

	/**
	 * @throws \PHPUnit\Framework\ExpectationFailedException
	 * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
	 */
	public function testWriteCheckbox() : void
	{
		$this->markdownFile->writeCheckbox( 'Checkbox 1', false );
		$this->markdownFile->writeCheckbox( 'Checkbox 2', true );
		$this->markdownFile->writeCheckbox( 'Checkbox 3', false );
		$this->markdownFile->writeCheckbox( 'Checkbox 4', true );

		$this->assertFileEquals(
			__DIR__ . '/_files/Checkboxes.md',
			$this->markdownFile->getFilePath()
		);
	}

	/**
	 * @throws ReflectionException
	 * @throws \PHPUnit\Framework\ExpectationFailedException
	 * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
	 */
	public function testClose() : void
	{
		$this->markdownFile->writeHeadline( 'Unit Test', 1 );

		$refClass    = new ReflectionClass( $this->markdownFile );
		$refProperty = $refClass->getProperty( 'fileHandle' );
		$refProperty->setAccessible( true );

		$fileHandle = $refProperty->getValue( $this->markdownFile );

		/** @noinspection PhpUnitTestsInspection */
		$this->assertTrue( is_resource( $fileHandle ) );

		$this->markdownFile->close();

		/** @noinspection PhpUnitTestsInspection */
		$this->assertFalse( is_resource( $fileHandle ) );
	}

	/**
	 * @throws \PHPUnit\Framework\ExpectationFailedException
	 * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
	 */
	public function testWriteLegend() : void
	{
		$this->markdownFile->writeLegend(
			[
				'Passed'     => '💚',
				'Error'      => '💔',
				'Failure'    => '💔',
				'Warning'    => '🧡',
				'Risky'      => '💛',
				'Incomplete' => '💙',
				'Skipped'    => '💜',
			]
		);

		$this->assertFileEquals(
			__DIR__ . '/_files/Legend.md',
			$this->markdownFile->getFilePath()
		);
	}

	/**
	 * @throws \PHPUnit\Framework\ExpectationFailedException
	 * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
	 */
	public function testWriteBulletListing() : void
	{
		$this->markdownFile->writeBulletListing(
			[
				'Element 1',
				'Element 2',
				'Element 3',
			]
		);

		$this->assertFileEquals(
			__DIR__ . '/_files/BulletListing.md',
			$this->markdownFile->getFilePath()
		);
	}

	/**
	 * @throws \PHPUnit\Framework\ExpectationFailedException
	 * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
	 */
	public function testWriteHorizontalRule() : void
	{
		$this->markdownFile->writeHorizontalRule();

		$this->assertFileEquals(
			__DIR__ . '/_files/HorizontalRule.md',
			$this->markdownFile->getFilePath()
		);
	}

	/**
	 * @throws \PHPUnit\Framework\ExpectationFailedException
	 * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
	 */
	public function testGetFilePath() : void
	{
		$this->assertNotEmpty( $this->markdownFile->getFilePath() );
	}

	/**
	 * @throws \PHPUnit\Framework\ExpectationFailedException
	 * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
	 */
	public function testWriteHeadline() : void
	{
		$this->markdownFile->writeHeadline( 'Headline 1', 1 );
		$this->markdownFile->writeHeadline( 'Headline 2', 2 );
		$this->markdownFile->writeHeadline( 'Headline 3', 3 );

		$this->assertFileEquals(
			__DIR__ . '/_files/Headlines.md',
			$this->markdownFile->getFilePath()
		);
	}

	/**
	 * @throws RuntimeException
	 */
	public function testThrowsExceptionIfFileHandleCouldNotBeCreated() : void
	{
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Could not open output file.' );

		$markdownFile = new MarkdownFile( '/does/not/exist.md' );
		$markdownFile->writeHeadline( 'Headline', 1 );
	}

	/**
	 * @throws \PHPUnit\Framework\ExpectationFailedException
	 * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
	 */
	public function testEmptyArrayWritesNoListing() : void
	{
		$this->markdownFile->writeBulletListing( [] );

		$this->assertEmpty( file_get_contents( $this->markdownFile->getFilePath() ) );

		$this->markdownFile->writeBlockQuoteListing( [] );

		$this->assertEmpty( file_get_contents( $this->markdownFile->getFilePath() ) );
	}
}
