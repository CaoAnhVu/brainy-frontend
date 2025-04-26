import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import api from '../services/api';
import { Swiper, SwiperSlide } from 'swiper/react';
import { Autoplay, EffectFade } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/effect-fade';
import 'swiper/css/autoplay';
import '../assets/css/auth-styles.css';

const ForgotPasswordPage = () => {
  const [email, setEmail] = useState('');
  const [error, setError] = useState('');
  const [message, setMessage] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const navigate = useNavigate();

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (!email) {
      setError('Vui lòng nhập địa chỉ email của bạn');
      return;
    }

    setIsSubmitting(true);
    setError('');
    setMessage('');

    try {
      const response = await api.post('/auth.php?action=forgot_password', { email });

      if (response.data.requireOtp) {
        // Nếu cần OTP, chuyển hướng đến trang nhập OTP
        navigate('/verify-otp', {
          state: {
            email,
            fromForgotPassword: true,
          },
        });
      } else {
        // Hiển thị thông báo gửi email thành công
        setMessage('Hướng dẫn đặt lại mật khẩu đã được gửi đến email của bạn.');
      }
    } catch (err) {
      setError(err.response?.data?.message || 'Không thể gửi email khôi phục. Vui lòng thử lại sau.');
    } finally {
      setIsSubmitting(false);
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
              <img src="/static/images/login/login8.avif" alt="Culinary Experience" />
              <div className="auth-image-content">
                <h3>Cultural Insights</h3>
                <p>Gain insights into the history, traditions, and customs of each destination through its food, discovering how cuisine reflects the soul of a community.</p>
                <div className="auth-location">
                  <i className="fa-solid fa-map-location-dot"></i>
                  <span>VietNam, Ho Chi Minh City</span>
                </div>
              </div>
            </SwiperSlide>

            <SwiperSlide>
              <div className="auth-overlay"></div>
              <img src="/static/images/login/login7.avif" alt="Food Experience" />
              <div className="auth-image-content">
                <h3>Culinary Experiences</h3>
                <p>Delight your taste buds with cooking classes, market visits, and food tastings, curated to provide a deep dive into the local culinary scene.</p>
                <div className="auth-location">
                  <i className="fa-solid fa-map-location-dot"></i>
                  <span>VietNam, Ho Chi Minh City</span>
                </div>
              </div>
            </SwiperSlide>
          </Swiper>
        </div>

        {/* Right side - Forgot Password Form */}
        <div className="auth-form-side">
          <h2>Quên Mật Khẩu</h2>
          <p className="auth-greeting">
            Vui lòng nhập địa chỉ email của bạn. <br />
            Chúng tôi sẽ gửi hướng dẫn để giúp bạn đặt lại mật khẩu.
          </p>

          {error && <div className="auth-message auth-error">{error}</div>}
          {message && <div className="auth-message auth-success">{message}</div>}

          <form onSubmit={handleSubmit}>
            <div className="auth-form-group">
              <input type="email" className="auth-input" placeholder="Email" value={email} onChange={(e) => setEmail(e.target.value)} required />
            </div>

            <button type="submit" className="auth-submit-btn" disabled={isSubmitting}>
              {isSubmitting ? 'Đang gửi...' : 'Gửi yêu cầu đặt lại mật khẩu'}
            </button>

            <div className="auth-bottom-text">
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

export default ForgotPasswordPage;
