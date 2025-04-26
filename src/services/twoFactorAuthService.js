import axios from 'axios';
import { API_URL } from '../config';

class TwoFactorAuthService {
  baseUrl = `${API_URL}/2fa.php`;

  async getStatus() {
    const response = await axios.get(`${this.baseUrl}?action=status`);
    return response.data;
  }

  async getSetupData() {
    const response = await axios.get(`${this.baseUrl}?action=setup`);
    return response.data;
  }

  async getBackupCodes() {
    const response = await axios.get(`${this.baseUrl}?action=backup-codes`);
    return response.data;
  }

  async enable2FA(code) {
    const response = await axios.post(`${this.baseUrl}?action=enable`, { code });
    return response.data;
  }

  async verify2FA(code) {
    const response = await axios.post(`${this.baseUrl}?action=verify`, { code });
    return response.data;
  }

  async disable2FA(code) {
    const response = await axios.post(`${this.baseUrl}?action=disable`, { code });
    return response.data;
  }

  async verifyBackupCode(code) {
    const response = await axios.post(`${this.baseUrl}?action=verify-backup`, { code });
    return response.data;
  }
}

export default new TwoFactorAuthService();
