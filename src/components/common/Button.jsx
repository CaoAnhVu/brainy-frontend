// src/components/common/Button.jsx
const Button = ({ children, onClick, type = 'button', variant = 'primary', size = 'md', fullWidth = false, disabled = false, className = '' }) => {
  const baseClasses = 'font-medium rounded focus:outline-none transition-colors';

  const variantClasses = {
    primary: 'bg-blue-600 text-white hover:bg-blue-700',
    secondary: 'bg-gray-200 text-gray-800 hover:bg-gray-300',
    success: 'bg-green-600 text-white hover:bg-green-700',
    danger: 'bg-red-600 text-white hover:bg-red-700',
    warning: 'bg-yellow-500 text-white hover:bg-yellow-600',
    outline: 'bg-transparent border border-blue-600 text-blue-600 hover:bg-blue-50',
  };

  const sizeClasses = {
    sm: 'px-3 py-1 text-sm',
    md: 'px-4 py-2',
    lg: 'px-6 py-3 text-lg',
  };

  const widthClass = fullWidth ? 'w-full' : '';
  const disabledClass = disabled ? 'opacity-50 cursor-not-allowed' : '';

  return (
    <button type={type} onClick={onClick} disabled={disabled} className={`${baseClasses} ${variantClasses[variant]} ${sizeClasses[size]} ${widthClass} ${disabledClass} ${className}`}>
      {children}
    </button>
  );
};

export default Button;
