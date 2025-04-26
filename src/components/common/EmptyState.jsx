// src/components/common/EmptyState.jsx
const EmptyState = ({ title, description, icon = null, actionText = '', onAction = null }) => {
  return (
    <div className="text-center py-12 px-4">
      {icon && <div className="flex justify-center mb-4">{icon}</div>}
      <h3 className="text-xl font-medium text-gray-900 mb-2">{title}</h3>
      {description && <p className="text-gray-500 mb-6">{description}</p>}
      {actionText && onAction && (
        <button onClick={onAction} className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
          {actionText}
        </button>
      )}
    </div>
  );
};

export default EmptyState;
