#!/bin/bash

# 在 StatisticsVisualizationService.php 中添加回傳類型註解
cd /var/www/html

# 修正剩餘的匿名函式回傳類型
sed -i 's/function () use ($startDate, $endDate, $granularity) {/function () use ($startDate, $endDate, $granularity): ChartData {/g' app/Infrastructure/Statistics/Services/StatisticsVisualizationService.php
sed -i 's/function () use ($limit, $timeRange) {/function () use ($limit, $timeRange): ChartData {/g' app/Infrastructure/Statistics/Services/StatisticsVisualizationService.php
sed -i 's/function () use ($metricName, $parameters, $chartOptions) {/function () use ($metricName, $parameters, $chartOptions): ChartData {/g' app/Infrastructure/Statistics/Services/StatisticsVisualizationService.php
sed -i 's/function () use ($metricNames, $startDate, $endDate, $granularity, $chartOptions) {/function () use ($metricNames, $startDate, $endDate, $granularity, $chartOptions): ChartData {/g' app/Infrastructure/Statistics/Services/StatisticsVisualizationService.php
sed -i 's/function () use ($metrics, $startDate, $endDate, $granularity) {/function () use ($metrics, $startDate, $endDate, $granularity): ChartData {/g' app/Infrastructure/Statistics/Services/StatisticsVisualizationService.php

echo "修正完成"
