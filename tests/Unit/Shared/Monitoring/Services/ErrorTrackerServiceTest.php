<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Monitoring\Services;

use App\Shared\Monitoring\Services\ErrorTrackerService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Exception;
use ErrorException;

/**
 * ErrorTrackerService 測試類別。
 */
class ErrorTrackerServiceTest extends TestCase
{
    private ErrorTrackerService $errorTracker;
    private LoggerInterface $mockLogger;

    protected function setUp(): void
    {
        $this->mockLogger = $this->createMock(LoggerInterface::class);
        $this->errorTracker = new ErrorTrackerService($this->mockLogger);
    }

    /**
     * 測試記錄錯誤功能。
     */
    public function testRecordError(): void
    {
        $exception = new Exception('Test error message', 123);

        $this->mockLogger->expects($this->once())
            ->method('error')
            ->with(
                $this->equalTo('Test error message'),
                $this->callback(function ($context) {
                    return isset($context['exception_class'])
                        && $context['exception_class'] === Exception::class;
                })
            );

        $errorId = $this->errorTracker->recordError($exception, ['test_context' => 'value']);

        $this->assertNotEmpty($errorId);
        $this->assertIsString($errorId);
    }

    /**
     * 測試記錄警告功能。
     */
    public function testRecordWarning(): void
    {
        $message = 'This is a warning';
        $context = ['warning_type' => 'test'];

        $this->mockLogger->expects($this->once())
            ->method('warning')
            ->with($this->equalTo($message), $this->equalTo($context));

        $warningId = $this->errorTracker->recordWarning($message, $context);

        $this->assertNotEmpty($warningId);
        $this->assertIsString($warningId);
    }

    /**
     * 測試記錄資訊功能。
     */
    public function testRecordInfo(): void
    {
        $message = 'This is information';
        $context = ['info_type' => 'test'];

        $this->mockLogger->expects($this->once())
            ->method('info')
            ->with($this->equalTo($message), $this->equalTo($context));

        $infoId = $this->errorTracker->recordInfo($message, $context);

        $this->assertNotEmpty($infoId);
        $this->assertIsString($infoId);
    }

    /**
     * 測試記錄關鍵錯誤功能。
     */
    public function testRecordCriticalError(): void
    {
        $exception = new Exception('Critical error');

        $this->mockLogger->expects($this->once())
            ->method('critical')
            ->with(
                $this->equalTo('Critical error'),
                $this->callback(function ($context) {
                    return isset($context['exception_class']);
                })
            );

        // 添加通知處理器以測試通知機制
        $notificationTriggered = false;
        $this->errorTracker->addNotificationHandler(function ($level, $message, $context, $exception) use (&$notificationTriggered) {
            $notificationTriggered = true;
            $this->assertEquals('critical', $level);
            $this->assertEquals('Critical error', $message);
        });

        $errorId = $this->errorTracker->recordCriticalError($exception);

        $this->assertNotEmpty($errorId);
        $this->assertTrue($notificationTriggered);
    }

    /**
     * 測試錯誤統計功能。
     */
    public function testGetErrorStats(): void
    {
        // 記錄一些錯誤
        $this->errorTracker->recordError(new Exception('Error 1'));
        $this->errorTracker->recordWarning('Warning 1');
        $this->errorTracker->recordInfo('Info 1');

        $stats = $this->errorTracker->getErrorStats(24);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_errors', $stats);
        $this->assertArrayHasKey('levels', $stats);
        $this->assertArrayHasKey('error_types', $stats);
        $this->assertArrayHasKey('error_trend', $stats);

        $this->assertEquals(3, $stats['total_errors']);
        $this->assertEquals(24, $stats['time_period_hours']);
    }

    /**
     * 測試最近錯誤記錄功能。
     */
    public function testGetRecentErrors(): void
    {
        // 記錄一些錯誤
        $this->errorTracker->recordError(new Exception('Recent error 1'));
        $this->errorTracker->recordError(new Exception('Recent error 2'));

        $recentErrors = $this->errorTracker->getRecentErrors(10);

        $this->assertIsArray($recentErrors);
        $this->assertCount(2, $recentErrors);
        $this->assertEquals('Recent error 2', $recentErrors[0]['message']); // 最新的在前
        $this->assertEquals('Recent error 1', $recentErrors[1]['message']);
    }

    /**
     * 測試錯誤趨勢分析功能。
     */
    public function testGetErrorTrends(): void
    {
        // 記錄一些錯誤
        $this->errorTracker->recordError(new Exception('Trend error'));
        $this->errorTracker->recordWarning('Trend warning');

        $trends = $this->errorTracker->getErrorTrends(7);

        $this->assertIsArray($trends);
        $this->assertArrayHasKey('daily_counts', $trends);
        $this->assertArrayHasKey('level_trends', $trends);
        $this->assertArrayHasKey('type_trends', $trends);
        $this->assertArrayHasKey('total_errors', $trends);
        $this->assertEquals(2, $trends['total_errors']);
        $this->assertEquals(7, $trends['period_days']);
    }

    /**
     * 測試檢查關鍵錯誤功能。
     */
    public function testHasCriticalErrors(): void
    {
        // 初始狀態應該沒有關鍵錯誤
        $this->assertFalse($this->errorTracker->hasCriticalErrors(5));

        // 記錄一個關鍵錯誤
        $this->errorTracker->recordCriticalError(new Exception('Critical'));

        // 現在應該有關鍵錯誤
        $this->assertTrue($this->errorTracker->hasCriticalErrors(5));
    }

    /**
     * 測試錯誤摘要報告功能。
     */
    public function testGetErrorSummary(): void
    {
        // 記錄各種類型的錯誤
        $this->errorTracker->recordCriticalError(new Exception('Critical error'));
        $this->errorTracker->recordError(new Exception('Regular error'));
        $this->errorTracker->recordWarning('Warning message');

        $summary = $this->errorTracker->getErrorSummary(24);

        $this->assertIsArray($summary);
        $this->assertArrayHasKey('summary', $summary);
        $this->assertArrayHasKey('top_issues', $summary);
        $this->assertArrayHasKey('recent_critical', $summary);
        $this->assertArrayHasKey('health_status', $summary);

        $this->assertEquals(3, $summary['summary']['total_errors']);
        $this->assertEquals(1, $summary['summary']['critical_errors']);
        $this->assertEquals(1, $summary['summary']['warnings']);
    }

    /**
     * 測試清理舊錯誤記錄功能。
     */
    public function testCleanupOldErrors(): void
    {
        // 記錄一些錯誤
        $this->errorTracker->recordError(new Exception('Error to clean'));
        $this->errorTracker->recordWarning('Warning to clean');

        // 清理舊記錄
        $cleanedCount = $this->errorTracker->cleanupOldErrors(30);

        // 因為記錄是剛建立的，應該不會被清理
        $this->assertEquals(0, $cleanedCount);

        // 檢查記錄仍然存在
        $recentErrors = $this->errorTracker->getRecentErrors();
        $this->assertCount(2, $recentErrors);
    }

    /**
     * 測試錯誤過濾功能。
     */
    public function testErrorFilter(): void
    {
        // 設置一個過濾器，只允許錯誤級別的記錄
        $this->errorTracker->setErrorFilter(function ($level, $message, $context, $exception) {
            return $level === 'error';
        });

        // 記錄不同級別的訊息
        $errorId = $this->errorTracker->recordError(new Exception('This should be recorded'));
        $warningId = $this->errorTracker->recordWarning('This should be filtered');

        // 錯誤應該被記錄
        $this->assertNotEmpty($errorId);

        // 警告應該被過濾（返回空字串）
        $this->assertEmpty($warningId);

        // 檢查只有一個記錄
        $recentErrors = $this->errorTracker->getRecentErrors();
        $this->assertCount(1, $recentErrors);
        $this->assertEquals('This should be recorded', $recentErrors[0]['message']);
    }

    /**
     * 測試通知處理器功能。
     */
    public function testNotificationHandler(): void
    {
        $notificationReceived = false;
        $receivedData = null;

        // 添加通知處理器
        $this->errorTracker->addNotificationHandler(function ($level, $message, $context, $exception) use (&$notificationReceived, &$receivedData) {
            $notificationReceived = true;
            $receivedData = [
                'level' => $level,
                'message' => $message,
                'context' => $context,
                'exception' => $exception,
            ];
        });

        $exception = new Exception('Test notification');
        $this->errorTracker->recordCriticalError($exception, ['test' => 'context']);

        $this->assertTrue($notificationReceived);
        $this->assertEquals('critical', $receivedData['level']);
        $this->assertEquals('Test notification', $receivedData['message']);
        $this->assertEquals(['test' => 'context'], $receivedData['context']);
        $this->assertSame($exception, $receivedData['exception']);
    }
}
