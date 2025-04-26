import React, { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import api from '../services/api';
import { Swiper, SwiperSlide } from 'swiper/react';
import { Autoplay, EffectFade } from 'swiper/modules';
import { FaGithub, FaFacebookF, FaEye, FaEyeSlash, FaMapMarkerAlt } from 'react-icons/fa';
import googleIconSvg from '../assets/logo/google-icon-logo-svgrepo-com.svg';
import styled from 'styled-components';
import 'swiper/css';
import 'swiper/css/effect-fade';
import 'swiper/css/autoplay';
import '../assets/css/auth-styles.css';
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

  &.facebook-btn {
    color: #4267b2;
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
`;

const RegisterPage = () => {
  // React Hook Form
  const {
    register,
    handleSubmit,
    watch,
    formState: { errors, isValid },
  } = useForm({
    mode: 'onChange', // Validate on change
    defaultValues: {
      firstName: '',
      lastName: '',
      email: '',
      password: '',
      confirmPassword: '',
    },
  });

  // State cho UI và logic khác
  const [error, setError] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);
  const [passwordStrength, setPasswordStrength] = useState('');
  const navigate = useNavigate();

  // Lắng nghe thay đổi từ password để kiểm tra độ mạnh
  const password = watch('password');

  // Kiểm tra độ mạnh của mật khẩu khi password thay đổi
  useEffect(() => {
    if (!password) {
      setPasswordStrength('');
      return;
    }

    let strength = '';
    if (password.length < 6) {
      strength = 'weak';
    } else if (password.length < 10) {
      strength = 'medium';
    } else {
      strength = 'strong';
    }

    setPasswordStrength(strength);
  }, [password]);

  // Body class và setup
  useEffect(() => {
    document.body.classList.add('auth-page');

    const header = document.querySelector('header');
    const footer = document.querySelector('footer');

    if (header) header.style.display = 'none';
    if (footer) footer.style.display = 'none';

    return () => {
      document.body.classList.remove('auth-page');
      if (header) header.style.display = '';
      if (footer) footer.style.display = '';
    };
  }, []);

  // Xử lý form submit
  const onSubmit = async (data) => {
    try {
      const response = await api.post('/auth.php?action=register', data);

      if (response.data.requireOtp) {
        navigate('/verify-otp', {
          state: {
            email: data.email,
            fromRegister: true,
          },
        });
      } else {
        localStorage.setItem('token', response.data.token);
        localStorage.setItem('user', JSON.stringify(response.data.user));
        navigate('/dashboard');
      }
    } catch (err) {
      console.log(err);
      setError(err.response?.data?.message || 'Registration failed. Please try again.');
    }
  };

  const handleGoogleSignup = async () => {
    try {
      const provider = new GoogleAuthProvider();
      const result = await signInWithPopup(auth, provider);

      // Lấy thông tin user từ Google
      const { user } = result;
      const userData = {
        email: user.email,
        firstName: user.displayName?.split(' ')[0] || '',
        lastName: user.displayName?.split(' ').slice(1).join(' ') || '',
        googleId: user.uid,
        avatar: user.photoURL,
      };

      // Gọi API đăng ký/đăng nhập của bạn
      try {
        const response = await api.post('/auth.php?action=google-auth', userData);

        if (response.data.requireOtp) {
          navigate('/verify-otp', {
            state: {
              email: userData.email,
              fromRegister: true,
            },
          });
        } else {
          localStorage.setItem('token', response.data.token);
          localStorage.setItem('user', JSON.stringify(response.data.user));
          navigate('/dashboard');
        }
      } catch (err) {
        console.error('API Error:', err);
        setError(err.response?.data?.message || 'Registration with Google failed. Please try again.');
      }
    } catch (err) {
      console.error('Google Sign In Error:', err);
      setError('Could not sign in with Google. Please try again.');
    }
  };

  const handleFacebookSignup = () => {
    console.log('Sign up with Facebook');
  };

  const handleGithubSignup = () => {
    console.log('Sign up with Github');
  };

  // Add password strength indicator display
  const getPasswordStrengthClass = (strength) => {
    switch (strength) {
      case 'weak':
        return 'password-weak';
      case 'medium':
        return 'password-medium';
      case 'strong':
        return 'password-strong';
      default:
        return '';
    }
  };

  return (
    <>
      <div className="auth-container">
        {/* Left side - Image Slider */}
        <div className="auth-image-side">
          <Swiper modules={[Autoplay, EffectFade]} effect="fade" autoplay={{ delay: 5000 }} loop={true}>
            <SwiperSlide>
              <div className="auth-overlay">
                <img src="/images/login/login1.avif" alt="Students learning English online" />
                <div className="auth-image-content">
                  <h3>Interactive Online Learning</h3>
                  <p>Join our virtual classrooms for immersive English learning experiences with native speakers.</p>
                  <div className="auth-location">
                    <FaMapMarkerAlt style={{ marginRight: '8px' }} />
                    <span>Learn from anywhere in the world</span>
                  </div>
                </div>
              </div>
            </SwiperSlide>
            <SwiperSlide>
              <div className="auth-overlay">
                <img src="/images/login/login2.avif" alt="Vocabulary learning interface" />
                <div className="auth-image-content">
                  <h3>Smart Vocabulary Builder</h3>
                  <p>Master new words with our AI-powered vocabulary learning system and spaced repetition technology.</p>
                  <div className="auth-location">
                    <FaMapMarkerAlt style={{ marginRight: '8px' }} />
                    <span>Personalized learning path</span>
                  </div>
                </div>
              </div>
            </SwiperSlide>
            <SwiperSlide>
              <div className="auth-overlay">
                <img src="/images/login/login3.avif" alt="Business English course" />
                <div className="auth-image-content">
                  <h3>Business English Excellence</h3>
                  <p>Enhance your professional communication skills with specialized business English courses.</p>
                  <div className="auth-location">
                    <FaMapMarkerAlt style={{ marginRight: '8px' }} />
                    <span>Global business communication</span>
                  </div>
                </div>
              </div>
            </SwiperSlide>
            <SwiperSlide>
              <div className="auth-overlay">
                <img src="/images/login/login4.avif" alt="IELTS preparation course" />
                <div className="auth-image-content">
                  <h3>IELTS Mastery Program</h3>
                  <p>Achieve your target IELTS score with our comprehensive preparation courses and practice tests.</p>
                  <div className="auth-location">
                    <FaMapMarkerAlt style={{ marginRight: '8px' }} />
                    <span>International test preparation</span>
                  </div>
                </div>
              </div>
            </SwiperSlide>
            <SwiperSlide>
              <div className="auth-overlay">
                <img src="/images/login/login5.avif" alt="Speaking practice session" />
                <div className="auth-image-content">
                  <h3>Conversation Practice</h3>
                  <p>Improve your speaking skills through real-time conversations with language partners worldwide.</p>
                  <div className="auth-location">
                    <FaMapMarkerAlt style={{ marginRight: '8px' }} />
                    <span>Global speaking community</span>
                  </div>
                </div>
              </div>
            </SwiperSlide>
            <SwiperSlide>
              <div className="auth-overlay">
                <img src="/images/login/login6.avif" alt="Grammar learning tools" />
                <div className="auth-image-content">
                  <h3>Grammar Made Easy</h3>
                  <p>Master English grammar with interactive exercises and clear, concise explanations.</p>
                  <div className="auth-location">
                    <FaMapMarkerAlt style={{ marginRight: '8px' }} />
                    <span>Structured learning approach</span>
                  </div>
                </div>
              </div>
            </SwiperSlide>
            <SwiperSlide>
              <div className="auth-overlay">
                <img src="/images/login/login7.avif" alt="Pronunciation training" />
                <div className="auth-image-content">
                  <h3>Perfect Pronunciation</h3>
                  <p>Refine your accent with our AI-powered pronunciation tools and expert guidance.</p>
                  <div className="auth-location">
                    <FaMapMarkerAlt style={{ marginRight: '8px' }} />
                    <span>Speech recognition technology</span>
                  </div>
                </div>
              </div>
            </SwiperSlide>
            <SwiperSlide>
              <div className="auth-overlay">
                <img src="/images/login/login8.avif" alt="Writing skills workshop" />
                <div className="auth-image-content">
                  <h3>Writing Excellence</h3>
                  <p>Develop your writing skills through guided exercises and personalized feedback.</p>
                  <div className="auth-location">
                    <FaMapMarkerAlt style={{ marginRight: '8px' }} />
                    <span>Professional writing skills</span>
                  </div>
                </div>
              </div>
            </SwiperSlide>
            <SwiperSlide>
              <div className="auth-overlay">
                <img src="/images/login/login9.avif" alt="Listening comprehension practice" />
                <div className="auth-image-content">
                  <h3>Advanced Listening Skills</h3>
                  <p>Enhance your listening comprehension with diverse audio materials and exercises.</p>
                  <div className="auth-location">
                    <FaMapMarkerAlt style={{ marginRight: '8px' }} />
                    <span>Audio learning resources</span>
                  </div>
                </div>
              </div>
            </SwiperSlide>
            <SwiperSlide>
              <div className="auth-overlay">
                <img src="/images/login/login10.avif" alt="Mobile learning app" />
                <div className="auth-image-content">
                  <h3>Learn Anywhere, Anytime</h3>
                  <p>Access your English courses on any device with our mobile-friendly platform.</p>
                  <div className="auth-location">
                    <FaMapMarkerAlt style={{ marginRight: '8px' }} />
                    <span>Mobile learning flexibility</span>
                  </div>
                </div>
              </div>
            </SwiperSlide>
          </Swiper>
        </div>

        {/* Right side - Register Form */}
        <div className="auth-form-side">
          <h2>Create Account Brainy</h2>

          <div className="auth-social-options">
            <SocialButton className="google-btn" onClick={handleGoogleSignup}>
              <img src={googleIconSvg} alt="Google" width="32" height="32" />
            </SocialButton>
            <SocialButton className="github-btn" onClick={handleGithubSignup}>
              <FaGithub size={32} />
            </SocialButton>
            <SocialButton className="facebook-btn" onClick={handleFacebookSignup}>
              <FaFacebookF size={32} />
            </SocialButton>
          </div>

          <div className="auth-divider">
            <span>or use your email for registration</span>
          </div>

          {error && <div className="auth-message auth-error">{error}</div>}

          <form onSubmit={handleSubmit(onSubmit)}>
            <div className="auth-form-row">
              <div className="auth-form-group">
                <div className="auth-input-floating">
                  <input
                    type="text"
                    id="firstName"
                    className={`auth-input ${errors.firstName ? 'input-error' : ''} text-black`}
                    placeholder=" "
                    {...register('firstName', {
                      required: 'First name is required',
                      minLength: { value: 2, message: 'First name must be at least 2 characters' },
                      maxLength: { value: 50, message: 'First name cannot exceed 50 characters' },
                      pattern: { value: /^[A-Za-zÀ-ỹ\s]+$/, message: 'First name can only contain letters' },
                    })}
                  />
                  <label htmlFor="firstName">First Name</label>
                </div>
                {errors.firstName && <div className="auth-input-error">{errors.firstName.message}</div>}
              </div>

              <div className="auth-form-group">
                <div className="auth-input-floating">
                  <input
                    type="text"
                    id="lastName"
                    className={`auth-input ${errors.lastName ? 'input-error' : ''} text-black`}
                    placeholder=" "
                    {...register('lastName', {
                      required: 'Last name is required',
                      minLength: { value: 2, message: 'Last name must be at least 2 characters' },
                      maxLength: { value: 50, message: 'Last name cannot exceed 50 characters' },
                      pattern: { value: /^[A-Za-zÀ-ỹ\s]+$/, message: 'Last name can only contain letters' },
                    })}
                  />
                  <label htmlFor="lastName">Last Name</label>
                </div>
                {errors.lastName && <div className="auth-input-error">{errors.lastName.message}</div>}
              </div>
            </div>

            <div className="auth-form-group">
              <div className="auth-input-floating">
                <input
                  type="email"
                  id="email"
                  className={`auth-input ${errors.email ? 'input-error' : ''} text-black`}
                  placeholder=" "
                  {...register('email', {
                    required: 'Email is required',
                    pattern: {
                      value: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
                      message: 'Invalid email format',
                    },
                  })}
                />
                <label htmlFor="email">Email</label>
              </div>
              {errors.email && <div className="auth-input-error">{errors.email.message}</div>}
            </div>

            <div className="auth-form-group">
              <div className="auth-input-floating">
                <input
                  type={showPassword ? 'text' : 'password'}
                  id="password"
                  className={`auth-input ${errors.password ? 'input-error' : ''} text-black`}
                  placeholder=" "
                  {...register('password', {
                    required: 'Password is required',
                    minLength: { value: 6, message: 'Password must be at least 6 characters' },
                    maxLength: { value: 100, message: 'Password cannot exceed 100 characters' },
                    validate: (value) => {
                      const hasLetter = /[A-Za-z]/.test(value);
                      const hasNumber = /[0-9]/.test(value);
                      return (hasLetter && hasNumber) || 'Password must contain both letters and numbers';
                    },
                  })}
                />
                <label htmlFor="password">Password</label>
                <div className="auth-input-icon" onClick={() => setShowPassword(!showPassword)}>
                  {showPassword ? <FaEye /> : <FaEyeSlash />}
                </div>
              </div>
              {errors.password && <div className="auth-input-error">{errors.password.message}</div>}
              {password && <div className={`password-strength ${getPasswordStrengthClass(passwordStrength)}`}>Password Strength: {passwordStrength}</div>}
            </div>

            <div className="auth-form-group">
              <div className="auth-input-floating">
                <input
                  type={showConfirmPassword ? 'text' : 'password'}
                  id="confirmPassword"
                  className={`auth-input ${errors.confirmPassword ? 'input-error' : ''} text-black`}
                  placeholder=" "
                  {...register('confirmPassword', {
                    required: 'Please confirm your password',
                    validate: (value) => value === password || 'Passwords do not match',
                  })}
                />
                <label htmlFor="confirmPassword">Confirm Password</label>
                <div className="auth-input-icon" onClick={() => setShowConfirmPassword(!showConfirmPassword)}>
                  {showConfirmPassword ? <FaEye /> : <FaEyeSlash />}
                </div>
              </div>
              {errors.confirmPassword && <div className="auth-input-error">{errors.confirmPassword.message}</div>}
            </div>

            <button
              type="submit"
              disabled={!isValid}
              style={{
                width: '100%',
                height: '50px',
                borderRadius: '8px',
                backgroundColor: '#007bff',
                color: 'white',
                fontSize: '16px',
                fontWeight: '600',
                border: 'none',
                cursor: isValid ? 'pointer' : 'not-allowed',
                transition: 'all 0.3s ease',
                marginTop: '20px',
                opacity: isValid ? 1 : 0.6,
              }}
            >
              Sign Up
            </button>

            <div className="auth-bottom-text">
              Already have an account?{' '}
              <Link to="/login" className="auth-link">
                Sign In
              </Link>
            </div>
          </form>
        </div>
      </div>

      <div className="auth-footer">
        <a href="#">© 2025</a>
        <a href="#">Brainy Terms</a>
        <a href="#">Privacy Policy</a>
        <a href="#">Cookie Policy</a>
        <a href="#">Report an Issue</a>
      </div>
    </>
  );
};

export default RegisterPage;
