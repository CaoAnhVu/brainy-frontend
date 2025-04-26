<?php
require_once 'controllers/BaseController.php';
require_once 'services/TwoFactorAuthService.php';

class TwoFactorAuthController extends BaseController {
    private $twoFactorAuthService;

    public function __construct() {
        parent::__construct();
        $this->twoFactorAuthService = new TwoFactorAuthService($this->conn);
    }

    public function enable2FA() {
        try {
            $userId = $this->getUserIdFromToken();
            
            // Tạo secret key và QR code
            $setupData = $this->twoFactorAuthService->generateSetupData($userId);
            
            $this->sendResponse(200, [
                'success' => true,
                'data' => $setupData
            ]);
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Error enabling 2FA: ' . $e->getMessage()
            ]);
        }
    }

    public function verify2FA() {
        try {
            $userId = $this->getUserIdFromToken();
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['code'])) {
                $this->sendResponse(400, [
                    'success' => false,
                    'message' => '2FA code is required'
                ]);
                return;
            }

            $isValid = $this->twoFactorAuthService->verifyCode(
                $userId, 
                $data['code']
            );
            
            if ($isValid) {
                $this->sendResponse(200, [
                    'success' => true,
                    'message' => '2FA verification successful'
                ]);
            } else {
                $this->sendResponse(400, [
                    'success' => false,
                    'message' => 'Invalid 2FA code'
                ]);
            }
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Error verifying 2FA: ' . $e->getMessage()
            ]);
        }
    }

    public function disable2FA() {
        try {
            $userId = $this->getUserIdFromToken();
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['code'])) {
                $this->sendResponse(400, [
                    'success' => false,
                    'message' => '2FA code is required'
                ]);
                return;
            }

            $result = $this->twoFactorAuthService->disable2FA(
                $userId, 
                $data['code']
            );
            
            if ($result) {
                $this->sendResponse(200, [
                    'success' => true,
                    'message' => '2FA has been disabled'
                ]);
            } else {
                $this->sendResponse(400, [
                    'success' => false,
                    'message' => 'Invalid 2FA code'
                ]);
            }
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Error disabling 2FA: ' . $e->getMessage()
            ]);
        }
    }

    public function getBackupCodes() {
        try {
            $userId = $this->getUserIdFromToken();
            
            $backupCodes = $this->twoFactorAuthService->generateBackupCodes($userId);
            
            $this->sendResponse(200, [
                'success' => true,
                'data' => $backupCodes
            ]);
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Error generating backup codes: ' . $e->getMessage()
            ]);
        }
    }

    public function get2FAStatus() {
        try {
            $userId = $this->getUserIdFromToken();
            
            $status = $this->twoFactorAuthService->get2FAStatus($userId);
            
            $this->sendResponse(200, [
                'success' => true,
                'data' => $status
            ]);
        } catch (Exception $e) {
            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Error getting 2FA status: ' . $e->getMessage()
            ]);
        }
    }
}