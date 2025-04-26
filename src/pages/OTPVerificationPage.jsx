import { useState, useEffect, useRef } from 'react';
import { Link, useNavigate, useLocation } from 'react-router-dom';
import api from '../services/api';
import { Swiper, SwiperSlide } from 'swiper/react';
import { Autoplay, EffectFade } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/effect-fade';
import 'swiper/css/autoplay';
import '../assets/css/auth-styles.css';

const OTPVerificationPage = () => {
  const [otp, setOtp] = useState(['', '', '', '']);
  const [error, setError] = useState('');
  const [message, setMessage] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [email, setEmail] = useState('');
  const [resendDisabled, setResendDisabled] = useState(false);
  const [countdown, setCountdown] = useState(0);

  const inputRefs = [useRef(), useRef(), useRef(), useRef()];
  const navigate = useNavigate();
  const location = useLocation();

  // Lấy email từ state khi chuyển hướng từ đăng ký hoặc khôi phục mật khẩu
  useEffect(() => {
    if (location.state?.email) {
      setEmail(location.state.email);
    } else {
      // Nếu không có email trong state, chuyển hướng về trang đăng nhập
      navigate('/login');
    }
  }, [location, navigate]);

  // Hàm đếm ngược cho chức năng gửi lại mã
  useEffect(() => {
    let timer;
    if (countdown > 0) {
      timer = setTimeout(() => {
        setCountdown(countdown - 1);
      }, 1000);
    } else {
      setResendDisabled(false);
    }

    return () => {
      if (timer) clearTimeout(timer);
    };
  }, [countdown]);

  // Xử lý input OTP
  const handleOtpChange = (index, value) => {
    // Chỉ cho phép nhập số
    if (!/^\d*$/.test(value)) return;

    // Cập nhật giá trị OTP
    const newOtp = [...otp];
    newOtp[index] = value;
    setOtp(newOtp);

    // Tự động focus vào ô tiếp theo khi nhập xong
    if (value && index < 3) {
      inputRefs[index + 1].current.focus();
    }
  };

  // Xử lý khi nhấn phím trên input OTP
  const handleKeyDown = (index, e) => {
    // Nếu nhấn Delete/Backspace và ô trống, focus về ô trước đó
    if (e.key === 'Backspace' && !otp[index] && index > 0) {
      inputRefs[index - 1].current.focus();
    }
  };

  // Xử lý gửi OTP để xác thực
  const handleSubmit = async (e) => {
    e.preventDefault();
    setIsSubmitting(true);
    setError('');

    const otpCode = otp.join('');

    if (otpCode.length !== 4) {
      setError('Vui lòng nhập đủ 4 chữ số mã xác thực');
      setIsSubmitting(false);
      return;
    }

    try {
      const response = await api.post('/auth.php?action=verify_otp', {
        email,
        otp: otpCode,
      });

      // Tùy thuộc vào trường hợp sử dụng (đăng ký hoặc quên mật khẩu)
      if (location.state?.fromForgotPassword) {
        navigate(`/reset-password/${response.data.token}`);
      } else {
        // Đăng ký thành công
        localStorage.setItem('token', response.data.token);
        localStorage.setItem('user', JSON.stringify(response.data.user));
        navigate('/dashboard');
      }
    } catch (error) {
      setError(error.response?.data?.message || 'Mã OTP không chính xác. Vui lòng thử lại.');
    } finally {
      setIsSubmitting(false);
    }
  };

  // Xử lý gửi lại mã OTP
  const handleResendOtp = async () => {
    if (resendDisabled) return;

    setResendDisabled(true);
    setCountdown(60); // Đếm ngược 60 giây
    setMessage('');
    setError('');

    try {
      const action = location.state?.fromForgotPassword ? 'forgot_password' : 'resend_otp';

      await api.post(`/auth.php?action=${action}`, { email });
      setMessage('Đã gửi lại mã xác thực. Vui lòng kiểm tra email của bạn.');
    } catch (error) {
      setError('Không thể gửi lại mã. Vui lòng thử lại sau.');
      console.error('Lỗi khi gửi lại OTP:', error);
    }
  };

  return (
    <>
      <div className="auth-container">
        {/* Left side - Image Slider */}
        <div className="auth-image-side">
          <Swiper modules={[Autoplay, EffectFade]} effect="fade" autoplay={{ delay: 5000 }} loop={true}>
            <SwiperSlide>
              <div className="auth-overlay"></div>
              <img src="/static/images/login/login6.avif" alt="Food" />
              <div className="auth-image-content">
                <h3>Join a Foodie Community</h3>
                <p>Connect with fellow food enthusiasts, sharing recommendations, recipes, and stories from your culinary adventures around the world.</p>
                <div className="auth-location">
                  <i className="fa-solid fa-map-location-dot"></i>
                  <span>VietNam, Ho Chi Minh City</span>
                </div>
              </div>
            </SwiperSlide>

            <SwiperSlide>
              <div className="auth-overlay"></div>
              <img src="/static/images/login/login3.avif" alt="Culinary Experience" />
              <div className="auth-image-content">
                <h3>Meet Local Chefs and Food Artisans</h3>
                <p>Connect with passionate chefs and artisans, learning about their techniques, traditions, and the stories behind their creations.</p>
                <div className="auth-location">
                  <i className="fa-solid fa-map-location-dot"></i>
                  <span>VietNam, Ho Chi Minh City</span>
                </div>
              </div>
            </SwiperSlide>

            {/* Add more slides as needed */}
          </Swiper>
        </div>

        {/* Right side - OTP Verification Form */}
        <div className="auth-form-side">
          <h2>Xác Thực 2 Bước</h2>
          <p className="auth-greeting">
            Chúng tôi đã gửi mã xác thực đến email của bạn. <br />
            Nhập mã từ email vào các ô dưới đây.
          </p>

          {error && <div className="auth-message auth-error">{error}</div>}
          {message && <div className="auth-message auth-success">{message}</div>}

          <form onSubmit={handleSubmit}>
            <div className="otp-inputs">
              {otp.map((digit, index) => (
                <input
                  key={index}
                  ref={inputRefs[index]}
                  type="text"
                  className="otp-input"
                  maxLength="1"
                  value={digit}
                  onChange={(e) => handleOtpChange(index, e.target.value)}
                  onKeyDown={(e) => handleKeyDown(index, e)}
                  required
                  autoFocus={index === 0}
                />
              ))}
            </div>

            <button type="submit" className="auth-submit-btn" disabled={isSubmitting}>
              {isSubmitting ? 'Đang xác thực...' : 'Xác nhận'}
            </button>

            <div className="auth-bottom-text">
              Chưa nhận được mã?{' '}
              <button type="button" onClick={handleResendOtp} disabled={resendDisabled} className="otp-resend">
                {resendDisabled ? `Gửi lại sau (${countdown}s)` : 'Gửi lại mã'}
              </button>
            </div>

            <div className="auth-bottom-text" style={{ marginTop: '15px' }}>
              <Link to="/login" className="auth-link">
                Quay lại đăng nhập
              </Link>
            </div>
          </form>
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

export default OTPVerificationPage;
