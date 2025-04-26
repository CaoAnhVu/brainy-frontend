import React, { useState } from 'react';
import { Modal, Input, Button, message } from 'antd';
import twoFactorAuthService from '../../services/twoFactorAuthService';

const TwoFactorVerification = ({ visible, onSuccess, onCancel }) => {
  const [code, setCode] = useState('');
  const [loading, setLoading] = useState(false);
  const [showBackupCode, setShowBackupCode] = useState(false);

  const handleVerify = async () => {
    try {
      setLoading(true);
      if (showBackupCode) {
        await twoFactorAuthService.verifyBackupCode(code);
      } else {
        await twoFactorAuthService.verify2FA(code);
      }
      message.success('Verification successful');
      onSuccess();
    } catch (error) {
      message.error(`Invalid verification code: ${error.message}`);
    } finally {
      setLoading(false);
    }
  };

  return (
    <Modal title="Two-Factor Authentication" open={visible} onCancel={onCancel} footer={null}>
      <div>
        <p>{showBackupCode ? 'Enter a backup code' : 'Enter the 6-digit code from your authenticator app'}</p>
        <Input value={code} onChange={(e) => setCode(e.target.value)} placeholder={showBackupCode ? 'Backup code' : '6-digit code'} style={{ marginBottom: 16 }} />
        <Button type="primary" onClick={handleVerify} loading={loading}>
          Verify
        </Button>
        <Button type="link" onClick={() => setShowBackupCode(!showBackupCode)} style={{ marginLeft: 8 }}>
          {showBackupCode ? 'Use authenticator app' : 'Use backup code'}
        </Button>
      </div>
    </Modal>
  );
};

export default TwoFactorVerification;
