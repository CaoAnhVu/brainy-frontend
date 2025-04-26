// src/components/common/Card.jsx
const Card = ({ children, title, titleClass = '', className = '', onClick = null, hoverable = false }) => {
  const hoverClasses = hoverable ? 'transition-shadow hover:shadow-lg cursor-pointer' : '';

  return (
    <div className={`bg-white rounded-lg shadow-md overflow-hidden ${hoverClasses} ${className}`} onClick={onClick}>
      {title && (
        <div className={`px-6 py-4 border-b ${titleClass}`}>
          <h3 className="text-xl font-semibold">{title}</h3>
        </div>
      )}
      <div className="p-6">{children}</div>
    </div>
  );
};

export default Card;
