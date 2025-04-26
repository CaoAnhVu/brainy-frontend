import React from 'react';
import Button from '../common/Button';
import Card from '../common/Card';

const QuizResult = ({ score, totalQuestions, timeSpent, onRetry, onBackToTopics }) => {
  // Calculate percentage
  const percentage = Math.round((score / totalQuestions) * 100);

  // Format time spent
  const formatTime = (seconds) => {
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;
    return `${minutes}m ${remainingSeconds}s`;
  };

  // Get result message based on score
  const getResultMessage = () => {
    if (percentage >= 90) {
      return 'Excellent! You have mastered these words.';
    } else if (percentage >= 70) {
      return 'Great job! Keep practicing to improve your score.';
    } else if (percentage >= 50) {
      return 'Good effort! More practice will help you improve.';
    } else {
      return "Keep practicing! You'll get better with more review.";
    }
  };

  return (
    <Card className="max-w-md mx-auto">
      <div className="text-center">
        <h2 className="text-2xl font-bold text-gray-800 mb-4">Quiz Results</h2>

        <div className="relative mb-6 pt-4">
          <svg className="w-32 h-32 mx-auto" viewBox="0 0 36 36">
            <path
              d="M18 2.0845
                a 15.9155 15.9155 0 0 1 0 31.831
                a 15.9155 15.9155 0 0 1 0 -31.831"
              fill="none"
              stroke="#e5e7eb"
              strokeWidth="3"
              strokeDasharray="100, 100"
            />
            <path
              d="M18 2.0845
                a 15.9155 15.9155 0 0 1 0 31.831
                a 15.9155 15.9155 0 0 1 0 -31.831"
              fill="none"
              stroke="#3b82f6"
              strokeWidth="3"
              strokeDasharray={`${percentage}, 100`}
            />
            <text x="18" y="20.5" textAnchor="middle" fill="#3b82f6" fontSize="10" fontWeight="bold">
              {percentage}%
            </text>
          </svg>

          <div className="text-gray-700 mb-2 mt-2">
            <span className="font-bold text-lg">{score}</span> out of <span className="font-bold text-lg">{totalQuestions}</span> correct
          </div>

          <div className="text-gray-500 mb-4">Time spent: {formatTime(timeSpent)}</div>
        </div>

        <p className="text-gray-700 mb-6">{getResultMessage()}</p>

        <div className="flex flex-col space-y-3">
          <Button onClick={onRetry}>Try Again</Button>
          <Button variant="outline" onClick={onBackToTopics}>
            Back to Topics
          </Button>
        </div>
      </div>
    </Card>
  );
};

export default QuizResult;
