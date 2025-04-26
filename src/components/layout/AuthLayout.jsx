import { useEffect } from 'react';
import { Outlet } from 'react-router-dom';

const AuthLayout = () => {
  useEffect(() => {
    document.body.classList.add('auth-page');

    // Ẩn header và footer nếu có
    const header = document.querySelector('header');
    const footer = document.querySelector('footer');
    const navbar = document.querySelector('nav');

    if (header) header.style.display = 'none';
    if (footer) footer.style.display = 'none';
    if (navbar) navbar.style.display = 'none';

    // Clean up khi component unmount
    return () => {
      document.body.classList.remove('auth-page');
      if (header) header.style.display = '';
      if (footer) footer.style.display = '';
      if (navbar) navbar.style.display = '';
    };
  }, []);

  return <Outlet />;
};

export default AuthLayout;
