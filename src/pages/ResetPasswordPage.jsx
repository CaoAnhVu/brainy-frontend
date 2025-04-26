import { useState, useEffect } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import api from '../services/api';
import { Swiper, SwiperSlide } from 'swiper/react';
import { Autoplay, EffectFade } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/effect-fade';
import 'swiper/css/autoplay';
import '../assets/css/auth-styles.css';

const ResetPasswordPage = () => {
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [successMessage, setSuccessMessage] = useState('');
  const [error, setError] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isSuccess, setIsSuccess] = useState(false);
  const [isValidToken, setIsValidToken] = useState(true);
  const [passwordStrength, setPasswordStrength] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);

  const { token } = useParams();
  const navigate = useNavigate();

  // Kiểm tra tính hợp lệ của token khi tải trang
  useEffect(() => {
    const verifyToken = async () => {
      try {
        await api.get(`/auth.php?action=verify_reset_token&token=${token}`);
      } catch (error) {
        setIsValidToken(false);
        setError('Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.');
        console.error('Lỗi khi xác thực token:', error);
      }
    };

    verifyToken();
  }, [token]);

  // Kiểm tra độ mạnh của mật khẩu
  const checkPasswordStrength = (pwd) => {
    if (!pwd) {
      setPasswordStrength('');
      return;
    }

    let strength = '';

    if (pwd.length < 6) {
      strength = 'weak';
    } else if (pwd.length < 10) {
      strength = 'medium';
    } else {
      strength = 'strong';
    }

    setPasswordStrength(strength);
  };

  // Xử lý thay đổi mật khẩu
  const handlePasswordChange = (e) => {
    const value = e.target.value;
    setPassword(value);
    checkPasswordStrength(value);
  };

  // Xử lý gửi form
  const handleSubmit = async (e) => {
    e.preventDefault();
    setIsSubmitting(true);
    setError('');
    setSuccessMessage('');

    if (password !== confirmPassword) {
      setError('Mật khẩu không khớp.');
      setIsSubmitting(false);
      return;
    }

    if (password.length < 6) {
      setError('Mật khẩu phải có ít nhất 6 ký tự.');
      setIsSubmitting(false);
      return;
    }

    try {
      await api.post('/auth.php?action=reset_password', {
        token,
        password,
      });

      setIsSuccess(true);
      setSuccessMessage('Mật khẩu đã được đặt lại thành công!');

      // Tự động chuyển hướng đến trang đăng nhập sau 3 giây
      setTimeout(() => {
        navigate('/login');
      }, 3000);
    } catch (error) {
      setError(error.response?.data?.message || 'Không thể đặt lại mật khẩu. Vui lòng thử lại.');
    } finally {
      setIsSubmitting(false);
    }
  };

  if (!isValidToken) {
    return (
      <div className="auth-container" style={{ flexDirection: 'column', alignItems: 'center', justifyContent: 'center' }}>
        <div className="auth-message auth-error" style={{ maxWidth: '500px', margin: '20px auto' }}>
          <div style={{ textAlign: 'center' }}>
            <i className="fa-solid fa-circle-exclamation" style={{ fontSize: '48px', marginBottom: '20px' }}></i>
            <h2 style={{ marginBottom: '15px' }}>Liên kết không hợp lệ</h2>
            <p>{error}</p>
            <div style={{ marginTop: '20px' }}>
              <Link to="/forgot-password" className="auth-link">
                Yêu cầu liên kết mới
              </Link>
            </div>
          </div>
        </div>
      </div>
    );
  }

  return (
    <>
      <div className="auth-container">
        {/* Left side - Image Slider */}
        <div className="auth-image-side">
          <Swiper modules={[Autoplay, EffectFade]} effect="fade" autoplay={{ delay: 5000 }} loop={true}>
            <SwiperSlide>
              <div className="auth-overlay"></div>
              <img src="/static/images/login/login9.avif" alt="Food" />
              <div className="auth-image-content">
                <h3>Foodie Destinations</h3>
                <p>Travel to renowned food capitals and hidden culinary gems, uncovering the secrets behind each region's unique culinary identity.</p>
                <div className="auth-location">
                  <i className="fa-solid fa-map-location-dot"></i>
                  <span>VietNam, Ho Chi Minh City</span>
                </div>
              </div>
            </SwiperSlide>

            <SwiperSlide>
              <div className="auth-overlay"></div>
              <img src="/static/images/login/login10.avif" alt="Sustainable Dining" />
              <div className="auth-image-content">
                <h3>Sustainable Dining</h3>
                <p>Support local communities and sustainable practices by choosing eco-friendly food tours that prioritize local ingredients and minimize environmental impact.</p>
                <div className="auth-location">
                  <i className="fa-solid fa-map-location-dot"></i>
                  <span>VietNam, Ho Chi Minh City</span>
                </div>
              </div>
            </SwiperSlide>
          </Swiper>
        </div>

        {/* Right side - Reset Password Form */}
        <div className="auth-form-side">
          {!isSuccess ? (
            <>
              <h2>Đặt Lại Mật Khẩu</h2>
              <p className="auth-greeting">Tạo mật khẩu mới cho tài khoản của bạn</p>

              {error && <div className="auth-message auth-error">{error}</div>}

              <form onSubmit={handleSubmit}>
                <div className="auth-form-group">
                  <div className="auth-input-wrapper">
                    <input type={showPassword ? 'text' : 'password'} className="auth-input" placeholder="Mật khẩu mới" value={password} onChange={handlePasswordChange} required />
                    <div className="auth-input-icon" onClick={() => setShowPassword(!showPassword)}>
                      <i className={`fa-solid ${showPassword ? 'fa-eye' : 'fa-eye-slash'}`}></i>
                    </div>
                  </div>
                  {passwordStrength && (
                    <div className={`password-strength password-${passwordStrength}`} style={{ marginTop: '5px', fontSize: '13px' }}>
                      Độ mạnh mật khẩu: {passwordStrength === 'weak' ? 'Yếu' : passwordStrength === 'medium' ? 'Trung bình' : 'Mạnh'}
                    </div>
                  )}
                </div>

                <div className="auth-form-group">
                  <div className="auth-input-wrapper">
                    <input
                      type={showConfirmPassword ? 'text' : 'password'}
                      className="auth-input"
                      placeholder="Xác nhận mật khẩu mới"
                      value={confirmPassword}
                      onChange={(e) => setConfirmPassword(e.target.value)}
                      required
                    />
                    <div className="auth-input-icon" onClick={() => setShowConfirmPassword(!showConfirmPassword)}>
                      <i className={`fa-solid ${showConfirmPassword ? 'fa-eye' : 'fa-eye-slash'}`}></i>
                    </div>
                  </div>
                  {password && confirmPassword && (
                    <div style={{ marginTop: '5px', fontSize: '13px', color: password === confirmPassword ? '#2e7d32' : '#d32f2f' }}>
                      {password === confirmPassword ? 'Mật khẩu khớp' : 'Mật khẩu không khớp'}
                    </div>
                  )}
                </div>

                <button type="submit" className="auth-submit-btn" disabled={isSubmitting}>
                  {isSubmitting ? 'Đang xử lý...' : 'Đặt lại mật khẩu'}
                </button>

                <div className="auth-bottom-text">
                  <Link to="/login" className="auth-link">
                    Quay lại đăng nhập
                  </Link>
                </div>
              </form>
            </>
          ) : (
            <div style={{ textAlign: 'center', padding: '20px' }}>
              <div style={{ color: '#2e7d32', fontSize: '48px', marginBottom: '20px' }}>
                <i className="fa-solid fa-circle-check"></i>
              </div>
              <h2 style={{ marginBottom: '15px', color: '#2e7d32' }}>Thành công!</h2>
              <p style={{ marginBottom: '20px' }}>
                {successMessage || 'Mật khẩu đã được đặt lại thành công.'} <br />
                Bạn sẽ được chuyển hướng đến trang đăng nhập.
              </p>
              <Link to="/login" className="auth-submit-btn" style={{ display: 'inline-block', textDecoration: 'none', padding: '10px 20px' }}>
                Đến trang đăng nhập ngay
              </Link>
            </div>
          )}
        </div>
      </div>

      <div className="auth-footer">
        <a href="#">© 2024</a>
        <a href="#">Điều khoản của Freal</a>
        <a href="#">Chính sách quyền riêng tư</a>
        <a href="#">Chính sách cookie</a>
        <a href="#">Báo cáo dự cố</a>
      </div>
    </>
  );
};

export default ResetPasswordPage;
