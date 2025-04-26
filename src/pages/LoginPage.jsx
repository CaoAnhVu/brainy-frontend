// pages/LoginPage.jsx
import { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import api from '../services/api';
import { Swiper, SwiperSlide } from 'swiper/react';
import { Autoplay, EffectFade } from 'swiper/modules';
import { FaGithub, FaFacebookF, FaEye, FaEyeSlash, FaMapMarkerAlt } from 'react-icons/fa';
import 'swiper/css';
import 'swiper/css/effect-fade';
import 'swiper/css/autoplay';
import '../assets/css/auth-styles.css';
import styled from 'styled-components';
import googleIconSvg from '../assets/logo/google-icon-logo-svgrepo-com.svg';
import { signInWithPopup, GoogleAuthProvider } from 'firebase/auth';
import { auth } from '../config/firebase';

const SocialButton = styled.button`
  width: 64px;
  height: 64px;
  border-radius: 50%;
  border: 1px solid #ddd;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: white;
  cursor: pointer;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
  margin: 0 10px;

  &:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
  }

  &.google-btn {
    &:hover {
      background-color: #f5f9ff;
    }
  }

  &.github-btn {
    color: #333;
    &:hover {
      background-color: #f5f5f5;
    }
  }
  &.facebook-btn {
    color: #4267b2;
    &:hover {
      background-color: #f5f5f5;
    }
  }
`;

const CustomCheckbox = styled.input`
  appearance: none;
  -webkit-appearance: none;
  width: 16px;
  height: 16px;
  border: 1px solid #ccc;
  border-radius: 3px;
  margin-right: 8px;
  position: relative;
  background-color: white;
  cursor: pointer;

  &:checked {
    background-color: #4f46e5; /* Màu tím Indigo */
    border-color: #4f46e5;
  }

  &:checked::after {
    content: '✓';
    color: white;
    font-size: 12px;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
  }

  &:hover {
    border-color: #4f46e5;
  }
`;

const LoginPage = () => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [rememberMe, setRememberMe] = useState(false);
  const [isFormValid, setIsFormValid] = useState(false);
  const [emailError, setEmailError] = useState('');
  const [passwordError, setPasswordError] = useState('');
  const navigate = useNavigate();

  // Thêm class vào body khi component mount
  useEffect(() => {
    document.body.classList.add('auth-page');

    // Ẩn header và footer nếu có
    const header = document.querySelector('header');
    const footer = document.querySelector('footer');

    if (header) header.style.display = 'none';
    if (footer) footer.style.display = 'none';

    // Clean up khi component unmount
    return () => {
      document.body.classList.remove('auth-page');
      if (header) header.style.display = '';
      if (footer) footer.style.display = '';
    };
  }, []);

  useEffect(() => {
    // CSS cho checkbox
    const style = document.createElement('style');
    style.innerHTML = `
      .custom-checkbox {
        appearance: none;
        -webkit-appearance: none;
        width: 16px;
        height: 16px;
        border: 1px solid #ccc;
        border-radius: 3px;
        margin-right: 8px;
        position: relative;
        background-color: white;
        cursor: pointer;
      }

      .custom-checkbox:checked {
        background-color: #4f46e5;
        border-color: #4f46e5;
      }

      .custom-checkbox:checked::after {
        content: '✓';
        color: white;
        font-size: 12px;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
      }

      .custom-checkbox:hover {
        border-color: #4f46e5;
      }
    `;
    document.head.appendChild(style);

    return () => {
      document.head.removeChild(style);
    };
  }, []);

  useEffect(() => {
    // Kiểm tra email và mật khẩu có hợp lệ không
    const validateEmail = (email) => {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      return emailRegex.test(email);
    };

    // Reset các lỗi
    setEmailError('');
    setPasswordError('');

    // Kiểm tra lỗi
    if (email && !validateEmail(email)) {
      setEmailError('Invalid email format');
    }

    if (password && password.length < 6) {
      setPasswordError('Password must be at least 6 characters');
    }

    // Cập nhật trạng thái hợp lệ của form
    setIsFormValid(email && password && validateEmail(email) && password.length >= 6);
  }, [email, password]);

  const handleLogin = async (e) => {
    e.preventDefault();

    if (!email || !password) {
      setError('Please enter both email and password');
      return;
    }

    if (!isFormValid) {
      setError('Please check your login information');
      return;
    }

    try {
      const response = await api.post('/auth.php?action=login', {
        email,
        password,
        remember: rememberMe,
      });

      localStorage.setItem('token', response.data.token);
      localStorage.setItem('user', JSON.stringify(response.data.user));
      navigate('/dashboard');
    } catch (error) {
      console.log(error);
      setError(error.response?.data?.message || 'Invalid email or password');
    }
  };

  const togglePassword = () => {
    setShowPassword(!showPassword);
  };

  const handleGoogleLogin = async () => {
    try {
      const provider = new GoogleAuthProvider();
      // Thêm scope để lấy thêm thông tin từ Google
      provider.addScope('https://www.googleapis.com/auth/userinfo.profile');
      provider.addScope('https://www.googleapis.com/auth/userinfo.email');

      const result = await signInWithPopup(auth, provider);

      // Lấy thông tin user từ Google
      const userData = {
        email: result.user.email,
        firstName: result.user.displayName?.split(' ')[0] || '',
        lastName: result.user.displayName?.split(' ').slice(1).join(' ') || '',
        googleId: result.user.uid,
        avatar: result.user.photoURL,
        // Thêm thông tin token
        idToken: await result.user.getIdToken(),
        // Thêm provider để backend biết đây là đăng nhập qua Google
        provider: 'google',
      };

      try {
        // Gọi API với đầy đủ thông tin hơn
        const response = await api.post('/auth.php?action=google-auth', userData);

        if (response.data.requireOtp) {
          navigate('/verify-otp', {
            state: {
              email: userData.email,
              fromRegister: true,
            },
          });
        } else {
          // Lưu token và thông tin user
          localStorage.setItem('token', response.data.token);
          localStorage.setItem('user', JSON.stringify(response.data.user));
          navigate('/dashboard');
        }
      } catch (apiError) {
        console.error('API Error:', apiError);
        // Hiển thị lỗi cụ thể từ backend nếu có
        setError(apiError.response?.data?.message || 'Login with Google failed. Please try again.');
      }
    } catch (googleError) {
      console.error('Google Sign In Error:', googleError);
      // Xử lý các lỗi cụ thể từ Google
      if (googleError.code === 'auth/popup-closed-by-user') {
        setError('Login cancelled. Please try again.');
      } else if (googleError.code === 'auth/popup-blocked') {
        setError('Pop-up blocked. Please allow pop-ups for this site.');
      } else {
        setError('Could not sign in with Google. Please try again.');
      }
    }
  };

  const handleGithubLogin = () => {
    // Xử lý đăng nhập với GitHub ở đây
    console.log('Đăng nhập với Github');
    // Gọi API hoặc redirect đến trang xác thực GitHub
  };

  const handleFacebookLogin = () => {
    // Xử lý đăng nhập với Facebook ở đây
    console.log('Đăng nhập với Facebook');
    // Gọi API hoặc redirect đến trang xác thực Facebook
  };

  return (
    <>
      <div className="auth-container">
        {/* Left side - Image Slider */}
        <div className="auth-image-side">
          <Swiper modules={[Autoplay, EffectFade]} effect="fade" autoplay={{ delay: 5000 }} loop={true}>
            <SwiperSlide>
              <div className="auth-overlay">
                <img src="/images/login/login1.avif" alt="Pancakes with maple syrup" />
                <div className="auth-image-content">
                  <h3>Sweet Morning Delights</h3>
                  <p>Start your day with our perfectly stacked pancakes, drizzled with pure maple syrup and fresh ingredients.</p>
                  <div className="auth-location">
                    <FaMapMarkerAlt style={{ marginRight: '8px' }} />
                    <span>VietNam, Ho Chi Minh City</span>
                  </div>
                </div>
              </div>
            </SwiperSlide>
            <SwiperSlide>
              <div className="auth-overlay">
                <img src="/images/login/login2.avif" alt="Fresh salad with vegetables" />
                <div className="auth-image-content">
                  <h3>Fresh & Healthy</h3>
                  <p>Discover our selection of fresh salads and healthy dishes, made with locally sourced ingredients.</p>
                  <div className="auth-location">
                    <FaMapMarkerAlt style={{ marginRight: '8px' }} />
                    <span>VietNam, Ho Chi Minh City</span>
                  </div>
                </div>
              </div>
            </SwiperSlide>
            <SwiperSlide>
              <div className="auth-overlay">
                <img src="/images/login/login3.avif" alt="Desert landscape at sunset" />
                <div className="auth-image-content">
                  <h3>Scenic Dining</h3>
                  <p>Experience unforgettable meals against the backdrop of breathtaking natural landscapes.</p>
                  <div className="auth-location">
                    <FaMapMarkerAlt style={{ marginRight: '8px' }} />
                    <span>VietNam, Ho Chi Minh City</span>
                  </div>
                </div>
              </div>
            </SwiperSlide>
            <SwiperSlide>
              <div className="auth-overlay">
                <img src="/images/login/login4.avif" alt="Grilled meat and vegetables" />
                <div className="auth-image-content">
                  <h3>Grilled Perfection</h3>
                  <p>Savor our expertly grilled dishes, featuring premium cuts of meat and fresh seasonal vegetables.</p>
                  <div className="auth-location">
                    <FaMapMarkerAlt style={{ marginRight: '8px' }} />
                    <span>VietNam, Ho Chi Minh City</span>
                  </div>
                </div>
              </div>
            </SwiperSlide>
            <SwiperSlide>
              <div className="auth-overlay">
                <img src="/images/login/login5.avif" alt="Night sky with stars" />
                <div className="auth-image-content">
                  <h3>Dining Under the Stars</h3>
                  <p>Join us for an enchanting dining experience beneath the starlit sky.</p>
                  <div className="auth-location">
                    <FaMapMarkerAlt style={{ marginRight: '8px' }} />
                    <span>VietNam, Ho Chi Minh City</span>
                  </div>
                </div>
              </div>
            </SwiperSlide>
            <SwiperSlide>
              <div className="auth-overlay">
                <img src="/images/login/login6.avif" alt="Traditional Vietnamese cuisine" />
                <div className="auth-image-content">
                  <h3>Local Flavors</h3>
                  <p>Explore authentic Vietnamese cuisine with our carefully curated selection of traditional dishes.</p>
                  <div className="auth-location">
                    <FaMapMarkerAlt style={{ marginRight: '8px' }} />
                    <span>VietNam, Ho Chi Minh City</span>
                  </div>
                </div>
              </div>
            </SwiperSlide>
            <SwiperSlide>
              <div className="auth-overlay">
                <img src="/images/login/login7.avif" alt="Seafood platter" />
                <div className="auth-image-content">
                  <h3>Ocean's Bounty</h3>
                  <p>Indulge in fresh seafood delicacies, prepared with traditional recipes and modern techniques.</p>
                  <div className="auth-location">
                    <FaMapMarkerAlt style={{ marginRight: '8px' }} />
                    <span>VietNam, Ho Chi Minh City</span>
                  </div>
                </div>
              </div>
            </SwiperSlide>
            <SwiperSlide>
              <div className="auth-overlay">
                <img src="/images/login/login8.avif" alt="Dessert selection" />
                <div className="auth-image-content">
                  <h3>Sweet Endings</h3>
                  <p>Complete your dining experience with our selection of handcrafted desserts and pastries.</p>
                  <div className="auth-location">
                    <FaMapMarkerAlt style={{ marginRight: '8px' }} />
                    <span>VietNam, Ho Chi Minh City</span>
                  </div>
                </div>
              </div>
            </SwiperSlide>
            <SwiperSlide>
              <div className="auth-overlay">
                <img src="/images/login/login9.avif" alt="Coffee and pastries" />
                <div className="auth-image-content">
                  <h3>Café Culture</h3>
                  <p>Start your morning with freshly brewed coffee and homemade pastries in our cozy atmosphere.</p>
                  <div className="auth-location">
                    <FaMapMarkerAlt style={{ marginRight: '8px' }} />
                    <span>VietNam, Ho Chi Minh City</span>
                  </div>
                </div>
              </div>
            </SwiperSlide>
            <SwiperSlide>
              <div className="auth-overlay">
                <img src="/images/login/login10.avif" alt="Street food variety" />
                <div className="auth-image-content">
                  <h3>Street Food Adventure</h3>
                  <p>Experience the vibrant flavors of Vietnamese street food in a comfortable setting.</p>
                  <div className="auth-location">
                    <FaMapMarkerAlt style={{ marginRight: '8px' }} />
                    <span>VietNam, Ho Chi Minh City</span>
                  </div>
                </div>
              </div>
            </SwiperSlide>
          </Swiper>
        </div>

        {/* Right side - Login Form */}
        <div className="auth-form-side">
          <h2>Sign In</h2>
          <p className="auth-greeting">Hello, Friend! Please enter your details.</p>

          <div className="auth-social-options">
            <SocialButton className="google-btn" onClick={handleGoogleLogin} style={{ width: '64px', height: '64px' }}>
              <img src={googleIconSvg} alt="Google" width="32" height="32" />
            </SocialButton>
            <SocialButton className="github-btn" onClick={handleGithubLogin} style={{ width: '64px', height: '64px' }}>
              <FaGithub size={32} />
            </SocialButton>
            <SocialButton className="facebook-btn" onClick={handleFacebookLogin} style={{ width: '64px', height: '64px' }}>
              <FaFacebookF size={32} />
            </SocialButton>
          </div>

          <div className="auth-divider">
            <span>or use your email password</span>
          </div>

          {error && <div className="auth-message auth-error">{error}</div>}

          <form onSubmit={handleLogin}>
            <div className="auth-form-group">
              <div className="auth-input-floating ">
                <input
                  type="email"
                  id="email"
                  className={`auth-input ${emailError ? 'input-error' : ''} text-black`}
                  placeholder=" "
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  onBlur={() => {
                    if (!email) setEmailError('Email is required');
                  }}
                />
                <label htmlFor="email">Email</label>
              </div>
              {emailError && <div className="auth-input-error">{emailError}</div>}
            </div>

            <div className="auth-form-group">
              <div className="auth-input-floating">
            <input
                  type={showPassword ? 'text' : 'password'}
                  id="password"
                  className={`auth-input ${passwordError ? 'input-error' : ''} text-black`}
                  placeholder=" "
              value={password}
              onChange={(e) => setPassword(e.target.value)}
                  onBlur={() => {
                    if (!password) setPasswordError('Password is required');
                  }}
                />
                <label htmlFor="password">Password</label>
                <div className="auth-input-icon" onClick={togglePassword}>
                  {showPassword ? <FaEye /> : <FaEyeSlash />}
                </div>
              </div>
              {passwordError && <div className="auth-input-error">{passwordError}</div>}
            </div>

            <div className="auth-options-row">
              <label className="auth-checkbox">
                <CustomCheckbox type="checkbox" checked={rememberMe} onChange={() => setRememberMe(!rememberMe)} />
                <span>Remember me?</span>
              </label>

              <Link to="/forgot-password" className="auth-forgot-link">
                Forget Your Password?
              </Link>
          </div>

            <button
              type="submit"
              disabled={!isFormValid}
              style={{
                width: '100%',
                height: '50px',
                borderRadius: '8px',
                backgroundColor: '#007bff',
                color: 'white',
                fontSize: '16px',
                fontWeight: '600',
                border: 'none',
                cursor: isFormValid ? 'pointer' : 'not-allowed',
                transition: 'all 0.3s ease',
                marginTop: '20px',
                opacity: isFormValid ? 1 : 0.6,
              }}
            >
              Sign In
          </button>

            <div className="auth-bottom-text">
              Don't have an account?{' '}
              <Link to="/register" className="auth-link">
                Sign Up
              </Link>
            </div>
        </form>
        </div>
      </div>
      <div className="auth-footer">
        <a href="#">© 2025</a>
        <a href="#">Điều khoản của Brainy</a>
        <a href="#">Chính sách quyền riêng tư</a>
        <a href="#">Chính sách cookie</a>
        <a href="#">Báo cáo dự cố</a>
    </div>
    </>
  );
};

export default LoginPage;
