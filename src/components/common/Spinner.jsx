// src/components/common/Spinner.jsx
const Spinner = ({ size = 'md', color = 'blue', className = '' }) => {
  const sizeClasses = {
    sm: 'h-4 w-4 border-2',
    md: 'h-8 w-8 border-2',
    lg: 'h-12 w-12 border-3',
    xl: 'h-16 w-16 border-4',
  };

  const colorClasses = {
    blue: 'border-blue-500',
    gray: 'border-gray-500',
    white: 'border-white',
  };

  return (
    <div className={`${className} flex justify-center items-center`}>
      <div className={`${sizeClasses[size]} rounded-full border-t-transparent animate-spin ${colorClasses[color]}`}></div>
    </div>
  );
};

export default Spinner;
