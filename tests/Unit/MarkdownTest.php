<?php declare(strict_types=1);

namespace hollodotme\PHPUnit\TestListeners\TestDox\Tests\Unit;

use Error;
use Exception;
use hollodotme\PHPUnit\TestListeners\TestDox\Exceptions\RuntimeException;
use hollodotme\PHPUnit\TestListeners\TestDox\Markdown;
use InvalidArgumentException;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Framework\Warning;
use PHPUnit\Runner\BaseTestRunner;
use function file_get_contents;
use function getcwd;
use function ob_end_clean;
use function ob_get_clean;
use function ob_start;
use function unlink;
use const DIRECTORY_SEPARATOR;

final class MarkdownTest extends TestCase
{
	/** @var string */
	private $filePath;

	/** @var Markdown */
	private $markdown;

	/** @var TestSuite */
	private $testSuite;

	/**
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 */
	protected function setUp() : void
	{
		$filePath        = 'tests/Unit/Output/_temp/MarkdownTest.md';
		$this->filePath  = getcwd() . DIRECTORY_SEPARATOR . $filePath;
		$this->markdown  = new Markdown(
			'Testing',
			$filePath,
			'hollodotme\\PHPUnit'
		);
		$this->testSuite = $this->getTestSuite( 'Unit-Test-Suite' );

		$this->markdown->startTestSuite( $this->getTestSuite( '' ) );
		$this->markdown->startTestSuite( $this->testSuite );
	}

	private function getTestSuite( string $testName ) : TestSuite
	{
		$testTestSuite = $this->getMockBuilder( TestSuite::class )->getMock();
		$testTestSuite->method( 'getName' )->willReturn( $testName );

		/** @var TestSuite $testTestSuite */
		return $testTestSuite;
	}

	/**
	 * @throws RuntimeException
	 */
	protected function tearDown() : void
	{
		if ( null !== $this->markdown )
		{
			$this->markdown->endTestSuite( $this->getTestSuite( '' ) );
			$this->markdown->endTestSuite( $this->getTestSuite( 'UnitTest::testSomething' ) );
			$this->markdown->endTestSuite( $this->getTestSuite( 'hollodotme\\PHPUnit\\UnitTest::testSomething' ) );
			$this->markdown->endTestSuite( $this->testSuite );

			ob_start();
			$this->markdown = null;
			ob_end_clean();
		}

		$this->testSuite = null;
		unlink( $this->filePath );
		$this->filePath = null;
	}

	/**
	 * @throws RuntimeException
	 * @throws \PHPUnit\Framework\ExpectationFailedException
	 * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
	 */
	public function testAddSkippedTest() : void
	{
		$testTestSuite = $this->getTestSuite( 'hollodotme\\PHPUnit\\UnitTest' );
		$test          = $this->getTest(
			'testCanAddSkippedTest',
			BaseTestRunner::STATUS_SKIPPED
		);

		$this->markdown->startTestSuite( $testTestSuite );
		$this->markdown->startTest( $test );
		$this->markdown->addSkippedTest( $test, new Exception( 'Test was skipped.' ), time() );
		$this->markdown->endTest( $test, time() );
		$this->markdown->endTestSuite( $testTestSuite );

		$this->assertFileEquals(
			__DIR__ . '/Output/_files/SkippedTest.md',
			$this->filePath
		);
	}

	/**
	 * @throws RuntimeException
	 * @throws \PHPUnit\Framework\ExpectationFailedException
	 * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
	 */
	public function testAddSkippedTestWithDataSets() : void
	{
		$testTestSuite = $this->getTestSuite( 'hollodotme\\PHPUnit\\UnitTest' );

		$test0 = $this->getTest( 'testCanAddSkippedTest', BaseTestRunner::STATUS_SKIPPED, 0 );
		$test1 = $this->getTest( 'testCanAddSkippedTest', BaseTestRunner::STATUS_SKIPPED, 1 );
		$test2 = $this->getTest( 'testCanAddSkippedTest', BaseTestRunner::STATUS_SKIPPED, 2 );

		$this->markdown->startTestSuite( $testTestSuite );

		$this->markdown->startTest( $test0 );
		$this->markdown->addSkippedTest( $test0, new Exception( 'Test was skipped.' ), time() );
		$this->markdown->endTest( $test0, time() );

		$this->markdown->startTest( $test1 );
		$this->markdown->addSkippedTest( $test1, new Exception( 'Test was skipped.' ), time() );
		$this->markdown->endTest( $test1, time() );

		$this->markdown->startTest( $test2 );
		$this->markdown->addSkippedTest( $test2, new Exception( 'Test was skipped.' ), time() );
		$this->markdown->endTest( $test2, time() );

		$this->markdown->endTestSuite( $testTestSuite );

		$this->assertFileEquals(
			__DIR__ . '/Output/_files/SkippedTestWithDataSets.md',
			$this->filePath
		);
	}

	private function getTest( string $testName, int $status, ?int $dataSet = null ) : Test
	{
		$test = $this->getMockBuilder( Test::class )
		             ->setMethods( ['getName', 'getStatus'] )
		             ->getMockForAbstractClass();
		$test->method( 'getName' )->willReturn(
			sprintf(
				'%s%s',
				$testName,
				(null !== $dataSet) ? " with data set #{$dataSet}" : ''
			)
		);
		$test->method( 'getStatus' )->willReturn( $status );

		/** @var Test $test */
		return $test;
	}

	/**
	 * @throws RuntimeException
	 * @throws \PHPUnit\Framework\ExpectationFailedException
	 * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
	 */
	public function testAddIncompleteTest() : void
	{
		$testTestSuite = $this->getTestSuite( 'hollodotme\\PHPUnit\\UnitTest' );

		$test = $this->getTest( 'testCanAddIncompleteTest', BaseTestRunner::STATUS_INCOMPLETE );

		$this->markdown->startTestSuite( $testTestSuite );

		$this->markdown->startTest( $test );
		$this->markdown->addIncompleteTest( $test, new Exception( 'Test is incomplete.' ), time() );
		$this->markdown->endTest( $test, time() );

		$this->markdown->endTestSuite( $testTestSuite );

		$this->assertFileEquals(
			__DIR__ . '/Output/_files/IncompleteTest.md',
			$this->filePath
		);
	}

	/**
	 * @throws RuntimeException
	 * @throws \PHPUnit\Framework\ExpectationFailedException
	 * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
	 */
	public function testAddIncompleteTestWithDataSets() : void
	{
		$testTestSuite = $this->getTestSuite( 'hollodotme\\PHPUnit\\UnitTest' );

		$test0 = $this->getTest( 'testCanAddIncompleteTest', BaseTestRunner::STATUS_INCOMPLETE, 0 );
		$test1 = $this->getTest( 'testCanAddIncompleteTest', BaseTestRunner::STATUS_INCOMPLETE, 1 );
		$test2 = $this->getTest( 'testCanAddIncompleteTest', BaseTestRunner::STATUS_INCOMPLETE, 2 );

		$this->markdown->startTestSuite( $testTestSuite );

		$this->markdown->startTest( $test0 );
		$this->markdown->addIncompleteTest( $test0, new Exception( 'Test is incomplete.' ), time() );
		$this->markdown->endTest( $test0, time() );

		$this->markdown->startTest( $test1 );
		$this->markdown->addIncompleteTest( $test1, new Exception( 'Test is incomplete.' ), time() );
		$this->markdown->endTest( $test1, time() );

		$this->markdown->startTest( $test2 );
		$this->markdown->addIncompleteTest( $test2, new Exception( 'Test is incomplete.' ), time() );
		$this->markdown->endTest( $test2, time() );

		$this->markdown->endTestSuite( $testTestSuite );

		$this->assertFileEquals(
			__DIR__ . '/Output/_files/IncompleteTestWithDataSets.md',
			$this->filePath
		);
	}

	/**
	 * @throws RuntimeException
	 * @throws \PHPUnit\Framework\ExpectationFailedException
	 * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
	 */
	public function testAddFailure() : void
	{
		$testTestSuite = $this->getTestSuite( 'hollodotme\\PHPUnit\\UnitTest' );

		$test = $this->getTest( 'testCanAddFailure', BaseTestRunner::STATUS_FAILURE );

		$this->markdown->startTestSuite( $testTestSuite );

		$this->markdown->startTest( $test );
		$this->markdown->addFailure( $test, new AssertionFailedError( 'Test failed.' ), time() );
		$this->markdown->endTest( $test, time() );

		$this->markdown->endTestSuite( $testTestSuite );

		$this->assertFileEquals(
			__DIR__ . '/Output/_files/FailureTest.md',
			$this->filePath
		);
	}

	/**
	 * @throws RuntimeException
	 * @throws \PHPUnit\Framework\ExpectationFailedException
	 * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
	 */
	public function testAddFailureWithDataSets() : void
	{
		$testTestSuite = $this->getTestSuite( 'hollodotme\\PHPUnit\\UnitTest' );

		$test0 = $this->getTest( 'testCanAddFailure', BaseTestRunner::STATUS_FAILURE, 0 );
		$test1 = $this->getTest( 'testCanAddFailure', BaseTestRunner::STATUS_FAILURE, 1 );
		$test2 = $this->getTest( 'testCanAddFailure', BaseTestRunner::STATUS_FAILURE, 2 );

		$this->markdown->startTestSuite( $testTestSuite );

		$this->markdown->startTest( $test0 );
		$this->markdown->addFailure( $test0, new AssertionFailedError( 'Test failed.' ), time() );
		$this->markdown->endTest( $test0, time() );

		$this->markdown->startTest( $test1 );
		$this->markdown->addFailure( $test1, new AssertionFailedError( 'Test failed.' ), time() );
		$this->markdown->endTest( $test1, time() );

		$this->markdown->startTest( $test2 );
		$this->markdown->addFailure( $test2, new AssertionFailedError( 'Test failed.' ), time() );
		$this->markdown->endTest( $test2, time() );

		$this->markdown->endTestSuite( $testTestSuite );

		$this->assertFileEquals(
			__DIR__ . '/Output/_files/FailureTestWithDataSets.md',
			$this->filePath
		);
	}

	/**
	 * @throws RuntimeException
	 * @throws \PHPUnit\Framework\ExpectationFailedException
	 * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
	 */
	public function testAddRiskyTest() : void
	{
		$testTestSuite = $this->getTestSuite( 'hollodotme\\PHPUnit\\UnitTest' );

		$test = $this->getTest( 'testCanAddRiskyTest', BaseTestRunner::STATUS_RISKY );

		$this->markdown->startTestSuite( $testTestSuite );

		$this->markdown->startTest( $test );
		$this->markdown->addRiskyTest( $test, new Exception( 'Test is risky.' ), time() );
		$this->markdown->endTest( $test, time() );

		$this->markdown->endTestSuite( $testTestSuite );

		$this->assertFileEquals(
			__DIR__ . '/Output/_files/RiskyTest.md',
			$this->filePath
		);
	}

	/**
	 * @throws RuntimeException
	 * @throws \PHPUnit\Framework\ExpectationFailedException
	 * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
	 */
	public function testAddRiskyTestWithDataSets() : void
	{
		$testTestSuite = $this->getTestSuite( 'hollodotme\\PHPUnit\\UnitTest' );

		$test0 = $this->getTest( 'testCanAddRiskyTest', BaseTestRunner::STATUS_RISKY, 0 );
		$test1 = $this->getTest( 'testCanAddRiskyTest', BaseTestRunner::STATUS_RISKY, 1 );
		$test2 = $this->getTest( 'testCanAddRiskyTest', BaseTestRunner::STATUS_RISKY, 2 );

		$this->markdown->startTestSuite( $testTestSuite );

		$this->markdown->startTest( $test0 );
		$this->markdown->addRiskyTest( $test0, new Exception( 'Test is risky.' ), time() );
		$this->markdown->endTest( $test0, time() );

		$this->markdown->startTest( $test1 );
		$this->markdown->addRiskyTest( $test1, new Exception( 'Test is risky.' ), time() );
		$this->markdown->endTest( $test1, time() );

		$this->markdown->startTest( $test2 );
		$this->markdown->addRiskyTest( $test2, new Exception( 'Test is risky.' ), time() );
		$this->markdown->endTest( $test2, time() );

		$this->markdown->endTestSuite( $testTestSuite );

		$this->assertFileEquals(
			__DIR__ . '/Output/_files/RiskyTestWithDataSets.md',
			$this->filePath
		);
	}

	/**
	 * @throws RuntimeException
	 * @throws \PHPUnit\Framework\ExpectationFailedException
	 * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
	 */
	public function testAddWarning() : void
	{
		$testTestSuite = $this->getTestSuite( 'hollodotme\\PHPUnit\\UnitTest' );

		$test = $this->getTest( 'testCanAddWarning', BaseTestRunner::STATUS_WARNING );

		$this->markdown->startTestSuite( $testTestSuite );

		$this->markdown->startTest( $test );
		$this->markdown->addWarning( $test, new Warning( 'Warning.' ), time() );
		$this->markdown->endTest( $test, time() );

		$this->markdown->endTestSuite( $testTestSuite );

		$this->assertFileEquals(
			__DIR__ . '/Output/_files/WarningTest.md',
			$this->filePath
		);
	}

	/**
	 * @throws RuntimeException
	 * @throws \PHPUnit\Framework\ExpectationFailedException
	 * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
	 */
	public function testAddWarningWithDataSets() : void
	{
		$testTestSuite = $this->getTestSuite( 'hollodotme\\PHPUnit\\UnitTest' );

		$test0 = $this->getTest( 'testCanAddWarning', BaseTestRunner::STATUS_WARNING, 0 );
		$test1 = $this->getTest( 'testCanAddWarning', BaseTestRunner::STATUS_WARNING, 1 );
		$test2 = $this->getTest( 'testCanAddWarning', BaseTestRunner::STATUS_WARNING, 2 );

		$this->markdown->startTestSuite( $testTestSuite );

		$this->markdown->startTest( $test0 );
		$this->markdown->addWarning( $test0, new Warning( 'Warning.' ), time() );
		$this->markdown->endTest( $test0, time() );

		$this->markdown->startTest( $test1 );
		$this->markdown->addWarning( $test1, new Warning( 'Warning.' ), time() );
		$this->markdown->endTest( $test1, time() );

		$this->markdown->startTest( $test2 );
		$this->markdown->addWarning( $test2, new Warning( 'Warning.' ), time() );
		$this->markdown->endTest( $test2, time() );

		$this->markdown->endTestSuite( $testTestSuite );

		$this->assertFileEquals(
			__DIR__ . '/Output/_files/WarningTestWithDataSets.md',
			$this->filePath
		);
	}

	/**
	 * @throws RuntimeException
	 * @throws \PHPUnit\Framework\ExpectationFailedException
	 * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
	 */
	public function testAddError() : void
	{
		$testTestSuite = $this->getTestSuite( 'hollodotme\\PHPUnit\\UnitTest' );

		$test = $this->getTest( 'testCanAddError', BaseTestRunner::STATUS_ERROR );

		$this->markdown->startTestSuite( $testTestSuite );

		$this->markdown->startTest( $test );
		$this->markdown->addError( $test, new Error( 'Error.' ), time() );
		$this->markdown->endTest( $test, time() );

		$this->markdown->endTestSuite( $testTestSuite );

		$this->assertFileEquals(
			__DIR__ . '/Output/_files/ErrorTest.md',
			$this->filePath
		);
	}

	/**
	 * @throws RuntimeException
	 * @throws \PHPUnit\Framework\ExpectationFailedException
	 * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
	 */
	public function testAddErrorWithDataSets() : void
	{
		$testTestSuite = $this->getTestSuite( 'hollodotme\\PHPUnit\\UnitTest' );

		$test0 = $this->getTest( 'testCanAddError', BaseTestRunner::STATUS_ERROR, 0 );
		$test1 = $this->getTest( 'testCanAddError', BaseTestRunner::STATUS_ERROR, 1 );
		$test2 = $this->getTest( 'testCanAddError', BaseTestRunner::STATUS_ERROR, 2 );

		$this->markdown->startTestSuite( $testTestSuite );

		$this->markdown->startTest( $test0 );
		$this->markdown->addError( $test0, new Error( 'Error.' ), time() );
		$this->markdown->endTest( $test0, time() );

		$this->markdown->startTest( $test1 );
		$this->markdown->addError( $test1, new Error( 'Error.' ), time() );
		$this->markdown->endTest( $test1, time() );

		$this->markdown->startTest( $test2 );
		$this->markdown->addError( $test2, new Error( 'Error.' ), time() );
		$this->markdown->endTest( $test2, time() );

		$this->markdown->endTestSuite( $testTestSuite );

		$this->assertFileEquals(
			__DIR__ . '/Output/_files/ErrorTestWithDataSets.md',
			$this->filePath
		);
	}

	/**
	 * @throws \PHPUnit\Framework\ExpectationFailedException
	 * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
	 */
	public function testDestruct() : void
	{
		ob_start();
		$this->markdown = null;
		$content        = ob_get_clean();

		$this->assertSame(
			"Test results written to markdown file:\n{$this->filePath}\n",
			$content
		);

		$this->assertRegExp(
			'#\nReport created at \d{4}-\d{2}-\d{2} \d{2}\:\d{2}\:\d{2} \(.+\)$#',
			file_get_contents( $this->filePath )
		);
	}

	/**
	 * @throws InvalidArgumentException
	 */
	public function testThrowsExceptionForInvalidFilePath() : void
	{
		$this->expectException( InvalidArgumentException::class );

		new Markdown( 'Testing', 'README.md/test' );
	}

	/**
	 * @throws RuntimeException
	 * @throws \PHPUnit\Framework\ExpectationFailedException
	 * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
	 */
	public function testAddPassingTest() : void
	{
		$testTestSuite = $this->getTestSuite( 'hollodotme\\PHPUnit\\UnitTest' );

		$test = $this->getTest( 'testCanAddPassingTest', BaseTestRunner::STATUS_PASSED );

		$this->markdown->startTestSuite( $testTestSuite );

		$this->markdown->startTest( $test );
		$this->markdown->endTest( $test, time() );

		$this->markdown->endTestSuite( $testTestSuite );

		$this->assertFileEquals(
			__DIR__ . '/Output/_files/PassingTest.md',
			$this->filePath
		);
	}

	/**
	 * @throws RuntimeException
	 * @throws \PHPUnit\Framework\ExpectationFailedException
	 * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
	 */
	public function testAddPassingTestWithDataSets() : void
	{
		$testTestSuite = $this->getTestSuite( 'hollodotme\\PHPUnit\\UnitTest' );

		$test0 = $this->getTest( 'testCanAddPassingTest', BaseTestRunner::STATUS_PASSED, 0 );
		$test1 = $this->getTest( 'testCanAddPassingTest', BaseTestRunner::STATUS_PASSED, 1 );
		$test2 = $this->getTest( 'testCanAddPassingTest', BaseTestRunner::STATUS_PASSED, 2 );

		$this->markdown->startTestSuite( $testTestSuite );

		$this->markdown->startTest( $test0 );
		$this->markdown->endTest( $test0, time() );

		$this->markdown->startTest( $test1 );
		$this->markdown->endTest( $test1, time() );

		$this->markdown->startTest( $test2 );
		$this->markdown->endTest( $test2, time() );

		$this->markdown->endTestSuite( $testTestSuite );

		$this->assertFileEquals(
			__DIR__ . '/Output/_files/PassingTestWithDataSets.md',
			$this->filePath
		);
	}

	/**
	 * @throws RuntimeException
	 * @throws \PHPUnit\Framework\ExpectationFailedException
	 * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
	 */
	public function testCanAddMultipleTests() : void
	{
		$testTestSuite = $this->getTestSuite( 'hollodotme\\PHPUnit\\UnitTest' );

		$passingTest  = $this->getTest( 'testCanHaveSingleTest', BaseTestRunner::STATUS_PASSED );
		$passingTest0 = $this->getTest( 'testCanHaveTestWithDataSets', BaseTestRunner::STATUS_PASSED, 0 );
		$passingTest1 = $this->getTest( 'testCanHaveTestWithDataSets', BaseTestRunner::STATUS_PASSED, 1 );
		$passingTest2 = $this->getTest( 'testCanHaveTestWithDataSets', BaseTestRunner::STATUS_PASSED, 2 );

		$riskyTest0 = $this->getTest( 'testCanHaveTestWithDataSets', BaseTestRunner::STATUS_RISKY, 3 );
		$riskyTest1 = $this->getTest( 'testCanHaveTestWithDataSets', BaseTestRunner::STATUS_RISKY, 4 );
		$riskyTest2 = $this->getTest( 'testCanHaveTestWithDataSets', BaseTestRunner::STATUS_RISKY, 5 );

		$skippedTest0 = $this->getTest( 'testCanHaveTestWithDataSets', BaseTestRunner::STATUS_SKIPPED, 6 );
		$skippedTest1 = $this->getTest( 'testCanHaveTestWithDataSets', BaseTestRunner::STATUS_SKIPPED, 7 );
		$skippedTest2 = $this->getTest( 'testCanHaveTestWithDataSets', BaseTestRunner::STATUS_SKIPPED, 8 );

		$incompleteTest0 = $this->getTest( 'testCanHaveTestWithDataSets', BaseTestRunner::STATUS_INCOMPLETE, 9 );
		$incompleteTest1 = $this->getTest( 'testCanHaveTestWithDataSets', BaseTestRunner::STATUS_INCOMPLETE, 10 );
		$incompleteTest2 = $this->getTest( 'testCanHaveTestWithDataSets', BaseTestRunner::STATUS_INCOMPLETE, 11 );

		$warningTest0 = $this->getTest( 'testCanHaveTestWithDataSets', BaseTestRunner::STATUS_WARNING, 12 );
		$warningTest1 = $this->getTest( 'testCanHaveTestWithDataSets', BaseTestRunner::STATUS_WARNING, 13 );
		$warningTest2 = $this->getTest( 'testCanHaveTestWithDataSets', BaseTestRunner::STATUS_WARNING, 14 );

		$failureTest0 = $this->getTest( 'testCanHaveTestWithDataSets', BaseTestRunner::STATUS_FAILURE, 15 );
		$errorTest0   = $this->getTest( 'testCanHaveTestWithDataSets', BaseTestRunner::STATUS_ERROR, 16 );
		$errorTest1   = $this->getTest( 'testCanHaveTestWithDataSets', BaseTestRunner::STATUS_ERROR, 17 );

		$this->markdown->startTestSuite( $testTestSuite );

		$this->markdown->startTest( $passingTest );
		$this->markdown->endTest( $passingTest, time() );

		$this->markdown->startTest( $passingTest0 );
		$this->markdown->endTest( $passingTest0, time() );

		$this->markdown->startTest( $passingTest1 );
		$this->markdown->endTest( $passingTest1, time() );

		$this->markdown->startTest( $passingTest2 );
		$this->markdown->endTest( $passingTest2, time() );

		$this->markdown->startTest( $riskyTest0 );
		$this->markdown->addRiskyTest( $riskyTest0, new Exception( 'DataSet is risky.' ), time() );
		$this->markdown->endTest( $riskyTest0, time() );

		$this->markdown->startTest( $riskyTest1 );
		$this->markdown->addRiskyTest( $riskyTest1, new Exception( 'DataSet is risky.' ), time() );
		$this->markdown->endTest( $riskyTest1, time() );

		$this->markdown->startTest( $riskyTest2 );
		$this->markdown->addRiskyTest( $riskyTest2, new Exception( 'DataSet is risky.' ), time() );
		$this->markdown->endTest( $riskyTest2, time() );

		$this->markdown->startTest( $skippedTest0 );
		$this->markdown->addSkippedTest( $skippedTest0, new Exception( 'DataSet was skipped.' ), time() );
		$this->markdown->endTest( $skippedTest0, time() );

		$this->markdown->startTest( $skippedTest1 );
		$this->markdown->addSkippedTest( $skippedTest1, new Exception( 'DataSet was skipped.' ), time() );
		$this->markdown->endTest( $skippedTest1, time() );

		$this->markdown->startTest( $skippedTest2 );
		$this->markdown->addSkippedTest( $skippedTest2, new Exception( 'DataSet was skipped.' ), time() );
		$this->markdown->endTest( $skippedTest2, time() );

		$this->markdown->startTest( $incompleteTest0 );
		$this->markdown->addIncompleteTest( $incompleteTest0, new Exception( 'DataSet is incomplete.' ), time() );
		$this->markdown->endTest( $incompleteTest0, time() );

		$this->markdown->startTest( $incompleteTest1 );
		$this->markdown->addIncompleteTest( $incompleteTest1, new Exception( 'DataSet is incomplete.' ), time() );
		$this->markdown->endTest( $incompleteTest1, time() );

		$this->markdown->startTest( $incompleteTest2 );
		$this->markdown->addIncompleteTest( $incompleteTest2, new Exception( 'DataSet is incomplete.' ), time() );
		$this->markdown->endTest( $incompleteTest2, time() );

		$this->markdown->startTest( $warningTest0 );
		$this->markdown->addWarning( $warningTest0, new Warning( 'DataSet creates warning.' ), time() );
		$this->markdown->endTest( $warningTest0, time() );

		$this->markdown->startTest( $warningTest1 );
		$this->markdown->addWarning( $warningTest1, new Warning( 'DataSet creates warning.' ), time() );
		$this->markdown->endTest( $warningTest1, time() );

		$this->markdown->startTest( $warningTest2 );
		$this->markdown->addWarning( $warningTest2, new Warning( 'DataSet creates warning.' ), time() );
		$this->markdown->endTest( $warningTest2, time() );

		$this->markdown->startTest( $failureTest0 );
		$this->markdown->addFailure( $failureTest0, new AssertionFailedError( 'DataSet fails.' ), time() );
		$this->markdown->endTest( $failureTest0, time() );

		$this->markdown->startTest( $errorTest0 );
		$this->markdown->addError( $errorTest0, new Error( 'DataSet errors out.' ), time() );
		$this->markdown->endTest( $errorTest0, time() );

		$this->markdown->startTest( $errorTest1 );
		$this->markdown->addError( $errorTest1, new Error( 'DataSet errors out.' ), time() );
		$this->markdown->endTest( $errorTest1, time() );

		$this->markdown->endTestSuite( $testTestSuite );

		$this->assertFileEquals(
			__DIR__ . '/Output/_files/MultipleTests.md',
			$this->filePath
		);
	}
}
