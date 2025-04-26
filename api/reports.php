<?php
require_once 'controllers/ReportController.php';

// Khởi tạo controller
$reportController = new ReportController();

// Xử lý các loại request
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Sử dụng middleware xác thực JWT cho tất cả các route
$reportController->useJwtMiddleware();

// Xử lý các route
switch ($method) {
    case 'GET':
        switch ($action) {
            case 'weekly':
                // GET /api/reports.php?action=weekly
                $reportController->generateWeeklyReport();
                break;
                
            case 'category':
                // GET /api/reports.php?action=category&categoryId=123
                $reportController->generateCategoryReport();
                break;
                
            case 'overview':
                // GET /api/reports.php?action=overview
                $reportController->getLearningOverview();
                break;
                
            case 'saved-reports':
                // GET /api/reports.php?action=saved-reports
                $reportController->getSavedReports();
                break;
                
            case 'report-detail':
                // GET /api/reports.php?action=report-detail&reportId=123
                $reportController->getSavedReportDetail();
                break;
                
            default:
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Route not found'
                ]);
                break;
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
        break;
} 