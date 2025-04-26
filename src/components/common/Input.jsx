// src/components/common/Input.jsx
const Input = ({ label, type = 'text', id, value, onChange, placeholder = '', error = '', required = false, className = '' }) => {
  return (
    <div className="mb-4">
      {label && (
        <label htmlFor={id} className="block text-gray-700 font-medium mb-2">
          {label}
          {required && <span className="text-red-500 ml-1">*</span>}
        </label>
      )}
      <input
        type={type}
        id={id}
        value={value}
        onChange={onChange}
        placeholder={placeholder}
        required={required}
        className={`w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 ${error ? 'border-red-500' : 'border-gray-300'} ${className}`}
      />
      {error && <p className="mt-1 text-red-500 text-sm">{error}</p>}
    </div>
  );
};

export default Input;
