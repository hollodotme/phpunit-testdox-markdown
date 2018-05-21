<?php declare(strict_types=1);

namespace hollodotme\PHPUnit\TestListeners\TestDox;

use hollodotme\PHPUnit\TestListeners\TestDox\Exceptions\RuntimeException;
use hollodotme\PHPUnit\TestListeners\TestDox\Interfaces\WritesMarkdownFile;
use hollodotme\PHPUnit\TestListeners\TestDox\Output\MarkdownFile;
use InvalidArgumentException;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Framework\Warning;
use PHPUnit\Runner\BaseTestRunner;
use Throwable;
use function array_filter;
use function array_merge;
use function class_exists;
use function dirname;
use function getcwd;
use function implode;
use function in_array;
use function is_dir;
use function preg_quote;
use function preg_replace;
use const DIRECTORY_SEPARATOR;

final class Markdown implements TestListener
{
	private const TEST_STATUS_MAP_DEFAULT = [
		'Passed'     => '💚',
		'Error'      => '💔',
		'Failure'    => '💔',
		'Warning'    => '🧡',
		'Risky'      => '💛',
		'Incomplete' => '💙',
		'Skipped'    => '💜',
	];

	/** @var string */
	private $environment;

	/** @var string */
	private $baseNamespace;

	/** @var array */
	private $testStatusMap;

	/** @var WritesMarkdownFile */
	private $markdownFile;

	/** @var array */
	private $testCases = [];

	/**
	 * @param string $environment
	 * @param string $filePath
	 * @param string $baseNamespace
	 * @param array  $testStatusMap
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		string $environment,
		string $filePath,
		string $baseNamespace = '',
		array $testStatusMap = self::TEST_STATUS_MAP_DEFAULT
	)
	{
		$this->environment   = $environment;
		$this->baseNamespace = rtrim( $baseNamespace, '\\' );
		$this->testStatusMap = array_merge( self::TEST_STATUS_MAP_DEFAULT, $testStatusMap );

		$this->requireOutputClassIfNecessary();
		$this->markdownFile = new MarkdownFile( $this->getRealFilePath( $filePath ) );
	}

	private function requireOutputClassIfNecessary() : void
	{
		if ( !class_exists( MarkdownFile::class, true ) )
		{
			require_once __DIR__ . '/Interfaces/WritesMarkdownFile.php';
			require_once __DIR__ . '/Output/MarkdownFile.php';
		}
	}

	/**
	 * @param string $filePath
	 *
	 * @throws InvalidArgumentException
	 * @return string
	 */
	private function getRealFilePath( string $filePath ) : string
	{
		$realFilePath = getcwd() . DIRECTORY_SEPARATOR . ltrim( $filePath, '/' );
		$outputDir    = dirname( $realFilePath );
		if ( !@mkdir( $outputDir, 0777, true ) && !is_dir( $outputDir ) )
		{
			throw new InvalidArgumentException( 'Invalid file path: ' . $realFilePath );
		}

		return $realFilePath;
	}

	public function addError( Test $test, Throwable $t, float $time ) : void
	{
		/** @noinspection PhpUndefinedMethodInspection */
		$testNameWithDataSet = $this->getHumanReadableTestName( $test->getName() );
		$testResultInfo      = $this->getTestResultInfo(
			$testNameWithDataSet,
			$this->testStatusMap['Error'],
			$t->getMessage()
		);

		$this->testCases[ $testResultInfo['testName'] ][] = $testResultInfo;
	}

	public function addWarning( Test $test, Warning $e, float $time ) : void
	{
		/** @noinspection PhpUndefinedMethodInspection */
		$testNameWithDataSet = $this->getHumanReadableTestName( $test->getName() );
		$testResultInfo      = $this->getTestResultInfo(
			$testNameWithDataSet,
			$this->testStatusMap['Warning'],
			$e->getMessage()
		);

		$this->testCases[ $testResultInfo['testName'] ][] = $testResultInfo;
	}

	public function addFailure( Test $test, AssertionFailedError $e, float $time ) : void
	{
		/** @noinspection PhpUndefinedMethodInspection */
		$testNameWithDataSet = $this->getHumanReadableTestName( $test->getName() );
		$testResultInfo      = $this->getTestResultInfo(
			$testNameWithDataSet,
			$this->testStatusMap['Failure'],
			$e->getMessage()
		);

		$this->testCases[ $testResultInfo['testName'] ][] = $testResultInfo;
	}

	public function addIncompleteTest( Test $test, Throwable $t, float $time ) : void
	{
		/** @noinspection PhpUndefinedMethodInspection */
		$testNameWithDataSet = $this->getHumanReadableTestName( $test->getName() );
		$testResultInfo      = $this->getTestResultInfo(
			$testNameWithDataSet,
			$this->testStatusMap['Incomplete'],
			$t->getMessage()
		);

		$this->testCases[ $testResultInfo['testName'] ][] = $testResultInfo;
	}

	public function addRiskyTest( Test $test, Throwable $t, float $time ) : void
	{
		/** @noinspection PhpUndefinedMethodInspection */
		$testNameWithDataSet = $this->getHumanReadableTestName( $test->getName() );
		$testResultInfo      = $this->getTestResultInfo(
			$testNameWithDataSet,
			$this->testStatusMap['Risky'],
			$t->getMessage()
		);

		$this->testCases[ $testResultInfo['testName'] ][] = $testResultInfo;
	}

	public function addSkippedTest( Test $test, Throwable $t, float $time ) : void
	{
		/** @noinspection PhpUndefinedMethodInspection */
		$testNameWithDataSet = $this->getHumanReadableTestName( $test->getName() );
		$testResultInfo      = $this->getTestResultInfo(
			$testNameWithDataSet,
			$this->testStatusMap['Skipped'],
			$t->getMessage()
		);

		$this->testCases[ $testResultInfo['testName'] ][] = $testResultInfo;
	}

	/**
	 * @param TestSuite $suite
	 *
	 * @throws RuntimeException
	 */
	public function startTestSuite( TestSuite $suite ) : void
	{
		$suiteName = $suite->getName();

		if ( '' === $suiteName )
		{
			return;
		}

		if ( false === strpos( $suiteName, '\\' ) )
		{
			$this->markdownFile->writeLegend( $this->testStatusMap );
			$this->markdownFile->writeHeadline(
				sprintf( 'Test suite: %s', $suiteName ),
				1
			);
			$this->markdownFile->writeBulletListing(
				[
					'Environment: `' . $this->environment . '`',
					'Base namespace: `' . ($this->baseNamespace ?: 'not provided') . '`',
				]
			);

			return;
		}

		if ( false === strpos( $suiteName, '::' ) )
		{
			$this->markdownFile->writeHeadline( $this->getTestClassName( $suiteName ), 2 );
			$this->testCases = [];
		}
	}

	private function getTestClassName( string $className ) : string
	{
		$baseNamespaceQuoted = preg_quote( $this->baseNamespace, '#' );

		return preg_replace( "#^{$baseNamespaceQuoted}\\\\#", '', $className );
	}

	/**
	 * @param TestSuite $suite
	 *
	 * @throws RuntimeException
	 */
	public function endTestSuite( TestSuite $suite ) : void
	{
		if ( '' === $suite->getName() || false === strpos( $suite->getName(), '\\' ) )
		{
			return;
		}

		if ( false !== strpos( $suite->getName(), '::' ) )
		{
			return;
		}

		foreach ( $this->testCases as $testName => $resultInfos )
		{
			$allPassed      = true;
			$passingResults = [
				$this->testStatusMap['Passed'],
				$this->testStatusMap['Skipped'],
			];

			$results  = [];
			$messages = [];
			foreach ( $resultInfos as $resultInfo )
			{
				$result     = $resultInfo['result'];
				$messages[] = $resultInfo['message'];

				if ( !in_array( $result, $passingResults, true ) )
				{
					$allPassed = false;
				}
				if ( !isset( $results[ $result ] ) )
				{
					$results[ $result ] = 0;
				}

				$results[ $result ]++;
			}

			$resultStrings = [];
			foreach ( $results as $result => $count )
			{
				$resultStrings[] = sprintf( '%s %d', $result, $count );
			}

			$this->markdownFile->writeCheckbox(
				sprintf( '%s (%s)', $testName, implode( ', ', $resultStrings ) ),
				$allPassed
			);

			$this->markdownFile->writeBlockQuoteListing( array_filter( $messages ), 1 );
		}

		$this->markdownFile->writeHorizontalRule();
	}

	private function getHumanReadableTestName( string $testName ) : string
	{
		$humanReadableTestName = preg_replace( '#^.+\:\:#', '', $testName );
		$humanReadableTestName = preg_replace( '#^test#i', '', $humanReadableTestName );
		$humanReadableTestName = preg_replace( '#([^A-Z])([A-Z])#', '$1 $2', $humanReadableTestName );

		return $humanReadableTestName;
	}

	public function startTest( Test $test ) : void
	{
	}

	public function endTest( Test $test, float $time ) : void
	{
		/** @noinspection PhpUndefinedMethodInspection */
		if ( BaseTestRunner::STATUS_PASSED !== $test->getStatus() )
		{
			return;
		}

		/** @noinspection PhpUndefinedMethodInspection */
		$testNameWithDataSet = $this->getHumanReadableTestName( $test->getName() );
		$testResultInfo      = $this->getTestResultInfo(
			$testNameWithDataSet,
			$this->testStatusMap['Passed']
		);

		$this->testCases[ $testResultInfo['testName'] ][] = $testResultInfo;
	}

	private function getTestResultInfo( string $testNameWithDataSet, string $result, ?string $message = null ) : array
	{
		$matches = [];
		preg_match( '#^([^\#]+)(?: with data set \#(\d+))?$#', $testNameWithDataSet, $matches );

		$dataSet = $matches[2] ?? null;

		return [
			'testName' => $matches[1],
			'dataSet'  => $dataSet ?? 0,
			'result'   => $result,
			'message'  => (null !== $message)
				? sprintf(
					'%s%s',
					(null !== $dataSet) ? "{$dataSet}: " : '',
					$message
				)
				: null,
		];
	}

	public function __destruct()
	{
		$filePath = $this->markdownFile->getFilePath();
		$this->markdownFile->close();

		echo "Test results written to markdown file:\n{$filePath}\n";
	}
}